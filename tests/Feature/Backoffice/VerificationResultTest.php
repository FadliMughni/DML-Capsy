<?php

namespace Tests\Feature\Backoffice;

use App\Enums\InquiryStatus;
use App\Enums\VerificationResult;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\VerificationCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** PBI 17 - Verifikasi dan Pencatatan Hasil Ketersediaan Bahan */
class VerificationResultTest extends TestCase
{
    use RefreshDatabase;

    private User $marketing;

    private User $gudang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketing = User::factory()->marketing()->create(['name' => 'Rina Marketing']);
        $this->gudang = User::factory()->gudang()->create(['name' => 'Budi Gudang']);
    }

    private function submittedRequest(array $inquiryAttributes = []): VerificationRequest
    {
        $inquiry = Inquiry::factory()->create($inquiryAttributes + ['quantity' => 500, 'unit' => 'Pcs']);

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry));

        return VerificationRequest::sole();
    }

    public static function resultProvider(): array
    {
        return [
            'tersedia' => [VerificationResult::Tersedia, InquiryStatus::Tersedia, []],
            'sebagian' => [VerificationResult::TersediaSebagian, InquiryStatus::TersediaSebagian, ['estimated_date' => '+14 days']],
            'tidak' => [VerificationResult::TidakTersedia, InquiryStatus::TidakTersedia, ['estimated_duration' => '3 minggu']],
        ];
    }

    #[DataProvider('resultProvider')]
    public function test_gudang_records_result_and_inquiry_status_follows(VerificationResult $result, InquiryStatus $expected, array $extra): void
    {
        $vr = $this->submittedRequest();

        if (isset($extra['estimated_date'])) {
            $extra['estimated_date'] = now()->modify($extra['estimated_date'])->toDateString();
        }

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), [
                'result' => $result->value,
                'available_quantity' => 200,
                'verification_notes' => 'Cek stok kompon',
            ] + $extra)
            ->assertRedirect(route('backoffice.gudang.verifications.show', $vr))
            ->assertSessionHasNoErrors();

        $vr->refresh();
        $this->assertSame($result, $vr->result);
        $this->assertSame(200, $vr->available_quantity);
        $this->assertSame($this->gudang->id, $vr->verified_by);
        $this->assertNotNull($vr->verified_at);
        $this->assertSame($expected, $vr->inquiry->status);
    }

    public function test_available_quantity_must_be_zero_or_more(): void
    {
        $vr = $this->submittedRequest();

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => -1])
            ->assertSessionHasErrors(['available_quantity' => 'Jumlah tersedia tidak boleh kurang dari 0.']);

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => 0])
            ->assertSessionHasNoErrors();
    }

    public function test_estimate_is_required_for_partial_and_unavailable(): void
    {
        $vr = $this->submittedRequest();

        foreach (['tersedia_sebagian', 'tidak_tersedia'] as $result) {
            $this->actingAs($this->gudang)
                ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => $result, 'available_quantity' => 0])
                ->assertSessionHasErrors('estimated_date');
        }

        $this->assertTrue($vr->fresh()->isPending());
        $this->assertSame(InquiryStatus::MenungguVerifikasi, $vr->inquiry->fresh()->status);
    }

    public function test_estimate_date_cannot_be_in_the_past_and_result_must_be_valid(): void
    {
        $vr = $this->submittedRequest();

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), [
                'result' => 'tersedia_sebagian', 'available_quantity' => 10, 'estimated_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['estimated_date' => 'Tanggal estimasi tidak boleh sebelum hari ini.']);

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'mungkin', 'available_quantity' => 10])
            ->assertSessionHasErrors(['result' => 'Hasil verifikasi tidak valid.']);
    }

    public function test_marketing_requester_receives_in_app_notification(): void
    {
        $vr = $this->submittedRequest();
        Notification::fake();

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => 500]);

        Notification::assertSentTo($this->marketing, VerificationCompletedNotification::class,
            fn ($n, $channels) => $channels === ['database'] && $n->verificationRequest->is($vr));
        Notification::assertNotSentTo($this->gudang, VerificationCompletedNotification::class);
    }

    public function test_result_is_shown_on_marketing_inquiry_detail_and_in_whatsapp_message(): void
    {
        $vr = $this->submittedRequest(['phone' => '081234567890']);
        $date = now()->addDays(10);

        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), [
                'result' => 'tersedia_sebagian',
                'available_quantity' => 320,
                'estimated_date' => $date->toDateString(),
                'verification_notes' => 'Sisa menunggu kompon dari supplier',
            ]);

        $response = $this->actingAs($this->marketing)->get(route('backoffice.marketing.inquiries.show', $vr->inquiry_id));

        $response->assertOk()
            ->assertSee('Hasil Verifikasi Ketersediaan Bahan')
            ->assertSee('Tersedia Sebagian')
            ->assertSee('320 Pcs')
            ->assertSee($date->translatedFormat('d F Y'))
            ->assertSee('Sisa menunggu kompon dari supplier')
            ->assertSee('Budi Gudang')
            ->assertSee('Tandai "Ditindaklanjuti"', false);

        $waText = rawurldecode($response->viewData('customerWhatsAppUrl'));
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $waText);
        $this->assertStringContainsString($vr->inquiry->inquiry_number, $waText);
        $this->assertStringContainsString('Tersedia Sebagian', $waText);
        $this->assertStringContainsString('320 Pcs', $waText);
    }

    public function test_request_cannot_be_verified_twice(): void
    {
        $vr = $this->submittedRequest();

        $this->actingAs($this->gudang)->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => 500]);
        $this->actingAs($this->gudang)
            ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tidak_tersedia', 'available_quantity' => 0, 'estimated_duration' => '1 bulan'])
            ->assertSessionHas('error', 'Pengajuan ini sudah diverifikasi sebelumnya.');

        $this->assertSame(VerificationResult::Tersedia, $vr->fresh()->result);
        $this->assertSame(InquiryStatus::Tersedia, $vr->inquiry->fresh()->status);
    }

    public function test_marketing_can_mark_inquiry_as_followed_up_after_contacting_customer(): void
    {
        $vr = $this->submittedRequest();
        $this->actingAs($this->gudang)->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => 500]);
        $inquiry = $vr->inquiry->fresh();

        // Belum ada catatan komunikasi â†’ ditolak.
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.mark-followed-up', $inquiry))
            ->assertSessionHas('error');
        $this->assertSame(InquiryStatus::Tersedia, $inquiry->fresh()->status);

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.follow-ups.store', $inquiry), ['channel' => 'WhatsApp', 'notes' => 'Sudah dikabari, customer setuju.']);

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.mark-followed-up', $inquiry))
            ->assertRedirect(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $this->assertSame(InquiryStatus::Ditindaklanjuti, $inquiry->fresh()->status);
    }

    public function test_invalid_status_jumps_are_rejected(): void
    {
        // Inquiry Baru â†’ Ditindaklanjuti (melompati verifikasi)
        $baru = Inquiry::factory()->create();
        InquiryFollowUp::create(['inquiry_id' => $baru->id, 'user_id' => $this->marketing->id, 'contacted_at' => now(), 'channel' => 'WhatsApp', 'notes' => 'x']);

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.mark-followed-up', $baru))
            ->assertSessionHas('error', 'Status inquiry tidak dapat diubah dari "Inquiry Baru" menjadi "Ditindaklanjuti".');
        $this->assertSame(InquiryStatus::Baru, $baru->fresh()->status);

        // Menunggu Verifikasi â†’ Ditindaklanjuti (sebelum Gudang mencatat hasil)
        $vr = $this->submittedRequest();
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.mark-followed-up', $vr->inquiry_id))
            ->assertSessionHas('error');
        $this->assertSame(InquiryStatus::MenungguVerifikasi, $vr->inquiry->fresh()->status);

        // Ditindaklanjuti â†’ ajukan verifikasi lagi
        $done = Inquiry::factory()->status(InquiryStatus::Ditindaklanjuti)->create();
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $done))
            ->assertSessionHas('error');
        $this->assertSame(InquiryStatus::Ditindaklanjuti, $done->fresh()->status);
    }

    public function test_marketing_and_member_cannot_record_verification_result(): void
    {
        $vr = $this->submittedRequest();

        foreach ([$this->marketing, User::factory()->member()->create()] as $user) {
            $this->actingAs($user)
                ->put(route('backoffice.gudang.verifications.update', $vr), ['result' => 'tersedia', 'available_quantity' => 1])
                ->assertForbidden();
        }

        $this->assertTrue($vr->fresh()->isPending());
    }

    public function test_gudang_cannot_mark_inquiry_as_followed_up(): void
    {
        $inquiry = Inquiry::factory()->status(InquiryStatus::Tersedia)->create();

        $this->actingAs($this->gudang)
            ->post(route('backoffice.marketing.inquiries.mark-followed-up', $inquiry))
            ->assertForbidden();
    }
}

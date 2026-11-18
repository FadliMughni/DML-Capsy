<?php

namespace Tests\Feature\Backoffice;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\VerificationRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** PBI 16 - Pengajuan Verifikasi Ketersediaan Bahan ke Gudang */
class VerificationRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $marketing;

    private User $gudang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketing = User::factory()->marketing()->create();
        $this->gudang = User::factory()->gudang()->create();
    }

    public function test_marketing_can_submit_verification_request_for_new_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry), ['request_notes' => 'Prioritas warna hitam'])
            ->assertRedirect(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $this->assertSame(InquiryStatus::MenungguVerifikasi, $inquiry->fresh()->status);

        $vr = VerificationRequest::sole();
        $this->assertSame($inquiry->id, $vr->inquiry_id);
        $this->assertSame($this->marketing->id, $vr->requested_by);
        $this->assertNotNull($vr->requested_at);
        $this->assertSame('Prioritas warna hitam', $vr->request_notes);
        $this->assertTrue($vr->isPending());
    }

    public function test_notes_are_optional(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry))
            ->assertSessionHasNoErrors();

        $this->assertNull(VerificationRequest::sole()->request_notes);
    }

    public function test_all_gudang_users_receive_in_app_notification(): void
    {
        Notification::fake();
        $gudang2 = User::factory()->gudang()->create();
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry));

        Notification::assertSentTo([$this->gudang, $gudang2], VerificationRequestedNotification::class,
            fn ($n, $channels) => $channels === ['database'] && $n->verificationRequest->inquiry_id === $inquiry->id);
        Notification::assertNotSentTo($this->marketing, VerificationRequestedNotification::class);
    }

    public function test_notification_is_stored_and_bell_badge_is_shown_to_gudang(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry));

        $this->assertSame(1, $this->gudang->unreadNotifications()->count());
        $notification = $this->gudang->notifications()->first();
        $this->assertSame($inquiry->inquiry_number, $notification->data['inquiry_number']);

        $this->actingAs($this->gudang)
            ->get(route('backoffice.gudang.dashboard'))
            ->assertSee('data-notif-badge', false)
            ->assertSee('Pengajuan cek bahan baru');

        // Klik notifikasi → ditandai dibaca & diarahkan ke halaman pengajuan.
        $this->actingAs($this->gudang)
            ->post(route('backoffice.notifications.read', $notification->id))
            ->assertRedirect(route('backoffice.gudang.verifications.show', VerificationRequest::sole()));
        $this->assertSame(0, $this->gudang->unreadNotifications()->count());
    }

    public function test_cannot_submit_when_inquiry_is_not_new(): void
    {
        foreach ([InquiryStatus::MenungguVerifikasi, InquiryStatus::Tersedia, InquiryStatus::Ditindaklanjuti] as $status) {
            $inquiry = Inquiry::factory()->status($status)->create();

            $this->actingAs($this->marketing)
                ->from(route('backoffice.marketing.inquiries.show', $inquiry))
                ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry))
                ->assertRedirect(route('backoffice.marketing.inquiries.show', $inquiry))
                ->assertSessionHas('error');

            $this->assertSame($status, $inquiry->fresh()->status);
        }

        $this->assertDatabaseCount('verification_requests', 0);
    }

    public function test_double_submit_creates_only_one_request(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)->post(route('backoffice.marketing.inquiries.verification.store', $inquiry));
        $this->actingAs($this->marketing)->post(route('backoffice.marketing.inquiries.verification.store', $inquiry))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('verification_requests', 1);
    }

    public function test_gudang_and_member_cannot_submit_verification_request(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->gudang)->post(route('backoffice.marketing.inquiries.verification.store', $inquiry))->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->post(route('backoffice.marketing.inquiries.verification.store', $inquiry))->assertForbidden();

        $this->assertSame(InquiryStatus::Baru, $inquiry->fresh()->status);
    }

    public function test_gudang_sees_request_list_with_product_details(): void
    {
        $inquiry = Inquiry::factory()->create([
            'product_name' => 'Plug Rubber Diameter 30 mm',
            'size' => 'Diameter 30 mm',
            'quantity' => 750,
            'unit' => 'Pcs',
            'variant' => 'Biru Industri',
            'notes' => 'Kemasan per 50 pcs',
        ]);
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.verification.store', $inquiry), ['request_notes' => 'Customer lama']);

        $this->actingAs($this->gudang)
            ->get(route('backoffice.gudang.verifications.index'))
            ->assertOk()
            ->assertSee($inquiry->inquiry_number)
            ->assertSee('Plug Rubber Diameter 30 mm')
            ->assertSee('Diameter 30 mm')
            ->assertSee('750 Pcs')
            ->assertSee('Biru Industri')
            ->assertSee('Kemasan per 50 pcs')
            ->assertSee('Customer lama');

        $this->actingAs($this->gudang)
            ->get(route('backoffice.gudang.verifications.show', VerificationRequest::sole()))
            ->assertOk()
            ->assertSee('Simpan Hasil Verifikasi');
    }

    public function test_gudang_list_avoids_n_plus_one_queries(): void
    {
        Inquiry::factory()->count(10)->create()->each(fn ($inq) => VerificationRequest::create([
            'inquiry_id' => $inq->id, 'requested_by' => $this->marketing->id, 'requested_at' => now(),
        ]));

        \DB::enableQueryLog();
        $this->actingAs($this->gudang)->get(route('backoffice.gudang.verifications.index'))->assertOk();
        $queries = count(\DB::getQueryLog());

        $this->assertLessThan(12, $queries, "Terlalu banyak query ({$queries}), indikasi N+1.");
    }

    public function test_marketing_and_member_cannot_access_gudang_pages(): void
    {
        $inquiry = Inquiry::factory()->create();
        $vr = VerificationRequest::create(['inquiry_id' => $inquiry->id, 'requested_by' => $this->marketing->id, 'requested_at' => now()]);

        foreach ([$this->marketing, User::factory()->member()->create()] as $user) {
            $this->actingAs($user)->get(route('backoffice.gudang.verifications.index'))->assertForbidden();
            $this->actingAs($user)->get(route('backoffice.gudang.verifications.show', $vr))->assertForbidden();
        }
    }
}

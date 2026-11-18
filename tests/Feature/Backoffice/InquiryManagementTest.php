<?php

namespace Tests\Feature\Backoffice;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PBI 15 - Dashboard dan Manajemen Inquiry (Marketing) */
class InquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $marketing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketing = User::factory()->marketing()->create(['name' => 'Rina Marketing']);
    }

    public function test_dashboard_shows_inquiry_count_per_status(): void
    {
        Inquiry::factory()->count(3)->create();
        Inquiry::factory()->count(2)->status(InquiryStatus::MenungguVerifikasi)->create();
        Inquiry::factory()->status(InquiryStatus::TersediaSebagian)->create();

        $response = $this->actingAs($this->marketing)->get(route('backoffice.marketing.dashboard'));

        $response->assertOk();
        $summary = $response->viewData('summary')->mapWithKeys(fn ($i) => [$i['status']->value => $i['total']]);
        $this->assertSame(3, $summary['Inquiry Baru']);
        $this->assertSame(2, $summary['Menunggu Verifikasi Gudang']);
        $this->assertSame(1, $summary['Tersedia Sebagian']);
        $this->assertSame(0, $summary['Ditindaklanjuti']);
        $this->assertSame(6, $response->viewData('total'));
        $response->assertSee('Menunggu Verifikasi Gudang');
    }

    public function test_inquiry_list_can_be_searched(): void
    {
        Inquiry::factory()->create(['name' => 'Budi Santoso', 'inquiry_number' => 'INQ-20261118-001AAAA']);
        Inquiry::factory()->create(['name' => 'Siti Aminah', 'inquiry_number' => 'INQ-20261118-002BBBB']);

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.index', ['q' => 'Budi']))
            ->assertOk()
            ->assertSee('INQ-20261118-001AAAA')
            ->assertDontSee('INQ-20261118-002BBBB');

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.index', ['q' => '002BBBB']))
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');
    }

    public function test_inquiry_list_can_be_filtered_by_status(): void
    {
        $baru = Inquiry::factory()->create();
        $menunggu = Inquiry::factory()->status(InquiryStatus::MenungguVerifikasi)->create();

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.index', ['status' => InquiryStatus::MenungguVerifikasi->value]))
            ->assertOk()
            ->assertSee($menunggu->inquiry_number)
            ->assertDontSee($baru->inquiry_number);
    }

    public function test_invalid_status_filter_is_ignored(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.index', ['status' => 'ngawur']))
            ->assertOk()
            ->assertSee($inquiry->inquiry_number);
    }

    public function test_inquiry_list_is_paginated(): void
    {
        Inquiry::factory()->count(20)->create();

        $response = $this->actingAs($this->marketing)->get(route('backoffice.marketing.inquiries.index'));

        $response->assertOk();
        $this->assertCount(15, $response->viewData('inquiries')->items());
        $this->assertSame(20, $response->viewData('inquiries')->total());

        $page2 = $this->actingAs($this->marketing)->get(route('backoffice.marketing.inquiries.index', ['page' => 2]));
        $this->assertCount(5, $page2->viewData('inquiries')->items());
    }

    public function test_inquiry_detail_shows_customer_and_product_data(): void
    {
        $inquiry = Inquiry::factory()->create([
            'name' => 'Budi Santoso',
            'company' => 'PT Sumber Mineral',
            'phone' => '081298765432',
            'email' => 'budi@sumber.co.id',
            'product_name' => 'Heavy-Duty Rubber Mudguard',
            'size' => '60 x 80 cm',
            'quantity' => 150,
            'unit' => 'Pcs',
            'variant' => 'Hitam Tebal 8mm',
            'notes' => 'Butuh lubang baut custom',
        ]);

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSeeInOrder([$inquiry->inquiry_number, 'Budi Santoso', 'PT Sumber Mineral', '081298765432', 'budi@sumber.co.id'])
            ->assertSee('Heavy-Duty Rubber Mudguard')
            ->assertSee('60 x 80 cm')
            ->assertSee('150 Pcs')
            ->assertSee('Hitam Tebal 8mm')
            ->assertSee('Butuh lubang baut custom')
            ->assertSee($inquiry->created_at->translatedFormat('d F Y'));
    }

    public function test_detail_has_whatsapp_button_with_inquiry_number(): void
    {
        $inquiry = Inquiry::factory()->create(['phone' => '0812-9876-5432']);

        $response = $this->actingAs($this->marketing)->get(route('backoffice.marketing.inquiries.show', $inquiry));

        $response->assertSee('Hubungi via WhatsApp');
        $url = $response->viewData('customerWhatsAppUrl');
        $this->assertStringStartsWith('https://wa.me/6281298765432?text=', $url);
        $this->assertStringContainsString($inquiry->inquiry_number, rawurldecode($url));
    }

    public function test_marketing_can_record_follow_up(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.follow-ups.store', $inquiry), [
                'channel' => 'WhatsApp',
                'notes' => 'Customer minta dikabari setelah cek Gudang.',
            ])
            ->assertRedirect(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inquiry_follow_ups', [
            'inquiry_id' => $inquiry->id,
            'user_id' => $this->marketing->id,
            'channel' => 'WhatsApp',
            'notes' => 'Customer minta dikabari setelah cek Gudang.',
        ]);
        $this->assertNotNull($inquiry->followUps()->first()->contacted_at);

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertSee('Customer minta dikabari setelah cek Gudang.')
            ->assertSee('Rina Marketing');
    }

    public function test_follow_up_validation_errors_are_in_indonesian(): void
    {
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.inquiries.follow-ups.store', $inquiry), [
                'channel' => 'Merpati',
                'notes' => '',
                'contacted_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertSessionHasErrors([
                'notes' => 'Catatan tindak lanjut wajib diisi.',
                'channel' => 'Media komunikasi tidak valid.',
                'contacted_at' => 'Waktu komunikasi tidak boleh di masa depan.',
            ]);

        $this->assertDatabaseCount('inquiry_follow_ups', 0);
    }

    public function test_follow_up_notes_are_escaped_against_xss(): void
    {
        $inquiry = Inquiry::factory()->create(['notes' => '<script>alert(1)</script>']);

        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_gudang_and_member_cannot_access_inquiry_management(): void
    {
        $inquiry = Inquiry::factory()->create();

        foreach ([User::factory()->gudang()->create(), User::factory()->member()->create()] as $user) {
            $this->actingAs($user)->get(route('backoffice.marketing.inquiries.index'))->assertForbidden();
            $this->actingAs($user)->get(route('backoffice.marketing.inquiries.show', $inquiry))->assertForbidden();
            $this->actingAs($user)
                ->post(route('backoffice.marketing.inquiries.follow-ups.store', $inquiry), ['channel' => 'WhatsApp', 'notes' => 'hack'])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('inquiry_follow_ups', 0);
    }
}

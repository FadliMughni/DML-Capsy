<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Matriks transisi status inquiry & otorisasi peran di service terpusat. */
class InquiryStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transition_matrix(): void
    {
        $valid = [
            'Inquiry Baru' => ['Menunggu Verifikasi Gudang'],
            'Menunggu Verifikasi Gudang' => ['Tersedia', 'Tersedia Sebagian', 'Tidak Tersedia'],
            'Tersedia' => ['Ditindaklanjuti'],
            'Tersedia Sebagian' => ['Ditindaklanjuti'],
            'Tidak Tersedia' => ['Ditindaklanjuti'],
            'Ditindaklanjuti' => [],
        ];

        foreach (InquiryStatus::cases() as $from) {
            foreach (InquiryStatus::cases() as $to) {
                $this->assertSame(
                    in_array($to->value, $valid[$from->value], true),
                    $from->canTransitionTo($to),
                    "{$from->value} → {$to->value}"
                );
            }
        }
    }

    public function test_service_rejects_wrong_role(): void
    {
        $service = app(InquiryStatusService::class);
        $inquiry = Inquiry::factory()->status(InquiryStatus::MenungguVerifikasi)->create();

        $this->expectException(InvalidStatusTransitionException::class);
        $this->expectExceptionMessage('Anda tidak berwenang');

        // Marketing tidak boleh mengisi hasil verifikasi.
        $service->transition($inquiry, InquiryStatus::Tersedia, User::factory()->marketing()->create());
    }

    public function test_service_rejects_invalid_order_and_keeps_status(): void
    {
        $service = app(InquiryStatusService::class);
        $inquiry = Inquiry::factory()->create();

        try {
            $service->transition($inquiry, InquiryStatus::Tersedia, User::factory()->gudang()->create());
            $this->fail('Seharusnya melempar exception.');
        } catch (InvalidStatusTransitionException $e) {
            $this->assertStringContainsString('tidak dapat diubah', $e->getMessage());
        }

        $this->assertSame(InquiryStatus::Baru, $inquiry->fresh()->status);
    }

    public function test_service_applies_valid_transition(): void
    {
        $inquiry = Inquiry::factory()->create();

        app(InquiryStatusService::class)->transition($inquiry, InquiryStatus::MenungguVerifikasi, User::factory()->marketing()->create());

        $this->assertSame(InquiryStatus::MenungguVerifikasi, $inquiry->fresh()->status);
    }
}

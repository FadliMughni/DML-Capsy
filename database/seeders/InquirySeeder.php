<?php

namespace Database\Seeders;

use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Enums\VerificationResult;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Contoh inquiry di berbagai status untuk demo backoffice Sprint 2. */
class InquirySeeder extends Seeder
{
    public function run(): void
    {
        if (Inquiry::where('inquiry_number', 'like', '%DEMO')->exists()) {
            return; // sudah pernah di-seed
        }

        $marketing = User::where('role', UserRole::Marketing->value)->firstOrFail();
        $gudang = User::where('role', UserRole::Gudang->value)->firstOrFail();
        $products = Product::with('productVariants')->get()->keyBy('slug');

        $samples = [
            ['slug' => 'rubber-outsole-retro-sneakers', 'name' => 'Budi Santoso', 'company' => 'PT Langkah Nusantara', 'phone' => '081298765432', 'qty' => 1200, 'unit' => 'Pasang', 'status' => InquiryStatus::Baru, 'notes' => 'Mohon info ketersediaan stok dan lead time.'],
            ['slug' => 'industrial-heavy-duty-rubber-seals', 'name' => 'Siti Rahmawati', 'company' => 'CV Hidrolik Jaya', 'phone' => '081377788899', 'qty' => 300, 'unit' => 'Pcs', 'status' => InquiryStatus::Baru, 'notes' => null],
            ['slug' => 'molded-industrial-plug-rubber-stopper', 'name' => 'Agus Prasetyo', 'company' => 'PT Pipa Prima', 'phone' => '085711122233', 'qty' => 750, 'unit' => 'Pcs', 'status' => InquiryStatus::MenungguVerifikasi, 'notes' => 'Kemasan per 50 pcs.'],
            ['slug' => 'heavy-duty-rubber-mudguard', 'name' => 'Rudi Hartono', 'company' => 'PT Armada Logistik', 'phone' => '081233344455', 'qty' => 120, 'unit' => 'Pcs', 'status' => InquiryStatus::Tersedia, 'notes' => 'Butuh lubang baut custom.'],
            ['slug' => 'automotive-steering-wheel-core-ring', 'name' => 'Dewi Lestari', 'company' => 'PT Otomotif Sejahtera', 'phone' => '087855566677', 'qty' => 2000, 'unit' => 'Pcs', 'status' => InquiryStatus::TersediaSebagian, 'notes' => null],
            ['slug' => 'custom-molded-technical-rubber-components', 'name' => 'Hendra Wijaya', 'company' => 'PT Tambang Mineral', 'phone' => '081199988877', 'qty' => 500, 'unit' => 'Pcs', 'status' => InquiryStatus::TidakTersedia, 'notes' => 'Sesuai gambar CAD terlampir via email.'],
            ['slug' => 'vulcanized-sneaker-rubber-outsole-white', 'name' => 'Maya Sari', 'company' => null, 'phone' => '081366655544', 'qty' => 600, 'unit' => 'Pasang', 'status' => InquiryStatus::Ditindaklanjuti, 'notes' => null],
            ['slug' => 'precision-molded-flange-rubber-seal-ring', 'name' => 'Yoga Pratama', 'company' => 'PT Kimia Andalan', 'phone' => '082144433322', 'qty' => 150, 'unit' => 'Pcs', 'status' => InquiryStatus::Baru, 'notes' => 'Tahan oli hidrolik.'],
        ];

        foreach ($samples as $i => $s) {
            $product = $products->get($s['slug']);
            $createdAt = now()->subDays(count($samples) - $i)->setTime(9 + $i, 15);

            $inquiry = Inquiry::create([
                'inquiry_number' => 'INQ-'.$createdAt->format('Ymd').'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT).'DEMO',
                'name' => $s['name'],
                'company' => $s['company'],
                'phone' => $s['phone'],
                'email' => strtolower(explode(' ', $s['name'])[0]).'@contoh.co.id',
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? 'Produk Custom',
                'size' => $product?->productVariants->first()?->value ?? 'Sesuai gambar teknik',
                'quantity' => $s['qty'],
                'unit' => $s['unit'],
                'variant' => $product?->productVariants->get(1)?->value,
                'notes' => $s['notes'],
                'status' => $s['status'],
            ]);
            $inquiry->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
            $inquiry->update(['whatsapp_url' => $inquiry->buildWhatsAppMessage()]);

            if ($s['status'] === InquiryStatus::Baru) {
                continue;
            }

            // Pengajuan verifikasi untuk status selain "Inquiry Baru".
            $vr = $inquiry->verificationRequests()->create([
                'requested_by' => $marketing->id,
                'requested_at' => $createdAt->copy()->addHours(2),
                'request_notes' => 'Mohon cek ketersediaan kompon & kapasitas cetak.',
            ]);

            $result = match ($s['status']) {
                InquiryStatus::Tersedia, InquiryStatus::Ditindaklanjuti => VerificationResult::Tersedia,
                InquiryStatus::TersediaSebagian => VerificationResult::TersediaSebagian,
                InquiryStatus::TidakTersedia => VerificationResult::TidakTersedia,
                default => null,
            };

            if (! $result) {
                continue;
            }

            $verifiedAt = $createdAt->copy()->addHours(6);
            $vr->update([
                'result' => $result,
                'available_quantity' => match ($result) {
                    VerificationResult::Tersedia => $s['qty'],
                    VerificationResult::TersediaSebagian => intdiv($s['qty'], 2),
                    VerificationResult::TidakTersedia => 0,
                },
                'estimated_date' => $result->requiresEstimate() ? now()->addDays(14)->toDateString() : null,
                'estimated_duration' => $result === VerificationResult::TidakTersedia ? '3–4 minggu (pembuatan mold baru)' : null,
                'verification_notes' => 'Dicek langsung di gudang bahan baku.',
                'verified_by' => $gudang->id,
                'verified_at' => $verifiedAt,
            ]);

            if ($s['status'] === InquiryStatus::Ditindaklanjuti) {
                $inquiry->followUps()->create([
                    'user_id' => $marketing->id,
                    'contacted_at' => $verifiedAt->copy()->addHour(),
                    'channel' => 'WhatsApp',
                    'notes' => 'Customer sudah dikabari stok tersedia dan meminta penawaran harga resmi.',
                ]);
            }
        }
    }
}

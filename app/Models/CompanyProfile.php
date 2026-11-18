<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konten company profile (satu baris). Dikelola Marketing lewat backoffice (PBI 24).
 */
class CompanyProfile extends Model
{
    protected $fillable = [
        'company_name',
        'brand_name',
        'tagline',
        'founded_info',
        'short_description',
        'description',
        'vision',
        'mission',
        'address',
        'phone',
        'fax',
        'whatsapp',
        'email',
        'operating_hours',
        'maps_url',
        'stats',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
        ];
    }

    /** Ambil profil aktif; jika tabel kosong, kembalikan nilai bawaan (tidak disimpan). */
    public static function current(): self
    {
        return static::query()->oldest('id')->first() ?? new static(static::defaults());
    }

    /** @return list<string> */
    public function missionItems(): array
    {
        return $this->lines($this->mission);
    }

    /** @return list<string> */
    public function operatingHourLines(): array
    {
        return $this->lines($this->operating_hours);
    }

    /** @return list<array{value: string, label: string}> */
    public function statItems(): array
    {
        return array_values(array_filter(
            $this->stats ?? [],
            fn ($s) => is_array($s) && filled($s['value'] ?? null) && filled($s['label'] ?? null)
        ));
    }

    public function whatsappNumber(): string
    {
        return Inquiry::normalizeWhatsAppNumber($this->whatsapp ?: config('services.sales_whatsapp', '6289501823280'));
    }

    public function phoneHref(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->phone);
    }

    /** @return list<string> */
    private function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }

    /** Nilai bawaan = konten resmi yang sebelumnya ditulis langsung di Blade (Sprint 1). */
    public static function defaults(): array
    {
        return [
            'company_name' => 'PT Duta Mitra Luhur',
            'brand_name' => 'Duta Rubber',
            'tagline' => 'Manufactur of Rubber Product',
            'founded_info' => 'Berdiri Sejak 03 Agustus 2010 • Akta Pendirian No. 01 tgl 03 Agustus 2010',
            'short_description' => 'Perusahaan manufaktur spesialis produk karet berkualitas di Indonesia. Kami memproduksi rubber soles (sol sepatu karet), industrial rubber seals, plug rubber, mudguard truk, cover stir mobil, dan custom molded rubber dengan standar mutu ramah lingkungan.',
            'description' => 'PT Duta Mitra Luhur (Duta Rubber) adalah perusahaan manufaktur terkemuka yang memproduksi aneka produk karet teknik berkualitas tinggi dan ramah lingkungan. Berlokasi strategis di Kawasan Industri & Pergudangan Sentra Bitung, Curug, Tangerang - Banten, kami telah dipercaya lebih dari 15 tahun oleh berbagai mitra industri nasional dan korporasi manufaktur multinasional.',
            'vision' => 'Menjadi Perusahaan Manufacturing product yang berkaitan dengan Rubber dengan kualitas terbaik dan ramah lingkungan.',
            'mission' => implode("\n", [
                'Secara berkesinambungan memproduksi dan menyediakan product-product berkualitas tinggi serta memenuhi kebutuhan pelanggan melalui program pemasaran yang terbaik.',
                'Menghasilkan keuntungan yang cukup memuaskan dan meningkatkan kesejahteraan bagi seluruh karyawan.',
                'Mengembangkan kemampuan karyawan yang berkompeten untuk tujuan kemajuan perusahaan.',
                'Menjadi perusahaan yang kuat dan terus berkembang serta siap menghadapi persaingan Regional Maupun Global kedepannya.',
            ]),
            'address' => 'Kawasan Industri & Pergudangan Sentra Bitung C7, Jl. Baitussaadah RT 01/03, Kadu, Curug, Tangerang, Banten',
            'phone' => '(021) 2951-4148',
            'fax' => '(021) 2951-4149',
            'whatsapp' => '0895-0182-3280',
            'email' => 'duta_mitraluhur@yahoo.co.id',
            'operating_hours' => "Senin - Jumat: 08:00 - 17:00 WIB\nSabtu: 08:00 - 13:00 WIB",
            'maps_url' => null,
            'stats' => [
                ['value' => '500+', 'label' => 'KLIEN PABRIK & TAMBANG'],
                ['value' => '1,200+', 'label' => 'SPESIFIKASI KOMPONEN'],
                ['value' => '15+ Thn', 'label' => 'PENGALAMAN FABRIKASI'],
                ['value' => 'SNI & ISO', 'label' => 'GARANSI MUTU LAB'],
            ],
        ];
    }
}

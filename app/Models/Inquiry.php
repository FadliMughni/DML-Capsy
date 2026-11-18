<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_number',
        'name',
        'company',
        'phone',
        'email',
        'product_id',
        'product_name',
        'size',
        'quantity',
        'unit',
        'variant',
        'notes',
        'status',
        'whatsapp_url',
    ];

    protected $attributes = [
        'status' => 'Inquiry Baru',
    ];

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(InquiryFollowUp::class)->latest('contacted_at');
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class)->latest('requested_at');
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(VerificationRequest::class)->latestOfMany();
    }

    /** Pencarian backoffice: nomor inquiry, nama, perusahaan, kontak, produk. */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            foreach (['inquiry_number', 'name', 'company', 'phone', 'email', 'product_name'] as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    public static function generateInquiryNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $count = self::whereDate('created_at', today())->count() + 1;
        $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);

        return "INQ-{$date}-{$sequence}{$random}";
    }

    /** Normalisasi nomor telepon Indonesia ke format wa.me (62xxx). */
    public static function normalizeWhatsAppNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '0')) {
            return '62'.substr($clean, 1);
        }

        if (str_starts_with($clean, '8')) {
            return '62'.$clean;
        }

        return $clean;
    }

    /** Pesan WA dari customer ke Sales (Sprint 1, PBI 11). */
    public function buildWhatsAppMessage(string $salesPhone = '6289501823280'): string
    {
        $cleanPhone = self::normalizeWhatsAppNumber($salesPhone);

        $text = "Halo Tim Sales PT Duta Mitra Luhur (Duta Rubber),\n\n";
        $text .= "Saya ingin menanyakan ketersediaan dan penawaran resmi untuk produk berikut:\n\n";
        $text .= "📌 *No. Inquiry:* {$this->inquiry_number}\n";
        $text .= "👤 *Nama Pemohon:* {$this->name}\n";
        if ($this->company) {
            $text .= "🏢 *Perusahaan:* {$this->company}\n";
        }
        $text .= "📞 *Kontak:* {$this->phone}\n";
        if ($this->email) {
            $text .= "✉️ *Email:* {$this->email}\n";
        }
        $text .= "⚙️ *Produk:* {$this->product_name}\n";
        if ($this->variant) {
            $text .= "🏷️ *Varian/Material:* {$this->variant}\n";
        }
        $text .= "📐 *Ukuran/Dimensi:* {$this->size}\n";
        $text .= "📦 *Jumlah Kebutuhan:* {$this->quantity} {$this->unit}\n";
        if ($this->notes) {
            $text .= "📝 *Catatan Khusus:* {$this->notes}\n";
        }
        $text .= "\nMohon konfirmasi ketersediaan stok, lead time produksi, dan penawaran harga resmi (RFQ). Terima kasih!";

        return "https://wa.me/{$cleanPhone}?text=".rawurlencode($text);
    }

    /**
     * Pesan WA dari Marketing ke customer (Sprint 2, PBI 15/17).
     * Berisi nomor inquiry dan hasil verifikasi Gudang bila sudah ada.
     */
    public function buildCustomerWhatsAppUrl(?string $senderName = null): string
    {
        $verification = $this->relationLoaded('latestVerification')
            ? $this->latestVerification
            : $this->latestVerification()->first();

        $text = "Halo Bapak/Ibu {$this->name},\n\n";
        $text .= 'Kami dari Tim Marketing PT Duta Mitra Luhur (Duta Rubber)'.($senderName ? " ({$senderName})" : '')." menindaklanjuti permintaan Anda:\n\n";
        $text .= "📌 *No. Inquiry:* {$this->inquiry_number}\n";
        $text .= "⚙️ *Produk:* {$this->product_name}\n";
        $text .= "📐 *Ukuran:* {$this->size}\n";
        if ($this->variant) {
            $text .= "🏷️ *Varian:* {$this->variant}\n";
        }
        $text .= "📦 *Jumlah:* {$this->quantity} {$this->unit}\n\n";

        if ($verification && $verification->result) {
            $text .= "✅ *Hasil pengecekan ketersediaan bahan:* {$verification->result->label()}\n";
            $text .= "📊 *Jumlah tersedia:* {$verification->available_quantity} {$this->unit}\n";
            if ($estimate = $verification->estimateLabel()) {
                $text .= "🗓️ *Estimasi penyediaan:* {$estimate}\n";
            }
            $text .= "\nApakah Bapak/Ibu ingin melanjutkan ke tahap penawaran? Terima kasih.";
        } else {
            $text .= 'Permintaan Anda sedang kami proses. Kami akan segera mengabarkan hasil pengecekan ketersediaan bahan. Terima kasih.';
        }

        return 'https://wa.me/'.self::normalizeWhatsAppNumber($this->phone).'?text='.rawurlencode($text);
    }
}

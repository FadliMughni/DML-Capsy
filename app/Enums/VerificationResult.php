<?php

namespace App\Enums;

/** Hasil verifikasi ketersediaan bahan oleh Gudang. */
enum VerificationResult: string
{
    case Tersedia = 'tersedia';
    case TersediaSebagian = 'tersedia_sebagian';
    case TidakTersedia = 'tidak_tersedia';

    public function label(): string
    {
        return $this->toInquiryStatus()->label();
    }

    public function toInquiryStatus(): InquiryStatus
    {
        return match ($this) {
            self::Tersedia => InquiryStatus::Tersedia,
            self::TersediaSebagian => InquiryStatus::TersediaSebagian,
            self::TidakTersedia => InquiryStatus::TidakTersedia,
        };
    }

    /** Estimasi penyediaan wajib diisi untuk hasil ini. */
    public function requiresEstimate(): bool
    {
        return $this !== self::Tersedia;
    }

    public function badgeClasses(): string
    {
        return $this->toInquiryStatus()->badgeClasses();
    }
}

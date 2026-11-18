<?php

namespace App\Enums;

/**
 * Status inquiry (Sprint 2).
 * Nilai disimpan apa adanya di kolom `inquiries.status` (kompatibel dengan data Sprint 1).
 *
 * Alur: Inquiry Baru → Menunggu Verifikasi Gudang → Tersedia / Tersedia Sebagian / Tidak Tersedia → Ditindaklanjuti
 */
enum InquiryStatus: string
{
    case Baru = 'Inquiry Baru';
    case MenungguVerifikasi = 'Menunggu Verifikasi Gudang';
    case Tersedia = 'Tersedia';
    case TersediaSebagian = 'Tersedia Sebagian';
    case TidakTersedia = 'Tidak Tersedia';
    case Ditindaklanjuti = 'Ditindaklanjuti';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Baru => [self::MenungguVerifikasi],
            self::MenungguVerifikasi => [self::Tersedia, self::TersediaSebagian, self::TidakTersedia],
            self::Tersedia, self::TersediaSebagian, self::TidakTersedia => [self::Ditindaklanjuti],
            self::Ditindaklanjuti => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Peran yang berwenang memindahkan inquiry KE status ini. */
    public function actorRole(): ?UserRole
    {
        return match ($this) {
            self::MenungguVerifikasi, self::Ditindaklanjuti => UserRole::Marketing,
            self::Tersedia, self::TersediaSebagian, self::TidakTersedia => UserRole::Gudang,
            self::Baru => null, // hanya dibuat oleh sistem saat customer mengirim form
        };
    }

    /** Status yang sudah memiliki hasil verifikasi Gudang. */
    public function hasVerificationResult(): bool
    {
        return in_array($this, [self::Tersedia, self::TersediaSebagian, self::TidakTersedia], true);
    }

    /** Kelas Tailwind untuk badge (kontras teks ≥ 4.5:1 pada latar). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Baru => 'bg-sky-100 text-sky-900 ring-sky-300',
            self::MenungguVerifikasi => 'bg-amber-100 text-amber-900 ring-amber-300',
            self::Tersedia => 'bg-emerald-100 text-emerald-900 ring-emerald-300',
            self::TersediaSebagian => 'bg-orange-100 text-orange-900 ring-orange-300',
            self::TidakTersedia => 'bg-red-100 text-red-900 ring-red-300',
            self::Ditindaklanjuti => 'bg-slate-200 text-slate-900 ring-slate-400',
        };
    }
}

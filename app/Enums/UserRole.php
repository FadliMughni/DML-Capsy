<?php

namespace App\Enums;

/**
 * Peran pengguna terautentikasi. "Guest" = pengunjung yang belum login.
 */
enum UserRole: string
{
    case Member = 'member';
    case Marketing = 'marketing';
    case Gudang = 'gudang';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Marketing => 'Marketing',
            self::Gudang => 'Gudang',
        };
    }

    /** Peran yang boleh masuk backoffice. */
    public function canAccessBackoffice(): bool
    {
        return in_array($this, [self::Marketing, self::Gudang], true);
    }

    /** Nama route dashboard sesuai peran. */
    public function dashboardRoute(): ?string
    {
        return match ($this) {
            self::Marketing => 'backoffice.marketing.dashboard',
            self::Gudang => 'backoffice.gudang.dashboard',
            self::Member => null,
        };
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGudang();
    }

    public function view(User $user, VerificationRequest $verificationRequest): bool
    {
        return $user->isGudang();
    }

    /** Mencatat hasil verifikasi. Cek "masih menunggu" ada di VerificationService. */
    public function record(User $user, VerificationRequest $verificationRequest): bool
    {
        return $user->isGudang();
    }
}

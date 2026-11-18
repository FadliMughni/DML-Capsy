<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

/**
 * Otorisasi berbasis peran. Validasi urutan status ditangani InquiryStatusService.
 */
class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMarketing();
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->isMarketing();
    }

    public function addFollowUp(User $user, Inquiry $inquiry): bool
    {
        return $user->isMarketing();
    }

    public function requestVerification(User $user, Inquiry $inquiry): bool
    {
        return $user->isMarketing();
    }

    public function markFollowedUp(User $user, Inquiry $inquiry): bool
    {
        return $user->isMarketing();
    }
}

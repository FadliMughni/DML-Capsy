<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Inquiry;
use App\Models\User;

/**
 * SATU-SATUNYA tempat perubahan status inquiry.
 * Sprint 3: tambahkan pencatatan riwayat status (inquiry_status_histories) di method transition().
 */
class InquiryStatusService
{
    /**
     * @throws InvalidStatusTransitionException
     */
    public function transition(Inquiry $inquiry, InquiryStatus $to, User $actor): Inquiry
    {
        $from = $inquiry->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidStatusTransitionException::invalidOrder($from, $to);
        }

        if ($to->actorRole() === null || ! $actor->hasRole($to->actorRole())) {
            throw InvalidStatusTransitionException::unauthorizedRole($to);
        }

        $inquiry->status = $to;
        $inquiry->save();

        return $inquiry;
    }

    /**
     * @throws InvalidStatusTransitionException
     */
    public function ensureCanTransition(Inquiry $inquiry, InquiryStatus $to): void
    {
        if (! $inquiry->status->canTransitionTo($to)) {
            throw InvalidStatusTransitionException::invalidOrder($inquiry->status, $to);
        }
    }
}

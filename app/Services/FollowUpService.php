<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FollowUpService
{
    public function __construct(private readonly InquiryStatusService $statusService) {}

    /**
     * Catat tindak lanjut komunikasi dengan customer (PBI 15).
     *
     * @param  array{contacted_at?: string|null, channel: string, notes: string}  $data
     */
    public function record(Inquiry $inquiry, User $user, array $data): InquiryFollowUp
    {
        return $inquiry->followUps()->create([
            'user_id' => $user->id,
            'contacted_at' => $data['contacted_at'] ?? now(),
            'channel' => $data['channel'],
            'notes' => $data['notes'],
        ]);
    }

    /**
     * Tandai inquiry "Ditindaklanjuti" (PBI 17).
     * Syarat: hasil verifikasi sudah ada dan Marketing sudah mencatat komunikasi setelah verifikasi.
     *
     * @throws InvalidStatusTransitionException
     */
    public function markAsFollowedUp(Inquiry $inquiry, User $user): Inquiry
    {
        return DB::transaction(function () use ($inquiry, $user) {
            $this->statusService->ensureCanTransition($inquiry, InquiryStatus::Ditindaklanjuti);

            $verifiedAt = $inquiry->latestVerification()->value('verified_at');

            $hasContactAfterVerification = $inquiry->followUps()
                ->when($verifiedAt, fn ($q) => $q->where('contacted_at', '>=', $verifiedAt))
                ->exists();

            if (! $hasContactAfterVerification) {
                throw InvalidStatusTransitionException::precondition(
                    'Catat tindak lanjut komunikasi dengan customer terlebih dahulu sebelum menandai inquiry sebagai "Ditindaklanjuti".'
                );
            }

            return $this->statusService->transition($inquiry, InquiryStatus::Ditindaklanjuti, $user);
        });
    }
}

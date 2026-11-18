<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Enums\VerificationResult;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Notifications\VerificationCompletedNotification;
use App\Notifications\VerificationRequestedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VerificationService
{
    public function __construct(private readonly InquiryStatusService $statusService) {}

    /**
     * PBI 16 - Marketing mengajukan verifikasi ketersediaan bahan ke Gudang.
     *
     * @throws InvalidStatusTransitionException
     */
    public function submit(Inquiry $inquiry, User $marketing, ?string $notes = null): VerificationRequest
    {
        $verificationRequest = DB::transaction(function () use ($inquiry, $marketing, $notes) {
            // Kunci baris agar dua klik bersamaan tidak membuat dua pengajuan.
            $locked = Inquiry::query()->lockForUpdate()->findOrFail($inquiry->getKey());

            $this->statusService->ensureCanTransition($locked, InquiryStatus::MenungguVerifikasi);

            $verificationRequest = $locked->verificationRequests()->create([
                'requested_by' => $marketing->id,
                'requested_at' => now(),
                'request_notes' => $notes,
            ]);

            $this->statusService->transition($locked, InquiryStatus::MenungguVerifikasi, $marketing);
            $inquiry->setRawAttributes($locked->getAttributes(), true);

            return $verificationRequest;
        });

        Notification::send(
            User::query()->role(UserRole::Gudang)->get(),
            new VerificationRequestedNotification($verificationRequest->setRelation('inquiry', $inquiry)),
        );

        return $verificationRequest;
    }

    /**
     * PBI 17 - Gudang mencatat hasil verifikasi.
     *
     * @param  array{result: string, available_quantity: int, estimated_date?: ?string, estimated_duration?: ?string, verification_notes?: ?string}  $data
     *
     * @throws InvalidStatusTransitionException
     */
    public function record(VerificationRequest $verificationRequest, User $gudang, array $data): VerificationRequest
    {
        $result = VerificationResult::from($data['result']);

        DB::transaction(function () use ($verificationRequest, $gudang, $data, $result) {
            $locked = VerificationRequest::query()->lockForUpdate()->findOrFail($verificationRequest->getKey());

            if (! $locked->isPending()) {
                throw InvalidStatusTransitionException::precondition('Pengajuan ini sudah diverifikasi sebelumnya.');
            }

            $inquiry = Inquiry::query()->lockForUpdate()->findOrFail($locked->inquiry_id);
            $this->statusService->ensureCanTransition($inquiry, $result->toInquiryStatus());

            $locked->fill([
                'result' => $result,
                'available_quantity' => (int) $data['available_quantity'],
                'estimated_date' => $data['estimated_date'] ?? null,
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'verification_notes' => $data['verification_notes'] ?? null,
                'verified_by' => $gudang->id,
                'verified_at' => now(),
            ])->save();

            $this->statusService->transition($inquiry, $result->toInquiryStatus(), $gudang);

            $verificationRequest->setRawAttributes($locked->getAttributes(), true);
            $verificationRequest->setRelation('inquiry', $inquiry);
        });

        $verificationRequest->loadMissing('requester');
        $verificationRequest->requester?->notify(new VerificationCompletedNotification($verificationRequest));

        return $verificationRequest;
    }
}

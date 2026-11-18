<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Dikirim ke semua pengguna Gudang saat Marketing mengajukan verifikasi (PBI 16). */
class VerificationRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly VerificationRequest $verificationRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $inquiry = $this->verificationRequest->inquiry;

        return [
            'type' => 'verification_requested',
            'title' => 'Pengajuan cek bahan baru',
            'message' => "Inquiry {$inquiry->inquiry_number}: {$inquiry->product_name} ({$inquiry->size}), {$inquiry->quantity} {$inquiry->unit}.",
            'url' => route('backoffice.gudang.verifications.show', $this->verificationRequest),
            'verification_request_id' => $this->verificationRequest->id,
            'inquiry_number' => $inquiry->inquiry_number,
        ];
    }
}

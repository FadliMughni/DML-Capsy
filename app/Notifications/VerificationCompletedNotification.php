<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Dikirim ke Marketing pengaju saat Gudang selesai mencatat hasil verifikasi (PBI 17). */
class VerificationCompletedNotification extends Notification
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
            'type' => 'verification_completed',
            'title' => 'Hasil verifikasi bahan: '.$this->verificationRequest->result->label(),
            'message' => "Inquiry {$inquiry->inquiry_number} ({$inquiry->product_name}) sudah dicek Gudang. Segera hubungi customer.",
            'url' => route('backoffice.marketing.inquiries.show', $inquiry),
            'verification_request_id' => $this->verificationRequest->id,
            'inquiry_number' => $inquiry->inquiry_number,
            'result' => $this->verificationRequest->result->value,
        ];
    }
}

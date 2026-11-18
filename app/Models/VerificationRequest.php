<?php

namespace App\Models;

use App\Enums\VerificationResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'requested_by',
        'requested_at',
        'request_notes',
        'verified_by',
        'verified_at',
        'result',
        'available_quantity',
        'estimated_date',
        'estimated_duration',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'verified_at' => 'datetime',
            'estimated_date' => 'date',
            'available_quantity' => 'integer',
            'result' => VerificationResult::class,
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function isPending(): bool
    {
        return $this->verified_at === null;
    }

    /** Teks estimasi penyediaan, gabungan tanggal dan/atau durasi. */
    public function estimateLabel(): ?string
    {
        $parts = array_filter([
            $this->estimated_date?->translatedFormat('d F Y'),
            $this->estimated_duration,
        ]);

        return $parts ? implode(' / ', $parts) : null;
    }
}

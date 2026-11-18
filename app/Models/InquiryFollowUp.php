<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryFollowUp extends Model
{
    public const CHANNELS = ['WhatsApp', 'Telepon', 'Email', 'Tatap Muka', 'Lainnya'];

    protected $fillable = [
        'inquiry_id',
        'user_id',
        'contacted_at',
        'channel',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

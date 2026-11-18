<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    public const TYPES = [
        'ukuran' => 'Ukuran',
        'warna' => 'Warna',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['product_id', 'type', 'value', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}

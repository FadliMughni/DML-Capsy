<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    public const PLACEHOLDER_IMAGE = 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'badge',
        'material',
        'short_description',
        'description',
        'hardness',
        'temperature_range',
        'tensile_strength',
        'elongation',
        'standards',
        'min_order',
        'image_url',
        'specifications',
        'is_featured',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
    ];

    protected $casts = [
        'specifications' => 'array',
        'variants' => 'array', // DEPRECATED (Sprint 1). Data sudah dipindah ke tabel product_variants.
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'product_id');
    }

    /** Varian (ukuran/warna/lainnya). Nama relasi berbeda dari kolom JSON lama "variants". */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['is_primary' => 'max', 'id' => 'min']);
    }

    /** Hanya produk aktif yang tampil di website publik. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** URL gambar utama: foto upload → image_url lama (Sprint 1) → placeholder. */
    public function displayImageUrl(): string
    {
        return $this->primaryImage?->url() ?? $this->image_url ?? self::PLACEHOLDER_IMAGE;
    }

    public function displayImageAlt(): string
    {
        return $this->primaryImage?->alt_text ?? $this->name;
    }

    /** @return array<string, Collection<int, ProductVariant>> */
    public function variantsGroupedByType(): array
    {
        return $this->productVariants->groupBy('type')->all();
    }
}

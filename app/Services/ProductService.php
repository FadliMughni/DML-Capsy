<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Pengelolaan konten produk: data, spesifikasi, varian, foto, aktif/nonaktif (PBI 24). */
class ProductService
{
    private const ATTRIBUTES = [
        'category_id', 'name', 'slug', 'badge', 'material', 'short_description', 'description',
        'hardness', 'temperature_range', 'tensile_strength', 'elongation', 'standards', 'min_order',
        'is_featured', 'is_active',
    ];

    public function create(array $data): Product
    {
        return $this->save(new Product, $data);
    }

    public function update(Product $product, array $data): Product
    {
        return $this->save($product, $data);
    }

    public function toggleActive(Product $product): Product
    {
        $product->update(['is_active' => ! $product->is_active]);

        return $product;
    }

    public function deleteImage(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            $product = $image->product;
            $image->delete();

            if ($image->is_primary) {
                $product->images()->first()?->update(['is_primary' => true]);
            }
        });

        Storage::disk(ProductImage::DISK)->delete($image->path);
    }

    private function save(Product $product, array $data): Product
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($product, $data, &$storedPaths) {
                $product->fill(Arr::only($data, self::ATTRIBUTES));
                $product->specifications = collect($data['specifications'] ?? [])
                    ->mapWithKeys(fn ($row) => [trim($row['key']) => trim($row['value'])])
                    ->all() ?: null;
                $product->save();

                $this->syncVariants($product, $data['variants'] ?? []);
                $this->updateExistingImages($product, $data['images'] ?? [], $data['primary_image_id'] ?? null);
                $storedPaths = $this->storeNewImages($product, $data['new_images'] ?? []);
                $this->ensurePrimaryImage($product);

                return $product;
            });
        } catch (Throwable $e) {
            // Transaksi gagal â†’ hapus file yang terlanjur diunggah.
            Storage::disk(ProductImage::DISK)->delete($storedPaths);
            throw $e;
        }
    }

    /** Varian disinkronkan ulang (inquiry menyimpan varian sebagai teks, jadi aman). */
    private function syncVariants(Product $product, array $variants): void
    {
        ProductVariant::where('product_id', $product->id)->delete();

        $product->productVariants()->createMany(
            collect($variants)->values()->map(fn ($row, $i) => [
                'type' => $row['type'],
                'value' => trim($row['value']),
                'sort_order' => $i,
            ])->all()
        );
    }

    private function updateExistingImages(Product $product, array $images, int|string|null $primaryId): void
    {
        if (! $product->exists) {
            return;
        }

        $existing = $product->images()->get()->keyBy('id');

        foreach ($images as $id => $row) {
            $existing->get((int) $id)?->update(['alt_text' => trim($row['alt_text'])]);
        }

        if ($primaryId && $existing->has((int) $primaryId)) {
            ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);
            $existing->get((int) $primaryId)->update(['is_primary' => true]);
        }
    }

    /**
     * @param  array<int, array{file: UploadedFile, alt_text: string}>  $newImages
     * @return list<string> path yang tersimpan
     */
    private function storeNewImages(Product $product, array $newImages): array
    {
        $paths = [];
        $sort = (int) $product->images()->max('sort_order');

        foreach ($newImages as $row) {
            $path = $row['file']->store("products/{$product->id}", ProductImage::DISK);
            $paths[] = $path;

            $product->images()->create([
                'path' => $path,
                'alt_text' => trim($row['alt_text']),
                'sort_order' => ++$sort,
                'is_primary' => false,
            ]);
        }

        return $paths;
    }

    private function ensurePrimaryImage(Product $product): void
    {
        if (! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->first()?->update(['is_primary' => true]);
        }
    }
}

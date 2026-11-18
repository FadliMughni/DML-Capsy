<?php

namespace App\Http\Requests\Backoffice;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Dipakai untuk tambah & ubah produk (PBI 24). */
class ProductRequest extends FormRequest
{
    public const MAX_IMAGE_KB = 2048;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()->can('update', $product)
            : $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        $rows = fn (string $key, array $fields) => collect($this->input($key, []))
            ->filter(fn ($row) => is_array($row) && collect($fields)->contains(fn ($f) => filled($row[$f] ?? null)))
            ->values()
            ->all();

        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
            'specifications' => $rows('specifications', ['key', 'value']),
            'variants' => $rows('variants', ['value']),
        ]);
    }

    /** Baris "foto baru" tanpa file (baris kosong di form) diabaikan. */
    public function validationData(): array
    {
        $data = $this->all();
        $data['new_images'] = collect($data['new_images'] ?? [])
            ->filter(fn ($row) => is_array($row) && ($row['file'] ?? null) instanceof UploadedFile)
            ->values()
            ->all();

        return $data;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'integer', Rule::exists('product_categories', 'id')],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:220', 'alpha_dash', Rule::unique('products', 'slug')->ignore($productId)],
            'badge' => ['nullable', 'string', 'max:50'],
            'material' => ['nullable', 'string', 'max:200'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'hardness' => ['nullable', 'string', 'max:100'],
            'temperature_range' => ['nullable', 'string', 'max:100'],
            'tensile_strength' => ['nullable', 'string', 'max:100'],
            'elongation' => ['nullable', 'string', 'max:100'],
            'standards' => ['nullable', 'string', 'max:200'],
            'min_order' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],

            'specifications' => ['array', 'max:30'],
            'specifications.*.key' => ['required', 'string', 'max:100', 'distinct'],
            'specifications.*.value' => ['required', 'string', 'max:300'],

            'variants' => ['array', 'max:50'],
            'variants.*.type' => ['required', Rule::in(array_keys(ProductVariant::TYPES))],
            'variants.*.value' => ['required', 'string', 'max:150'],

            'images' => ['array'],
            'images.*.alt_text' => ['required', 'string', 'max:150'],
            'primary_image_id' => ['nullable', 'integer'],

            'new_images' => ['array', 'max:10'],
            'new_images.*.file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            'new_images.*.alt_text' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 200 karakter.',
            'slug.unique' => 'Slug/URL produk sudah dipakai produk lain. Ubah nama atau slug.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, dan tanda hubung.',
            'specifications.*.key.required' => 'Nama spesifikasi wajib diisi.',
            'specifications.*.key.distinct' => 'Nama spesifikasi tidak boleh duplikat.',
            'specifications.*.value.required' => 'Nilai spesifikasi wajib diisi.',
            'variants.*.type.in' => 'Jenis varian tidak valid.',
            'variants.*.value.required' => 'Nilai varian wajib diisi.',
            'variants.*.value.max' => 'Nilai varian maksimal 150 karakter.',
            'images.*.alt_text.required' => 'Teks alternatif (alt) foto wajib diisi.',
            'new_images.max' => 'Maksimal 10 foto per sekali unggah.',
            'new_images.*.file.image' => 'File harus berupa gambar.',
            'new_images.*.file.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'new_images.*.file.max' => 'Ukuran foto maksimal '.(self::MAX_IMAGE_KB / 1024).' MB.',
            'new_images.*.file.uploaded' => 'Foto gagal diunggah. Pastikan ukuran maksimal '.(self::MAX_IMAGE_KB / 1024).' MB.',
            'new_images.*.alt_text.required' => 'Teks alternatif (alt) wajib diisi untuk setiap foto baru.',
            'new_images.*.alt_text.max' => 'Teks alternatif maksimal 150 karakter.',
        ];
    }
}

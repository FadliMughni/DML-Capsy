<?php

namespace App\Http\Requests\Backoffice;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof ProductCategory
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', ProductCategory::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name', ''))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', 'alpha_dash', Rule::unique('product_categories', 'slug')->ignore($this->route('category')?->id)],
            'icon' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 150 karakter.',
            'slug.unique' => 'Slug kategori sudah dipakai. Gunakan nama/slug lain.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, dan tanda hubung.',
            'icon.alpha_dash' => 'Nama ikon hanya boleh berisi huruf, angka, dan tanda hubung.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
        ];
    }
}

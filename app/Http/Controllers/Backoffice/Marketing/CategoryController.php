<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->withCount([
                'products',
                'products as active_products_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->paginate(20);

        return view('backoffice.marketing.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', ProductCategory::class);

        return view('backoffice.marketing.categories.form', ['category' => new ProductCategory]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = ProductCategory::create($request->validated());

        return redirect()->route('backoffice.marketing.categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil ditambahkan.");
    }

    public function edit(ProductCategory $category): View
    {
        $this->authorize('update', $category);

        return view('backoffice.marketing.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('backoffice.marketing.categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil diperbarui.");
    }

    /** Kategori hanya boleh dihapus jika tidak memiliki produk (aktif maupun nonaktif). */
    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($count = $category->products()->count()) {
            return back()->with('error', "Kategori \"{$category->name}\" masih memiliki {$count} produk. Pindahkan produk ke kategori lain terlebih dahulu.");
        }

        $category->delete();

        return redirect()->route('backoffice.marketing.categories.index')
            ->with('success', "Kategori \"{$category->name}\" dihapus.");
    }
}

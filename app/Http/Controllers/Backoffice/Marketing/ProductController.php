<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $keyword = $request->string('q')->trim()->limit(100, '')->toString();
        $status = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;
        $categoryId = $request->integer('kategori') ?: null;

        $products = Product::query()
            ->with(['category:id,name', 'primaryImage'])
            ->withCount('inquiries')
            ->when($keyword !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('material', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")))
            ->when($status, fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('is_active')
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.marketing.products.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'keyword' => $keyword,
            'status' => $status,
            'categoryId' => $categoryId,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return $this->form(new Product(['is_active' => true]));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->service->create($request->validated());

        return redirect()->route('backoffice.marketing.products.edit', $product)
            ->with('success', "Produk \"{$product->name}\" berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['productVariants', 'images']);

        return $this->form($product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update($product, $request->validated());

        return redirect()->route('backoffice.marketing.products.edit', $product)
            ->with('success', 'Perubahan produk berhasil disimpan.');
    }

    /** Aktifkan / nonaktifkan produk (tidak menghapus permanen). */
    public function toggle(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->service->toggleActive($product);

        return back()->with('success', $product->is_active
            ? "Produk \"{$product->name}\" diaktifkan dan tampil kembali di katalog publik."
            : "Produk \"{$product->name}\" dinonaktifkan. Produk tidak tampil di katalog publik, data inquiry lama tetap tersimpan.");
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->service->deleteImage($image);

        return back()->with('success', 'Foto produk dihapus.');
    }

    private function form(Product $product): View
    {
        return view('backoffice.marketing.products.form', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'variantTypes' => ProductVariant::TYPES,
            'maxImageKb' => ProductRequest::MAX_IMAGE_KB,
        ]);
    }
}

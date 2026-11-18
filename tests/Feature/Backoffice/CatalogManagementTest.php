<?php

namespace Tests\Feature\Backoffice;

use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** PBI 24 - Manajemen Konten Katalog dan Company Profile (Marketing) */
class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $marketing;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(CompanyProfileSeeder::class);
        $this->marketing = User::factory()->marketing()->create();
    }

    private function jpg(string $name = 'foto.jpg', int $kb = 200): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'image/jpeg');
    }

    private function productPayload(ProductCategory $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Seal Karet NBR Tahan Oli',
            'slug' => '',
            'material' => 'NBR',
            'short_description' => 'Seal tahan oli hidrolik.',
            'hardness' => '70 Shore A',
            'is_active' => '1',
            'is_featured' => '0',
            'specifications' => [
                ['key' => 'Tekanan Maks', 'value' => '25 Bar'],
                ['key' => '', 'value' => ''], // baris kosong diabaikan
            ],
            'variants' => [
                ['type' => 'ukuran', 'value' => 'ID 50 mm'],
                ['type' => 'warna', 'value' => 'Hitam'],
                ['type' => 'ukuran', 'value' => ''], // baris kosong diabaikan
            ],
            'new_images' => [
                ['file' => $this->jpg(), 'alt_text' => 'Seal karet NBR hitam diameter 50 mm'],
            ],
        ], $overrides);
    }

    // ---------------- KATEGORI ----------------

    public function test_marketing_can_create_update_and_delete_empty_category(): void
    {
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.categories.store'), ['name' => 'Karet Gasket', 'description' => 'Gasket industri'])
            ->assertRedirect(route('backoffice.marketing.categories.index'));

        $category = ProductCategory::where('slug', 'karet-gasket')->firstOrFail();

        $this->actingAs($this->marketing)
            ->put(route('backoffice.marketing.categories.update', $category), ['name' => 'Gasket Karet Industri', 'slug' => 'gasket'])
            ->assertSessionHasNoErrors();
        $this->assertSame('gasket', $category->fresh()->slug);

        $this->actingAs($this->marketing)
            ->delete(route('backoffice.marketing.categories.destroy', $category))
            ->assertRedirect(route('backoffice.marketing.categories.index'));
        $this->assertModelMissing($category);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->marketing)
            ->delete(route('backoffice.marketing.categories.destroy', $product->category))
            ->assertSessionHas('error');

        $this->assertModelExists($product->category);
        $this->assertModelExists($product);
    }

    public function test_category_slug_must_be_unique(): void
    {
        ProductCategory::factory()->create(['slug' => 'seal']);

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.categories.store'), ['name' => 'Seal'])
            ->assertSessionHasErrors(['slug' => 'Slug kategori sudah dipakai. Gunakan nama/slug lain.']);
    }

    // ---------------- PRODUK ----------------

    public function test_marketing_can_create_product_with_specs_variants_and_photo(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.products.store'), $this->productPayload($category));

        $product = Product::where('slug', 'seal-karet-nbr-tahan-oli')->firstOrFail();
        $response->assertRedirect(route('backoffice.marketing.products.edit', $product))->assertSessionHasNoErrors();

        $this->assertTrue($product->is_active);
        $this->assertSame(['Tekanan Maks' => '25 Bar'], $product->specifications);
        $this->assertSame(['ID 50 mm', 'Hitam'], $product->productVariants->pluck('value')->all());
        $this->assertSame(['ukuran', 'warna'], $product->productVariants->pluck('type')->all());

        $image = $product->images()->sole();
        $this->assertTrue($image->is_primary);
        $this->assertSame('Seal karet NBR hitam diameter 50 mm', $image->alt_text);
        Storage::disk('public')->assertExists($image->path);
        $this->assertStringStartsWith("products/{$product->id}/", $image->path);
    }

    public function test_product_photo_and_alt_are_shown_on_public_pages(): void
    {
        $category = ProductCategory::factory()->create();
        $this->actingAs($this->marketing)->post(route('backoffice.marketing.products.store'), $this->productPayload($category));
        $product = Product::where('slug', 'seal-karet-nbr-tahan-oli')->firstOrFail();
        $image = $product->images()->sole();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($image->url(), false)
            ->assertSee('alt="Seal karet NBR hitam diameter 50 mm"', false);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Seal Karet NBR Tahan Oli')
            ->assertSee('Tekanan Maks')
            ->assertSee('25 Bar')
            ->assertSee('ID 50 mm')
            ->assertSee('Hitam')
            ->assertSee('alt="Seal karet NBR hitam diameter 50 mm"', false);
    }

    public function test_photo_type_size_and_alt_are_validated(): void
    {
        $category = ProductCategory::factory()->create();

        $cases = [
            ['file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'), 'alt_text' => 'x', 'error' => 'Format foto harus JPG, PNG, atau WEBP.'],
            ['file' => UploadedFile::fake()->create('script.gif', 100, 'image/gif'), 'alt_text' => 'x', 'error' => 'Format foto harus JPG, PNG, atau WEBP.'],
            ['file' => $this->jpg('besar.jpg', 3000), 'alt_text' => 'x', 'error' => 'Ukuran foto maksimal 2 MB.'],
            ['file' => $this->jpg(), 'alt_text' => '', 'error' => 'Teks alternatif (alt) wajib diisi untuk setiap foto baru.', 'field' => 'new_images.0.alt_text'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($this->marketing)
                ->post(route('backoffice.marketing.products.store'), $this->productPayload($category, [
                    'new_images' => [['file' => $case['file'], 'alt_text' => $case['alt_text']]],
                ]))
                ->assertSessionHasErrors([($case['field'] ?? 'new_images.0.file') => $case['error']]);
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_png_and_webp_are_accepted(): void
    {
        $category = ProductCategory::factory()->create();

        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.products.store'), $this->productPayload($category, [
                'new_images' => [
                    ['file' => UploadedFile::fake()->create('a.png', 100, 'image/png'), 'alt_text' => 'Tampak depan'],
                    ['file' => UploadedFile::fake()->create('b.webp', 100, 'image/webp'), 'alt_text' => 'Tampak samping'],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('product_images', 2);
        $this->assertSame(1, ProductImage::where('is_primary', true)->count());
    }

    public function test_marketing_can_update_product_variants_alt_and_primary_photo(): void
    {
        $category = ProductCategory::factory()->create();
        $this->actingAs($this->marketing)->post(route('backoffice.marketing.products.store'), $this->productPayload($category));
        $product = Product::where('slug', 'seal-karet-nbr-tahan-oli')->firstOrFail();
        $first = $product->images()->sole();

        $this->actingAs($this->marketing)
            ->put(route('backoffice.marketing.products.update', $product), $this->productPayload($category, [
                'name' => 'Seal Karet NBR Premium',
                'slug' => 'seal-karet-nbr-tahan-oli',
                'variants' => [['type' => 'warna', 'value' => 'Merah']],
                'images' => [$first->id => ['alt_text' => 'Alt baru']],
                'new_images' => [['file' => $this->jpg('kedua.jpg'), 'alt_text' => 'Foto kedua']],
            ]))
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Seal Karet NBR Premium', $product->name);
        $this->assertSame(['Merah'], $product->productVariants->pluck('value')->all());
        $this->assertSame('Alt baru', $first->fresh()->alt_text);
        $this->assertCount(2, $product->images);

        $second = $product->images()->where('alt_text', 'Foto kedua')->sole();
        $this->actingAs($this->marketing)
            ->put(route('backoffice.marketing.products.update', $product), $this->productPayload($category, [
                'slug' => 'seal-karet-nbr-tahan-oli', 'new_images' => [], 'primary_image_id' => $second->id,
            ]));
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertFalse($first->fresh()->is_primary);
    }

    public function test_marketing_can_delete_photo_and_file_is_removed(): void
    {
        $category = ProductCategory::factory()->create();
        $this->actingAs($this->marketing)->post(route('backoffice.marketing.products.store'), $this->productPayload($category));
        $product = Product::where('slug', 'seal-karet-nbr-tahan-oli')->firstOrFail();
        $image = $product->images()->sole();

        $this->actingAs($this->marketing)
            ->delete(route('backoffice.marketing.products.images.destroy', [$product, $image]))
            ->assertSessionHas('success');

        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_product_validation_messages_are_in_indonesian(): void
    {
        $this->actingAs($this->marketing)
            ->post(route('backoffice.marketing.products.store'), ['name' => '', 'category_id' => 999])
            ->assertSessionHasErrors([
                'name' => 'Nama produk wajib diisi.',
                'category_id' => 'Kategori tidak ditemukan.',
            ]);
    }

    public function test_deactivated_product_is_hidden_from_public_catalog_but_kept(): void
    {
        $product = Product::factory()->create(['name' => 'Plug Rubber Rahasia', 'slug' => 'plug-rubber-rahasia', 'is_featured' => true]);
        $inquiry = Inquiry::factory()->create(['product_id' => $product->id, 'product_name' => $product->name]);

        $this->get(route('catalog.index'))->assertSee('Plug Rubber Rahasia');

        $this->actingAs($this->marketing)
            ->patch(route('backoffice.marketing.products.toggle', $product))
            ->assertSessionHas('success');

        $this->assertFalse($product->fresh()->is_active);
        $this->assertModelExists($product); // tidak dihapus permanen

        auth()->logout();
        $this->flushSession(); // buang flash message backoffice agar tidak ikut tampil
        $this->get(route('catalog.index'))->assertOk()->assertDontSee('Plug Rubber Rahasia');
        $this->get(route('catalog.index', ['q' => 'Rahasia']))->assertDontSee('Plug Rubber Rahasia');
        $this->get(route('home'))->assertDontSee('Plug Rubber Rahasia');
        $this->get(route('catalog.show', 'plug-rubber-rahasia'))->assertNotFound();
        $this->get(route('inquiry.create'))->assertDontSee('Plug Rubber Rahasia');

        // Inquiry lama tetap utuh & masih terhubung ke produk.
        $this->assertSame($product->id, $inquiry->fresh()->product_id);
        $this->actingAs($this->marketing)
            ->get(route('backoffice.marketing.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Plug Rubber Rahasia');

        // Bisa diaktifkan kembali.
        $this->actingAs($this->marketing)->patch(route('backoffice.marketing.products.toggle', $product));
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_public_inquiry_rejects_inactive_product_id(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->post(route('inquiry.store'), [
            'name' => 'Budi', 'phone' => '081234567890', 'product_name' => $product->name, 'product_id' => $product->id,
            'size' => '10 mm', 'quantity' => 10, 'unit' => 'Pcs',
        ])->assertSessionHasErrors('product_id');
    }

    public function test_product_cannot_be_permanently_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->marketing)
            ->delete('/backoffice/marketing/produk/'.$product->id)
            ->assertStatus(405);

        $this->assertModelExists($product);
    }

    // ---------------- COMPANY PROFILE ----------------

    public function test_marketing_can_update_company_profile_and_public_pages_reflect_it(): void
    {
        $payload = array_merge(CompanyProfile::defaults(), [
            'description' => 'Deskripsi perusahaan versi terbaru 2026.',
            'vision' => 'Visi baru: menjadi pemimpin karet ramah lingkungan.',
            'mission' => "Misi pertama baru\nMisi kedua baru",
            'address' => 'Jl. Industri Baru No. 7, Tangerang',
            'phone' => '(021) 1111-2222',
            'whatsapp' => '0812-0000-1111',
            'email' => 'sales@dutarubber.test',
            'stats' => [['value' => '999+', 'label' => 'PROYEK SELESAI'], ['value' => '', 'label' => '']],
        ]);

        $this->actingAs($this->marketing)
            ->put(route('backoffice.marketing.company-profile.update'), $payload)
            ->assertRedirect(route('backoffice.marketing.company-profile.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, CompanyProfile::count());
        auth()->logout();

        $this->get(route('about'))
            ->assertSee('Deskripsi perusahaan versi terbaru 2026.')
            ->assertSee('Visi baru: menjadi pemimpin karet ramah lingkungan.')
            ->assertSeeInOrder(['Misi pertama baru', 'Misi kedua baru'])
            ->assertSee('Jl. Industri Baru No. 7, Tangerang');

        $this->get(route('contact'))
            ->assertSee('(021) 1111-2222')
            ->assertSee('sales@dutarubber.test')
            ->assertSee('https://wa.me/6281200001111', false);

        $this->get(route('home'))
            ->assertSee('999+')
            ->assertSee('PROYEK SELESAI')
            ->assertDontSee('KLIEN PABRIK &amp; TAMBANG', false)
            ->assertSee('Jl. Industri Baru No. 7, Tangerang'); // footer
    }

    public function test_company_profile_validation(): void
    {
        $this->actingAs($this->marketing)
            ->put(route('backoffice.marketing.company-profile.update'), [
                'company_name' => '', 'email' => 'bukan-email', 'whatsapp' => 'abc', 'maps_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors([
                'company_name' => 'Nama perusahaan wajib diisi.',
                'email' => 'Format email tidak valid.',
                'whatsapp' => 'Format nomor WhatsApp tidak valid.',
                'maps_url' => 'Tautan Google Maps harus berupa URL https yang valid.',
            ]);
    }

    public function test_company_profile_content_is_escaped_on_public_pages(): void
    {
        CompanyProfile::current()->update(['vision' => '<script>alert("xss")</script>']);

        $this->get(route('about'))
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    // ---------------- OTORISASI ----------------

    public function test_gudang_and_member_cannot_manage_catalog_or_profile(): void
    {
        $product = Product::factory()->create();

        foreach ([User::factory()->gudang()->create(), User::factory()->member()->create()] as $user) {
            $this->actingAs($user)->get(route('backoffice.marketing.products.index'))->assertForbidden();
            $this->actingAs($user)->get(route('backoffice.marketing.products.create'))->assertForbidden();
            $this->actingAs($user)->patch(route('backoffice.marketing.products.toggle', $product))->assertForbidden();
            $this->actingAs($user)->get(route('backoffice.marketing.categories.index'))->assertForbidden();
            $this->actingAs($user)->post(route('backoffice.marketing.categories.store'), ['name' => 'X'])->assertForbidden();
            $this->actingAs($user)->get(route('backoffice.marketing.company-profile.edit'))->assertForbidden();
            $this->actingAs($user)->put(route('backoffice.marketing.company-profile.update'), ['company_name' => 'X'])->assertForbidden();
        }

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_backoffice_pages_render(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->marketing)->get(route('backoffice.marketing.products.index'))->assertOk()->assertSee($product->name);
        $this->actingAs($this->marketing)->get(route('backoffice.marketing.products.create'))->assertOk()->assertSee('Tambah Produk');
        $this->actingAs($this->marketing)->get(route('backoffice.marketing.products.edit', $product))->assertOk()->assertSee($product->name);
        $this->actingAs($this->marketing)->get(route('backoffice.marketing.categories.index'))->assertOk();
        $this->actingAs($this->marketing)->get(route('backoffice.marketing.categories.create'))->assertOk();
        $this->actingAs($this->marketing)->get(route('backoffice.marketing.company-profile.edit'))->assertOk()->assertSee('PT Duta Mitra Luhur');
    }
}

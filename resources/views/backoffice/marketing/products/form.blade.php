@extends('layouts.backoffice')
@php
    $isEdit = $product->exists;
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600';

    $specRows = old('specifications', collect($product->specifications ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->all());
    $variantRows = old('variants', $product->relationLoaded('productVariants')
        ? $product->productVariants->map(fn ($v) => ['type' => $v->type, 'value' => $v->value])->all()
        : []);
    $images = $product->relationLoaded('images') ? $product->images : collect();
    $technical = [
        'material' => 'Material / Formula Kompon',
        'hardness' => 'Kekerasan (Shore A)',
        'temperature_range' => 'Rentang Suhu',
        'tensile_strength' => 'Tensile Strength',
        'elongation' => 'Elongation',
        'standards' => 'Standar',
        'min_order' => 'Minimal Order',
        'badge' => 'Label/Badge',
    ];
@endphp
@section('title', $isEdit ? 'Ubah Produk' : 'Tambah Produk')
@section('heading', $isEdit ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
<nav class="text-sm mb-4 flex flex-wrap gap-4 justify-between">
    <a href="{{ route('backoffice.marketing.products.index') }}" class="text-red-700 font-semibold hover:underline">&larr; Kembali ke daftar produk</a>
    @if ($isEdit && $product->is_active)
        <a href="{{ route('catalog.show', $product->slug) }}" target="_blank" rel="noopener" class="text-slate-800 font-semibold hover:underline">Lihat di katalog publik ↗</a>
    @endif
</nav>

<form method="POST" enctype="multipart/form-data" id="product-form"
      action="{{ $isEdit ? route('backoffice.marketing.products.update', $product) : route('backoffice.marketing.products.store') }}"
      class="grid gap-6 xl:grid-cols-3">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="xl:col-span-2 space-y-6">
        {{-- INFORMASI UTAMA --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5 space-y-4" aria-labelledby="info-utama">
            <h2 id="info-utama" class="font-bold">Informasi Utama</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-semibold mb-1">Nama Produk <span class="text-red-700">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="200" value="{{ old('name', $product->name) }}" class="{{ $input }}">
                    @error('name') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-semibold mb-1">Kategori <span class="text-red-700">*</span></label>
                    <select id="category_id" name="category_id" required class="{{ $input }} bg-white">
                        <option value="">— Pilih kategori —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) old('category_id', $product->category_id) === $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="slug" class="block text-sm font-semibold mb-1">Slug URL</label>
                    <input id="slug" name="slug" type="text" maxlength="220" value="{{ old('slug', $product->slug) }}" class="{{ $input }} font-mono" placeholder="otomatis dari nama">
                    @error('slug') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="short_description" class="block text-sm font-semibold mb-1">Deskripsi Singkat</label>
                    <textarea id="short_description" name="short_description" rows="2" maxlength="500" class="{{ $input }}">{{ old('short_description', $product->short_description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-semibold mb-1">Deskripsi Lengkap</label>
                    <textarea id="description" name="description" rows="5" maxlength="5000" class="{{ $input }}">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </section>

        {{-- SPESIFIKASI TEKNIS --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5 space-y-4" aria-labelledby="spesifikasi">
            <h2 id="spesifikasi" class="font-bold">Spesifikasi Teknis</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ($technical as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-semibold mb-1">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="200" value="{{ old($field, $product->{$field}) }}" class="{{ $input }}">
                        @error($field) <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="text-sm font-semibold mb-2">Spesifikasi Tambahan</h3>
                <div class="space-y-2" data-repeater="specs">
                    @foreach ($specRows as $i => $row)
                        <div class="grid grid-cols-[1fr_1fr_auto] gap-2" data-row>
                            <input name="specifications[{{ $i }}][key]" value="{{ $row['key'] ?? '' }}" aria-label="Nama spesifikasi" placeholder="Nama, mis. Pola Tapak" class="{{ $input }}">
                            <input name="specifications[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" aria-label="Nilai spesifikasi" placeholder="Nilai" class="{{ $input }}">
                            <button type="button" data-remove class="px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Hapus baris spesifikasi">&times;</button>
                        </div>
                    @endforeach
                </div>
                <template id="tpl-specs">
                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2" data-row>
                        <input name="specifications[__I__][key]" aria-label="Nama spesifikasi" placeholder="Nama, mis. Pola Tapak" class="{{ $input }}">
                        <input name="specifications[__I__][value]" aria-label="Nilai spesifikasi" placeholder="Nilai" class="{{ $input }}">
                        <button type="button" data-remove class="px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Hapus baris spesifikasi">&times;</button>
                    </div>
                </template>
                <button type="button" data-add="specs" class="mt-2 text-sm font-semibold text-red-700 hover:underline">+ Tambah spesifikasi</button>
            </div>
        </section>

        {{-- VARIAN --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="varian">
            <h2 id="varian" class="font-bold mb-1">Varian (Ukuran / Warna)</h2>
            <p class="text-sm text-slate-600 mb-3">Ditampilkan di halaman detail produk dan dapat dipilih customer saat bertanya ketersediaan.</p>
            <div class="space-y-2" data-repeater="variants">
                @foreach ($variantRows as $i => $row)
                    <div class="grid grid-cols-[9rem_1fr_auto] gap-2" data-row>
                        <select name="variants[{{ $i }}][type]" aria-label="Jenis varian" class="{{ $input }} bg-white">
                            @foreach ($variantTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($row['type'] ?? 'ukuran') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="variants[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" maxlength="150" aria-label="Nilai varian" placeholder="mis. Diameter 30 mm / Hitam" class="{{ $input }}">
                        <button type="button" data-remove class="px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Hapus varian">&times;</button>
                    </div>
                @endforeach
            </div>
            <template id="tpl-variants">
                <div class="grid grid-cols-[9rem_1fr_auto] gap-2" data-row>
                    <select name="variants[__I__][type]" aria-label="Jenis varian" class="{{ $input }} bg-white">
                        @foreach ($variantTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="variants[__I__][value]" maxlength="150" aria-label="Nilai varian" placeholder="mis. Diameter 30 mm / Hitam" class="{{ $input }}">
                    <button type="button" data-remove class="px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" aria-label="Hapus varian">&times;</button>
                </div>
            </template>
            <button type="button" data-add="variants" class="mt-2 text-sm font-semibold text-red-700 hover:underline">+ Tambah varian</button>
        </section>

        {{-- FOTO --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="foto">
            <h2 id="foto" class="font-bold mb-1">Foto Produk</h2>
            <p class="text-sm text-slate-600 mb-4">Format JPG, PNG, atau WEBP, maksimal {{ $maxImageKb / 1024 }} MB per foto. <strong>Teks alternatif (alt) wajib</strong> diisi: jelaskan isi foto untuk pengguna pembaca layar.</p>

            @if ($images->isNotEmpty())
                <ul class="grid sm:grid-cols-2 gap-4 mb-5">
                    @foreach ($images as $image)
                        <li class="rounded-lg border border-slate-200 p-3 space-y-2">
                            <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" class="w-full h-40 object-cover rounded-md bg-slate-100">
                            <label class="block text-xs font-semibold" for="alt-{{ $image->id }}">Teks alternatif</label>
                            <input id="alt-{{ $image->id }}" name="images[{{ $image->id }}][alt_text]" maxlength="150" required value="{{ old('images.'.$image->id.'.alt_text', $image->alt_text) }}" class="{{ $input }}">
                            <div class="flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked((int) old('primary_image_id', $image->is_primary ? $image->id : 0) === $image->id) class="accent-red-700"> Foto utama
                                </label>
                                <button type="submit" form="delete-image-{{ $image->id }}" class="text-sm font-semibold text-red-700 hover:underline">Hapus foto</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @elseif ($product->image_url)
                <p class="text-sm text-slate-700 mb-4">Saat ini memakai gambar bawaan: <code class="text-xs">{{ $product->image_url }}</code>. Unggah foto untuk menggantinya.</p>
            @endif

            <h3 class="text-sm font-semibold mb-2">Unggah Foto Baru</h3>
            <div class="space-y-3" data-repeater="images">
                <div class="grid sm:grid-cols-2 gap-2 p-3 rounded-lg bg-slate-50 border border-slate-200" data-row>
                    <div>
                        <label class="block text-xs font-semibold mb-1" for="new-file-0">File foto</label>
                        <input id="new-file-0" type="file" name="new_images[0][file]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" for="new-alt-0">Teks alternatif (alt)</label>
                        <input id="new-alt-0" name="new_images[0][alt_text]" maxlength="150" placeholder="mis. Seal karet NBR hitam diameter 50 mm" class="{{ $input }}">
                    </div>
                </div>
            </div>
            <template id="tpl-images">
                <div class="grid sm:grid-cols-[1fr_1fr_auto] gap-2 p-3 rounded-lg bg-slate-50 border border-slate-200" data-row>
                    <div>
                        <label class="block text-xs font-semibold mb-1" for="new-file-__I__">File foto</label>
                        <input id="new-file-__I__" type="file" name="new_images[__I__][file]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" for="new-alt-__I__">Teks alternatif (alt)</label>
                        <input id="new-alt-__I__" name="new_images[__I__][alt_text]" maxlength="150" class="{{ $input }}">
                    </div>
                    <button type="button" data-remove class="self-end px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white" aria-label="Hapus baris foto">&times;</button>
                </div>
            </template>
            <button type="button" data-add="images" class="mt-2 text-sm font-semibold text-red-700 hover:underline">+ Tambah foto lain</button>
        </section>
    </div>

    {{-- SIDEBAR --}}
    <aside class="space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-5 space-y-4 xl:sticky xl:top-20" aria-labelledby="publikasi">
            <h2 id="publikasi" class="font-bold">Publikasi</h2>
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="mt-0.5 accent-red-700">
                <span><strong>Aktif</strong><br><span class="text-slate-600">Produk nonaktif tidak tampil di katalog publik, tetapi tetap tersimpan untuk inquiry lama.</span></span>
            </label>
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="mt-0.5 accent-red-700">
                <span><strong>Produk unggulan</strong><br><span class="text-slate-600">Ditampilkan di beranda.</span></span>
            </label>
            <button type="submit" class="w-full rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold px-5 py-2.5 text-sm">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
        </section>
    </aside>
</form>

{{-- Form hapus foto (di luar form utama, dipanggil via atribut form="") --}}
@foreach ($images as $image)
    <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('backoffice.marketing.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('Hapus foto ini?');" class="hidden">
        @csrf @method('DELETE')
    </form>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    var counter = 1000;
    document.querySelectorAll('[data-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.dataset.add;
            var tpl = document.getElementById('tpl-' + name);
            var container = document.querySelector('[data-repeater="' + name + '"]');
            var html = tpl.innerHTML.replace(/__I__/g, String(counter++));
            container.insertAdjacentHTML('beforeend', html);
            var first = container.lastElementChild.querySelector('input, select');
            if (first) first.focus();
        });
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-remove]');
        if (btn) btn.closest('[data-row]').remove();
    });
})();
</script>
@endpush

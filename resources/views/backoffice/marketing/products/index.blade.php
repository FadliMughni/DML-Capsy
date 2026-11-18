@extends('layouts.backoffice')
@section('title', 'Produk')
@section('heading', 'Manajemen Produk')

@section('content')
<div class="flex flex-col lg:flex-row gap-3 lg:items-end mb-5">
    <form method="GET" action="{{ route('backoffice.marketing.products.index') }}" class="flex-1 bg-white rounded-xl border border-slate-200 p-4 grid gap-3 sm:grid-cols-[1fr_12rem_10rem_auto] items-end" role="search">
        <div>
            <label for="q" class="block text-sm font-semibold mb-1">Cari</label>
            <input id="q" name="q" type="search" value="{{ $keyword }}" placeholder="Nama, material, slug…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="kategori" class="block text-sm font-semibold mb-1">Kategori</label>
            <select id="kategori" name="kategori" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-semibold mb-1">Status</label>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2 text-sm">Terapkan</button>
    </form>
    <a href="{{ route('backoffice.marketing.products.create') }}" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-semibold px-4 py-2.5 text-sm text-center">+ Tambah Produk</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-700">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Produk</th>
                <th scope="col" class="px-4 py-3 font-semibold">Kategori</th>
                <th scope="col" class="px-4 py-3 font-semibold text-right">Inquiry</th>
                <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($products as $product)
                <tr class="{{ $product->is_active ? '' : 'bg-slate-50' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->displayImageAlt() }}" class="w-12 h-12 rounded-lg object-cover bg-slate-200 shrink-0" loading="lazy">
                            <div class="min-w-0">
                                <span class="font-semibold block">{{ $product->name }}</span>
                                <span class="text-xs text-slate-600 font-mono">{{ $product->slug }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $product->category?->name }}</td>
                    <td class="px-4 py-3 text-right">{{ $product->inquiries_count }}</td>
                    <td class="px-4 py-3">
                        @if ($product->is_active)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset bg-emerald-100 text-emerald-900 ring-emerald-300">Aktif</span>
                        @else
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset bg-slate-200 text-slate-900 ring-slate-400">Nonaktif</span>
                        @endif
                        @if ($product->is_featured)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset bg-sky-100 text-sky-900 ring-sky-300">Unggulan</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end items-center gap-3 whitespace-nowrap">
                            <a href="{{ route('backoffice.marketing.products.edit', $product) }}" class="font-semibold text-red-700 hover:underline">Ubah<span class="sr-only"> {{ $product->name }}</span></a>
                            <form method="POST" action="{{ route('backoffice.marketing.products.toggle', $product) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="font-semibold text-slate-700 hover:underline">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}<span class="sr-only"> {{ $product->name }}</span></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-600">Tidak ada produk yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection

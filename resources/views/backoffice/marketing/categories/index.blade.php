@extends('layouts.backoffice')
@section('title', 'Kategori Produk')
@section('heading', 'Kategori Produk')

@section('content')
<div class="flex justify-end mb-4">
    <a href="{{ route('backoffice.marketing.categories.create') }}" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-semibold px-4 py-2 text-sm">+ Tambah Kategori</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-700">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Nama</th>
                <th scope="col" class="px-4 py-3 font-semibold">Slug</th>
                <th scope="col" class="px-4 py-3 font-semibold text-right">Produk Aktif / Total</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($categories as $category)
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-semibold block">{{ $category->name }}</span>
                        <span class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($category->description, 90) }}</span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $category->slug }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $category->active_products_count }} / {{ $category->products_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-3 whitespace-nowrap">
                            <a href="{{ route('backoffice.marketing.categories.edit', $category) }}" class="font-semibold text-red-700 hover:underline">Ubah<span class="sr-only"> {{ $category->name }}</span></a>
                            @if ($category->products_count === 0)
                                <form method="POST" action="{{ route('backoffice.marketing.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-semibold text-slate-700 hover:text-red-700 hover:underline">Hapus<span class="sr-only"> {{ $category->name }}</span></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-600">Belum ada kategori.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection

@extends('layouts.backoffice')
@php($isEdit = $category->exists)
@section('title', $isEdit ? 'Ubah Kategori' : 'Tambah Kategori')
@section('heading', $isEdit ? 'Ubah Kategori' : 'Tambah Kategori')

@php($input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600')

@section('content')
<nav class="text-sm mb-4"><a href="{{ route('backoffice.marketing.categories.index') }}" class="text-red-700 font-semibold hover:underline">&larr; Kembali ke daftar kategori</a></nav>

<form method="POST" action="{{ $isEdit ? route('backoffice.marketing.categories.update', $category) : route('backoffice.marketing.categories.store') }}"
      class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 max-w-2xl space-y-5">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div>
        <label for="name" class="block text-sm font-semibold mb-1">Nama Kategori <span class="text-red-700">*</span></label>
        <input id="name" name="name" type="text" required maxlength="150" value="{{ old('name', $category->name) }}" class="{{ $input }}">
        @error('name') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="slug" class="block text-sm font-semibold mb-1">Slug URL</label>
        <input id="slug" name="slug" type="text" maxlength="170" value="{{ old('slug', $category->slug) }}" class="{{ $input }} font-mono" aria-describedby="slug-help">
        <p id="slug-help" class="text-xs text-slate-600 mt-1">Kosongkan untuk dibuat otomatis dari nama. Dipakai di filter katalog: /katalog?kategori=<em>slug</em></p>
        @error('slug') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="icon" class="block text-sm font-semibold mb-1">Ikon <span class="font-normal text-slate-600">(opsional, nama ikon)</span></label>
        <input id="icon" name="icon" type="text" maxlength="50" value="{{ old('icon', $category->icon) }}" class="{{ $input }}" placeholder="mis. shield-check">
        @error('icon') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-semibold mb-1">Deskripsi</label>
        <textarea id="description" name="description" rows="4" maxlength="1000" class="{{ $input }}">{{ old('description', $category->description) }}</textarea>
        @error('description') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold px-5 py-2.5 text-sm">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Kategori' }}</button>
        <a href="{{ route('backoffice.marketing.categories.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">Batal</a>
    </div>
</form>
@endsection

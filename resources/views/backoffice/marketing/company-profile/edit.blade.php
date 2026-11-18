@extends('layouts.backoffice')
@section('title', 'Profil Perusahaan')
@section('heading', 'Konten Profil Perusahaan')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600';
    $stats = old('stats', $profile->stats ?? []);
    $stats = array_pad(array_values($stats), 4, ['value' => '', 'label' => '']);
@endphp

@section('content')
<p class="text-sm text-slate-700 mb-5">Perubahan di halaman ini langsung tampil di Beranda, Tentang Pabrik, Kontak, dan footer website publik. <a href="{{ route('about') }}" target="_blank" rel="noopener" class="font-semibold text-red-700 hover:underline">Lihat halaman publik ↗</a></p>

<form method="POST" action="{{ route('backoffice.marketing.company-profile.update') }}" class="space-y-6 max-w-4xl">
    @csrf
    @method('PUT')

    <section class="bg-white rounded-xl border border-slate-200 p-5 grid sm:grid-cols-2 gap-4" aria-labelledby="identitas">
        <h2 id="identitas" class="font-bold sm:col-span-2">Identitas Perusahaan</h2>
        @foreach ([
            'company_name' => ['Nama Perusahaan', true, 150],
            'brand_name' => ['Nama Merek', false, 100],
            'tagline' => ['Tagline', false, 200],
            'founded_info' => ['Info Pendirian', false, 200],
        ] as $field => [$label, $required, $max])
            <div>
                <label for="{{ $field }}" class="block text-sm font-semibold mb-1">{{ $label }} @if ($required)<span class="text-red-700">*</span>@endif</label>
                <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="{{ $max }}" @required($required) value="{{ old($field, $profile->{$field}) }}" class="{{ $input }}">
                @error($field) <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div class="sm:col-span-2">
            <label for="short_description" class="block text-sm font-semibold mb-1">Deskripsi Singkat <span class="font-normal text-slate-600">(footer)</span></label>
            <textarea id="short_description" name="short_description" rows="2" maxlength="600" class="{{ $input }}">{{ old('short_description', $profile->short_description) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-semibold mb-1">Deskripsi Perusahaan <span class="text-red-700">*</span></label>
            <textarea id="description" name="description" rows="5" required maxlength="5000" class="{{ $input }}">{{ old('description', $profile->description) }}</textarea>
            @error('description') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5 space-y-4" aria-labelledby="visi-misi">
        <h2 id="visi-misi" class="font-bold">Visi &amp; Misi</h2>
        <div>
            <label for="vision" class="block text-sm font-semibold mb-1">Visi <span class="text-red-700">*</span></label>
            <textarea id="vision" name="vision" rows="2" required maxlength="1000" class="{{ $input }}">{{ old('vision', $profile->vision) }}</textarea>
            @error('vision') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="mission" class="block text-sm font-semibold mb-1">Misi <span class="text-red-700">*</span></label>
            <textarea id="mission" name="mission" rows="6" required maxlength="3000" class="{{ $input }}" aria-describedby="mission-help">{{ old('mission', $profile->mission) }}</textarea>
            <p id="mission-help" class="text-xs text-slate-600 mt-1">Tulis satu poin misi per baris. Penomoran dibuat otomatis.</p>
            @error('mission') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5 grid sm:grid-cols-2 gap-4" aria-labelledby="kontak">
        <h2 id="kontak" class="font-bold sm:col-span-2">Kontak &amp; Alamat</h2>
        <div class="sm:col-span-2">
            <label for="address" class="block text-sm font-semibold mb-1">Alamat Pabrik <span class="text-red-700">*</span></label>
            <textarea id="address" name="address" rows="2" required maxlength="300" class="{{ $input }}">{{ old('address', $profile->address) }}</textarea>
            @error('address') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>
        @foreach ([
            'phone' => ['Telepon', true, 'tel'],
            'fax' => ['Fax', false, 'tel'],
            'whatsapp' => ['WhatsApp Sales', true, 'tel'],
            'email' => ['Email', true, 'email'],
        ] as $field => [$label, $required, $type])
            <div>
                <label for="{{ $field }}" class="block text-sm font-semibold mb-1">{{ $label }} @if ($required)<span class="text-red-700">*</span>@endif</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" @required($required) value="{{ old($field, $profile->{$field}) }}" class="{{ $input }}">
                @error($field) <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div>
            <label for="operating_hours" class="block text-sm font-semibold mb-1">Jam Operasional</label>
            <textarea id="operating_hours" name="operating_hours" rows="3" maxlength="500" class="{{ $input }}" placeholder="Satu jadwal per baris">{{ old('operating_hours', $profile->operating_hours) }}</textarea>
        </div>
        <div>
            <label for="maps_url" class="block text-sm font-semibold mb-1">Tautan Google Maps</label>
            <input id="maps_url" name="maps_url" type="url" maxlength="500" value="{{ old('maps_url', $profile->maps_url) }}" placeholder="https://maps.app.goo.gl/..." class="{{ $input }}">
            @error('maps_url') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="statistik">
        <h2 id="statistik" class="font-bold mb-1">Angka Statistik Beranda</h2>
        <p class="text-sm text-slate-600 mb-3">Kosongkan baris yang tidak dipakai.</p>
        <div class="space-y-2">
            @foreach ($stats as $i => $stat)
                <div class="grid grid-cols-[8rem_1fr] gap-2">
                    <input name="stats[{{ $i }}][value]" value="{{ $stat['value'] ?? '' }}" maxlength="30" aria-label="Nilai statistik {{ $i + 1 }}" placeholder="500+" class="{{ $input }}">
                    <input name="stats[{{ $i }}][label]" value="{{ $stat['label'] ?? '' }}" maxlength="60" aria-label="Label statistik {{ $i + 1 }}" placeholder="KLIEN PABRIK" class="{{ $input }}">
                </div>
            @endforeach
        </div>
    </section>

    <button type="submit" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold px-6 py-2.5 text-sm">Simpan Profil Perusahaan</button>
</form>
@endsection

@extends('layouts.backoffice')
@section('title', 'Dashboard Gudang')
@section('heading', 'Dashboard Gudang')

@section('content')
<p class="text-sm text-slate-700 mb-6">Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Berikut ringkasan pengajuan pengecekan bahan dari Marketing.</p>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <a href="{{ route('backoffice.gudang.verifications.index', ['status' => 'menunggu']) }}" class="rounded-xl bg-amber-100 border border-amber-300 p-5 hover:shadow-sm">
        <p class="text-sm font-semibold text-amber-900">Menunggu Verifikasi</p>
        <p class="text-4xl font-extrabold text-amber-950 mt-1">{{ $stats['pending'] }}</p>
    </a>
    <div class="rounded-xl bg-white border border-slate-200 p-5">
        <p class="text-sm font-semibold text-slate-700">Selesai Hari Ini</p>
        <p class="text-4xl font-extrabold mt-1">{{ $stats['completed_today'] }}</p>
    </div>
    <a href="{{ route('backoffice.gudang.verifications.index', ['status' => 'selesai']) }}" class="rounded-xl bg-white border border-slate-200 p-5 hover:border-red-700">
        <p class="text-sm font-semibold text-slate-700">Total Sudah Diverifikasi</p>
        <p class="text-4xl font-extrabold mt-1">{{ $stats['completed_total'] }}</p>
    </a>
</div>

<section class="mt-8 bg-white rounded-xl border border-slate-200" aria-labelledby="antrean">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 id="antrean" class="font-bold">Antrean Terlama</h2>
        <a href="{{ route('backoffice.gudang.verifications.index') }}" class="text-sm font-semibold text-red-700 hover:underline">Semua pengajuan &rarr;</a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($oldestPending as $vr)
            <li>
                <a href="{{ route('backoffice.gudang.verifications.show', $vr) }}" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 px-5 py-3 hover:bg-slate-50">
                    <span class="font-mono text-sm font-semibold sm:w-56">{{ $vr->inquiry->inquiry_number }}</span>
                    <span class="flex-1 text-sm">{{ $vr->inquiry->product_name }} · {{ $vr->inquiry->size }} · {{ number_format($vr->inquiry->quantity, 0, ',', '.') }} {{ $vr->inquiry->unit }}</span>
                    <span class="text-xs text-slate-600">diajukan {{ $vr->requested_at->diffForHumans() }} oleh {{ $vr->requester->name }}</span>
                </a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-slate-600">Tidak ada pengajuan yang menunggu. 🎉</li>
        @endforelse
    </ul>
</section>
@endsection

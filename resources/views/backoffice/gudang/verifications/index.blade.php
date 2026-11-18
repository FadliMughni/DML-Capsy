@extends('layouts.backoffice')
@section('title', 'Pengajuan Cek Bahan')
@section('heading', 'Daftar Pengajuan Pengecekan Bahan')

@section('content')
<nav class="flex flex-wrap gap-2 mb-5" aria-label="Filter status pengajuan">
    @foreach ($filters as $key => $label)
        <a href="{{ route('backoffice.gudang.verifications.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-full text-sm font-semibold border {{ $activeFilter === $key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-800 border-slate-300 hover:bg-slate-50' }}"
           @if ($activeFilter === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

<div class="space-y-4">
    @forelse ($requests as $vr)
        @php($inq = $vr->inquiry)
        <article class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-mono text-sm font-semibold text-slate-700">{{ $inq->inquiry_number }}</p>
                    <h2 class="text-lg font-bold">{{ $inq->product_name }}</h2>
                    <dl class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 text-sm">
                        <div><dt class="text-slate-600">Ukuran</dt><dd class="font-semibold">{{ $inq->size }}</dd></div>
                        <div><dt class="text-slate-600">Jumlah</dt><dd class="font-semibold">{{ number_format($inq->quantity, 0, ',', '.') }} {{ $inq->unit }}</dd></div>
                        <div><dt class="text-slate-600">Varian</dt><dd class="font-semibold">{{ $inq->variant ?: '—' }}</dd></div>
                        <div><dt class="text-slate-600">Diajukan</dt><dd class="font-semibold">{{ $vr->requested_at->translatedFormat('d M Y H:i') }}</dd></div>
                    </dl>
                    @if ($inq->notes || $vr->request_notes)
                        <div class="mt-3 text-sm space-y-1">
                            @if ($inq->notes)<p><span class="text-slate-600">Catatan customer:</span> {{ \Illuminate\Support\Str::limit($inq->notes, 160) }}</p>@endif
                            @if ($vr->request_notes)<p><span class="text-slate-600">Catatan Marketing ({{ $vr->requester->name }}):</span> {{ \Illuminate\Support\Str::limit($vr->request_notes, 160) }}</p>@endif
                        </div>
                    @endif
                </div>
                <div class="flex sm:flex-col items-center sm:items-end gap-3">
                    @if ($vr->result)
                        @include('backoffice.partials.status-badge', ['status' => $vr->result])
                    @else
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset bg-amber-100 text-amber-900 ring-amber-300">Menunggu</span>
                    @endif
                    <a href="{{ route('backoffice.gudang.verifications.show', $vr) }}" class="rounded-lg {{ $vr->isPending() ? 'bg-red-700 hover:bg-red-800 text-white' : 'border border-slate-300 text-slate-800 hover:bg-slate-50' }} font-semibold px-4 py-2 text-sm whitespace-nowrap">
                        {{ $vr->isPending() ? 'Verifikasi' : 'Lihat Hasil' }}<span class="sr-only"> {{ $inq->inquiry_number }}</span>
                    </a>
                </div>
            </div>
        </article>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 px-5 py-10 text-center text-sm text-slate-600">Tidak ada pengajuan pada filter ini.</div>
    @endforelse
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection

@extends('layouts.backoffice')
@section('title', 'Daftar Inquiry')
@section('heading', 'Manajemen Inquiry')

@section('content')
<form method="GET" action="{{ route('backoffice.marketing.inquiries.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid gap-3 sm:grid-cols-[1fr_16rem_auto] items-end" role="search">
    <div>
        <label for="q" class="block text-sm font-semibold mb-1">Cari</label>
        <input id="q" name="q" type="search" value="{{ $keyword }}" placeholder="No. inquiry, nama, perusahaan, telepon, produk…"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600">
    </div>
    <div>
        <label for="status" class="block text-sm font-semibold mb-1">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($activeStatus === $status)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-semibold px-4 py-2 text-sm">Terapkan</button>
        @if ($keyword !== '' || $activeStatus)
            <a href="{{ route('backoffice.marketing.inquiries.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Reset</a>
        @endif
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-200 text-sm text-slate-700">
        Menampilkan {{ $inquiries->firstItem() ?? 0 }}–{{ $inquiries->lastItem() ?? 0 }} dari {{ $inquiries->total() }} inquiry
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">No. Inquiry</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Customer</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Produk</th>
                    <th scope="col" class="px-4 py-3 font-semibold text-right">Jumlah</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($inquiries as $inquiry)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono font-semibold whitespace-nowrap">{{ $inquiry->inquiry_number }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $inquiry->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="block font-semibold">{{ $inquiry->name }}</span>
                            <span class="block text-xs text-slate-600">{{ $inquiry->company ?: '—' }} · {{ $inquiry->phone }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block">{{ $inquiry->product_name }}</span>
                            <span class="block text-xs text-slate-600">{{ $inquiry->size }}</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ number_format($inquiry->quantity, 0, ',', '.') }} {{ $inquiry->unit }}</td>
                        <td class="px-4 py-3">@include('backoffice.partials.status-badge', ['status' => $inquiry->status])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('backoffice.marketing.inquiries.show', $inquiry) }}" class="font-semibold text-red-700 hover:underline whitespace-nowrap">Detail<span class="sr-only"> {{ $inquiry->inquiry_number }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-600">Tidak ada inquiry yang cocok dengan pencarian/filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $inquiries->links() }}</div>
@endsection

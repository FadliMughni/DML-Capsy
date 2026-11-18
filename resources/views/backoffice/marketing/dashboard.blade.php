@extends('layouts.backoffice')
@section('title', 'Dashboard Marketing')
@section('heading', 'Dashboard Marketing')

@section('content')
<p class="text-sm text-slate-700 mb-6">Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Berikut ringkasan inquiry customer.</p>

<section aria-labelledby="ringkasan-status">
    <h2 id="ringkasan-status" class="sr-only">Ringkasan inquiry per status</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-7 gap-3 sm:gap-4">
        <a href="{{ route('backoffice.marketing.inquiries.index') }}" class="rounded-xl bg-slate-900 text-white p-4 hover:bg-slate-800">
            <p class="text-xs font-semibold text-slate-300">Total Inquiry</p>
            <p class="text-3xl font-extrabold mt-1" data-summary="total">{{ $total }}</p>
        </a>
        @foreach ($summary as $item)
            <a href="{{ route('backoffice.marketing.inquiries.index', ['status' => $item['status']->value]) }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-red-700 hover:shadow-sm">
                <p class="text-xs font-semibold text-slate-700 leading-snug min-h-8">{{ $item['status']->label() }}</p>
                <p class="text-3xl font-extrabold mt-1">{{ $item['total'] }}</p>
            </a>
        @endforeach
    </div>
</section>

<section class="mt-8 bg-white rounded-xl border border-slate-200" aria-labelledby="perlu-tindakan">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <h2 id="perlu-tindakan" class="font-bold">Perlu Tindakan Marketing</h2>
        <a href="{{ route('backoffice.marketing.inquiries.index') }}" class="text-sm font-semibold text-red-700 hover:underline">Semua inquiry &rarr;</a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($needsAction as $inquiry)
            <li>
                <a href="{{ route('backoffice.marketing.inquiries.show', $inquiry) }}" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 px-5 py-3 hover:bg-slate-50">
                    <span class="font-mono text-sm font-semibold text-slate-900 sm:w-56">{{ $inquiry->inquiry_number }}</span>
                    <span class="flex-1 text-sm text-slate-800">{{ $inquiry->name }}@if ($inquiry->company) · {{ $inquiry->company }}@endif — {{ $inquiry->product_name }}</span>
                    @include('backoffice.partials.status-badge', ['status' => $inquiry->status])
                </a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-slate-600">Tidak ada inquiry yang perlu ditindaklanjuti.</li>
        @endforelse
    </ul>
</section>
@endsection

@extends('layouts.backoffice')
@section('title', 'Inquiry '.$inquiry->inquiry_number)
@section('heading', 'Detail Inquiry')

@use('App\Enums\InquiryStatus')
@php($verification = $inquiry->latestVerification)

@section('content')
<nav class="text-sm mb-4" aria-label="Breadcrumb">
    <a href="{{ route('backoffice.marketing.inquiries.index') }}" class="text-red-700 font-semibold hover:underline">&larr; Kembali ke daftar inquiry</a>
</nav>

<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
    <div>
        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Nomor Inquiry</p>
        <p class="font-mono text-xl sm:text-2xl font-extrabold">{{ $inquiry->inquiry_number }}</p>
        <p class="text-sm text-slate-700">Diterima {{ $inquiry->created_at->translatedFormat('l, d F Y Â· H:i') }} WIB</p>
    </div>
    <div class="sm:ml-auto">@include('backoffice.partials.status-badge', ['status' => $inquiry->status])</div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- KOLOM KIRI --}}
    <div class="lg:col-span-2 space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="data-customer">
            <h2 id="data-customer" class="font-bold mb-4">Data Customer</h2>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-600">Nama / PIC</dt><dd class="font-semibold">{{ $inquiry->name }}</dd></div>
                <div><dt class="text-slate-600">Perusahaan</dt><dd class="font-semibold">{{ $inquiry->company ?: 'â€”' }}</dd></div>
                <div><dt class="text-slate-600">WhatsApp / Telepon</dt><dd class="font-semibold">{{ $inquiry->phone }}</dd></div>
                <div><dt class="text-slate-600">Email</dt><dd class="font-semibold break-all">{{ $inquiry->email ?: 'â€”' }}</dd></div>
            </dl>
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="data-produk">
            <h2 id="data-produk" class="font-bold mb-4">Kebutuhan Produk</h2>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div class="sm:col-span-2"><dt class="text-slate-600">Produk</dt>
                    <dd class="font-semibold">{{ $inquiry->product_name }}
                        @if ($inquiry->product)
                            <a href="{{ route('catalog.show', $inquiry->product->slug) }}" target="_blank" rel="noopener" class="ml-1 text-xs font-semibold text-red-700 hover:underline">(lihat di katalog)</a>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-slate-600">Ukuran / Dimensi</dt><dd class="font-semibold">{{ $inquiry->size }}</dd></div>
                <div><dt class="text-slate-600">Jumlah</dt><dd class="font-semibold">{{ number_format($inquiry->quantity, 0, ',', '.') }} {{ $inquiry->unit }}</dd></div>
                <div><dt class="text-slate-600">Warna / Varian</dt><dd class="font-semibold">{{ $inquiry->variant ?: 'â€”' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-600">Catatan Customer</dt><dd class="whitespace-pre-line">{{ $inquiry->notes ?: 'â€”' }}</dd></div>
            </dl>
        </section>

        {{-- HASIL VERIFIKASI GUDANG (PBI 17) --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="hasil-verifikasi">
            <h2 id="hasil-verifikasi" class="font-bold mb-4">Hasil Verifikasi Ketersediaan Bahan</h2>
            @if ($verification && $verification->result)
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    @include('backoffice.partials.status-badge', ['status' => $verification->result])
                    <span class="text-sm text-slate-700">oleh {{ $verification->verifier?->name }} Â· {{ $verification->verified_at->translatedFormat('d M Y H:i') }}</span>
                </div>
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-slate-600">Jumlah Tersedia</dt><dd class="font-semibold">{{ number_format($verification->available_quantity, 0, ',', '.') }} {{ $inquiry->unit }} <span class="text-slate-600 font-normal">dari {{ number_format($inquiry->quantity, 0, ',', '.') }} diminta</span></dd></div>
                    <div><dt class="text-slate-600">Estimasi Penyediaan</dt><dd class="font-semibold">{{ $verification->estimateLabel() ?? 'â€”' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-600">Catatan Gudang</dt><dd class="whitespace-pre-line">{{ $verification->verification_notes ?: 'â€”' }}</dd></div>
                </dl>
            @elseif ($verification)
                <p class="text-sm text-amber-900 bg-amber-50 border border-amber-300 rounded-lg px-4 py-3">
                    Menunggu hasil pengecekan Gudang. Diajukan {{ $verification->requested_at->translatedFormat('d M Y H:i') }}.
                </p>
            @else
                <p class="text-sm text-slate-700">Belum ada pengajuan verifikasi ke Gudang.</p>
            @endif
        </section>

        {{-- TINDAK LANJUT KOMUNIKASI (PBI 15) --}}
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="tindak-lanjut">
            <h2 id="tindak-lanjut" class="font-bold mb-4">Catatan Tindak Lanjut Komunikasi</h2>

            @if ($inquiry->status !== InquiryStatus::Ditindaklanjuti)
                <form method="POST" action="{{ route('backoffice.marketing.inquiries.follow-ups.store', $inquiry) }}" class="grid gap-3 sm:grid-cols-2 mb-6 p-4 rounded-lg bg-slate-50 border border-slate-200">
                    @csrf
                    <div>
                        <label for="contacted_at" class="block text-sm font-semibold mb-1">Waktu Komunikasi</label>
                        <input id="contacted_at" name="contacted_at" type="datetime-local" value="{{ old('contacted_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                    </div>
                    <div>
                        <label for="channel" class="block text-sm font-semibold mb-1">Media</label>
                        <select id="channel" name="channel" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                            @foreach ($channels as $channel)
                                <option value="{{ $channel }}" @selected(old('channel', 'WhatsApp') === $channel)>{{ $channel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-semibold mb-1">Catatan <span class="text-red-700">*</span></label>
                        <textarea id="notes" name="notes" rows="3" required maxlength="2000" placeholder="Contoh: Customer setuju menunggu estimasi 2 minggu, minta penawaran harga."
                                  class="w-full rounded-lg border px-3 py-2 text-sm bg-white {{ $errors->has('notes') ? 'border-red-600' : 'border-slate-300' }}">{{ old('notes') }}</textarea>
                        @error('notes') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2 text-sm">Simpan Catatan</button>
                    </div>
                </form>
            @endif

            <ol class="space-y-4">
                @forelse ($inquiry->followUps as $followUp)
                    <li class="border-l-4 border-slate-300 pl-4">
                        <p class="text-xs text-slate-600">
                            <time datetime="{{ $followUp->contacted_at->toIso8601String() }}">{{ $followUp->contacted_at->translatedFormat('d M Y H:i') }}</time>
                            Â· {{ $followUp->channel }} Â· dicatat oleh <strong class="text-slate-800">{{ $followUp->user->name }}</strong>
                        </p>
                        <p class="text-sm mt-1 whitespace-pre-line">{{ $followUp->notes }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-700">Belum ada catatan tindak lanjut.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- KOLOM KANAN: AKSI --}}
    <aside class="space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="aksi">
            <h2 id="aksi" class="font-bold mb-4">Aksi</h2>

            <a href="{{ $customerWhatsAppUrl }}" target="_blank" rel="noopener noreferrer"
               class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold px-4 py-2.5 text-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.1c-.24.68-1.42 1.3-1.95 1.34-.5.05-.97.23-3.27-.68-2.77-1.09-4.52-3.94-4.66-4.12-.13-.18-1.11-1.48-1.11-2.83s.71-2.01.96-2.28c.25-.28.55-.35.73-.35l.52.01c.17.01.39-.06.61.47.24.56.79 1.92.86 2.06.07.14.11.3.02.49-.09.18-.14.3-.27.46-.14.16-.29.36-.41.48-.14.14-.28.29-.12.56.16.28.71 1.17 1.53 1.9 1.05.94 1.94 1.23 2.21 1.37.28.14.44.12.6-.07.16-.19.69-.8.88-1.08.18-.28.37-.23.62-.14.25.09 1.6.76 1.87.9.28.14.46.2.53.32.07.12.07.68-.17 1.36z"/></svg>
                Hubungi via WhatsApp
            </a>
            <p class="text-xs text-slate-600 mt-2">Pesan otomatis berisi nomor inquiry{{ $verification?->result ? ' dan hasil verifikasi' : '' }}. Komunikasi dilakukan di luar sistem, lalu catat hasilnya di kolom tindak lanjut.</p>

            @if ($inquiry->status === InquiryStatus::Baru)
                <hr class="my-5 border-slate-200">
                <form method="POST" action="{{ route('backoffice.marketing.inquiries.verification.store', $inquiry) }}" class="space-y-3">
                    @csrf
                    <label for="request_notes" class="block text-sm font-semibold">Catatan untuk Gudang <span class="font-normal text-slate-600">(opsional)</span></label>
                    <textarea id="request_notes" name="request_notes" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Contoh: customer butuh cepat, prioritaskan warna hitam.">{{ old('request_notes') }}</textarea>
                    <button type="submit" class="w-full rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold px-4 py-2.5 text-sm">Ajukan Verifikasi ke Gudang</button>
                </form>
            @endif

            @if ($inquiry->status->hasVerificationResult())
                <hr class="my-5 border-slate-200">
                <form method="POST" action="{{ route('backoffice.marketing.inquiries.mark-followed-up', $inquiry) }}" onsubmit="return confirm('Tandai inquiry ini sebagai Ditindaklanjuti?');">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2.5 text-sm">Tandai "Ditindaklanjuti"</button>
                    <p class="text-xs text-slate-600 mt-2">Lakukan setelah menghubungi customer dan mencatat tindak lanjutnya.</p>
                </form>
            @endif
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="riwayat-pengajuan">
            <h2 id="riwayat-pengajuan" class="font-bold mb-3">Riwayat Pengajuan ke Gudang</h2>
            <ul class="space-y-3 text-sm">
                @forelse ($inquiry->verificationRequests as $vr)
                    <li class="rounded-lg border border-slate-200 p-3">
                        <p>Diajukan oleh <strong>{{ $vr->requester->name }}</strong></p>
                        <p class="text-xs text-slate-600">{{ $vr->requested_at->translatedFormat('d M Y H:i') }}</p>
                        @if ($vr->request_notes)
                            <p class="mt-1 text-slate-800 whitespace-pre-line">â€œ{{ $vr->request_notes }}â€</p>
                        @endif
                        <p class="mt-2">
                            @if ($vr->result)
                                @include('backoffice.partials.status-badge', ['status' => $vr->result])
                            @else
                                <span class="text-xs font-semibold text-amber-900">Menunggu Gudang</span>
                            @endif
                        </p>
                    </li>
                @empty
                    <li class="text-slate-700">Belum ada pengajuan.</li>
                @endforelse
            </ul>
        </section>
    </aside>
</div>
@endsection

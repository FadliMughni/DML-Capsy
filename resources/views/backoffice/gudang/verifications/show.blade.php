@extends('layouts.backoffice')
@section('title', 'Verifikasi '.$vr->inquiry->inquiry_number)
@section('heading', 'Verifikasi Ketersediaan Bahan')

@php($inq = $vr->inquiry)

@section('content')
<nav class="text-sm mb-4" aria-label="Breadcrumb">
    <a href="{{ route('backoffice.gudang.verifications.index') }}" class="text-red-700 font-semibold hover:underline">&larr; Kembali ke daftar pengajuan</a>
</nav>

<div class="grid gap-6 lg:grid-cols-5">
    <section class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 h-fit" aria-labelledby="detail-permintaan">
        <p class="font-mono text-sm font-semibold text-slate-700">{{ $inq->inquiry_number }}</p>
        <h2 id="detail-permintaan" class="text-lg font-bold mb-4">{{ $inq->product_name }}</h2>
        <dl class="space-y-3 text-sm">
            @if ($inq->product?->material)
                <div><dt class="text-slate-600">Material (katalog)</dt><dd class="font-semibold">{{ $inq->product->material }}</dd></div>
            @endif
            <div><dt class="text-slate-600">Ukuran / Dimensi</dt><dd class="font-semibold">{{ $inq->size }}</dd></div>
            <div><dt class="text-slate-600">Jumlah Diminta</dt><dd class="font-semibold text-lg">{{ number_format($inq->quantity, 0, ',', '.') }} {{ $inq->unit }}</dd></div>
            <div><dt class="text-slate-600">Warna / Varian</dt><dd class="font-semibold">{{ $inq->variant ?: '—' }}</dd></div>
            <div><dt class="text-slate-600">Catatan Customer</dt><dd class="whitespace-pre-line">{{ $inq->notes ?: '—' }}</dd></div>
            <div class="pt-3 border-t border-slate-200"><dt class="text-slate-600">Diajukan oleh</dt><dd class="font-semibold">{{ $vr->requester->name }} · {{ $vr->requested_at->translatedFormat('d M Y H:i') }}</dd></div>
            <div><dt class="text-slate-600">Catatan Marketing</dt><dd class="whitespace-pre-line">{{ $vr->request_notes ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="lg:col-span-3 bg-white rounded-xl border border-slate-200 p-5" aria-labelledby="hasil">
        <h2 id="hasil" class="text-lg font-bold mb-4">Hasil Verifikasi</h2>

        @if ($vr->isPending())
            <form method="POST" action="{{ route('backoffice.gudang.verifications.update', $vr) }}" class="space-y-5" id="form-verifikasi">
                @csrf
                @method('PUT')

                <fieldset>
                    <legend class="text-sm font-semibold mb-2">Ketersediaan bahan <span class="text-red-700">*</span></legend>
                    <div class="grid sm:grid-cols-3 gap-2">
                        @foreach ($results as $result)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold cursor-pointer has-[:checked]:border-red-700 has-[:checked]:bg-red-50">
                                <input type="radio" name="result" value="{{ $result->value }}" data-requires-estimate="{{ $result->requiresEstimate() ? '1' : '0' }}" @checked(old('result') === $result->value) required class="accent-red-700">
                                {{ $result->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('result') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="available_quantity" class="block text-sm font-semibold mb-1">Jumlah tersedia ({{ $inq->unit }}) <span class="text-red-700">*</span></label>
                    <input id="available_quantity" name="available_quantity" type="number" min="0" step="1" required value="{{ old('available_quantity') }}"
                           class="w-full sm:w-60 rounded-lg border px-3 py-2 text-sm {{ $errors->has('available_quantity') ? 'border-red-600' : 'border-slate-300' }}">
                    <p class="text-xs text-slate-600 mt-1">Diminta: {{ number_format($inq->quantity, 0, ',', '.') }} {{ $inq->unit }}</p>
                    @error('available_quantity') <p class="text-sm text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>

                <fieldset class="rounded-lg border border-slate-200 p-4">
                    <legend class="text-sm font-semibold px-1">Estimasi penyediaan <span id="estimate-required" class="text-red-700 font-normal">(wajib jika Tersedia Sebagian / Tidak Tersedia — isi tanggal atau durasi)</span></legend>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label for="estimated_date" class="block text-sm mb-1">Tanggal</label>
                            <input id="estimated_date" name="estimated_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('estimated_date') }}"
                                   class="w-full rounded-lg border px-3 py-2 text-sm {{ $errors->has('estimated_date') ? 'border-red-600' : 'border-slate-300' }}">
                        </div>
                        <div>
                            <label for="estimated_duration" class="block text-sm mb-1">Durasi</label>
                            <input id="estimated_duration" name="estimated_duration" type="text" maxlength="100" placeholder="mis. 2 minggu" value="{{ old('estimated_duration') }}"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    @error('estimated_date') <p class="text-sm text-red-700 mt-2">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="verification_notes" class="block text-sm font-semibold mb-1">Catatan</label>
                    <textarea id="verification_notes" name="verification_notes" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Contoh: kompon NBR hitam tersedia 300 kg, sisanya menunggu pengiriman supplier.">{{ old('verification_notes') }}</textarea>
                </div>

                <button type="submit" class="rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold px-5 py-2.5 text-sm">Simpan Hasil Verifikasi</button>
            </form>
        @else
            <div class="flex flex-wrap items-center gap-3 mb-4">
                @include('backoffice.partials.status-badge', ['status' => $vr->result])
                <span class="text-sm text-slate-700">oleh {{ $vr->verifier?->name }} · {{ $vr->verified_at->translatedFormat('d M Y H:i') }}</span>
            </div>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-600">Jumlah Tersedia</dt><dd class="font-semibold">{{ number_format($vr->available_quantity, 0, ',', '.') }} {{ $inq->unit }}</dd></div>
                <div><dt class="text-slate-600">Estimasi Penyediaan</dt><dd class="font-semibold">{{ $vr->estimateLabel() ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-600">Catatan</dt><dd class="whitespace-pre-line">{{ $vr->verification_notes ?: '—' }}</dd></div>
            </dl>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('form-verifikasi');
    if (!form) return;
    var hint = document.getElementById('estimate-required');
    function update() {
        var checked = form.querySelector('input[name="result"]:checked');
        var required = checked && checked.dataset.requiresEstimate === '1';
        hint.classList.toggle('font-bold', !!required);
    }
    form.querySelectorAll('input[name="result"]').forEach(function (r) { r.addEventListener('change', update); });
    update();
})();
</script>
@endpush

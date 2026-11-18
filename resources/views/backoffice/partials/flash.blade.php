@if (session('success'))
    <div class="mb-5 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900" role="status">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-900" role="alert">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="list-disc pl-5 mt-1 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

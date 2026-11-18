<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Backoffice · Duta Rubber</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex items-center justify-center p-4">
<main class="w-full max-w-md">
    <div class="text-center mb-6">
        <span class="inline-flex w-12 h-12 rounded-xl bg-red-700 items-center justify-center font-black text-white text-lg" aria-hidden="true">DR</span>
        <h1 class="mt-3 text-2xl font-extrabold">Login Backoffice</h1>
        <p class="text-sm text-slate-600 mt-1">PT Duta Mitra Luhur · Khusus tim Marketing &amp; Gudang</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('backoffice.login.store') }}" class="space-y-5" novalidate>
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold mb-1.5">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="w-full rounded-lg border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-600 {{ $errors->has('email') ? 'border-red-600' : 'border-slate-300' }}"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold mb-1.5">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-600 {{ $errors->has('password') ? 'border-red-600' : 'border-slate-300' }}">
                @error('password') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-400"> Ingat saya
            </label>
            <button type="submit" class="w-full rounded-lg bg-red-700 hover:bg-red-800 text-white font-bold py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-700">Masuk</button>
        </form>
    </div>
    <p class="text-center text-sm mt-5"><a href="{{ route('home') }}" class="text-slate-700 hover:text-red-700 underline">&larr; Kembali ke website</a></p>
</main>
</body>
</html>

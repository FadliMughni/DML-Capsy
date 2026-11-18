@php
    $authUser = auth()->user();
    $icons = [
        'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'chat' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        'box' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'clipboard' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'bell' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    ];
    $menus = match (true) {
        $authUser->isMarketing() => [
            ['route' => 'backoffice.marketing.dashboard', 'active' => 'backoffice.marketing.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['route' => 'backoffice.marketing.inquiries.index', 'active' => 'backoffice.marketing.inquiries.*', 'label' => 'Inquiry', 'icon' => 'chat'],
            ['route' => 'backoffice.marketing.products.index', 'active' => 'backoffice.marketing.products.*', 'label' => 'Produk', 'icon' => 'box'],
            ['route' => 'backoffice.marketing.categories.index', 'active' => 'backoffice.marketing.categories.*', 'label' => 'Kategori', 'icon' => 'tag'],
            ['route' => 'backoffice.marketing.company-profile.edit', 'active' => 'backoffice.marketing.company-profile.*', 'label' => 'Profil Perusahaan', 'icon' => 'building'],
        ],
        $authUser->isGudang() => [
            ['route' => 'backoffice.gudang.dashboard', 'active' => 'backoffice.gudang.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
            ['route' => 'backoffice.gudang.verifications.index', 'active' => 'backoffice.gudang.verifications.*', 'label' => 'Pengajuan Cek Bahan', 'icon' => 'clipboard'],
        ],
        default => [],
    };
    $menus[] = ['route' => 'backoffice.notifications.index', 'active' => 'backoffice.notifications.*', 'label' => 'Notifikasi', 'icon' => 'bell'];
    $menus = array_values(array_filter($menus, fn ($m) => \Illuminate\Support\Facades\Route::has($m['route'])));

    $unreadCount = $authUser->unreadNotifications()->count();
    $latestUnread = $unreadCount ? $authUser->unreadNotifications()->take(5)->get() : collect();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Backoffice') · Backoffice Duta Rubber</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
<a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-3 focus:py-2 focus:rounded">Lewati ke konten</a>

<div class="flex min-h-screen">
    {{-- SIDEBAR --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden" data-sidebar-close></div>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen transition-transform bg-slate-900 text-slate-100 flex flex-col shrink-0" aria-label="Menu backoffice">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800">
            <span class="w-9 h-9 rounded-lg bg-red-700 flex items-center justify-center font-black text-white" aria-hidden="true">DR</span>
            <div class="leading-tight">
                <p class="font-extrabold tracking-wide text-white">DUTA RUBBER</p>
                <p class="text-xs text-slate-300">Backoffice {{ $authUser->role->label() }}</p>
            </div>
            <button type="button" class="ml-auto lg:hidden p-1 text-slate-300 hover:text-white" data-sidebar-close aria-label="Tutup menu">&times;</button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($menus as $menu)
                @php $isActive = request()->routeIs($menu['active']); @endphp
                <a href="{{ route($menu['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors {{ $isActive ? 'bg-red-700 text-white' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}"
                   @if ($isActive) aria-current="page" @endif>
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$menu['icon']] }}"/></svg>
                    <span>{{ $menu['label'] }}</span>
                    @if ($menu['icon'] === 'bell' && $unreadCount)
                        <span class="ml-auto text-xs bg-amber-300 text-slate-900 rounded-full px-2 py-0.5 font-bold">{{ $unreadCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="px-3 py-4 border-t border-slate-800">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-sm text-slate-300 hover:text-white rounded-lg hover:bg-slate-800">&larr; Lihat Website Publik</a>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 sticky top-0 z-20">
            <button type="button" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-700 hover:bg-slate-100" data-sidebar-open aria-controls="sidebar" aria-label="Buka menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-base sm:text-lg font-bold truncate">@yield('heading', 'Backoffice')</h1>

            <div class="ml-auto flex items-center gap-2 sm:gap-4">
                {{-- Lonceng notifikasi --}}
                <details class="relative" data-dropdown>
                    <summary class="list-none cursor-pointer relative block p-2 rounded-full hover:bg-slate-100 text-slate-700" aria-label="Notifikasi, {{ $unreadCount }} belum dibaca">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['bell'] }}"/></svg>
                        @if ($unreadCount)
                            <span class="absolute -top-0.5 -right-0.5 min-w-5 h-5 px-1 rounded-full bg-red-700 text-white text-[11px] font-bold flex items-center justify-center" data-notif-badge>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </summary>
                    <div class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden z-30">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <p class="font-bold text-sm">Notifikasi</p>
                            <span class="text-xs text-slate-600">{{ $unreadCount }} belum dibaca</span>
                        </div>
                        <ul class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            @forelse ($latestUnread as $notif)
                                <li>
                                    <form method="POST" action="{{ route('backoffice.notifications.read', $notif->id) }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-3 hover:bg-slate-50">
                                            <span class="block text-sm font-semibold text-slate-900">{{ $notif->data['title'] ?? 'Notifikasi' }}</span>
                                            <span class="block text-xs text-slate-700 mt-0.5">{{ $notif->data['message'] ?? '' }}</span>
                                            <span class="block text-[11px] text-slate-500 mt-1">{{ $notif->created_at->diffForHumans() }}</span>
                                        </button>
                                    </form>
                                </li>
                            @empty
                                <li class="px-4 py-6 text-sm text-slate-600 text-center">Tidak ada notifikasi baru.</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('backoffice.notifications.index') }}" class="block text-center text-sm font-semibold text-red-700 hover:bg-slate-50 py-2.5 border-t border-slate-200">Lihat semua notifikasi</a>
                    </div>
                </details>

                <div class="hidden sm:block text-right leading-tight">
                    <p class="text-sm font-semibold">{{ $authUser->name }}</p>
                    <p class="text-xs text-slate-600">{{ $authUser->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('backoffice.logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 text-slate-800 hover:bg-slate-100">Keluar</button>
                </form>
            </div>
        </header>

        <main id="konten" class="flex-1 p-4 sm:p-6 lg:p-8">
            @include('backoffice.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script>
(function () {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    function toggle(open) {
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop.classList.toggle('hidden', !open);
    }
    document.querySelectorAll('[data-sidebar-open]').forEach(function (b) { b.addEventListener('click', function () { toggle(true); }); });
    document.querySelectorAll('[data-sidebar-close]').forEach(function (b) { b.addEventListener('click', function () { toggle(false); }); });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details[data-dropdown][open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
})();
</script>
@stack('scripts')
</body>
</html>

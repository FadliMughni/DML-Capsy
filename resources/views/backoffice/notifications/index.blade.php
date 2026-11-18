@extends('layouts.backoffice')
@section('title', 'Notifikasi')
@section('heading', 'Notifikasi')

@section('content')
<div class="bg-white rounded-xl border border-slate-200">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold">Semua Notifikasi</h2>
        @if ($notifications->contains(fn ($n) => $n->read_at === null))
            <form method="POST" action="{{ route('backoffice.notifications.read-all') }}">
                @csrf
                <button class="text-sm font-semibold text-red-700 hover:underline">Tandai semua dibaca</button>
            </form>
        @endif
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($notifications as $notif)
            <li class="{{ $notif->read_at ? '' : 'bg-amber-50' }}">
                <form method="POST" action="{{ route('backoffice.notifications.read', $notif->id) }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-5 py-4 hover:bg-slate-50 flex gap-3">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $notif->read_at ? 'bg-slate-300' : 'bg-red-700' }}" aria-hidden="true"></span>
                        <span>
                            <span class="block text-sm font-semibold">{{ $notif->data['title'] ?? 'Notifikasi' }} @unless ($notif->read_at)<span class="sr-only">(belum dibaca)</span>@endunless</span>
                            <span class="block text-sm text-slate-700">{{ $notif->data['message'] ?? '' }}</span>
                            <span class="block text-xs text-slate-500 mt-1">{{ $notif->created_at->translatedFormat('d M Y H:i') }} · {{ $notif->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            </li>
        @empty
            <li class="px-5 py-10 text-center text-sm text-slate-600">Belum ada notifikasi.</li>
        @endforelse
    </ul>
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection

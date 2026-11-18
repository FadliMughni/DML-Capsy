{{-- @include('backoffice.partials.status-badge', ['status' => InquiryStatus|VerificationResult]) --}}
<span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $status->badgeClasses() }}">{{ $status->label() }}</span>

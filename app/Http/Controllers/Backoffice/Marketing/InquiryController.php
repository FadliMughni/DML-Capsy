<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Services\FollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Inquiry::class);

        $status = InquiryStatus::tryFrom((string) $request->query('status'));
        $keyword = $request->string('q')->trim()->limit(100, '')->toString();

        $inquiries = Inquiry::query()
            ->select(['id', 'inquiry_number', 'name', 'company', 'phone', 'product_name', 'size', 'quantity', 'unit', 'status', 'created_at'])
            ->search($keyword)
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.marketing.inquiries.index', [
            'inquiries' => $inquiries,
            'statuses' => InquiryStatus::cases(),
            'activeStatus' => $status,
            'keyword' => $keyword,
        ]);
    }

    public function show(Inquiry $inquiry): View
    {
        $this->authorize('view', $inquiry);

        $inquiry->load([
            'product:id,name,slug',
            'latestVerification.verifier:id,name',
            'verificationRequests.requester:id,name',
            'verificationRequests.verifier:id,name',
            'followUps.user:id,name',
        ]);

        return view('backoffice.marketing.inquiries.show', [
            'inquiry' => $inquiry,
            'channels' => InquiryFollowUp::CHANNELS,
            'customerWhatsAppUrl' => $inquiry->buildCustomerWhatsAppUrl(auth()->user()->name),
        ]);
    }

    /** PBI 17 - Tandai inquiry "Ditindaklanjuti". */
    public function markFollowedUp(Inquiry $inquiry, FollowUpService $service): RedirectResponse
    {
        $this->authorize('markFollowedUp', $inquiry);

        $service->markAsFollowedUp($inquiry, auth()->user());

        return redirect()->route('backoffice.marketing.inquiries.show', $inquiry)
            ->with('success', "Inquiry {$inquiry->inquiry_number} ditandai sebagai \"Ditindaklanjuti\".");
    }
}

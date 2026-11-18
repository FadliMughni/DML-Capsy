<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StoreFollowUpRequest;
use App\Models\Inquiry;
use App\Services\FollowUpService;
use Illuminate\Http\RedirectResponse;

class InquiryFollowUpController extends Controller
{
    public function store(StoreFollowUpRequest $request, Inquiry $inquiry, FollowUpService $service): RedirectResponse
    {
        $service->record($inquiry, $request->user(), $request->validated());

        return redirect()->route('backoffice.marketing.inquiries.show', $inquiry)
            ->with('success', 'Catatan tindak lanjut berhasil disimpan.');
    }
}

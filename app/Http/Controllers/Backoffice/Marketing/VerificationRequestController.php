<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StoreVerificationRequestRequest;
use App\Models\Inquiry;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;

class VerificationRequestController extends Controller
{
    public function store(StoreVerificationRequestRequest $request, Inquiry $inquiry, VerificationService $service): RedirectResponse
    {
        $service->submit($inquiry, $request->user(), $request->validated('request_notes'));

        return redirect()->route('backoffice.marketing.inquiries.show', $inquiry)
            ->with('success', 'Pengajuan verifikasi berhasil dikirim ke Gudang. Status inquiry: "Menunggu Verifikasi Gudang".');
    }
}

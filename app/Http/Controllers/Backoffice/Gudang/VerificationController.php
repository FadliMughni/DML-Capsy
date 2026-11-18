<?php

namespace App\Http\Controllers\Backoffice\Gudang;

use App\Enums\VerificationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\RecordVerificationResultRequest;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    private const FILTERS = ['menunggu' => 'Menunggu Verifikasi', 'selesai' => 'Sudah Diverifikasi', 'semua' => 'Semua'];

    /** PBI 16 - Daftar pengajuan pengecekan bahan. */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VerificationRequest::class);

        $filter = (string) $request->query('status', 'menunggu');
        $filter = array_key_exists($filter, self::FILTERS) ? $filter : 'menunggu';

        $requests = VerificationRequest::query()
            ->with([
                'inquiry:id,inquiry_number,product_name,size,quantity,unit,variant,notes,status',
                'requester:id,name',
            ])
            ->when($filter === 'menunggu', fn ($q) => $q->pending()->oldest('requested_at'))
            ->when($filter === 'selesai', fn ($q) => $q->completed()->latest('verified_at'))
            ->when($filter === 'semua', fn ($q) => $q->latest('requested_at'))
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.gudang.verifications.index', [
            'requests' => $requests,
            'filters' => self::FILTERS,
            'activeFilter' => $filter,
        ]);
    }

    public function show(VerificationRequest $verificationRequest): View
    {
        $this->authorize('view', $verificationRequest);

        $verificationRequest->load(['inquiry.product:id,name,material', 'requester:id,name', 'verifier:id,name']);

        return view('backoffice.gudang.verifications.show', [
            'vr' => $verificationRequest,
            'results' => VerificationResult::cases(),
        ]);
    }

    /** PBI 17 - Simpan hasil verifikasi. */
    public function update(RecordVerificationResultRequest $request, VerificationRequest $verificationRequest, VerificationService $service): RedirectResponse
    {
        $service->record($verificationRequest, $request->user(), $request->validated());

        return redirect()->route('backoffice.gudang.verifications.show', $verificationRequest)
            ->with('success', 'Hasil verifikasi tersimpan dan Marketing telah diberi notifikasi.');
    }
}

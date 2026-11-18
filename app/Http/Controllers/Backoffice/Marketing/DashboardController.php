<?php

namespace App\Http\Controllers\Backoffice\Marketing;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // Satu query agregat untuk semua status.
        $counts = Inquiry::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = collect(InquiryStatus::cases())->map(fn (InquiryStatus $status) => [
            'status' => $status,
            'total' => (int) ($counts[$status->value] ?? 0),
        ]);

        $needsAction = Inquiry::query()
            ->whereIn('status', [
                InquiryStatus::Baru->value,
                InquiryStatus::Tersedia->value,
                InquiryStatus::TersediaSebagian->value,
                InquiryStatus::TidakTersedia->value,
            ])
            ->select(['id', 'inquiry_number', 'name', 'company', 'product_name', 'status', 'created_at'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('backoffice.marketing.dashboard', [
            'summary' => $summary,
            'total' => $counts->sum(),
            'needsAction' => $needsAction,
        ]);
    }
}

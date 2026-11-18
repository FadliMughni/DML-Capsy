<?php

namespace App\Http\Controllers\Backoffice\Gudang;

use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'pending' => VerificationRequest::pending()->count(),
            'completed_today' => VerificationRequest::completed()->whereDate('verified_at', today())->count(),
            'completed_total' => VerificationRequest::completed()->count(),
        ];

        $oldestPending = VerificationRequest::pending()
            ->with(['inquiry:id,inquiry_number,product_name,size,quantity,unit', 'requester:id,name'])
            ->oldest('requested_at')
            ->limit(5)
            ->get();

        return view('backoffice.gudang.dashboard', compact('stats', 'oldestPending'));
    }
}

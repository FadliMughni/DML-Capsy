<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    /** Arahkan pengguna backoffice ke dashboard sesuai perannya. */
    public function __invoke(Request $request): RedirectResponse
    {
        $route = $request->user()->role->dashboardRoute();

        abort_if($route === null, 403, 'Anda tidak memiliki hak akses ke backoffice.');

        return redirect()->route($route);
    }
}

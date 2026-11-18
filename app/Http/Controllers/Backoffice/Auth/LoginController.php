<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('backoffice.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (! $request->user()->canAccessBackoffice()) {
            $this->logout($request);

            // Redirect eksplisit: session sudah di-invalidate sehingga URL "back" tidak tersedia.
            return redirect()->route('backoffice.login')
                ->withErrors(['email' => 'Akun ini tidak memiliki akses ke backoffice.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->role->dashboardRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout($request);

        return redirect()->route('backoffice.login')->with('status', 'Anda telah keluar dari backoffice.');
    }

    private function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian: ->middleware('role:marketing') atau 'role:marketing,gudang'.
 * Pengguna yang sudah login tetapi perannya tidak sesuai mendapat 403.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('backoffice.login'));
        }

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        abort_unless($user->hasRole(...$allowed), 403, 'Anda tidak memiliki hak akses ke halaman ini.');

        return $next($request);
    }
}

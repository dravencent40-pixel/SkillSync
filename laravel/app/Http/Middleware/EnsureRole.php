<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Menggantikan require_role() dari versi native — memastikan user login
     * DAN perannya termasuk daftar yang diizinkan.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        if (!in_array(Auth::user()->role, $roles, true)) {
            session()->flash('error', 'Kamu tidak memiliki akses ke halaman ini.');
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

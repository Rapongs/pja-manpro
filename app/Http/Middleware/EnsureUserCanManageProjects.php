<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageProjects
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if((bool) $request->session()->get('guest_mode', false), 403, 'Akun guest hanya memiliki akses baca.');

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(InstallationService::class)->isInstalled()) {
            return $next($request);
        }

        return redirect()->to('/admin/login');
    }
}

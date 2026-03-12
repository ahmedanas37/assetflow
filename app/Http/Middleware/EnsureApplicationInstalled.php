<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('setup') || $request->is('setup/*') || $request->is('up')) {
            return $next($request);
        }

        if (app(InstallationService::class)->isInstalled()) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}

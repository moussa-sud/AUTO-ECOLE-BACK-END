<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->tenant_id) {
            return response()->json(['message' => 'Tenant not found.'], 403);
        }

        return $next($request);
    }
}

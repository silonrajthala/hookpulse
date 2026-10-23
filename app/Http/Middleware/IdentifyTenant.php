<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check session or header for tenant selection
        $tenantId = session('selected_tenant_id') ?? $request->header('X-Tenant-Id');

        // 2. Fall back to authenticated user's tenant if applicable
        if (! $tenantId && auth()->check()) {
            $tenantId = auth()->user()->tenant_id;
        }

        // 3. Fall back to the first available tenant for local testing/bootstrapping
        if (! $tenantId) {
            $firstTenant = Tenant::first();
            $tenantId = $firstTenant?->id;
        }

        // 4. Bind tenant ID to our singleton TenantContext service
        if ($tenantId) {
            app(TenantContext::class)->setTenantId($tenantId);
        }

        return $next($request);
    }
}

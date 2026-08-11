<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenant
{
    public function __construct(
        protected TenantContext $context,
        protected PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-Id');

        abort_unless($tenantId, 400, 'The X-Tenant-Id header is required.');

        $tenant = Tenant::find($tenantId);

        abort_unless($tenant, 404, 'Tenant not found.');

        $isMember = $tenant->users()
            ->where('users.id', Auth::id())
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless(Auth::user()->is_super_admin || $isMember, 403, 'You are not a member of this tenant.');

        $this->context->set($tenant);
        $this->permissions->setPermissionsTeamId($tenant->id);

        return $next($request);
    }
}

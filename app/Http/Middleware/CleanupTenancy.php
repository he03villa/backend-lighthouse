<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWT;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class CleanupTenancy
{
    public function __construct(
        protected TenantContext $context,
        protected PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        Auth::forgetGuards();
        app(JWT::class)->unsetToken();

        try {
            return $next($request);
        } finally {
            $this->permissions->forgetCachedPermissions();
            $this->permissions->setPermissionsTeamId(null);
            $this->context->clear();
        }
    }
}

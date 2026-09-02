<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);

        $this->app->booted(function () {
            $broadcaster = Broadcast::driver();
            if (method_exists($broadcaster, 'resolveAuthenticatedUserUsing')) {
                $broadcaster->resolveAuthenticatedUserUsing(function ($request) {
                    try {
                        return JWTAuth::parseToken()->authenticate();
                    } catch (\Exception $e) {
                        return null;
                    }
                });
            }
        });

        require base_path('routes/channels.php');

        Gate::before(fn ($user, $ability) => $user->is_super_admin ? true : null);
    }
}

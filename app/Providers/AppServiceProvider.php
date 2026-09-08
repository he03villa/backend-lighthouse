<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->input('email').$request->ip()
            );
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

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

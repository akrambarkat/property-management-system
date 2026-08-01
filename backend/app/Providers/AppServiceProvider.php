<?php

namespace App\Providers;

use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (!$user instanceof \App\Models\User) {
                return null;
            }
            return app(PermissionService::class)->gateResolver($user, $ability);
        });
    }
}

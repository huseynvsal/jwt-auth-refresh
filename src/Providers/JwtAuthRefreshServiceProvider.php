<?php

namespace Huseynvsal\JwtAuthRefresh\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Huseynvsal\JwtAuthRefresh\Guards\JwtGuard;
use Huseynvsal\JwtAuthRefresh\Services\JwtAuthService;

class JwtAuthRefreshServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/jwt-auth.php' => config_path('jwt-auth.php'),
        ], 'jwt-auth-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'jwt-auth-migrations');
    }

    public function register(): void
    {
        // Register the JWT authentication guard
        Auth::extend('jwt', function ($app, $name, $config) {
            return new JwtGuard(
                Auth::createUserProvider($config['provider']),
                $app->make(JwtAuthService::class),
                $app->make(Request::class)
            );
        });

        // Bind the JWT Auth Service as a singleton
        $this->app->singleton(JwtAuthService::class, function ($app) {
            return new JwtAuthService();
        });
    }
}

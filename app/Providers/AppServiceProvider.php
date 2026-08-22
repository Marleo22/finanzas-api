<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarRateLimiters();
    }

    /**
     * Laravel 11+ elimino RouteServiceProvider, donde antes venia definido el
     * limitador 'api'. Sin esta definicion, usar throttle:api en las rutas
     * lanza MissingRateLimiterException y responde 500.
     *
     * Se limita por usuario autenticado, con la IP como respaldo para las
     * peticiones sin token.
     */
    private function configurarRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }
}

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(10)
                ->by($email.'|'.$request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Demasiados intentos de inicio de sesion. Intenta nuevamente en un minuto.',
                ], 429));
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Demasiados registros desde esta direccion. Intenta nuevamente en un minuto.',
                ], 429));
        });
    }
}

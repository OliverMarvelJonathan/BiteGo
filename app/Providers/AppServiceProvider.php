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
        // Per-account limit stops password guessing; the IP limit still caps credential stuffing
        // across many accounts from one host.
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower(trim($email)) : '';

            return [
                Limit::perMinute(5)
                    ->by('login:'.$email.'|'.$request->ip())
                    ->response(fn () => $this->throttledResponse($request)),
                Limit::perMinute(30)
                    ->by('login-ip:'.$request->ip())
                    ->response(fn () => $this->throttledResponse($request)),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(30)
                ->by('register-ip:'.$request->ip())
                ->response(fn () => redirect()->route('auth.register')
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' => 'Terlalu banyak pendaftaran dari jaringan ini. Coba lagi nanti.']));
        });
    }

    private function throttledResponse(Request $request)
    {
        return redirect()->route('auth.login')
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Tunggu sebentar lalu coba lagi.']);
    }
}

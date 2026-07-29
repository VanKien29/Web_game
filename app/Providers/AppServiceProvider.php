<?php

namespace App\Providers;

use App\Auth\PlainTextUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('plaintext', function ($app, array $config) {
            return new PlainTextUserProvider($config['model']);
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)
                ->by(mb_strtolower((string) $request->input('username')).'|'.$request->ip())
                ->response(function () {
                    return back()
                        ->withErrors(['username' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ít phút.'])
                        ->onlyInput('username');
                });
        });

        RateLimiter::for('client-login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('username')));

            return [
                Limit::perMinute(5)
                    ->by('client-login-user|'.$identity.'|'.$request->ip())
                    ->response(fn (Request $request, array $headers) => response()->json([
                        'status' => 'error',
                        'message' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng chờ một phút.',
                    ], 429, $headers)),
                Limit::perMinute(20)
                    ->by('client-login-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('client-register', function (Request $request) {
            return [
                Limit::perMinute(3)
                    ->by('client-register-minute|'.$request->ip())
                    ->response(fn (Request $request, array $headers) => response()->json([
                        'status' => 'error',
                        'message' => 'Bạn đang tạo tài khoản quá nhanh. Vui lòng chờ một phút.',
                    ], 429, $headers)),
                Limit::perHour(10)
                    ->by('client-register-hour|'.$request->ip()),
            ];
        });
    }
}

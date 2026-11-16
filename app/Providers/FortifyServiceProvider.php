<?php

namespace App\Providers;

use App\Actions\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, \App\Http\Responses\LoginResponse::class);
    }

    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $r) => view('auth.reset-password', ['request' => $r]));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::authenticateUsing(function (Request $r) {
            $user = User::where('email', Str::lower($r->string('email')->toString()))->first();
            if ($user && Hash::check($r->string('password')->toString(), $user->password) && $user->is_active) {
                return $user;
            }

            return null;
        });
        RateLimiter::for('login', fn (Request $r) => [Limit::perMinute(5)->by(Str::lower((string) $r->input('email')).'|'.$r->ip()), Limit::perMinute(30)->by($r->ip())]);
    }
}

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('billing-login', fn (Request $request) => Limit::perMinute(config('billing.rate_limits.login'))->by($request->ip().'|'.mb_strtolower((string) $request->input('username'))));
        RateLimiter::for('billing-api', fn (Request $request) => Limit::perMinute(config('billing.rate_limits.api'))->by('client:'.($request->attributes->get('billing_client')?->id ?? $request->ip())));
    }
}

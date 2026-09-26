<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\BillingAccessToken;

Schedule::command('arca:renew-tickets')->everySixHours()->withoutOverlapping()->onOneServer()->appendOutputTo(storage_path('logs/arca.log'));
Schedule::call(fn () => BillingAccessToken::where('expires_at', '<', now()->subDay())->delete())->daily()->name('billing:prune-tokens')->onOneServer();

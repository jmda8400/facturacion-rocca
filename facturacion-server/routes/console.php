<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('arca:renew-tickets')->everySixHours()->withoutOverlapping()->onOneServer()->appendOutputTo(storage_path('logs/arca.log'));

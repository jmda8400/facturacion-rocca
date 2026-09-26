<?php

namespace App\Services;

use App\Models\BillingEvent;

class BillingEventLogger
{
    public function record(string $event, string $message, array $context = [], string $level = 'info'): void
    {
        // Context must contain identifiers only: never credentials, request bodies or headers.
        BillingEvent::create(compact('event', 'message', 'context', 'level'));
    }
}

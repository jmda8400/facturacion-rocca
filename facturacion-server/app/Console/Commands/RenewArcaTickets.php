<?php

namespace App\Console\Commands;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use App\Services\BillingEventLogger;
use Illuminate\Console\Command;

class RenewArcaTickets extends Command
{
    protected $signature = 'arca:renew-tickets {--profile=}';

    protected $description = 'Renueva los tickets de acceso de todos los perfiles ARCA activos';

    public function handle(ArcaService $service, BillingEventLogger $events): int
    {
        $events->record('scheduler.started', 'Se activó la ejecución programada de renovación de tickets ARCA.');
        $profiles = ArcaProfile::where('active', true)->when($this->option('profile'), fn ($q, $slug) => $q->where('slug', $slug))->get();
        foreach ($profiles as $profile) {
            $service->renew($profile);
            $this->info("TA renovado: {$profile->slug}");
            $events->record('arca.ticket_renewed', 'Ticket de acceso ARCA renovado.', ['profile' => $profile->slug]);
        }

return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use Illuminate\Console\Command;
use Throwable;

class CheckArcaVatConditions extends Command
{
    protected $signature = 'arca:check-vat-conditions {--profile= : Slug del perfil ARCA} {--class=B : Clase de comprobante A o B}';

    protected $description = 'Consulta condiciones IVA receptor permitidas en WSFE sin emitir comprobantes';

    public function handle(ArcaService $arca): int
    {
        $slug = (string) $this->option('profile');
        $class = strtoupper((string) $this->option('class'));
        if ($slug === '' || ! in_array($class, ['A', 'B'], true)) {
            $this->error('Debe indicar --profile y una --class A o B.');
            return self::INVALID;
        }
        $profile = ArcaProfile::where('slug', $slug)->where('active', true)->first();
        if (! $profile) {
            $this->error("No existe un perfil ARCA activo con slug {$slug}.");
            return self::FAILURE;
        }
        try {
            $conditions = $arca->vatConditions($profile, $class);
        } catch (Throwable) {
            // Do not echo SoapFault details: request context may contain Token/Sign.
            $this->error('WSFE no respondió correctamente al consultar las condiciones IVA.');
            return self::FAILURE;
        }
        $this->table(['ID', 'Descripción', 'Clase permitida'], array_map(fn ($row) => [$row['id'], $row['description'], $row['class']], $conditions));
        return self::SUCCESS;
    }
}

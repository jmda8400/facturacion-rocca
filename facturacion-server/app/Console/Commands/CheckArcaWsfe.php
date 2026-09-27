<?php

namespace App\Console\Commands;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use Illuminate\Console\Command;
use Throwable;

class CheckArcaWsfe extends Command
{
    protected $signature = 'arca:check-wsfe {--profile= : Slug del perfil ARCA}';

    protected $description = 'Comprueba WSFE consultando el último comprobante B autorizado, sin emitir';

    public function handle(ArcaService $arca): int
    {
        $slug = (string) $this->option('profile');
        if ($slug === '') {
            $this->error('Debe indicar --profile.');

            return self::INVALID;
        }

        $profile = ArcaProfile::where('slug', $slug)->where('active', true)->first();
        if (! $profile) {
            $this->error("No existe un perfil ARCA activo con slug {$slug}.");

            return self::FAILURE;
        }

        try {
            $last = $arca->lastAuthorized($profile, 6);
        } catch (Throwable $e) {
            $this->error('WSFE no respondió correctamente: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Campo', 'Valor'], [
            ['Perfil', $profile->slug],
            ['Punto de venta', $profile->sales_point],
            ['Tipo de comprobante', '6 (Factura B)'],
            ['Último autorizado', $last],
        ]);

        return self::SUCCESS;
    }
}

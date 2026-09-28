<?php

namespace App\Services;

use App\Models\ArcaProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;
use SoapClient;
use Symfony\Component\Process\Process;

class ArcaService
{
    public function ticket(ArcaProfile $profile): SimpleXMLElement
    {
        $path = Storage::path($profile->ta_path);
        if (is_file($path)) {
            $ticket = @simplexml_load_file($path);
            $expiration = $ticket ? strtotime((string) $ticket->header->expirationTime) : 0;
            if ($expiration - time() > config('billing.arca.renew_before_minutes') * 60) {
                return $ticket;
            }
        }

        return $this->renew($profile);
    }

    public function renew(ArcaProfile $profile): SimpleXMLElement
    {
        $certificate = Storage::path($profile->certificate_path);
        $key = Storage::path($profile->private_key_path);
        foreach ([$certificate, $key] as $file) {
            if (! is_readable($file)) {
                throw new RuntimeException("Credencial ARCA no accesible: $file");
            }
        }

        $now = CarbonImmutable::now('America/Argentina/Buenos_Aires');
        $tra = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0"><header/><service>wsfe</service></loginTicketRequest>');
        $tra->header->uniqueId = $now->timestamp;
        $tra->header->generationTime = $now->subMinute()->format('Y-m-d\TH:i:sP');
        $tra->header->expirationTime = $now->addHour()->format('Y-m-d\TH:i:sP');
        $xml = tempnam(sys_get_temp_dir(), 'tra_');
        $cms = tempnam(sys_get_temp_dir(), 'cms_');
        file_put_contents($xml, $tra->asXML());
        (new Process(['openssl', 'smime', '-sign', '-signer', $certificate, '-inkey', $key, '-outform', 'DER', '-nodetach', '-binary', '-in', $xml, '-out', $cms]))->mustRun();
        $client = new SoapClient(null, ['location' => config('billing.arca.wsaa_url'), 'uri' => 'http://wsaa.view.sua.dvadac.desein.afip.gov.ar/ws/services/LoginCms', 'exceptions' => true]);
        $raw = $client->loginCms(base64_encode(file_get_contents($cms)));
        @unlink($xml);
        @unlink($cms);
        Storage::makeDirectory(dirname($profile->ta_path));
        Storage::put($profile->ta_path, $raw);
        $ticket = simplexml_load_string($raw);
        if (! $ticket) {
            throw new RuntimeException('ARCA devolvió un Ticket de Acceso inválido.');
        }

        return $ticket;
    }

    /** Solicita exactamente el número durablemente reservado por la aplicación. */
    public function authorize(ArcaProfile $profile, array $data, int $number): array
    {
        $type = $this->voucherType($data['invoice_type']);
        $amounts = $this->amounts($data);
        $date = CarbonImmutable::now('America/Argentina/Buenos_Aires')->format('Ymd');
        $detail = [
            'Concepto' => (int) $data['concept'],
            'DocTipo' => (int) $data['customer']['document_type'],
            'DocNro' => (int) $data['customer']['document_number'],
            'CondicionIVAReceptorId' => (int) $data['customer']['vat_condition_id'],
            'CbteDesde' => $number,
            'CbteHasta' => $number,
            'CbteFch' => $date,
            'ImpTotal' => $amounts['total'],
            'ImpTotConc' => 0,
            'ImpNeto' => $amounts['net'],
            'ImpOpEx' => 0,
            'ImpTrib' => 0,
            'ImpIVA' => $amounts['vat'],
            'MonId' => 'PES',
            'MonCotiz' => 1,
            'Iva' => ['AlicIva' => [['Id' => 5, 'BaseImp' => $amounts['net'], 'Importe' => $amounts['vat']]]],
        ];
        if (in_array((int) $data['concept'], [2, 3], true)) {
            $detail += [
                'FchServDesde' => str_replace('-', '', $data['service_from']),
                'FchServHasta' => str_replace('-', '', $data['service_to']),
                'FchVtoPago' => str_replace('-', '', $data['payment_due_date']),
            ];
        }
        $response = $this->wsfeClient()->FECAESolicitar([
            'Auth' => $this->authentication($profile),
            'FeCAEReq' => ['FeCabReq' => ['CantReg' => 1, 'PtoVta' => $profile->sales_point, 'CbteTipo' => $type], 'FeDetReq' => ['FECAEDetRequest' => [$detail]]],
        ]);
        $result = $response->FECAESolicitarResult ?? null;
        $approved = $result?->FeDetResp?->FECAEDetResponse ?? null;
        if (is_array($approved)) {
            $approved = $approved[0] ?? null;
        }
        if (! $approved || ($approved->Resultado ?? null) !== 'A') {
            throw new RuntimeException('ARCA rechazó el comprobante: '.json_encode($result?->Errors ?? $approved?->Observaciones, JSON_UNESCAPED_UNICODE));
        }

        return $this->authorization($type, $number, $date, $amounts, (string) $approved->CAE, (string) $approved->CAEFchVto, (int) $data['customer']['document_type'], (int) $data['customer']['document_number'], (int) $profile->sales_point) + $this->fiscalContract($data);
    }

    /** Consulta un comprobante sin emitir nada. Devuelve null cuando aún no existe. */
    public function consult(ArcaProfile $profile, int $voucherType, int $number): ?array
    {
        $response = $this->wsfeClient()->FECompConsultar([
            'Auth' => $this->authentication($profile),
            'FeCompConsReq' => ['PtoVta' => $profile->sales_point, 'CbteTipo' => $voucherType, 'CbteNro' => $number],
        ]);
        $result = $response->FECompConsultarResult?->ResultGet ?? null;
        if (! $result || ! isset($result->CbteDesde)) {
            return null;
        }

        $authorization = [
            'type' => (int) ($result->CbteTipo ?? $voucherType),
            'sales_point' => (int) ($result->PtoVta ?? $profile->sales_point),
            'number' => (int) $result->CbteDesde,
            'date' => CarbonImmutable::createFromFormat('Ymd', (string) $result->CbteFch)->format('Y-m-d'),
            'total' => round((float) $result->ImpTotal, 2),
            'net' => round((float) $result->ImpNeto, 2),
            'vat' => round((float) $result->ImpIVA, 2),
            'currency' => (string) ($result->MonId ?? ''),
            'currency_rate' => round((float) ($result->MonCotiz ?? 0), 6),
            'document_type' => (int) $result->DocTipo,
            'document_number' => (int) $result->DocNro,
            'cae' => (string) $result->CodAutorizacion,
            'cae_expires_at' => CarbonImmutable::createFromFormat('Ymd', (string) $result->FchVto)->format('Y-m-d'),
        ];

        foreach (['Concepto' => 'concept', 'CondicionIVAReceptorId' => 'vat_condition_id'] as $soap => $key) {
            if (isset($result->{$soap})) $authorization[$key] = (int) $result->{$soap};
        }
        foreach (['FchServDesde' => 'service_from', 'FchServHasta' => 'service_to', 'FchVtoPago' => 'payment_due_date'] as $soap => $key) {
            if (isset($result->{$soap}) && (string) $result->{$soap} !== '') $authorization[$key] = CarbonImmutable::createFromFormat('Ymd', (string) $result->{$soap})->format('Y-m-d');
        }

        return $authorization;
    }

    /** Consulta parámetros fiscales; nunca solicita una autorización. */
    public function vatConditions(ArcaProfile $profile, string $invoiceClass): array
    {
        $response = $this->wsfeClient()->FEParamGetCondicionIvaReceptor(['Auth' => $this->authentication($profile), 'ClaseCmp' => strtoupper($invoiceClass)]);
        $items = $response->FEParamGetCondicionIvaReceptorResult?->ResultGet?->CondicionIvaReceptor ?? [];
        $items = is_array($items) ? $items : [$items];

        return array_map(fn ($item) => ['id' => (int) ($item->Id ?? 0), 'description' => (string) ($item->Desc ?? ''), 'class' => (string) ($item->Cmp_Clase ?? strtoupper($invoiceClass))], $items);
    }

    public function lastAuthorized(ArcaProfile $profile, int $voucherType): int
    {
        $response = $this->wsfeClient()->FECompUltimoAutorizado(['Auth' => $this->authentication($profile), 'PtoVta' => $profile->sales_point, 'CbteTipo' => $voucherType]);
        $result = $response->FECompUltimoAutorizadoResult ?? null;
        if (! is_object($result) || ! isset($result->CbteNro)) {
            throw new RuntimeException('ARCA WSFE devolvió una respuesta inválida.');
        }

        return (int) $result->CbteNro;
    }

    public function voucherType(string $invoiceType): int
    {
        return $invoiceType === 'A' ? 1 : 6;
    }

    public function expectedAmounts(array $data): array
    {
        return $this->amounts($data);
    }

    private function amounts(array $data): array
    {
        // Compatibilidad fiscal actual: única alícuota soportada, IVA 21% incluido.
        // Neto = total / 1.21; IVA = total - neto.
        $total = round((float) $data['total'], 2);
        $net = round($total / 1.21, 2);

        return ['total' => $total, 'net' => $net, 'vat' => round($total - $net, 2)];
    }

    private function fiscalContract(array $data): array
    {
        $contract = ['concept' => (int) $data['concept'], 'vat_condition_id' => (int) $data['customer']['vat_condition_id']];
        if (in_array($contract['concept'], [2, 3], true)) {
            $contract += ['service_from' => $data['service_from'], 'service_to' => $data['service_to'], 'payment_due_date' => $data['payment_due_date']];
        }

        return $contract;
    }

    private function authorization(int $type, int $number, string $date, array $amounts, string $cae, string $expires, int $documentType, int $documentNumber, int $salesPoint): array
    {
        return $amounts + ['type' => $type, 'sales_point' => $salesPoint, 'number' => $number, 'date' => CarbonImmutable::createFromFormat('Ymd', $date)->format('Y-m-d'), 'currency' => 'PES', 'currency_rate' => 1.0, 'document_type' => $documentType, 'document_number' => $documentNumber, 'cae' => $cae, 'cae_expires_at' => CarbonImmutable::createFromFormat('Ymd', $expires)->format('Y-m-d')];
    }

    private function authentication(ArcaProfile $profile): array
    {
        $ticket = $this->ticket($profile);

        return ['Token' => (string) $ticket->credentials->token, 'Sign' => (string) $ticket->credentials->sign, 'Cuit' => (int) $profile->cuit];
    }

    protected function wsfeClient(): SoapClient
    {
        $context = stream_context_create(['ssl' => ['ciphers' => config('billing.arca.wsfe_ssl_ciphers'), 'verify_peer' => true, 'verify_peer_name' => true]]);

        return new SoapClient(config('billing.arca.wsfe_wsdl'), ['exceptions' => true, 'trace' => true, 'cache_wsdl' => WSDL_CACHE_NONE, 'connection_timeout' => 30, 'stream_context' => $context]);
    }
}

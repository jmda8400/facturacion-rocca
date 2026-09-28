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
            'Concepto' => 1,
            'DocTipo' => (int) $data['customer']['document_type'],
            'DocNro' => (int) $data['customer']['document_number'],
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

        return $this->authorization($type, $number, $date, $amounts, (string) $approved->CAE, (string) $approved->CAEFchVto, (int) $data['customer']['document_type'], (int) $data['customer']['document_number'], (int) $profile->sales_point);
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

        return [
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
        $total = round((float) $data['total'], 2);
        $net = round($total / 1.21, 2);

        return ['total' => $total, 'net' => $net, 'vat' => round($total - $net, 2)];
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

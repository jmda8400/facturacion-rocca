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
    public function ticket(ArcaProfile $p): SimpleXMLElement
    {
        $path = Storage::path($p->ta_path);
        if (is_file($path)) {
            $ta = @simplexml_load_file($path);
            $expiration = $ta ? strtotime((string) $ta->header->expirationTime) : 0;
            if ($expiration - time() > config('billing.arca.renew_before_minutes') * 60) {
                return $ta;
            }
        }

return $this->renew($p);
    }

    public function renew(ArcaProfile $p): SimpleXMLElement
    {
        $cert = Storage::path($p->certificate_path);
        $key = Storage::path($p->private_key_path);
        foreach ([$cert, $key] as $file) {
            if (! is_readable($file)) {
                throw new RuntimeException("Credencial ARCA no accesible: $file");
            }
        }$now = CarbonImmutable::now('America/Argentina/Buenos_Aires');
        $tra = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0"><header/><service>wsfe</service></loginTicketRequest>');
        $tra->header->uniqueId = $now->timestamp;
        $tra->header->generationTime = $now->subMinute()->format('Y-m-d\TH:i:sP');
        $tra->header->expirationTime = $now->addHour()->format('Y-m-d\TH:i:sP');
        $xml = tempnam(sys_get_temp_dir(), 'tra_');
        $cms = tempnam(sys_get_temp_dir(), 'cms_');
        file_put_contents($xml, $tra->asXML());
        $process = new Process(['openssl', 'smime', '-sign', '-signer', $cert, '-inkey', $key, '-outform', 'DER', '-nodetach', '-binary', '-in', $xml, '-out', $cms]);
        $process->mustRun();
        $client = new SoapClient(null, ['location' => config('billing.arca.wsaa_url'), 'uri' => 'http://wsaa.view.sua.dvadac.desein.afip.gov.ar/ws/services/LoginCms', 'exceptions' => true]);
        $raw = $client->loginCms(base64_encode(file_get_contents($cms)));
        @unlink($xml);
        @unlink($cms);
        Storage::makeDirectory(dirname($p->ta_path));
        Storage::put($p->ta_path, $raw);
        $ta = simplexml_load_string($raw);
        if (! $ta) {
            throw new RuntimeException('ARCA devolvió un Ticket de Acceso inválido.');
        }

return $ta;
    }

    public function authorize(ArcaProfile $p, array $data): array
    {
        $ta = $this->ticket($p);
        $auth = ['Token' => (string) $ta->credentials->token, 'Sign' => (string) $ta->credentials->sign, 'Cuit' => (int) $p->cuit];
        $type = $data['invoice_type'] === 'A' ? 1 : 6;
        $client = new SoapClient(config('billing.arca.wsfe_wsdl'), ['exceptions' => true, 'trace' => true, 'cache_wsdl' => WSDL_CACHE_NONE, 'connection_timeout' => 30]);
        $last = $client->FECompUltimoAutorizado(['Auth' => $auth, 'PtoVta' => $p->sales_point, 'CbteTipo' => $type]);
        $number = (int) ($last->FECompUltimoAutorizadoResult->CbteNro ?? 0) + 1;
        $total = round((float) $data['total'], 2);
        $net = round($total / 1.21, 2);
        $vat = round($total - $net, 2);
        $detail = ['Concepto' => 1, 'DocTipo' => $data['customer']['document_type'], 'DocNro' => (int) $data['customer']['document_number'], 'CbteDesde' => $number, 'CbteHasta' => $number, 'CbteFch' => date('Ymd'), 'ImpTotal' => $total, 'ImpTotConc' => 0, 'ImpNeto' => $net, 'ImpOpEx' => 0, 'ImpTrib' => 0, 'ImpIVA' => $vat, 'MonId' => 'PES', 'MonCotiz' => 1, 'Iva' => ['AlicIva' => [['Id' => 5, 'BaseImp' => $net, 'Importe' => $vat]]]];
        $response = $client->FECAESolicitar(['Auth' => $auth, 'FeCAEReq' => ['FeCabReq' => ['CantReg' => 1, 'PtoVta' => $p->sales_point, 'CbteTipo' => $type], 'FeDetReq' => ['FECAEDetRequest' => [$detail]]]]);
        $result = $response->FECAESolicitarResult ?? null;
        $approved = $result?->FeDetResp?->FECAEDetResponse ?? null;
        if (is_array($approved)) {
            $approved = $approved[0] ?? null;
        }if (! $approved || ($approved->Resultado ?? null) !== 'A') {
            throw new RuntimeException('ARCA rechazó el comprobante: '.json_encode($result?->Errors ?? $approved?->Observaciones, JSON_UNESCAPED_UNICODE));
        }

return ['type' => $type, 'number' => $number, 'date' => date('Y-m-d'), 'total' => $total, 'net' => $net, 'vat' => $vat, 'cae' => (string) $approved->CAE, 'cae_expires_at' => CarbonImmutable::createFromFormat('Ymd', (string) $approved->CAEFchVto)->format('Y-m-d')];
    }
}

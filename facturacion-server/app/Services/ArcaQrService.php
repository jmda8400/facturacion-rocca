<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use JsonException;

class ArcaQrService
{
    private const QR_BASE_URL = 'https://www.afip.gob.ar/fe/qr/?p=';

    /**
     * Build the AFIP QR payload according to the public specification.
     */
    public function buildPayload(
        string $fechaComprobante,
        string $cuitEmisor,
        int $puntoDeVenta,
        int $tipoComprobante,
        int $numeroComprobante,
        float $importeTotal,
        string $moneda,
        float $cotizacion,
        string $tipoCodigoAutorizacion,
        string $codigoAutorizacion,
        ?int $tipoDocumentoReceptor = null,
        int|string|null $numeroDocumentoReceptor = null,
    ): array {
        $payload = [
            'ver' => 1,
            'fecha' => $fechaComprobante,
            'cuit' => (int) $cuitEmisor,
            'ptoVta' => $puntoDeVenta,
            'tipoCmp' => $tipoComprobante,
            'nroCmp' => $numeroComprobante,
            'importe' => $this->roundAmount($importeTotal, 2),
            'moneda' => $moneda,
            'ctz' => $this->roundAmount($cotizacion, 6),
        ];

        if ($tipoDocumentoReceptor !== null && $numeroDocumentoReceptor !== null && $numeroDocumentoReceptor !== '') {
            $payload['tipoDocRec'] = $tipoDocumentoReceptor;
            $payload['nroDocRec'] = (int) $numeroDocumentoReceptor;
        }

        $payload['tipoCodAut'] = $tipoCodigoAutorizacion;
        $payload['codAut'] = (int) $codigoAutorizacion;

        return $payload;
    }

    /**
     * Encode the payload into the Base64 string required by AFIP.
     */
    public function encodePayload(array $payload): string
    {
        try {
            $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new Exception('No se pudo codificar el payload del QR de AFIP', 0, $exception);
        }

        return base64_encode($json);
    }

    /**
     * Generate the QR URL text (https://www.afip.gob.ar/fe/qr/?p=...) that must be encoded as image.
     */
    public function buildQrUrl(array $payload): string
    {
        return self::QR_BASE_URL.rawurlencode($this->encodePayload($payload));
    }

    /**
     * Render the QR code image as a PNG data URI ready to be embedded in the PDF.
     */
    public function generateDataUri(array $payload): string
    {
        $qrText = $this->buildQrUrl($payload);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 4,
            'imageTransparent' => false,
        ]);

        $qr = new QRCode($options);
        $pngData = $qr->render($qrText);

        if (! is_string($pngData) || $pngData === '') {
            throw new Exception('No se pudo generar la imagen PNG del QR de AFIP');
        }

        if (str_starts_with($pngData, 'data:image/')) {
            return $pngData;
        }

        return 'data:image/png;base64,'.base64_encode($pngData);
    }

    private function roundAmount(float $amount, int $decimals): float
    {
        return (float) number_format($amount, $decimals, '.', '');
    }
}

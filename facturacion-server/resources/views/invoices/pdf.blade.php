<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .document { width: 100%; border: 1px solid #555; padding: 8px; }
        .title { margin: 1px 0 2px; text-align: center; font-size: 20px; font-weight: bold; }
        .copy { margin-bottom: 8px; text-align: center; font-size: 11px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .box { border: 1px solid #777; }
        .header td { height: 104px; padding: 8px; }
        .issuer { width: 43%; }
        .voucher { width: 17%; border-right: 1px solid #777; border-left: 1px solid #777; text-align: center; }
        .voucher-name { margin-top: 10px; font-size: 18px; font-weight: bold; }
        .voucher-code { margin-top: 8px; font-size: 10px; }
        .numbering { width: 40%; }
        .section { margin-top: 7px; }
        .label { font-weight: bold; }
        .line { margin: 0 0 5px; }
        .fiscal td, .period td { padding: 6px 8px; }
        .fiscal td { width: 33.33%; }
        .period td { width: 33.33%; }
        .section-title { padding: 4px 7px; border: 1px solid #777; border-bottom: 0; background: #eee; font-size: 10px; font-weight: bold; }
        .customer td { padding: 5px 7px; }
        .customer .wide { width: 58%; }
        .items th { padding: 6px 5px; border: 1px solid #777; background: #eee; text-align: center; }
        .items td { padding: 6px 5px; border-right: 1px solid #999; border-left: 1px solid #999; }
        .items tbody tr:last-child td { border-bottom: 1px solid #777; }
        .description { width: 55%; }
        .quantity { width: 12%; text-align: right; }
        .amount { width: 16.5%; text-align: right; white-space: nowrap; }
        .totals { margin-top: 7px; }
        .totals td { padding: 4px 6px; }
        .vat { width: 57%; padding-top: 22px !important; font-weight: bold; }
        .totals-box { width: 43%; border: 1px solid #777; }
        .totals-box table td:first-child { font-weight: bold; }
        .total-row { border-top: 1px solid #999; font-size: 12px; }
        .right { text-align: right; }
        .footer { margin-top: 9px; border-top: 1px solid #777; }
        .footer td { padding: 8px; vertical-align: middle; }
        .qr-cell { width: 42%; text-align: center; }
        .qr { width: 105px; height: 105px; }
        .qr-caption { margin-top: 3px; font-size: 8px; }
        .cae { width: 58%; border-left: 1px solid #999; font-size: 12px; line-height: 2; }
    </style>
</head>
<body>
@php
    $money = fn ($value) => '$ '.number_format((float) $value, 2, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : null;
    $customer = $payload['customer'];
@endphp
<div class="document">
    <div class="title">FACTURA ELECTRÓNICA</div>
    <div class="copy">ORIGINAL</div>

    <table class="header box">
        <tr>
            <td class="issuer">
                @if($invoice->profile->business_name)<div class="line"><span class="label">Razón Social:</span> {{ $invoice->profile->business_name }}</div>@endif
                @if($invoice->profile->address)<div class="line"><span class="label">Domicilio:</span> {{ $invoice->profile->address }}</div>@endif
                @if($invoice->profile->vat_condition)<div class="line"><span class="label">Condición frente al IVA:</span> {{ $invoice->profile->vat_condition }}</div>@endif
            </td>
            <td class="voucher">
                <div class="voucher-name">FACTURA {{ strtoupper($invoice->invoice_type) }}</div>
                <div class="voucher-code">Cod. {{ str_pad((string) $arca['type'], 3, '0', STR_PAD_LEFT) }}</div>
            </td>
            <td class="numbering">
                <div class="line"><span class="label">Punto de Venta:</span> {{ str_pad((string) $invoice->profile->sales_point, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="line"><span class="label">Comp. N°:</span> {{ str_pad((string) $arca['number'], 8, '0', STR_PAD_LEFT) }}</div>
                <div class="line"><span class="label">Fecha de Emisión:</span> {{ $date($arca['date']) }}</div>
            </td>
        </tr>
    </table>

    <table class="fiscal box section">
        <tr>
            <td><span class="label">CUIT:</span> {{ $invoice->profile->cuit }}</td>
            <td>@if($invoice->profile->gross_income)<span class="label">Ingresos Brutos:</span> {{ $invoice->profile->gross_income }}@endif</td>
            <td>@if($invoice->profile->activity_started_at)<span class="label">Fecha de Inicio de Actividades:</span> {{ $date($invoice->profile->activity_started_at) }}@endif</td>
        </tr>
    </table>

    @if(in_array((int) ($payload['concept'] ?? 1), [2, 3], true))
        <table class="period box section">
            <tr>
                <td><span class="label">Período Facturado Desde:</span> {{ $date($payload['service_from'] ?? null) }}</td>
                <td><span class="label">Hasta:</span> {{ $date($payload['service_to'] ?? null) }}</td>
                <td><span class="label">Fecha de Vto. para el pago:</span> {{ $date($payload['payment_due_date'] ?? null) }}</td>
            </tr>
        </table>
    @endif

    <div class="section section-title">DATOS DEL CLIENTE</div>
    <table class="customer box">
        <tr>
            <td><span class="label">Documento:</span> @if(!empty($customer['document_type'])){{ $customer['document_type'] }} @endif{{ $customer['document_number'] ?? '' }}</td>
            <td class="wide"><span class="label">Apellido y Nombre / Razón Social:</span> {{ $customer['name'] ?? '' }}</td>
        </tr>
        <tr>
            <td><span class="label">Condición frente al IVA:</span> {{ $customer['vat_condition'] ?? '' }}</td>
            <td class="wide"><span class="label">Domicilio:</span> {{ $customer['address'] ?? '' }}</td>
        </tr>
    </table>

    <table class="items section">
        <thead><tr><th class="description">Producto / Servicio</th><th class="quantity">Cantidad</th><th class="amount">Precio Unit.</th><th class="amount">Subtotal</th></tr></thead>
        <tbody>
        @foreach($payload['items'] as $item)
            <tr><td>{{ $item['description'] }}</td><td class="quantity">{{ number_format((float) $item['quantity'], 2, ',', '.') }}</td><td class="amount">{{ $money($item['unit_price']) }}</td><td class="amount">{{ $money($item['quantity'] * $item['unit_price']) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="vat">IVA Contenido: {{ $money($arca['vat']) }}</td>
            <td class="totals-box">
                <table>
                    <tr><td>Subtotal:</td><td class="right">{{ $money($arca['total']) }}</td></tr>
                    <tr><td>Importe Otros Tributos:</td><td class="right">$ 0,00</td></tr>
                    <tr class="total-row"><td>Importe Total:</td><td class="right"><strong>{{ $money($arca['total']) }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td class="qr-cell"><img class="qr" src="{{ $qr }}" alt="Código QR"><div class="qr-caption">Código QR exigido por ARCA RG 4892</div></td>
            <td class="cae"><div><span class="label">CAE N°:</span> {{ $arca['cae'] }}</div><div><span class="label">Fecha de Vto. de CAE:</span> {{ $date($arca['cae_expires_at']) }}</div></td>
        </tr>
    </table>
</div>
</body>
</html>

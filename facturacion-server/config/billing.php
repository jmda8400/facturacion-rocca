<?php
return [
 'token_ttl_hours'=>(int)env('BILLING_TOKEN_TTL_HOURS',12),
 'rate_limits'=>['login'=>(int)env('BILLING_LOGIN_RATE_LIMIT',6),'api'=>(int)env('BILLING_API_RATE_LIMIT',60)],
 'settings_username'=>env('SETTINGS_USERNAME','facturacion'),'settings_password'=>env('SETTINGS_PASSWORD'),
 'bcc'=>array_values(array_filter(array_map('trim',explode(',',(string)env('BILLING_BCC_EMAILS',''))))),
 'arca'=>['environment'=>env('ARCA_ENVIRONMENT','production'),'wsaa_url'=>env('ARCA_WSAA_URL','https://wsaa.afip.gov.ar/ws/services/LoginCms'),'wsfe_wsdl'=>env('ARCA_WSFE_WSDL','https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL'),'renew_before_minutes'=>(int)env('ARCA_TA_RENEW_BEFORE_MINUTES',10)],
];

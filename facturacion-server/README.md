# Servidor de facturación Refugio Rocca

Aplicación Laravel **independiente** que recibe solicitudes por REST, autoriza comprobantes en ARCA/WSFE, genera el PDF y lo envía por SMTP. La carpeta está pensada para copiarse tal cual a un repositorio y servidor separados; no modifica el flujo vigente del motor de reservas.

## Contrato HTTP

Todos los endpoints `/api/v1/*` requieren `Authorization: Bearer <BILLING_API_KEY>`. Al crear una factura también se exige un `Idempotency-Key` único: repetir exactamente la operación devuelve la factura ya creada y evita emitir dos CAE.

```bash
curl -X POST https://facturacion.refugioagostinorocca.com/api/v1/invoices \
  -H 'Authorization: Bearer SU_CLAVE' \
  -H 'Idempotency-Key: pago-nave-1234' \
  -H 'Content-Type: application/json' \
  -d '{
    "external_reference":"GROUP001",
    "profile":"rocca",
    "invoice_type":"B",
    "customer":{"name":"Ana Pérez","address":"Bariloche","vat_condition":"Consumidor Final","document_type":99,"document_number":0},
    "items":[{"description":"Alojamiento Refugio Rocca","quantity":2,"unit_price":25000}],
    "total":50000,
    "email_to":"ana@example.com"
  }'
```

La creación responde `202` con `id`, `status_url` y eventualmente `download_url`. Consultar `GET /api/v1/invoices/{id}` hasta `completed` o `failed`; descargar con `GET /api/v1/invoices/{id}/pdf`. Todos requieren el Bearer token. El consumidor debe enviar un perfil ARCA explícito, lo que permite certificados, claves, CUIT, punto de venta y TA independientes en paralelo.

## Instalación

1. Copiar `.env.example` a `.env`, generar `APP_KEY` y crear claves API aleatorias de al menos 32 caracteres. Cambiar especialmente la contraseña inicial de `/settings` antes de exponer el sitio.
2. Configurar base de datos y SMTP en `.env`.
3. Ejecutar `composer install --no-dev --optimize-autoloader`, `php artisan key:generate`, `php artisan migrate --force` y dar permisos de escritura al usuario PHP sobre `storage` y `bootstrap/cache`.
4. Apuntar el document root del virtual host **solamente** a `facturacion-server/public` y configurar TLS para `facturacion.refugioagostinorocca.com`.
5. Entrar a `https://facturacion.refugioagostinorocca.com/settings`, iniciar sesión y subir un perfil por cada juego de credenciales ARCA. Los valores iniciales solicitados están en `.env.example`; deben rotarse en producción.
6. Mantener un worker con Supervisor/systemd: `php artisan queue:work --sleep=3 --tries=5 --timeout=120`.
7. Agregar al cron: `* * * * * cd /ruta/facturacion-server && php artisan schedule:run >> /dev/null 2>&1`.

Los certificados y private keys quedan en `storage/app/private/arca/<perfil>` (ignorados por Git). Nunca debe servirse `storage/app/private`; conservar un backup cifrado externo. El scheduler revisa los perfiles cada 30 minutos y el servicio también renueva el TA bajo demanda antes de vencer, usando un TA separado por perfil.

## Integración y operación

- No confirmar al usuario que existe factura sólo por recibir `202`: esperar `completed` o implementar reintentos/polling.
- El worker reintenta fallos transitorios. Una solicitud con la misma clave idempotente nunca crea otra fila ni otro trabajo.
- Los PDF quedan en `storage/app/private/invoices`, fuera del web root, y sólo se descargan mediante el endpoint autenticado.
- `/up` sirve para health checks. Revisar `storage/logs/laravel.log`, la tabla `failed_jobs` y el panel `/settings`.
- Extensiones PHP necesarias: `soap`, `openssl`, `simplexml`, `mbstring`, `pdo`, `fileinfo`; también debe estar disponible el binario `openssl`.

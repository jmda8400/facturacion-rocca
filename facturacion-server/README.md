# Microservicio central de facturación Rocca

Laravel recibe facturas de **Reservas** y **Comandas**, autentica cada sistema como un `billing_client`, encola `IssueInvoice`, autoriza en ARCA, genera el PDF privado y envía el correo. El flujo es asíncrono: `POST` → cola → ARCA → PDF → email; los consumidores hacen polling, sin callbacks en esta versión.

## Autenticación y clientes

Los passwords se guardan con el hasher de Laravel. Cada login entrega un token opaco aleatorio; la base guarda sólo su SHA-256. El TTL (`BILLING_TOKEN_TTL_HOURS`, 12 por defecto) y límites de login/API son configurables. Nunca registre tokens, passwords, headers Authorization, certificados o keys.

```bash
php artisan billing:client:create reservas "Sistema de Reservas"
php artisan billing:client:create comandas "Sistema de Comandas"
php artisan billing:client:list
php artisan billing:client:disable reservas
php artisan billing:client:enable reservas
php artisan billing:client:reset-password reservas
php artisan billing:client:set-profile reservas rocca-reservas
php artisan billing:client:set-profile comandas rocca-comandas
```

`create` y `reset-password` generan una contraseña fuerte, la muestran una sola vez y jamás muestran hashes. Resetear/deshabilitar revoca tokens. No existe asociación hardcodeada: cada cliente sólo puede omitir `profile` o enviar exactamente el slug de su `default_arca_profile_id`. El diseño puede ampliarse luego a varios perfiles permitidos.

### Login

`POST /api/v1/auth/login`, JSON `{"username":"reservas","password":"PASSWORD"}`:

```json
{"data":{"token":"TOKEN","expires_at":"2026-09-24T01:00:00-03:00"},"error":null,"message":null,"pagination":null,"status_code":200,"success":true}
```

Credenciales incorrectas/inactivas responden `401` con `data:null`, `error:"Unauthorized"` y `message:"Credenciales inválidas."`. Un token inválido devuelve `message:"Token inválido."`; uno vencido devuelve `data:{"token_expired":true}` y `message:"El token ha expirado."`.

## Contrato de facturas

Rutas finales:

- `POST /api/v1/auth/login` (rate limit por IP + username).
- `POST /api/v1/invoices` (Bearer y rate limit por cliente).
- `GET /api/v1/invoices/{uuid}` (Bearer, sólo propietario).
- `GET /api/v1/invoices/{uuid}/pdf` (Bearer, propietario, `completed` y PDF existente).

Crear requiere `Authorization: Bearer TOKEN`, `Idempotency-Key` (1–100 caracteres) y JSON:

```json
{
  "external_reference":"BOOKING-ABC123",
  "profile":"rocca",
  "invoice_type":"B",
  "customer":{"name":"Juan Pérez","address":"Bariloche","vat_condition":"Consumidor Final","document_type":99,"document_number":0},
  "items":[{"description":"Servicio Refugio Rocca","quantity":2,"unit_price":60000}],
  "total":120000,
  "email_to":"cliente@example.com"
}
```

`profile` es opcional; siempre se usa el perfil predeterminado del cliente y, si se envía, debe coincidir. `total` debe coincidir con la suma redondeada de ítems. Una creación nueva responde `202`; un replay idéntico, `200`. La respuesta incluye `id`, `external_reference`, `status`, `source` derivado del token, datos CAE si existen, `status_url` y `download_url` al completar.

La idempotencia es `(billing_client_id, idempotency_key)`: Reservas y Comandas pueden usar la misma clave. El servidor calcula SHA-256 del payload validado canonicalizado. Misma clave/payload recupera una sola factura y no reencola; misma clave/payload distinto devuelve `409`. El índice único de base y el manejo de duplicate-key protegen requests concurrentes. Tras renovar un token vencido se debe repetir con **la misma clave**.

Cada cliente sólo ve sus facturas. Un UUID ajeno devuelve `404` también para PDF, para no revelar su existencia. Estados de polling: `pending`, `processing`, `completed`, `failed`.

| HTTP | Significado / acción |
|---|---|
| 200 | Login, estado o replay idempotente |
| 202 | Aceptada; iniciar polling |
| 401 | Login/token inválido o vencido; reloguear sólo si `token_expired=true` |
| 404 | No existe o pertenece a otro cliente |
| 409 | Clave reutilizada con otro payload; error permanente |
| 422 | Validación/perfil/total; error permanente |
| 429 | Límite; reintentar con backoff |
| 500/503 | Error interno/transitorio; reintentar con la misma clave |

## Ejemplo end-to-end: Reservas

```bash
TOKEN=$(curl -fsS -X POST https://facturacion.refugioagostinorocca.com/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"reservas","password":"PASSWORD"}' | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"];')
curl -X POST https://facturacion.refugioagostinorocca.com/api/v1/invoices -H "Authorization: Bearer $TOKEN" -H 'Idempotency-Key: booking-payment-123' -H 'Content-Type: application/json' -d '{"external_reference":"BOOKING-ABC123","invoice_type":"B","customer":{"name":"Juan Pérez","address":"Bariloche","vat_condition":"Consumidor Final","document_type":99,"document_number":0},"items":[{"description":"Pernocte","quantity":2,"unit_price":60000}],"total":120000}'
curl -H "Authorization: Bearer $TOKEN" https://facturacion.refugioagostinorocca.com/api/v1/invoices/UUID
curl -o factura.pdf -H "Authorization: Bearer $TOKEN" https://facturacion.refugioagostinorocca.com/api/v1/invoices/UUID/pdf
```

## Ejemplo end-to-end: Comandas

```bash
TOKEN=$(curl -fsS -X POST https://facturacion.refugioagostinorocca.com/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"comandas","password":"PASSWORD"}' | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["data"]["token"];')
curl -X POST https://facturacion.refugioagostinorocca.com/api/v1/invoices -H "Authorization: Bearer $TOKEN" -H 'Idempotency-Key: order-payment-456' -H 'Content-Type: application/json' -d '{"external_reference":"ORDER-456","invoice_type":"B","customer":{"name":"Cliente mostrador","address":"Bariloche","vat_condition":"Consumidor Final","document_type":99,"document_number":0},"items":[{"description":"Consumo","quantity":1,"unit_price":15000}],"total":15000}'
curl -H "Authorization: Bearer $TOKEN" https://facturacion.refugioagostinorocca.com/api/v1/invoices/UUID
curl -o factura.pdf -H "Authorization: Bearer $TOKEN" https://facturacion.refugioagostinorocca.com/api/v1/invoices/UUID/pdf
```

Si cualquiera recibe `401` con `data.token_expired=true`: hacer login, guardar el token nuevo y repetir el POST con **la misma `Idempotency-Key`**.

## ARCA, panel y cronómetro

En `/settings` se crean/editan perfiles con CUIT real de 11 dígitos, punto de venta positivo, razón social, domicilio, condición IVA y datos fiscales opcionales. `.crt` y `.key` deben ser legibles por OpenSSL y corresponder; se guardan sólo en `storage/app/private`. El panel muestra trazabilidad de facturas y una terminal de eventos sin secretos.

El scheduler ejecuta `arca:renew-tickets` cada seis horas y registra cada activación/renovación. Cada tarjeta muestra una cuenta regresiva calculada desde el TA. El servicio también renueva bajo demanda antes del vencimiento. Diariamente se eliminan tokens vencidos desde hace más de un día.

## Configuración y Docker

Requiere PHP `>=8.4.1`; la imagen usa PHP 8.4 FPM. Nuevas variables:

```dotenv
BILLING_TOKEN_TTL_HOURS=12
BILLING_LOGIN_RATE_LIMIT=6
BILLING_API_RATE_LIMIT=60
```

`BILLING_API_KEYS` quedó eliminado. Mantenga `APP_DEBUG=false`. Docker conserva MariaDB en `database` y certificados/PDF/TA en `private_storage`.

## Deploy seguro (Docker Compose v1)

No use `down -v`, `migrate:fresh`, ni borre storage. Desde producción:

```bash
cd /var/www/facturacion-rocca/facturacion-server
git pull --ff-only
# Verifique/agregue las tres BILLING_* nuevas en .env; no copie .env.example sobre .env.
docker-compose build --pull app
docker-compose up -d db
docker-compose up -d app
# entrypoint ya ejecuta config:cache, view:cache y migrate --force; verifique explícitamente:
docker-compose exec -T app php artisan migrate:status
docker-compose exec -T app php artisan about
docker-compose exec -T app php artisan schedule:list
docker-compose exec -T app php artisan queue:failed
curl --fail --silent --show-error https://facturacion.refugioagostinorocca.com/up
docker-compose ps
docker-compose logs --tail=100 app
```

Antes, se recomienda backup sin destruir volúmenes:

```bash
docker-compose exec -T db sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > "facturacion-$(date +%F-%H%M).sql"
docker run --rm -v facturacion-server_private_storage:/data:ro -v "$PWD":/backup alpine tar czf /backup/private-storage-$(date +%F-%H%M).tgz -C /data .
```

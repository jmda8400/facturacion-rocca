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

### Opción recomendada: Docker Compose

El contenedor incluye Nginx, PHP-FPM, las extensiones de PHP requeridas, el worker y el scheduler. MariaDB se ejecuta en un contenedor separado y tanto la base como certificados, TA y facturas se guardan en volúmenes persistentes.

```bash
cd facturacion-server
cp .env.example .env
# Edite .env. Puede generar secretos, por ejemplo, con:
openssl rand -base64 32 # APP_KEY debe llevar el prefijo "base64:"
openssl rand -hex 32    # BILLING_API_KEYS, SETTINGS_PASSWORD y contraseñas de DB
docker compose up -d --build
docker compose ps
curl --fail http://127.0.0.1:8080/up
```

El puerto se publica sólo en `127.0.0.1:8080` para colocarlo detrás del reverse proxy TLS del servidor. Se puede cambiar con `HTTP_PORT` al ejecutar Compose. El arranque valida las variables sensibles, prepara los directorios, cachea la configuración y aplica migraciones antes de iniciar Nginx, PHP-FPM, la cola y el scheduler.

Ejemplo mínimo de proxy Nginx en el host (Certbot puede administrar el certificado):

```nginx
server {
    listen 443 ssl http2;
    server_name facturacion.refugioagostinorocca.com;

    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass http://127.0.0.1:8080;
    }
}
```

Después del primer arranque, ingresar a `/settings` y cargar cada certificado y clave ARCA. Para actualizar: obtener el código nuevo y ejecutar `docker compose up -d --build`. Antes de una actualización, respaldar ambos volúmenes:

```bash
docker compose exec -T db mariadb-dump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > facturacion.sql
docker run --rm -v facturacion-server_private_storage:/data -v "$PWD":/backup alpine tar czf /backup/private-storage.tgz -C /data .
```

Verificación y diagnóstico:

```bash
docker compose logs -f app
docker compose exec app php artisan about
docker compose exec app php artisan queue:failed
docker compose exec app php artisan schedule:list
```

No exponga el puerto de MariaDB ni monte `storage/app/private` en un servidor web. Conserve copias cifradas de la base y del volumen privado fuera del servidor.

### Instalación tradicional

1. Copiar `.env.example` a `.env`, generar `APP_KEY` y crear claves API aleatorias de al menos 32 caracteres. Cambiar especialmente la contraseña inicial de `/settings` antes de exponer el sitio.
2. Configurar base de datos y SMTP en `.env`.
3. Ejecutar `composer install --no-dev --optimize-autoloader`, `php artisan key:generate`, `php artisan migrate --force` y dar permisos de escritura al usuario PHP sobre `storage` y `bootstrap/cache`.
4. Apuntar el document root del virtual host **solamente** a `facturacion-server/public` y configurar TLS para `facturacion.refugioagostinorocca.com`.
5. Entrar a `https://facturacion.refugioagostinorocca.com/settings`, iniciar sesión y subir un perfil por cada juego de credenciales ARCA. Los valores iniciales solicitados están en `.env.example`; deben rotarse en producción.
6. Mantener un worker con Supervisor/systemd: `php artisan queue:work --sleep=3 --tries=5 --timeout=120`.
7. Agregar al cron: `* * * * * cd /ruta/facturacion-server && php artisan schedule:run >> /dev/null 2>&1`.

Los certificados y private keys quedan en `storage/app/private/arca/<perfil>` (ignorados por Git). Nunca debe servirse `storage/app/private`; conservar un backup cifrado externo. El scheduler renueva los TA de los puntos de venta cada 6 horas y el servicio también los renueva bajo demanda antes de vencer, usando un TA separado por punto.

## Integración y operación

- No confirmar al usuario que existe factura sólo por recibir `202`: esperar `completed` o implementar reintentos/polling.
- El worker reintenta fallos transitorios. Una solicitud con la misma clave idempotente nunca crea otra fila ni otro trabajo.
- Los PDF quedan en `storage/app/private/invoices`, fuera del web root, y sólo se descargan mediante el endpoint autenticado.
- `/up` sirve para health checks. Revisar `storage/logs/laravel.log`, la tabla `failed_jobs` y el panel `/settings`.
- Extensiones PHP necesarias: `soap`, `openssl`, `simplexml`, `mbstring`, `pdo`, `fileinfo`; también debe estar disponible el binario `openssl`.

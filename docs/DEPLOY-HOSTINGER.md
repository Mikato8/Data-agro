# Despliegue en Hostinger

Esta guía describe el despliegue real de AgroDiagnóstico en un plan de **hosting compartido o Cloud de Hostinger** con PHP 8.2+ y SSL. La base de datos **no se instala en Hostinger**: vive en **Neon PostgreSQL** y la aplicación se conecta por TCP con `pdo_pgsql`.

> **Regla de oro:** las migraciones y los seeds se ejecutan **desde tu equipo o por SSH**, nunca desde el navegador. El hosting sólo ejecuta la aplicación.

## 1. Arquitectura del despliegue

```text
tu-dominio.mx
   │ HTTPS (SSL de Hostinger)
   ▼
public_html/
   ├── .htaccess              redirige a public/ y bloquea rutas sensibles
   ├── public/                front controller, assets, descargas y uploads
   │   ├── index.php
   │   ├── assets/
   │   ├── descargas/         siete DOCX + ZIP del kit
   │   └── uploads/           imágenes de casos y avatares
   ├── app/                   código, fuera del alcance directo
   ├── config/
   ├── database/
   ├── storage/               logs, sesiones y caché
   └── .env                   configuración y credenciales, permisos 600
          │
          ├── pdo_pgsql (TCP 5432, TLS) ──► Neon, endpoint directo
          └── HTTPS /sql (si DB_DRIVER=http) ──► Neon
```

En la demo actual el documento raíz es `public_html` y el `.htaccess` de la raíz redirige a `public/`. Si tu plan permite cambiar el documento raíz, apuntarlo directamente a `public/` es más seguro; ambas opciones se explican en la sección 5.

## 2. Antes de empezar

### 2.1 Requisitos del plan

- PHP **8.2, 8.3 o 8.4** con `pdo_pgsql`, `curl`, `mbstring`, `fileinfo`, `gd`, `intl` y `zip`.
- SSL activo para el dominio o subdominio.
- Acceso por SSH (recomendado) o File Manager.
- Una cuenta en [Neon](https://neon.tech/) con un proyecto PostgreSQL.
- `psql` y `pg_dump` en tu equipo son opcionales, pero facilitan la verificación y los respaldos.

`zip` es necesario para exportar DOCX; `gd` para re-codificar imágenes y para el PDF con imágenes; `intl` para normalizar nombres con acentos en la verificación de cédula.

### 2.2 Crear la base en Neon

1. Entra a la consola de Neon y crea un proyecto en la región más cercana a tus usuarios.
2. Anota el nombre de la base (por defecto `neondb`), el usuario y la contraseña del rol.
3. Abre **Connect** y elige **Direct connection**, no la conexión agrupada. El host directo **no** lleva `-pooler`.
4. Copia la cadena completa. Debe verse como:

```text
postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require
```

5. Agrega el parámetro `options` con el identificador del endpoint, codificado:

```text
&options=endpoint%3Dep-polished-thunder-b5980ml2
```

La cadena final para este despliegue es:

```text
postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require&options=endpoint%3Dep-polished-thunder-b5980ml2
```

- `%3D` es el signo `=` codificado. No escribas `options=endpoint=...` dentro de la query: el segundo `=` rompería la URL.
- Si creas un proyecto nuevo, reemplaza `ep-polished-thunder-b5980ml2` por tu endpoint y `<REGION>` por la región que muestra Neon.
- Si la contraseña contiene `@`, `:`, `/`, `?`, `#`, `&` o `%`, debe ir codificada. Neon suele entregar la cadena ya codificada; si la armas a mano, usa un codificador URL.
- No publiques esta cadena en el repositorio, capturas, tickets ni mensajes.

> **Por qué el endpoint directo y `options`:** el cliente `libpq` que trae Hostinger puede ser antiguo y no enviar SNI, que Neon necesita para enrutar la conexión. El parámetro `options=endpoint=<id>` le indica al servidor a qué endpoint conectarse. Además, el pooler de Neon puede dejar `search_path` vacío en conexiones agrupadas y las consultas sin esquema fallan con `relation ... does not exist`. Es una **decisión de despliegue**, no un fallo abierto de la aplicación.

### 2.3 Verificar la conexión desde tu equipo

```bash
psql "postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require&options=endpoint%3Dep-polished-thunder-b5980ml2" \
  -c "select current_database(), current_schema(), current_setting('search_path'), current_user;"
```

Debes ver la base `neondb`, el esquema `public` y un `search_path` como `"$user", public`. Si `search_path` aparece vacío, no uses esa conexión para la aplicación: revisa que sea el endpoint directo y que la URL conserve `options`.

## 3. Preparar la base desde local

No ejecutes migraciones ni seeds desde el navegador. Todos los scripts leen `DATABASE_URL`; las variables de entorno tienen prioridad sobre `.env`, así que puedes apuntar a producción sin editar tu archivo local.

### 3.1 Configurar la conexión de producción en la sesión

```bash
export DATABASE_URL='postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require&options=endpoint%3Dep-polished-thunder-b5980ml2'
export DATABASE_URL_UNPOOLED="$DATABASE_URL"
export DB_DRIVER=pdo
```

`DATABASE_URL_UNPOOLED` se conserva por convención y para herramientas externas. En el código actual, la aplicación y **todos** los scripts —incluido `bin/migrate.php`— usan `DATABASE_URL`; cambiar sólo la variable `*_UNPOOLED` no mueve las migraciones.

### 3.2 Migrar, sembrar y crear el administrador

Desde la raíz del proyecto:

```bash
php bin/migrate.php
php bin/seed.php
php bin/create-admin.php \
  --name="Nombre del administrador" \
  --email="admin@tu-dominio.mx" \
  --license="CEDULA-ADMIN" \
  --state=CMX
```

- `bin/migrate.php` aplica en orden `001_schema.sql` … `005_farm_crm.sql` y registra cada versión en `schema_migrations`.
- `bin/seed.php` carga 32 estados, 46 cultivos, 238 variedades, 13 especialidades, 79 síntomas, 50 problemas fitosanitarios y 64 recursos con guía. **No uses `--demo` en producción** salvo que quieras contenido ficticio; además de cuentas y casos, crea parcelas, visitas, recomendaciones, insumos y una venta de ejemplo para la cuenta administradora.
- `bin/create-admin.php` pide la contraseña de forma interactiva; usa una contraseña única de al menos 12 caracteres. El administrador queda con rol `admin` y estado `verificado`.
- Si necesitas actualizar sólo el directorio de recursos después de un cambio editorial, ejecuta `php bin/seed-resources.php`.

### 3.3 Comprobar el resultado

```bash
php bin/check.php
```

El chequeo debe mostrar PHP 8.2+, extensiones, conexión a la base, migraciones aplicadas, conteos y permisos. Si `bin/check.php` no conecta, revisa la sección 14 antes de subir nada.

Verifica también los conteos:

```bash
psql "$DATABASE_URL" -c "select
  (select count(*) from states) as estados,
  (select count(*) from crops) as cultivos,
  (select count(*) from problems) as problemas,
  (select count(*) from resources) as recursos,
  (select count(*) from schema_migrations) as migraciones;"
```

## 4. Paquete de despliegue

### 4.1 Opción con `rsync` (recomendada)

```bash
rsync -avz \
  --exclude '.git/' \
  --exclude '.env' \
  --exclude '.ssh/' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/sessions/*' \
  --exclude 'storage/cache/*' \
  --exclude 'storage/demo-credentials.txt' \
  --exclude 'public/uploads/cases/*' \
  --exclude 'public/uploads/avatars/*' \
  --exclude 'tests/' \
  --exclude 'docs/capturas/' \
  --exclude 'ai-service/' \
  --exclude '*.DS_Store' \
  ./ USUARIO@HOST:~/domains/tu-dominio.mx/public_html/
```

Si tu plan usa un puerto SSH distinto, agrega:

```bash
rsync -avz -e "ssh -p PUERTO_SSH" ... ./ USUARIO@HOST:~/domains/tu-dominio.mx/public_html/
```

No uses `--delete` en la primera subida ni sin respaldo: podría borrar imágenes o archivos que sólo existen en el servidor.

### 4.2 Opción con ZIP

```bash
zip -r ../agrodiagnostico.zip . \
  -x ".git/*" ".env" ".ssh/*" \
     "storage/logs/*" "storage/sessions/*" "storage/cache/*" \
     "storage/demo-credentials.txt" \
     "public/uploads/cases/*" "public/uploads/avatars/*" \
     "tests/*" "docs/capturas/*" "ai-service/*" "*.DS_Store"
```

Antes de subir, confirma que el ZIP incluye:

```bash
unzip -l ../agrodiagnostico.zip | grep -E 'public/index.php|public/.htaccess|\.htaccess|public/descargas/kit-recursos-agronomicos.zip'
```

Debe incluir `public/descargas/` completa (siete DOCX y el ZIP), `public/uploads/.htaccess` y los `.htaccess`. Las carpetas vacías pueden no viajar en el ZIP; créalas en el servidor.

### 4.3 Qué no subir

- `.env` (se crea en el servidor).
- `.git/`, `.ssh/`, `storage/demo-credentials.txt`.
- `storage/logs/*`, `storage/sessions/*`, `storage/cache/*`.
- Imágenes locales de `public/uploads/cases/` y `public/uploads/avatars/`.
- `tests/`, `docs/capturas/` y, salvo que quieras operar la IA desde el mismo servidor, `ai-service/`.

## 5. Subir el proyecto y definir el documento raíz

### 5.1 Caso real de la demo: todo en `public_html`

1. Sube el proyecto a `~/domains/tu-dominio.mx/public_html/`.
2. Conserva el `.htaccess` de la raíz. Contiene las reglas que redirigen a `public/` y bloquean `app/`, `config/`, `database/`, `storage/`, `bin/`, `tests/`, `vendor/`, `docs/`, `tools/`, `ai-service/`, `.env`, `.git`, `composer.*`, `README.md`, `LICENSE`, `CONTRIBUTING.md`, `SECURITY.md` y `CONTENIDO-LICENCIA.md`.
3. Comprueba que `mod_rewrite` esté activo. En LiteSpeed/Apache de Hostinger suele estarlo; si el sitio devuelve 403 o 500, revisa los logs y prueba la opción 5.2.
4. Verifica que el `.htaccess` raíz no haya quedado dentro de `public/`: debe estar en la raíz del proyecto y el de `public/` debe ser el front controller.

Con esta opción, la URL `https://tu-dominio.mx/` sirve `public/index.php`, y los assets se resuelven como `/assets/...` y `/uploads/...`.

> El `.htaccess` raíz bloquea también `docs/`, `tools/`, `ai-service/` y los archivos `CONTRIBUTING.md`, `SECURITY.md` y `CONTENIDO-LICENCIA.md`. Aun así, no es necesario subir esas carpetas a producción: el sitio sólo necesita `public/`, `app/`, `config/`, `database/`, `routes/`, `storage/` y `.env`. `tests/` también está bloqueado, pero no se usa en el servidor.

### 5.2 Opción más segura: documento raíz en `public/`

Si el plan permite cambiar el documento raíz:

1. Sube el proyecto a `~/domains/tu-dominio.mx/agrodiagnostico/`.
2. En hPanel, apunta el documento raíz del dominio o subdominio a `agrodiagnostico/public`.
3. El `.htaccess` raíz ya no se usa; el de `public/` sigue enviando las rutas a `index.php`.
4. `.env`, `app/`, `database/` y `storage/` quedan fuera del árbol web.

Esta opción reduce la superficie expuesta, pero la demo actual funciona con la opción 5.1. No cambies el documento raíz sin probar primero en un subdominio.

## 6. Crear el `.env` de producción

Crea `.env` en la raíz del proyecto, **nunca** dentro de `public/`. Ejemplo completo con marcadores:

```dotenv
APP_NAME="AgroDiagnóstico"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.mx
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es_MX
APP_KEY=

# Neon: endpoint directo, sin -pooler, con options codificado.
DATABASE_URL="postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require&options=endpoint%3Dep-polished-thunder-b5980ml2"
DATABASE_URL_UNPOOLED="postgresql://USUARIO:CLAVE@ep-polished-thunder-b5980ml2.<REGION>.aws.neon.tech/neondb?sslmode=require&channel_binding=require&options=endpoint%3Dep-polished-thunder-b5980ml2"

# pdo si pdo_pgsql está disponible; http si el hosting no lo tiene o bloquea 5432.
DB_DRIVER=pdo

SESSION_NAME=agro_session
SESSION_LIFETIME=7200
SESSION_SECURE=true

UPLOAD_MAX_BYTES=5242880
UPLOAD_MAX_FILES=6

# Verificación automática de cédula: desactivada al inicio.
CEDULA_VALIDATION_ENABLED=false
CEDULA_API_URL=https://apimarket.mx/api/sep/grupo/validar-cedula
CEDULA_API_TOKEN=
CEDULA_API_TIMEOUT=15

# IA externa: vacía hasta desplegar ai-service/.
AI_SERVICE_URL=
AI_SERVICE_TOKEN=

# Correo: activar después de crear el buzón y configurar SPF/DKIM.
MAIL_ENABLED=false
MAIL_FROM=no-reply@tu-dominio.mx
MAIL_FROM_NAME="AgroDiagnóstico"

# Nunca en producción.
AUTO_VERIFY_USERS=false
```

Notas:

- El `.env.example` del repositorio ya recomienda la conexión directa, `DB_DRIVER=pdo` y el parámetro `options` cuando el hosting lo necesita. Ajusta los marcadores de host, usuario y clave.
- `APP_URL` debe incluir `https://` y no terminar en `/`.
- `APP_DEBUG=false` en producción. Si lo activas para diagnosticar, hazlo por minutos y revisa que no quede expuesto.
- `SESSION_SECURE=true` exige HTTPS. Si pruebas en HTTP, ponlo en `false` sólo temporalmente; con `true` la cookie no se envía y el acceso entra en bucle.
- `DB_DRIVER=pdo` es la opción de la demo. Si `php -m` no muestra `pdo_pgsql`, usa `DB_DRIVER=http`; el cliente HTTPS no necesita libpq ni `options`.
- No uses el host con `-pooler` en `DATABASE_URL` para este despliegue.
- `APP_KEY` está reservada; no se usa para cifrar datos en el código actual.
- `AUTO_VERIFY_USERS=true` marca todas las cuentas como verificadas: úsalo sólo en desarrollo.

## 7. Permisos

Por SSH o File Manager:

```bash
cd ~/domains/tu-dominio.mx/public_html
mkdir -p storage/logs storage/sessions storage/cache
mkdir -p public/uploads/cases public/uploads/avatars

chmod -R 775 storage public/uploads
find storage public/uploads -type d -exec chmod 775 {} \;
find storage public/uploads -type f -exec chmod 664 {} \;
# Todo el código y los assets deben ser legibles por el servidor web.
find . -type d -exec chmod 755 {} \;
find . -type f ! -name '.env' -exec chmod 644 {} \;
find . -name '.htaccess' -exec chmod 644 {} \;
chmod 600 .env
```

- Los `.htaccess` deben quedar **legibles** (644) para que LiteSpeed/Hostinger aplique las reglas. Con permisos 600 el servidor puede ignorarlos: la portada devuelve 403 y el código de `app/` queda expuesto. Verifica la raíz, `public/` y `public/uploads/`.
- El usuario de PHP debe poder escribir en `storage/sessions`, `storage/logs`, `storage/cache`, `public/uploads/cases` y `public/uploads/avatars`.
- No uses `777`. Si el usuario de PHP no es el mismo que el de SSH, ajusta el grupo o consulta a soporte de Hostinger.
- No cambies los permisos de `public/index.php` ni de los assets a valores que impidan la lectura. Si subes con `rsync -a`, los archivos pueden conservar 600 desde tu equipo: el CSS, el logo y las descargas responderán 403 aunque el HTML cargue. Corrige con `find . -type f ! -name '.env' -exec chmod 644 {} \;`.

## 8. Verificación inicial

### 8.1 Salud del servicio

```bash
curl -sS https://tu-dominio.mx/salud
```

Respuesta esperada, con variaciones de versión:

```json
{
  "status": "ok",
  "app": "AgroDiagnóstico",
  "php": "8.3.x",
  "database": {
    "ok": true,
    "driver": "pdo_pgsql",
    "error": null
  },
  "uploads_writable": true,
  "time": "2026-10-24T18:00:00-06:00",
  "timezone": "America/Mexico_City"
}
```

- `status` debe ser `ok`; si es `degradado`, la base no responde y el código HTTP es 503.
- `database.driver` debe ser `pdo_pgsql` si usas `DB_DRIVER=pdo`; `neon_http` si usas `http`.
- `uploads_writable` debe ser `true`.
- `/salud` sólo hace `select 1`: un verde no garantiza que las migraciones estén aplicadas. Prueba también `/casos`.

### 8.2 Rutas y flujo

1. Abre `/` y confirma que cargan portada, casos y recursos.
2. Abre `/casos`, `/apoyo-diagnostico`, `/enfermedades`, `/recursos`, `/agrodatos`, `/recursos/kit`, `/recursos/revistas` y `/recursos/glosario`.
3. Inicia sesión en `/acceder` con el administrador.
4. Abre `/admin` y confirma la cola editorial; `/admin/usuarios` y `/admin/recursos` sólo deben abrir con rol `admin`.
5. Crea un caso de prueba, envíalo a revisión, apruébalo y compruébalo en `/casos`.
6. Sube una imagen JPG/PNG/WebP y confirma que se muestra y que el PDF la incluye.
7. Descarga el caso en PDF y DOCX.
8. Abre `/panel/entrar`, crea una parcela, agenda una visita, registra un insumo y una venta; confirma que el stock baja.
9. Revisa `/sitemap.xml` y `/robots.txt`.
10. Si la IA está desplegada, define `AI_SERVICE_URL` y `AI_SERVICE_TOKEN` y prueba `/apoyo-diagnostico/ia`.

### 8.3 Archivos que no deben exponerse

```bash
curl -sS -o /dev/null -w '%{http_code}\n' https://tu-dominio.mx/.env
curl -sS -o /dev/null -w '%{http_code}\n' https://tu-dominio.mx/app/Core/Database.php
curl -sS -o /dev/null -w '%{http_code}\n' https://tu-dominio.mx/storage/logs/
curl -sS -o /dev/null -w '%{http_code}\n' https://tu-dominio.mx/README.md
```

Los cuatro deben devolver 403 o 404, nunca 200.

## 9. Administrador y primeras cuentas

- Crea el administrador con `bin/create-admin.php` desde local o SSH. No pegues la contraseña en el historial del shell: deja que el script la pida.
- Si el correo ya existe, el script se detiene; usa `--force` sólo si quieres restablecer la contraseña y el rol de esa cuenta.
- El estado del administrador es `verificado`; puede entrar a `/admin/usuarios`, verificar cuentas y asignar roles.
- Las cuentas nuevas quedan en `pendiente` hasta que un administrador las verifique, salvo que la verificación automática de cédula esté activa y tenga éxito.
- No dejes cuentas demo con contraseñas conocidas en producción.

## 10. Correo transaccional

La recuperación de contraseña usa `mail()` de PHP. En la demo está desactivada (`MAIL_ENABLED=false`): el formulario `/recuperar` responde con un mensaje genérico, pero no envía nada.

Para activarlo:

1. Crea el buzón `no-reply@tu-dominio.mx` en hPanel.
2. Configura SPF y DKIM en el DNS del dominio.
3. Cambia en `.env`:

```dotenv
MAIL_ENABLED=true
MAIL_FROM=no-reply@tu-dominio.mx
MAIL_FROM_NAME="AgroDiagnóstico"
```

4. Prueba `/recuperar` con una cuenta real y revisa spam y `storage/logs/`.
5. Si el plan bloquea `mail()`, integra un proveedor SMTP o una API de correo; el proyecto no incluye Composer ni un cliente SMTP.

El enlace de recuperación vence en 60 minutos, es de un solo uso y se guarda hasheado. El portal no envía notificaciones de decisiones editoriales; esa función está en el roadmap.

## 11. Actualizaciones

### 11.1 Sólo código

```bash
rsync -avz --exclude '.env' --exclude '.git/' \
  ./ USUARIO@HOST:~/domains/tu-dominio.mx/public_html/
```

No sobrescribas `.env`, `storage/` ni `public/uploads/`.

### 11.2 Con migraciones

1. Respalda la base (sección 12).
2. Sube el código.
3. Desde local o SSH, con `DATABASE_URL` de producción:

```bash
php bin/migrate.php
php bin/check.php
```

4. Si cambió el contenido de recursos: `php bin/seed-resources.php`.
5. Limpia el caché:

```bash
rm -f storage/cache/*.cache
```

6. Si el plan usa OPcache o caché de LiteSpeed, purga la caché desde hPanel.
7. Prueba `/salud`, `/casos`, `/admin` y una descarga.

Las migraciones se registran en `schema_migrations`. No edites una migración ya aplicada: agrega una nueva. Prueba las migraciones en una rama de Neon antes de aplicarlas a producción.

## 12. Respaldos

- **Neon:** activa las copias automáticas o el *point-in-time restore* que incluya tu plan. Comprueba la retención y prueba una restauración en una rama, no sobre producción.
- **Base manual:**

```bash
pg_dump "$DATABASE_URL" --no-owner --format=custom --file="respaldo-agro-$(date +%F).dump"
```

Evita escribir la contraseña en el comando; usa `.pgpass` o el prompt de `pg_dump`.

- **Imágenes:** respalda `public/uploads/` con `rsync` o un ZIP periódico.
- **Configuración:** guarda `.env` en un gestor de secretos, nunca en el repositorio.
- **Restauración:** prueba `pg_restore` en una base vacía o en una rama de Neon antes de necesitarlo:

```bash
pg_restore --clean --if-exists --no-owner -d "$DATABASE_URL" respaldo-agro-2026-10-24.dump
```

## 13. Seguridad posterior al despliegue

- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE=true` y `AUTO_VERIFY_USERS=false`.
- `.env` con permisos 600, fuera de `public/` y bloqueado por `.htaccess`.
- No subir `storage/demo-credentials.txt`, `tests/`, `docs/capturas/`, `tools/` ni `ai-service/` si no se usan.
- Eliminar cualquier cuenta demo y no ejecutar `bin/seed.php --demo` en producción.
- Rotar la contraseña de Neon si se compartió por chat, correo o captura.
- Mantener actualizados PHP, Neon y el certificado SSL.
- Revisar `storage/logs/` y el panel de Hostinger después de cada actualización.
- Limitar el acceso a `/admin` a las cuentas necesarias y revisar la lista de administradores.
- Configurar respaldos y probar una restauración.

## 14. Solución de problemas

### 14.1 Conexión con Neon, SNI y `search_path`

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `SQLSTATE[08006]` o `server closed the connection unexpectedly` al conectar | El libpq de Hostinger no envía SNI y Neon no resuelve el endpoint | Usa el host directo (sin `-pooler`) y agrega `&options=endpoint%3D<endpoint-id>` a `DATABASE_URL`. |
| `relation "users" does not exist` aunque las tablas existan | `search_path` vacío por una conexión agrupada, o la app apunta a otra base | Cambia al endpoint directo con `options`; verifica `current_database()` y `current_setting('search_path')`; corre las migraciones. |
| `relation "schema_migrations" does not exist` | Nunca se ejecutaron las migraciones en esa base | Ejecuta `php bin/migrate.php` con la `DATABASE_URL` correcta. |
| `password authentication failed` | Contraseña mal codificada o rotada | Vuelve a copiar la cadena desde Neon y codifica los caracteres especiales. |
| `Tenant or user not found` | Endpoint o proyecto equivocado | Revisa el host y el `endpoint` en `options`; no mezcles proyectos. |
| `channel binding` o error de SSL | Cliente libpq muy antiguo o parámetro incompatible | Actualiza el stack o usa `DB_DRIVER=http`; no desactives TLS a la ligera. |
| `Connection timed out` al puerto 5432 | El hosting bloquea el puerto | Cambia a `DB_DRIVER=http` o solicita a soporte la salida TCP. |

Comprobación útil desde el servidor, si hay SSH:

```bash
php -r 'echo PHP_VERSION, " ", (extension_loaded("pdo_pgsql") ? "pdo_pgsql" : "sin pdo_pgsql"), PHP_EOL;'
```

Y desde local, contra la misma URL:

```bash
psql "$DATABASE_URL" -c "show search_path; select count(*) from information_schema.tables where table_schema = 'public';"
```

Si `psql` funciona pero PHP no, el problema suele ser SNI/libpq o la extensión, no las credenciales.

### 14.2 Errores HTTP

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| 500 en todo el sitio | `.env` ausente o con sintaxis inválida; extensión faltante; error de PHP | Revisa `storage/logs/app-*.log` y el log de Hostinger; valida `php -l`; confirma PHP 8.2+ y extensiones. |
| 500 sólo al subir imágenes o al generar PDF | Falta `gd`, memoria insuficiente o permisos | Activa `gd`; revisa `memory_limit`; corrige permisos de `public/uploads/`. |
| 500 sólo al exportar DOCX | Falta `zip` | Activa la extensión `zip`. |
| 403 en la portada y `app/` accesible | `.htaccess` con permisos 600; LiteSpeed no puede leerlo y no aplica las reglas | `chmod 644 .htaccess public/.htaccess public/uploads/.htaccess`; confirma que el usuario de PHP pueda leerlos. |
| 403 en todo el sitio | `.htaccess` incompatible, `mod_rewrite` desactivado o permisos | Revisa logs; prueba temporalmente sin el `.htaccess` raíz para aislar; restáuralo; usa la opción de documento raíz en `public/`. |
| 403 sólo en `/admin` | La cuenta no es `editor` ni `admin` | Cambia el rol desde `/admin/usuarios` con un administrador. |
| 404 en `/casos` pero `/` funciona | Documento raíz o rewrite incorrecto | Apunta a `public/` o corrige el `.htaccess` raíz; verifica `public/.htaccess`. |
| 404 en CSS/JS | Documento raíz mal configurado o assets no subidos | Confirma `public/assets/` y que la URL sea `/assets/...`. |
| 403 al abrir `/uploads/...` | Permisos o reglas del `.htaccess` de uploads | Los archivos deben ser legibles; el `.htaccess` sólo bloquea scripts, no imágenes. |

### 14.3 Sesiones y CSRF

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| El acceso vuelve al formulario, sin error | `SESSION_SECURE=true` sobre HTTP, o `storage/sessions` sin escritura | Usa HTTPS; corrige permisos; revisa `session_save_path`. |
| «Demasiados intentos fallidos» | Rate limit de 12 fallos en 15 minutos | Espera o limpia `login_attempts` para la cuenta/IP; revisa que no sea un ataque. |
| 419 al enviar un formulario | Token CSRF vencido, cookies bloqueadas o sesión perdida | Recarga el formulario; permite cookies; revisa permisos de sesión. |
| La sesión se pierde entre peticiones | Cookies con dominio/ruta incorrectos o balanceo sin sesiones compartidas | Revisa `APP_URL`, `SESSION_NAME` y el almacenamiento de sesiones. |
| «Mantener la sesión abierta» no funciona | Cookie `dv_remember` bloqueada o `SESSION_SECURE` incompatible | Usa HTTPS y permite cookies de primera parte. |

### 14.4 SSL y redirecciones

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| Contenido mixto o CSS por HTTP | `APP_URL` con `http://` o enlaces absolutos antiguos | Corrige `APP_URL` a `https://` y recarga. |
| Bucle de redirección | Regla de HTTPS de Hostinger y `.htaccess` compitiendo | Revisa el `.htaccess` raíz; deja una sola redirección a HTTPS. |
| HSTS no aparece | La petición no llega como HTTPS al PHP | Configura SSL y *force HTTPS* en hPanel. |
| El sitio abre por IP o dominio temporal | `APP_URL` no coincide con el dominio público | Actualiza `APP_URL`; no requiere tocar el código. |

### 14.5 Imágenes y archivos

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| «Cada imagen debe pesar menos de 5 MB» | Límite de la aplicación | Ajusta `UPLOAD_MAX_BYTES` o sube una imagen menor. |
| «El servidor no pudo guardar la imagen» | Permisos, disco lleno o `upload_tmp_dir` | Corrige permisos; revisa cuota; consulta a Hostinger. |
| La imagen se sube pero no se ve | Ruta o permisos | Verifica `public/uploads/cases/` y la URL `/uploads/cases/...`. |
| El PDF no incluye imágenes | `gd` ausente o memoria baja | Activa `gd`; aumenta `memory_limit`; revisa el log. |
| No se puede crear un avatar | `public/uploads/avatars/` sin permisos | Créala y aplica 775. |

### 14.6 Correo y otros

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| No llega el correo de recuperación | `MAIL_ENABLED=false`, `mail()` bloqueado o DNS | Activa el correo, crea el buzón y configura SPF/DKIM. |
| El correo llega a spam | Falta de reputación o autenticación | Revisa SPF, DKIM y DMARC; prueba con otro proveedor. |
| La sección de IA dice «no configurada» | `AI_SERVICE_URL` vacío | Es el comportamiento esperado en la demo; despliega `ai-service/` y define la URL. |
| La IA responde 503 | El servicio aún carga | Espera unos segundos y reintenta. |
| El sitio va lento la primera petición | Arranque en frío de Neon o del plan | Repite la petición; considera el driver PDO y revisa índices. |

## 15. Checklist final

- [ ] PHP 8.2+ con `pdo_pgsql`, `curl`, `mbstring`, `fileinfo`, `gd`, `intl` y `zip`.
- [ ] Base Neon creada y cadena **directa** con `sslmode=require`, `channel_binding=require` y `options=endpoint%3D...`.
- [ ] Migraciones aplicadas desde local o SSH.
- [ ] `bin/seed.php` ejecutado sin `--demo`.
- [ ] Administrador real creado y contraseña fuerte.
- [ ] `.env` en la raíz, permisos 600, fuera de `public/`.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE=true`, `AUTO_VERIFY_USERS=false`.
- [ ] `.htaccess` con permisos 644 y reglas activas.
- [ ] `storage/` y `public/uploads/` escribibles.
- [ ] SSL activo y HTTPS forzado.
- [ ] `/salud` en `ok`, `uploads_writable` en `true`.
- [ ] `/`, `/casos`, `/apoyo-diagnostico`, `/enfermedades`, `/recursos`, `/agrodatos` y `/recursos/kit` responden.
- [ ] Registro, caso, imagen, revisión y publicación probados.
- [ ] PDF, DOCX y kit descargables.
- [ ] `/panel/entrar` y una venta con descuento de stock probados.
- [ ] `.env` y `app/` inaccesibles desde el navegador.
- [ ] Respaldos de Neon y de `public/uploads/` configurados.
- [ ] Sin cuentas demo ni contenido ficticio en producción.

## 16. Enlaces relacionados

- [README del proyecto](../README.md)
- [Arquitectura](ARQUITECTURA.md)
- [Verificación de cédula](VERIFICACION-CEDULA.md)
- [Variables de ejemplo](../.env.example)

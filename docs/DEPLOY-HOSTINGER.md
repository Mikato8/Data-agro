# Despliegue en Hostinger

Esta guía asume un plan de **hosting compartido o Cloud** con PHP 8.2+ y SSL. La base de datos **no se instala en Hostinger**: vive en **Neon PostgreSQL** y la aplicación se conecta por TCP o por HTTPS.

> Importante: los planes de hosting compartido de Hostinger no ofrecen PostgreSQL administrado. Neon cubre esa capa. Hostinger sólo ejecuta PHP.

## 1. Arquitectura del despliegue

```text
tu-dominio.mx
   │ HTTPS (SSL de Hostinger)
   ▼
public_html/ o carpeta del subdominio
   ├── public/            ← documento raíz recomendado
   │   ├── index.php
   │   ├── assets/
   │   └── uploads/
   ├── app/               ← fuera del documento raíz
   ├── config/
   ├── database/
   ├── storage/           ← logs, sesiones (fuera de public)
   └── .env               ← fuera de public, permisos 600
          │
          ├── pdo_pgsql (TCP 5432, TLS)  ← si está disponible
          └── HTTPS /sql                 ← DB_DRIVER=http
                   │
                   ▼
             Neon PostgreSQL
```

## 2. Antes de subir nada

### 2.1 Base de datos

Desde tu equipo, con el `.env` apuntando a Neon:

```bash
php bin/migrate.php
php bin/seed.php
php bin/seed-resources.php
php bin/create-admin.php --name="Tu Nombre" --email="tu@correo.mx" --license="MV-0000" --state=CMX
```

- `bin/migrate.php` usa `DATABASE_URL_UNPOOLED` (sin `-pooler`) para DDL.
- `bin/seed.php` carga especies, estados, enfermedades, signos y recursos. **No uses `--demo` en producción** salvo que quieras contenido ficticio de demostración.
- `bin/create-admin.php` pide contraseña de forma interactiva si no la pasas; usa una contraseña única de al menos 12 caracteres.

### 2.2 PHP y extensiones

En hPanel → **Sitios web → Administrar → PHP**:

- Selecciona PHP **8.2, 8.3 o 8.4**.
- Verifica que estén disponibles `curl`, `mbstring`, `fileinfo`, `gd`, `intl` y, si es posible, `pdo_pgsql`.
- Si **no** aparece `pdo_pgsql`, no pasa nada: la app usará el endpoint HTTPS de Neon con `DB_DRIVER=http`.

### 2.3 Paquete de despliegue

Genera un ZIP sin secretos ni archivos locales:

```bash
cd Diagnostico-veterinario
zip -r ../diagnostico-veterinario.zip . \
  -x ".git/*" ".env" "storage/logs/*" "storage/sessions/*" \
     "storage/cache/*" "storage/demo-credentials.txt" \
     "public/uploads/cases/*" "tests/*" "docs/capturas/*" "*.DS_Store"
```

Incluye `public/uploads/.htaccess` y `public/uploads/cases/.gitkeep`; el ZIP debe conservar esas rutas.

## 3. Subir el proyecto

### Opción A (recomendada): documento raíz en `public/`

1. En hPanel, crea un subdominio, por ejemplo `portal.tu-dominio.mx`.
2. Sube el proyecto a `/home/USUARIO/domains/tu-dominio.mx/diagnostico`.
3. En **Subdominios → Document root**, apunta a `diagnostico/public`.
4. Activa SSL para el subdominio.

Con esta opción, `.env`, `app/`, `storage/` y `database/` quedan fuera del alcance del navegador.

### Opción B: todo dentro de `public_html`

Si tu plan no permite cambiar el documento raíz:

1. Sube el proyecto completo a `public_html`.
2. El `.htaccess` raíz redirige las peticiones a `public/` y bloquea `app/`, `config/`, `database/`, `storage/`, `bin/`, `.env` y `.git`.
3. Comprueba que `mod_rewrite` esté activo. Si el sitio devuelve 500, revisa los logs de Hostinger y prueba a renombrar temporalmente el `.htaccess` raíz para aislar la causa.

> La opción A es más segura. La opción B funciona, pero mantiene el código y el `.env` dentro del árbol web (aunque bloqueados por `.htaccess`).

## 4. Crear el `.env` en el servidor

Crea `.env` en la raíz del proyecto (junto a `app/`, nunca dentro de `public/`). Ejemplo:

```dotenv
APP_NAME="Diagnóstico Veterinario"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.tu-dominio.mx
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es_MX

DATABASE_URL="postgresql://USUARIO:CLAVE@HOST-POOLER/neondb?sslmode=require&channel_binding=require"
DATABASE_URL_UNPOOLED="postgresql://USUARIO:CLAVE@HOST/neondb?sslmode=require&channel_binding=require"

# Recomendado en hosting compartido:
DB_DRIVER=http
# Si verificas pdo_pgsql y el puerto 5432 sale:
# DB_DRIVER=pdo

SESSION_NAME=dv_session
SESSION_LIFETIME=7200
SESSION_SECURE=true

UPLOAD_MAX_BYTES=5242880
UPLOAD_MAX_FILES=6

MAIL_ENABLED=true
MAIL_FROM=no-reply@tu-dominio.mx
MAIL_FROM_NAME="Diagnóstico Veterinario"

AUTO_VERIFY_USERS=false
```

- `APP_URL` debe incluir `https://` y **no** terminar en `/`.
- `APP_DEBUG=false` en producción.
- `SESSION_SECURE=true` porque el sitio usa HTTPS.
- No pegues estas credenciales en el repositorio, capturas o mensajes.

### Permisos

Por SSH o File Manager:

```bash
chmod -R 775 storage
chmod -R 775 public/uploads
```

El usuario de PHP debe poder escribir en `storage/sessions`, `storage/logs` y `public/uploads/cases`.

## 5. Migraciones y administrador

Como la base está en Neon, puedes ejecutar las migraciones **desde tu equipo** apuntando al mismo `.env` de producción. No necesitas terminal en Hostinger.

Si tu plan tiene SSH:

```bash
cd ~/domains/tu-dominio.mx/diagnostico
php bin/check.php
php bin/migrate.php
php bin/seed-resources.php
php bin/create-admin.php
```

Si no hay SSH, ejecuta esos comandos en local con las URLs de producción y luego sube sólo el código.

## 6. Verificación end-to-end

1. Abre `https://portal.tu-dominio.mx/salud`.
   - Debe devolver `"status":"ok"` y `"database":{"ok":true,...}`.
2. Abre `/` y comprueba que cargan los casos.
3. Inicia sesión con el administrador.
4. Abre `/admin` y verifica la cola editorial.
5. Crea un caso de prueba, envíalo a revisión, apruébalo y compruébalo en `/casos`.
6. Sube una imagen JPG/PNG/WebP y confirma que se muestra.
7. Prueba el registro de un veterinario y la verificación desde `/admin/usuarios`.
8. Abre `/recursos/vetdatahub`, `/recursos/kit` y `/recursos?goal=datos&species=canino&topic=parvovirus`; descarga una plantilla DOCX.
9. Abre `/panel/entrar`, entra con una cuenta verificada y prueba pacientes, citas, recetas, inventario y una venta.
10. Abre `/vetdatahub` y `/peteval`; comprueba los 87 datasets y los 4,999 registros integrados.
11. Guarda un recurso en favoritos con una cuenta verificada y descarga un caso en PDF y Word.
12. Comprueba `/sitemap.xml` y `/robots.txt`.
13. Si tienes token de API Market, define `CEDULA_VALIDATION_ENABLED=true` y `CEDULA_API_TOKEN`; prueba `/registro` con una cédula real.
14. Si la IA está desplegada, define `AI_SERVICE_URL` y `AI_SERVICE_TOKEN` y prueba `/apoyo-diagnostico/ia`.
15. Revisa `storage/logs/app-*.log` si algo falla.

## 7. Correo

La recuperación de contraseña usa `mail()` de PHP. En Hostinger suele funcionar si el dominio tiene DNS y SSL correctos, pero puede caer en spam.

- Crea el buzón `no-reply@tu-dominio.mx` en hPanel.
- Configura SPF y DKIM en el DNS.
- Prueba `/recuperar` con una cuenta real.
- Si necesitas mayor entregabilidad, sustituye `App\Core\Mailer` por un cliente SMTP (PHPMailer o la API de tu proveedor). El proyecto no incluye Composer a propósito para simplificar el despliegue.

## 8. Seguridad posterior al despliegue

- Elimina `storage/demo-credentials.txt` si subiste una copia local.
- No ejecutes `bin/seed.php --demo` en producción.
- Si conservas contenido demostrativo, mantén las cuentas ficticias con contraseñas aleatorias o elimínalas.
- Rota la contraseña de Neon si alguna vez se compartió por chat o correo.
- Configura copias de seguridad o *point-in-time restore* en Neon.
- Revisa que `.env` no sea accesible por URL: `https://tu-dominio.mx/.env` debe devolver 403/404.
- Comprueba que `https://tu-dominio.mx/app/Core/Database.php` no se descargue ni ejecute.
- Activa el modo `APP_DEBUG=false`.

## 9. Actualizaciones

```bash
git pull                 # o sube el nuevo ZIP
php bin/migrate.php      # desde local o SSH
```

Las migraciones son idempotentes y se registran en `schema_migrations`. Nunca edites una migración ya aplicada; agrega una nueva.

## 10. Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| 500 al abrir `/salud` | `.env` ausente o `DATABASE_URL` inválida | Revisa `.env` y `storage/logs`. |
| `could not connect to server` | Hosting bloquea 5432 | `DB_DRIVER=http`. |
| `Class "PDO" not found` | PHP sin PDO | Activa PDO o usa `DB_DRIVER=http`. |
| Imágenes no se guardan | Permisos | `chmod -R 775 public/uploads`. |
| CSS/JS no cargan | Document root incorrecto | Debe apuntar a `public/` o funcionar el `.htaccess` raíz. |
| 403 en todo el sitio | Reglas `.htaccess` incompatibles | Revisa logs y prueba la opción A. |
| Correo no llega | `mail()`/DNS | Revisa SPF/DKIM y la carpeta de spam. |
| Error de sesión | `storage/sessions` sin escritura | Corrige permisos o usa el directorio de sesiones de PHP. |

## 11. Checklist final

- [ ] `APP_ENV=production` y `APP_DEBUG=false`.
- [ ] `APP_URL` con HTTPS correcto.
- [ ] `DB_DRIVER` probado (`http` o `pdo`).
- [ ] Migraciones aplicadas.
- [ ] Administrador real creado y contraseña fuerte.
- [ ] `storage/` y `public/uploads/` escribibles.
- [ ] SSL activo y redirección HTTP → HTTPS.
- [ ] `/salud` en verde.
- [ ] Registro, caso, imagen, revisión y publicación probados.
- [ ] Fichas de recursos, kit DOCX y glosario accesibles.
- [ ] Favoritos, descarga PDF con imágenes y exportación Word probados.
- [ ] Catálogo VetDataHub integrado (87 datasets) visible.
- [ ] PetEVAL integrado (4,999 registros) visible y descargable.
- [ ] IA de urgencia conectada al Space (opcional) y probada.
- [ ] Verificación de cédula con API Market (opcional) probada.
- [ ] CRM `/panel/entrar` probado con pacientes, citas, recetas, inventario y venta.
- [ ] `.env` inaccesible desde el navegador.
- [ ] Copia de seguridad de Neon configurada.

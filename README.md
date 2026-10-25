# Diagnóstico Veterinario

**Portal de casos clínicos para médicos veterinarios de México.** Una biblioteca colaborativa para documentar experiencias reales, consultar diagnóstico diferencial y acercarse a los datos de salud animal.

[![CI](https://github.com/Mikato8/Diagnostico-veterinario/actions/workflows/ci.yml/badge.svg)](https://github.com/Mikato8/Diagnostico-veterinario/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%2B-336791?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Licencia](https://img.shields.io/badge/c%C3%B3digo-MIT-0d5c4d)](LICENSE)

![Inicio de Diagnóstico Veterinario](docs/capturas/home.png)

## Qué es

Diagnóstico Veterinario es una aplicación web en español (México) pensada para la práctica clínica cotidiana. Permite publicar casos con una estructura editorial, buscar en el archivo por especie, estado, desenlace o signo, y usar una herramienta de apoyo diagnóstico que ordena diferenciales por similitud de hallazgos.

El proyecto está construido con **PHP 8 + PostgreSQL** y se apoya en **Neon** para la base de datos. La app puede conectarse por TCP (`pdo_pgsql`) o por el **endpoint SQL sobre HTTPS de Neon**, lo que permite operar en hostings compartidos como Hostinger aunque no tengan la extensión `pdo_pgsql` habilitada.

> El contenido es educativo. No sustituye el juicio clínico, la valoración presencial ni las guías oficiales.

## Demo en vivo

**<https://moccasin-louse-938329.hostingersite.com>** — despliegue de demostración en Hostinger, con base de datos en Neon. El dominio es temporal; puede cambiarse sin tocar el código actualizando `APP_URL`.

## Funcionalidades

### Para médicos veterinarios

- Registro con cédula profesional, institución y estado; verificación manual por el comité editorial.
- Biblioteca de casos con búsqueda full-text en español, filtros por especie, estado, desenlace, año y tema.
- Casos con historia clínica completa: anamnesis, exploración, estudios, diferenciales, diagnóstico final, tratamiento, desenlace, discusión y referencias.
- Imágenes del caso con procesamiento y re-codificación para eliminar metadatos.
- Comentarios clínicos entre colegas verificados y reacciones «me resultó útil».
- Apoyo diagnóstico por similitud: especie + signos clínicos → diferenciales ponderados, signos clave faltantes, urgencia y alerta zoonótica.
- Fichas de enfermedades con etiología, diagnóstico, tratamiento, prevención y pronóstico.
- Directorio curado de datasets, APIs, repositorios y sistemas de vigilancia (VetCompass, SAVSNET, WAHIS, NAHMS, SENASICA y más).
- **VetDataHub integrado:** catálogo navegable de 87 datasets y 15 categorías, importado desde [Mikato8/VetDataHub](https://github.com/Mikato8/VetDataHub).
- **PetEVAL integrado:** explorador de 4,999 registros anonimizados del split de prueba, con búsqueda por texto, filtros ICD-11 y entidades resaltadas; datos y artículo descargables.
- **IA de urgencia (prototipo):** clasificador SetFit + ONNX en español con cuatro niveles y reglas de seguridad. Se puede probar en `/apoyo-diagnostico/ia` y dentro de cada caso en `/casos/{slug}/ia-urgencia`. Requiere un servicio Python externo; no sustituye el juicio clínico.
- **Recursos explicados en español:** cada ficha interna dice para qué sirve, cómo empezar, qué necesitas y cómo solicitar acceso, sin asumir que sabes programar.
- **Kit descargable:** plantilla de caso clínico, checklist de consentimiento, hoja de alta, consentimiento imprimible, carta de solicitud de datos, guía de citas y guía rápida en DOCX.
- **Glosario para no especialistas:** dataset, repositorio, API, licencia, CSV y otros términos explicados en lenguaje clínico.
- **Recomendador por objetivo, especie y tema/enfermedad:** sugiere fichas del portal (enfermedad y casos) y recursos externos según lo que el veterinario necesita.
- **Directorio de revistas de acceso abierto:** buscador en `/recursos/revistas` con DOAJ, REVIVEC, Latindex, Redalyc y revistas de México, Iberoamérica y el mundo.
- **Verificación de cédula:** integración opcional con API Market/SEP para contrastar cédula y nombre completo; si no hay token, el registro pasa a revisión manual.
- **Eliminación de cuenta:** el usuario puede borrar su cuenta desde el perfil; se anonimizan sus datos y su contenido publicado queda como “Cuenta eliminada”.
- **Foto de perfil:** subida de JPG/PNG/WebP con vista previa, recorte circular y opción de quitarla.
- **CRM de clínica:** panel en `/panel/entrar` con pacientes, citas, recetas, inventario y ventas, aislado por cuenta.
- **Favoritos por veterinario:** cada cuenta puede guardar recursos para consultarlos después.
- **Descarga del caso en PDF o Word:** genera un documento clínico con datos del paciente, secciones, referencias, licencia e imágenes del caso.
- Perfil profesional, cambio de contraseña y recuperación por correo.

### Para el comité editorial

- Cola de revisión con decisión de publicar, solicitar cambios o rechazar.
- Historial de revisiones y comentarios al autor.
- Verificación de cuentas y asignación de roles (`veterinario`, `editor`, `admin`).
- Administración del directorio de recursos.
- Moderación de comentarios y registro de auditoría.

### Técnicas

- Interfaz responsive, accesible y sin frameworks de frontend.
- Búsqueda con `tsvector` y `websearch_to_tsquery('spanish', …)`; similitud con `pg_trgm`.
- Sesiones seguras, CSRF, rate limiting de acceso, contraseñas con `password_hash`, roles y ownership.
- Validación de archivos por MIME real, tamaño, dimensiones y re-codificación con GD.
- CSP, `X-Frame-Options`, `Referrer-Policy`, cookies `HttpOnly`/`SameSite`.
- Dos controladores de base de datos: `pdo_pgsql` y HTTP de Neon, con la misma capa de consultas.
- Migraciones y seeds idempotentes; CI con PostgreSQL real.

## Capturas

| Caso clínico | Apoyo diagnóstico |
| --- | --- |
| ![Caso clínico](docs/capturas/caso.png) | ![Apoyo diagnóstico](docs/capturas/diagnostico.png) |

| Archivo de casos | Panel editorial |
| --- | --- |
| ![Casos](docs/capturas/casos.png) | ![Panel editorial](docs/capturas/admin.png) |

| Recursos explicados | Ficha de recurso |
| --- | --- |
| ![Recursos](docs/capturas/recursos.png) | ![Ficha de recurso](docs/capturas/recurso-detalle.png) |

| Kit descargable | Recomendador por especie y tema |
| --- | --- |
| ![Kit](docs/capturas/kit.png) | ![Recomendador](docs/capturas/recomendador.png) |

| Caso en PDF |
| --- |
| ![Caso en PDF](docs/capturas/caso-pdf.png) |

| Catálogo VetDataHub |
| --- |
| ![VetDataHub](docs/capturas/vetdatahub.png) |

| PetEVAL integrado | Registro PetEVAL |
| --- | --- |
| ![PetEVAL](docs/capturas/peteval.png) | ![Registro PetEVAL](docs/capturas/peteval-detalle.png) |

| IA de urgencia |
| --- |
| ![IA de urgencia](docs/capturas/ai-urgencia.png) |

| CRM clínico | Panel de la clínica |
| --- | --- |
| ![CRM clínico](docs/capturas/crm-login.png) | ![Panel de la clínica](docs/capturas/crm-dashboard.png) |

| Revistas de acceso abierto |
| --- |
| ![Revistas de acceso abierto](docs/capturas/revistas.png) |

## Stack

| Capa | Tecnología |
| --- | --- |
| Lenguaje | PHP 8.2+ (sin Composer ni dependencias de runtime) |
| Base de datos | PostgreSQL 15+ en Neon (Lakebase Postgres) |
| Acceso a datos | `pdo_pgsql` o endpoint SQL HTTPS de Neon |
| Servidor web | Apache/LiteSpeed en Hostinger; `php -S` en local |
| Frontend | HTML semántico, CSS propio, JavaScript sin dependencias |
| Correo | `mail()` de PHP / SMTP del hosting (configurable) |
| CI | GitHub Actions + PostgreSQL 16 |

## Arquitectura

```text
Navegador
   │ HTTPS
   ▼
Hostinger · PHP 8
   ├─ public/index.php  (front controller)
   ├─ app/Core          (router, request, DB, auth, CSRF, vistas)
   ├─ app/Controllers   (casos, cuenta, panel, diagnóstico, recursos)
   ├─ app/Repositories  (SQL de lectura/escritura)
   └─ app/Views         (plantillas en español)
          │
          ├─ pdo_pgsql (TCP 5432, TLS)
          └─ HTTPS /sql (fallback sin pdo_pgsql)
                   │
                   ▼
          Neon PostgreSQL
```

- **Front controller** en `public/index.php`; las rutas viven en `routes/web.php`.
- **Sin ORM**: SQL explícito y parametrizado con marcadores `$1` (el driver PDO los traduce a marcadores nombrados).
- **Sin Composer**: autoloader PSR-4 mínimo y utilidades propias. Esto simplifica el despliegue por FTP.
- **Separación por capas**: controladores delgados, repositorios con las consultas, vistas sin lógica de negocio.
- **Migraciones** versionadas en `database/migrations` y seeds en `database/seeds`.

### Modelo de datos

| Tabla | Propósito |
| --- | --- |
| `users` | Cuentas, rol, estado de verificación, cédula, institución. |
| `cases` | Caso clínico, autor, especie, estado, contenido, estado editorial, `tsvector`. |
| `case_images`, `case_tags` | Imágenes y temas del caso. |
| `case_comments`, `case_reactions` | Discusión clínica y reconocimiento. |
| `case_reviews` | Decisiones y retroalimentación editorial. |
| `diseases`, `clinical_signs`, `disease_signs` | Catálogo clínico y pesos para el motor de similitud. |
| `species`, `breeds`, `states` | Catálogos biológicos y geográficos de México. |
| `resources` | Directorio externo curado. |
| `audit_log` | Trazabilidad de acciones editoriales. |

## Inicio rápido (local)

### Requisitos

- PHP 8.2+ con `pdo_pgsql`, `curl`, `mbstring`, `fileinfo`, `gd` e `intl`.
- PostgreSQL 15+ o una base Neon.
- `psql` para migraciones y respaldos (recomendado).

### 1. Clonar y configurar

```bash
git clone https://github.com/Mikato8/Diagnostico-veterinario.git
cd Diagnostico-veterinario
cp .env.example .env
```

Edita `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8080
DATABASE_URL="postgresql://usuario:clave@host-pooler/neondb?sslmode=require&channel_binding=require"
DATABASE_URL_UNPOOLED="postgresql://usuario:clave@host/neondb?sslmode=require&channel_binding=require"
DB_DRIVER=pdo
SESSION_SECURE=false
```

### 2. Migrar y sembrar

```bash
php bin/migrate.php
DEMO_PASSWORD='UnaClaveLocalSegura2026!' php bin/seed.php --demo
```

El seed imprime las cuentas demo. La contraseña se toma de `DEMO_PASSWORD`; si no la defines, se genera una aleatoria y se muestra una sola vez. La cuenta administrativa es `admin@diagnostico.vet`.

### 3. Arrancar

```bash
php bin/serve.php
# http://127.0.0.1:8080
```

### 4. Verificar

```bash
php bin/check.php
./tests/smoke.sh
```

## Scripts

| Comando | Descripción |
| --- | --- |
| `php bin/migrate.php` | Aplica migraciones pendientes (usa `DATABASE_URL_UNPOOLED`/`DATABASE_URL`). |
| `php bin/seed.php` | Siembra catálogos, enfermedades y recursos. |
| `php bin/seed.php --demo` | Añade usuarios y siete casos demostrativos marcados como ficticios. |
| `php bin/seed-resources.php` | Actualiza sólo las fichas y guías del directorio de recursos. |
| `php bin/create-admin.php` | Crea o restablece un administrador verificado. |
| `php bin/check.php` | Diagnóstico de PHP, extensiones, base de datos y permisos. |
| `php bin/serve.php` | Servidor de desarrollo en `127.0.0.1:8080`. |
| `./tests/smoke.sh` | Pruebas HTTP de humo contra un servidor local. |
| `python3 tools/import_vetdatahub.py /ruta/VetDataHub database/data/vetdatahub.json` | Regenera el catálogo integrado desde el repositorio VetDataHub. |
| `python3 tools/import_peteval_bucket.py /ruta/test.parquet database/data/peteval.json` | Regenera el explorador PetEVAL (requiere pandas y pyarrow). |
| `./tools/sync_peteval_bucket.sh` | Descarga el bucket PetEVAL, actualiza Parquet/PDF/README y regenera el JSON. |

## Despliegue en Hostinger

La guía detallada está en **[docs/DEPLOY-HOSTINGER.md](docs/DEPLOY-HOSTINGER.md)**. Resumen:

1. La base de datos vive en **Neon**, no en el hosting compartido. Hostinger sólo ejecuta PHP.
2. Verifica que el plan tenga PHP 8.2+ y SSL. Si no tiene `pdo_pgsql`, define `DB_DRIVER=http`; la app usará HTTPS.
3. Sube el proyecto con el documento raíz en `public/` (recomendado) o usando el `.htaccess` raíz.
4. Crea `.env` fuera de `public/` con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` y las URLs de Neon.
5. Ejecuta migraciones y crea el administrador real **antes** de abrir el sitio.
6. Comprueba `/salud`, inicia sesión y publica un caso de prueba.

## IA de urgencia (SetFit + ONNX)

El apartado `/apoyo-diagnostico/ia` y el panel de cada caso (`/casos/{slug}/ia-urgencia`) usan un clasificador **SetFit** exportado a **ONNX int8** y servido por FastAPI desde `ai-service/`. Hostinger no puede ejecutar Python/onnxruntime, así que el servicio se despliega aparte (Hugging Face Space) y el portal lo consume por HTTPS con `AI_SERVICE_URL` y `AI_SERVICE_TOKEN`.

- Cuatro niveles: emergencia, urgente, consulta programada y preventivo.
- En un caso, el texto se prellena con especie, sexo, edad, motivo, anamnesis y exploración; el diagnóstico final no se envía.
- Reglas de seguridad para signos de alarma evidentes.
- Modelo entrenado con ejemplos sintéticos: es un **prototipo educativo**, no un dispositivo clínico.
- Guía: `ai-service/DEPLOY.md`; entrenamiento: `ai-service/training/`.

## Panel clínico (CRM)

El panel `/panel` es un CRM ligero para una clínica: pacientes y tutores, agenda de citas, recetas imprimibles, inventario con mínimos y ventas con descuento de existencias. Cada cuenta ve únicamente sus propios registros (`owner_id`). El acceso es `/panel/entrar`, con registro propio en `/panel/registro` y la misma cuenta del portal; "Mantener la sesión abierta" extiende la sesión 30 días.

## Seguridad

- Contraseñas con `password_hash` (bcrypt/Argon según PHP) y `password_needs_rehash`.
- CSRF en todos los `POST`, rotación de token al iniciar sesión.
- Cookies `HttpOnly`, `SameSite=Lax`, `Secure` cuando `APP_URL` es HTTPS.
- Rate limiting de login (12 intentos fallidos / 15 minutos por correo o IP).
- Autorización por rol y ownership en cada acción editorial.
- Validación de MIME real, tamaño, dimensiones y re-codificación de imágenes; `.htaccess` bloquea PHP en uploads.
- Consultas parametrizadas; sin concatenación de entrada del usuario.
- CSP y cabeceras de seguridad en cada respuesta.
- Auditoría de acciones editoriales.

Para reportar una vulnerabilidad, consulta [SECURITY.md](SECURITY.md).

## Pruebas

```bash
php -l app/Core/App.php                 # lint puntual
find app config routes bin -name '*.php' -print0 | xargs -0 -n1 php -l
./tests/smoke.sh
```

El workflow de GitHub Actions levanta PostgreSQL 16, aplica migraciones, siembra datos, arranca PHP y ejecuta las pruebas de humo.

## Estructura del repositorio

```text
app/
  Controllers/       Controladores HTTP
  Core/              Router, DB, auth, CSRF, vistas, uploads
  Repositories/      Consultas SQL
  Support/           Helpers e iconos SVG
  Views/             Plantillas en español
bin/                 CLI: migraciones, seeds, admin, checks
config/app.php       Configuración desde .env
database/
  migrations/        Esquema versionado
  seeds/             Catálogos, enfermedades, recursos, guías y demo
docs/                Arquitectura, despliegue y capturas
public/              Document root (front controller, assets, uploads, descargas)
routes/web.php       Mapa de rutas
storage/             Logs, sesiones y caché (fuera del dominio público)
tests/               Pruebas de humo
tools/               Generador reproducible del kit DOCX
```

## Roadmap

- [ ] Revisión por pares con asignación de revisores y notificaciones por correo.
- [x] Recursos explicados, kit descargable y glosario para no especialistas.
- [ ] Exportación de casos a PDF y RIS/BibTeX.
- [ ] Búsqueda semántica con embeddings sobre el archivo clínico.
- [ ] API REST pública con tokens y límites de uso.
- [ ] Panel de indicadores epidemiológicos por estado y especie.
- [ ] Integración con WAHIS/SENASICA para alertas zoosanitarias.
- [ ] Flujo de consentimiento informado descargable y firmado.

## Licencia

- **Código:** MIT. Ver [LICENSE](LICENSE).
- **Contenido editorial y fichas:** CC BY-NC-SA 4.0. Ver [CONTENIDO-LICENCIA.md](CONTENIDO-LICENCIA.md).
- **Casos publicados:** conservan la licencia elegida por su autor (por defecto CC BY-NC-SA 4.0).
- **Casos demostrativos:** ficticios, marcados como tales, con fines de demostración del flujo editorial.

## Créditos y referencias

La arquitectura y el alcance se inspiraron en proyectos y recursos abiertos como [VetAPI](https://github.com/Mikato8/VetAPI_Laravel), [PetCloud](https://github.com/francisco-correa/pet-cloud), [VetDataHub](https://github.com/Mikato8/VetDataHub), [PetEVAL](https://huggingface.co/buckets/Mikato8/PetEVAL-bucket), [VetCompass](https://www.rvc.ac.uk/vetcompass) y [SAVSNET](https://www.liverpool.ac.uk/savsnet/). El directorio completo está en el portal, sección **Recursos**. El catálogo VetDataHub se distribuye bajo licencia MIT; cada dataset enlazado conserva su propia licencia y condiciones de acceso. PetEVAL se distribuye con licencia Apache-2.0 y textos anonimizados; no deben usarse para reidentificar personas, animales o establecimientos.

---

Hecho en México · Español · Para la práctica veterinaria.

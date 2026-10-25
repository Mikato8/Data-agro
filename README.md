# AgroDiagnóstico

**Biblioteca colaborativa de casos de campo y apoyo diagnóstico para ingenieros agrónomos de México.**

[![CI](https://github.com/Mikato8/Data-agro/actions/workflows/ci.yml/badge.svg)](https://github.com/Mikato8/Data-agro/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%2B-336791?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Licencia](https://img.shields.io/badge/c%C3%B3digo-MIT-0d5c4d)](LICENSE)

## Qué es

AgroDiagnóstico es un portal en español de México para documentar casos agronómicos, consultar fichas de plagas y enfermedades, ordenar diagnósticos diferenciales por similitud de síntomas y encontrar recursos técnicos y datos abiertos. Está dirigido a ingenieros agrónomos, técnicos agrícolas, docentes y estudiantes que necesitan aprender de la experiencia de campo de otros colegas.

El proyecto usa **PHP 8.2+ sin Composer ni frameworks**, **PostgreSQL 15+ en Neon** y HTML, CSS y JavaScript propios. La aplicación puede conectarse por TCP con `pdo_pgsql` o por el endpoint SQL sobre HTTPS de Neon, lo que permite operar en hostings compartidos como Hostinger.

> **Aviso educativo.** El contenido y las herramientas de este portal son de apoyo y no sustituyen la valoración en campo, el criterio profesional, el diagnóstico de laboratorio ni las recomendaciones de las autoridades sanitarias. Verifica siempre dosis, registros y normativa aplicable.

## Demo

**<https://khaki-gnu-402937.hostingersite.com>**

Despliegue de demostración en Hostinger con base Neon. Incluye siete casos ficticios marcados como **Demostrativo**, siete cuentas de ejemplo, datos de CRM para la cuenta administradora (dos parcelas, dos visitas, dos recomendaciones, cuatro insumos y una venta que descuenta existencias) y el contenido sembrado del portal. La IA de riesgo fitosanitario no está conectada en esta demo: la sección lo indica y remite al apoyo diagnóstico por síntomas.

## Funcionalidades

### Para ingenieros agrónomos

- Registro con cédula profesional, institución, estado y semblanza; verificación manual por el comité editorial o automática contra el Registro Nacional de Profesionistas.
- Casos de campo con cultivo, variedad, ciclo, etapa fenológica, superficie, incidencia, municipio, sistema de producción, historial, inspección, análisis, diferenciales, diagnóstico, manejo, resultado, discusión y referencias.
- Búsqueda full-text en español y filtros por cultivo, estado, resultado, año y tema, con orden por relevancia, fecha o vistas.
- Imágenes del caso validadas por tipo real, tamaño y dimensiones, y re-codificadas con GD para reducir metadatos y tamaño.
- Comentarios de colegas verificados y reacciones «me resultó útil».
- Apoyo diagnóstico ponderado: se elige cultivo y hasta 20 síntomas; el portal muestra diferenciales ordenados por similitud, síntomas clave que faltan por explorar y alertas de problemas cuarentenarios.
- Fichas de plagas, enfermedades, virus, bacterias, nematodos, deficiencias y trastornos abióticos, con etiología, diagnóstico, manejo, prevención y pronóstico.
- Directorio curado de 64 recursos con ficha interna que explica para qué sirve cada uno, cómo empezar, qué requisitos tiene y cuál es su licencia; incluye 15 conjuntos de datos, 2 APIs, 16 revistas y 12 instituciones, entre otras categorías.
- Datos abiertos en `/agrodatos`: conjuntos de datos, APIs y recursos de aprendizaje automático enlazados a su fuente original.
- Recomendador de recursos por objetivo y tema, con el cultivo como contexto de la consulta.
- Directorio de revistas y plataformas de acceso abierto, con énfasis en México e Iberoamérica.
- Kit documental de siete plantillas DOCX y un ZIP: caso, consentimiento, checklist, hoja de recomendación, solicitud de datos, guía de recursos y referencias.
- Glosario de datos, licencias y términos técnicos.
- Exportación de cada caso a PDF y DOCX, con datos del cultivo, secciones, referencias, licencia e imágenes.
- Favoritos de recursos por cuenta.
- Perfil profesional, cambio de contraseña, recuperación por correo y eliminación de cuenta con anonimización.
- CRM de campo en `/panel`: parcelas, productores, visitas, recomendaciones, insumos y ventas con descuento de existencias.
- Clasificador opcional de riesgo fitosanitario (prototipo externo) en `/apoyo-diagnostico/ia` y dentro de cada caso.

### Para el comité editorial

- Cola de casos en revisión con decisión de publicar, solicitar cambios o rechazar.
- Historial de revisiones y comentarios al autor.
- Verificación manual de cuentas, suspensión y asignación de roles (`agronomo`, `editor`, `admin`).
- Administración del directorio de recursos y moderación de comentarios.
- Registro de auditoría de las acciones editoriales y de cuenta.

### Para investigación, docencia y divulgación

- Archivo de casos con estructura editorial y licencia declarada por el autor.
- Fichas fitosanitarias reutilizables con referencias y alertas cuarentenarias.
- Catálogo de datos y publicaciones de acceso abierto con atribución a la fuente.
- Material descargable para documentar casos y solicitar acceso a datos.

## Capturas

| Portada | Archivo de casos | Caso de campo |
| --- | --- | --- |
| ![Portada](docs/capturas/home.png) | ![Archivo de casos](docs/capturas/casos.png) | ![Caso de campo](docs/capturas/caso.png) |

| Apoyo diagnóstico | Plagas y enfermedades | Ficha fitosanitaria |
| --- | --- | --- |
| ![Apoyo diagnóstico](docs/capturas/diagnostico.png) | ![Plagas y enfermedades](docs/capturas/enfermedades.png) | ![Ficha fitosanitaria](docs/capturas/enfermedad.png) |

| Recursos | Datos abiertos | Revistas |
| --- | --- | --- |
| ![Recursos](docs/capturas/recursos.png) | ![Datos abiertos](docs/capturas/datos-abiertos.png) | ![Revistas](docs/capturas/revistas.png) |

| Kit documental | Acceso al CRM | Panel de campo |
| --- | --- | --- |
| ![Kit documental](docs/capturas/kit.png) | ![Acceso al CRM](docs/capturas/crm-login.png) | ![Panel de campo](docs/capturas/crm-panel.png) |

| Parcelas | Recomendación | Nosotros |
| --- | --- | --- |
| ![Parcelas](docs/capturas/crm-parcelas.png) | ![Recomendación](docs/capturas/crm-recomendacion.png) | ![Nosotros](docs/capturas/nosotros.png) |

| Glosario | Perfil profesional | Inventario |
| --- | --- | --- |
| ![Glosario](docs/capturas/glosario.png) | ![Perfil profesional](docs/capturas/perfil.png) | ![Inventario](docs/capturas/crm-inventario.png) |

| Panel editorial | Casos del panel |
| --- | --- |
| ![Panel editorial](docs/capturas/admin.png) | ![Casos del panel](docs/capturas/admin-casos.png) |

| Portada móvil | Caso móvil | Panel móvil |
| --- | --- | --- |
| ![Portada móvil](docs/capturas/mobile-home.png) | ![Caso móvil](docs/capturas/mobile-caso.png) | ![Panel móvil](docs/capturas/mobile-panel.png) |

Las capturas corresponden a la demo con contenido ficticio. No contienen datos de productores reales.

## Stack

| Capa | Tecnología |
| --- | --- |
| Lenguaje | PHP 8.2+ con `declare(strict_types=1)`; sin Composer ni frameworks |
| Base de datos | PostgreSQL 15+ en Neon; `pg_trgm`, `citext` y búsqueda full-text en español |
| Acceso a datos | `pdo_pgsql` o endpoint SQL HTTPS de Neon (`DB_DRIVER=auto\|pdo\|http`) |
| Servidor web | Apache o LiteSpeed en Hostinger; `php -S` en local |
| Frontend | HTML semántico, CSS propio y JavaScript sin dependencias |
| Correo | `mail()` de PHP, activable con `MAIL_ENABLED=true` |
| CI | GitHub Actions con PostgreSQL 16, PHP 8.3, migraciones, seed y pruebas de humo |
| Kit documental | Python + `python-docx` para regenerar los DOCX y el ZIP |

## Arquitectura resumida

```text
Navegador
   │ HTTPS
   ▼
Hostinger · public_html (documento raíz)
   ├─ .htaccess raíz ──► public/
   ├─ public/index.php        front controller
   ├─ app/Core               router, request, respuesta, sesión, CSRF, BD, vistas
   ├─ app/Controllers        casos, cuenta, diagnóstico, recursos, CRM, admin
   ├─ app/Repositories       SQL explícito y parametrizado
   ├─ app/Services           cédula y cliente de IA
   └─ app/Views              plantillas PHP con layouts
          │
          ├─ pdo_pgsql (TCP 5432, TLS) ──► Neon PostgreSQL
          └─ HTTPS /sql (fallback)     ──► Neon PostgreSQL
```

- **Front controller** en `public/index.php`; las rutas se declaran en `routes/web.php`.
- **Sin ORM**: consultas SQL propias con marcadores `$1`, `$2`, … y repositorios por dominio.
- **Sin Composer**: autoloader mínimo para `App\` y utilidades propias; el despliegue se hace por ZIP o `rsync`.
- **Dos transportes**: PDO para TCP y un cliente HTTPS para el endpoint SQL de Neon; la capa de datos expone la misma interfaz.
- **Migraciones y seeds** versionados en `database/`; la aplicación no ejecuta migraciones desde el hosting.
- **Contenido editorial** separado del código: catálogos, problemas, recursos, guías y demo viven en `database/seeds/`.

La explicación detallada, el flujo de una petición y las decisiones de diseño están en **[docs/ARQUITECTURA.md](docs/ARQUITECTURA.md)**.

## Modelo de datos

| Tabla | Propósito |
| --- | --- |
| `users` | Cuenta, rol, estado, cédula, institución, estado de la República y datos de verificación. |
| `cases` | Caso agronómico con cultivo, variedad, ubicación, contenido, estado editorial y `search_vector`. |
| `case_images`, `case_tags` | Imágenes y temas del caso. |
| `case_comments`, `case_reactions` | Discusión y reacciones de la comunidad. |
| `case_reviews` | Decisiones y retroalimentación del comité editorial. |
| `crops`, `varieties`, `states`, `specialties` | Catálogos agronómicos y geográficos. |
| `problems`, `symptoms`, `problem_crops`, `problem_symptoms` | Fichas fitosanitarias y perfiles de síntomas con peso y marca de síntoma clave. |
| `resources`, `resource_favorites` | Directorio externo con guías internas y favoritos por cuenta. |
| `farm_plots`, `farm_visits`, `farm_recommendations` | CRM: parcelas, agenda y recomendaciones. |
| `farm_inputs`, `farm_sales`, `farm_sale_items` | Inventario, ventas y partidas; la venta descuenta existencias. |
| `audit_log`, `login_attempts`, `password_resets` | Auditoría, control de intentos y recuperación de acceso. |

Las migraciones están en [`database/migrations/`](database/migrations) y los catálogos sembrados en [`database/seeds/`](database/seeds).

## Inicio rápido local

### Requisitos

- PHP 8.2+ con `pdo_pgsql`, `curl`, `mbstring`, `fileinfo`, `gd`, `intl` y `zip`.
- PostgreSQL 15+ local o una base Neon.
- `psql` es opcional, pero útil para respaldos y consultas de diagnóstico.

### 1. Clonar y configurar

```bash
git clone https://github.com/Mikato8/Data-agro.git
cd Data-agro
cp .env.example .env
```

Edita `.env` para desarrollo local:

```dotenv
APP_NAME="AgroDiagnóstico"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8080
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es_MX

DATABASE_URL="postgresql://usuario:clave@127.0.0.1:5432/data_agro?sslmode=disable"
DATABASE_URL_UNPOOLED="postgresql://usuario:clave@127.0.0.1:5432/data_agro?sslmode=disable"
DB_DRIVER=pdo

SESSION_NAME=agro_session
SESSION_LIFETIME=7200
SESSION_SECURE=false
```

Si usas Neon en local, copia la **conexión directa** (sin `-pooler`) y conserva `sslmode=require`. En un cliente libpq moderno no necesitas `options`; en Hostinger sí, como se explica en la guía de despliegue.

### 2. Migrar y sembrar

```bash
php bin/migrate.php
DEMO_PASSWORD='<clave-local-segura>' php bin/seed.php --demo
```

El seed carga 32 estados, 46 cultivos, 238 variedades, 13 especialidades, 79 síntomas, 50 problemas fitosanitarios (28 hongos, 11 plagas insectiles, 3 virus, 1 bacteria, 1 nematodo, 3 deficiencias y 3 trastornos abióticos; 3 cuarentenarios: HLB, Fusarium R4T y picudo del algodonero), 64 recursos con guía y, con `--demo`, 7 cuentas, 7 casos ficticios y datos de CRM para la cuenta administradora (2 parcelas, 2 visitas, 2 recomendaciones, 4 insumos y 1 venta con descuento de existencias). La contraseña demo se toma de `DEMO_PASSWORD`; si no la defines, se genera una aleatoria y se imprime una sola vez. La cuenta administrativa es `admin@agrodiagnostico.mx`.

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

`tests/smoke.sh` usa el caso demo `cogollero-maiz-temporal-sinaloa`; requiere haber sembrado con `--demo` o ajustar la URL y el slug.

## Scripts

| Comando | Descripción |
| --- | --- |
| `php bin/migrate.php` | Aplica las migraciones pendientes y las registra en `schema_migrations`. Usa `DATABASE_URL`. |
| `php bin/seed.php` | Siembra catálogos, problemas fitosanitarios y recursos con guías. Es idempotente. |
| `php bin/seed.php --demo` | Añade siete cuentas, siete casos y datos de CRM demostrativos. No usar en producción. |
| `php bin/seed-resources.php` | Actualiza sólo el directorio de recursos y sus guías internas. |
| `php bin/create-admin.php` | Crea o restablece un administrador verificado; pide contraseña si no se pasa. |
| `php bin/check.php` | Revisa PHP, extensiones, base de datos, migraciones, conteos y permisos. |
| `php bin/serve.php` | Levanta el servidor de desarrollo en `127.0.0.1:8080`. |
| `./tests/smoke.sh` | Pruebas HTTP de humo contra `BASE_URL` (por defecto `http://127.0.0.1:8080`). |
| `python3 tools/generar_kit_recursos.py` | Regenera los siete DOCX y el ZIP del kit en `public/descargas/`. |

## Despliegue en Hostinger

La guía completa está en **[docs/DEPLOY-HOSTINGER.md](docs/DEPLOY-HOSTINGER.md)**. Resumen:

1. La base vive en **Neon**; Hostinger sólo ejecuta PHP. Crea el proyecto, la base y el usuario en Neon.
2. Migra y siembra **desde local** apuntando a Neon; la aplicación no ejecuta migraciones desde el hosting.
3. Sube el proyecto a `public_html` y deja el `.htaccess` raíz que redirige a `public/`, o apunta el documento raíz a `public/` si tu plan lo permite.
4. Crea `.env` en la raíz, fuera de `public/`, con `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE=true` y `DB_DRIVER=pdo`.
5. En Hostinger usa el **endpoint directo** de Neon y añade `&options=endpoint%3D<endpoint-id>` a la URL; el cliente libpq del hosting es antiguo y necesita ese parámetro para resolver el endpoint.
6. Verifica `/salud`, crea el administrador real y prueba registro, caso, imagen, revisión, PDF, DOCX y CRM.

## IA de riesgo fitosanitario

El portal incluye una interfaz en `/apoyo-diagnostico/ia` y un panel por caso en `/casos/{slug}/ia-emergencia`. El cliente PHP (`App\Services\RiskAiClient`) llama por HTTPS a un servicio **FastAPI** externo que vive en [`ai-service/`](ai-service/README.md).

- El motor principal es un **clasificador determinista de reglas léxicas en español**, sin ONNX, transformers ni GPU; devuelve cuatro niveles: `emergencia`, `urgente`, `consulta` y `preventivo`.
- Opcionalmente puede mezclar un modelo TF-IDF + regresión logística entrenado con ejemplos sintéticos; no es necesario para funcionar.
- Expone `/health` y `/classify`; el portal envía como máximo 2,000 caracteres y aplica un intervalo mínimo de 2 segundos entre solicitudes.
- Se configura con `AI_SERVICE_URL` y, si el servicio exige autenticación, `AI_SERVICE_TOKEN`. Si `AI_SERVICE_URL` está vacío, la sección se muestra desactivada.
- Hostinger no ejecuta Python: el servicio se despliega aparte (Hugging Face Space, VPS o contenedor Docker). La guía está en [`ai-service/DEPLOY.md`](ai-service/DEPLOY.md).

Es un prototipo educativo y orientativo; no emite diagnósticos oficiales ni sustituye la determinación de laboratorio o la notificación a la autoridad fitosanitaria.

## CRM de parcelas

El panel `/panel` es un CRM ligero para la operación de campo. Se entra por `/panel/entrar` con la misma cuenta del portal; el registro redirige a `/registro?next=panel`. La opción «Mantener la sesión abierta» extiende la sesión 30 días.

- **Parcelas:** cultivo, variedad, superficie, municipio, estado, riego, suelo, productor y notas.
- **Visitas:** agenda, duración, motivo y estado (programada, confirmada, realizada, cancelada).
- **Recomendaciones:** diagnóstico, insumos prescritos e indicaciones; se imprimen con los datos de la parcela y la firma del agrónomo.
- **Insumos:** SKU, categoría, unidad, precio, existencias y mínimo; alerta de stock bajo.
- **Ventas:** partidas, forma de pago y descuento automático de existencias mediante la función PostgreSQL `farm_create_sale`.

El seed `--demo` crea para la cuenta administradora dos parcelas con productor, dos visitas, dos recomendaciones, cuatro insumos y una venta con dos partidas; todos los datos son ficticios.

Cada consulta filtra por `owner_id`: una cuenta sólo ve sus propios registros. Al eliminar la cuenta se borran los datos del CRM y se anonimiza el perfil.

## Seguridad

- Contraseñas con `password_hash` y rehash automático; mínimo de 10 caracteres en el portal y 12 al crear un administrador.
- CSRF global en todos los métodos mutantes, con rotación del token al iniciar sesión.
- Cookies `HttpOnly`, `SameSite=Lax` y `Secure` cuando el sitio opera con HTTPS.
- Límite de 12 intentos fallidos de acceso por correo o IP en 15 minutos.
- Autorización por rol y por propiedad del recurso: `agronomo`, `editor` y `admin`; el CRM filtra por `owner_id`.
- Consultas siempre parametrizadas; salida escapada con `e()`.
- Validación de MIME real, tamaño, dimensiones y re-codificación de imágenes con GD; `.htaccess` bloquea la ejecución de scripts en `public/uploads/`.
- Cabeceras de seguridad y CSP en cada respuesta; HSTS cuando la petición es HTTPS.
- Registro de auditoría para acciones editoriales y de cuenta; los logs redactan claves sensibles.
- `.env` fuera de `public/`, permisos restrictivos y exclusión de secretos en el paquete de despliegue.

Para reportar una vulnerabilidad, consulta [SECURITY.md](SECURITY.md).

## Pruebas

```bash
find app config routes bin -name '*.php' -print0 | xargs -0 -n1 php -l
php bin/check.php
php bin/serve.php &
./tests/smoke.sh
```

El workflow de GitHub Actions levanta PostgreSQL 16, configura PHP 8.3, aplica migraciones, siembra con `--demo`, ejecuta `bin/check.php` y corre las pruebas de humo. Las pruebas cubren portada, casos, PDF, DOCX, apoyo diagnóstico, fichas, recursos, kit, glosario, revistas, datos abiertos, descargas, páginas de registro y acceso, `/salud`, sitemap y robots.

## Estructura del repositorio

```text
app/
  Controllers/       Controladores HTTP por dominio
  Core/              Router, request, respuesta, BD, auth, CSRF, vistas, uploads
  Repositories/      Consultas SQL y armado de datos
  Services/          Verificación de cédula y cliente de IA
  Support/           Helpers, iconos, PDF, DOCX y seeder de recursos
  Views/             Plantillas y layouts en español
bin/                 CLI: migraciones, seeds, administrador, chequeo y servidor
config/app.php       Configuración leída de .env
database/
  migrations/        Esquema versionado
  seeds/             Catálogos, problemas, recursos, guías y demo
docs/                Arquitectura, despliegue, verificación y capturas
public/              Documento raíz: front controller, assets, descargas y uploads
routes/web.php       Mapa de rutas
storage/             Logs, sesiones y caché (fuera del dominio público)
tests/smoke.sh       Pruebas HTTP de humo
tools/               Generador del kit documental
ai-service/          Microservicio FastAPI opcional
```

## Roadmap

- [x] Recursos explicados, kit documental, glosario y recomendador por objetivo y tema.
- [x] Exportación de casos a PDF y DOCX, favoritos y CRM de parcelas.
- [x] Verificación automática de cédula como opción configurable.
- [ ] Notificaciones por correo de las decisiones editoriales.
- [ ] Asignación de revisores y revisión por pares.
- [ ] Exportación de referencias a RIS o BibTeX.
- [ ] API pública con tokens y límites de uso.
- [ ] Panel de indicadores fitosanitarios por estado y cultivo.
- [ ] Integración de alertas oficiales de vigilancia fitosanitaria.
- [ ] Revisión editorial continua de textos, etiquetas y fichas.

## Licencias

- **Código:** MIT. Ver [LICENSE](LICENSE).
- **Contenido editorial y fichas:** CC BY-NC-SA 4.0. Ver [CONTENIDO-LICENCIA.md](CONTENIDO-LICENCIA.md).
- **Casos publicados:** conservan la licencia elegida por su autor; la opción predeterminada es CC BY-NC-SA 4.0.
- **Casos demostrativos:** ficticios, marcados como tales y distribuidos con el contenido editorial.
- **Recursos externos:** cada ficha enlaza a su fuente y conserva su propia licencia y condiciones de acceso.

---

Hecho en México · Español · Para la práctica agronómica.

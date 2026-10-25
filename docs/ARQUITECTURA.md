# Arquitectura de AgroDiagnóstico

Este documento describe cómo está construido el portal, qué decisiones sostienen el diseño y dónde extenderlo. La referencia principal es el código de `app/`, `routes/web.php`, `database/migrations/` y `database/seeds/`.

## 1. Principios

1. **Operar en hosting compartido.** PHP puro, sin Composer, sin frameworks y con un front controller; el despliegue se resuelve con ZIP o `rsync`.
2. **PostgreSQL real, SQL explícito.** Sin ORM que oculte consultas, índices o búsqueda full-text. El esquema se versiona con migraciones.
3. **Dos transportes, una sola API de datos.** `pdo_pgsql` cuando está disponible y el endpoint HTTPS de Neon cuando el hosting no expone PostgreSQL.
4. **El caso como unidad editorial.** Búsqueda, fichas, apoyo diagnóstico, recursos y CRM giran alrededor del caso de campo y del catálogo fitosanitario.
5. **Privacidad por diseño.** Consentimiento del productor, anonimización obligatoria para publicar, mínimo dato personal y anonimización al eliminar la cuenta.
6. **Contenido en archivos, no en la base del código.** Catálogos, problemas, recursos y demo viven en `database/seeds/` y se cargan con scripts idempotentes.

## 2. Vista de capas

```text
public/index.php
   │
   ▼
App\Core\App::run()
   ├─ Request::capture()
   ├─ Csrf::validate()
   ├─ routes/web.php ──► Router::dispatch()
   │                         │
   │                         ▼
   │                    Controller (app/Controllers)
   │                         ├─ Repository (app/Repositories)
   │                         │      └─ Database (fachada)
   │                         │            ├─ PdoDatabase
   │                         │            └─ HttpDatabase
   │                         └─ View (app/Views + layouts)
   └─ Response::send() ──► cabeceras de seguridad + cuerpo
```

### Componentes principales

| Componente | Responsabilidad |
| --- | --- |
| `App\Core\App` | Ciclo de la petición, CSRF global, carga de rutas, manejo de errores y envío de la respuesta. |
| `App\Core\Router` | Registro de rutas GET/POST/PUT/DELETE, parámetros `{slug}` y errores 404/405. |
| `App\Core\Request` | Normaliza método, ruta, query, cuerpo, archivos, IP y detección de HTTPS. |
| `App\Core\Response` | Respuestas HTML, JSON, redirecciones y cabeceras de seguridad. |
| `App\Core\Auth` | Sesión, roles, verificación, rate limit de acceso y redirección a la ruta pretendida. |
| `App\Core\Csrf` | Token por sesión y validación de métodos mutantes. |
| `App\Core\Validator` | Reglas declarativas y mensajes en español. |
| `App\Core\Upload` | Validación de imágenes, re-codificación con GD y nombres aleatorios. |
| `App\Core\View` | Render de plantillas con layout e inyección de usuario, flash y ruta actual. |
| `App\Core\Cache` | Caché de archivos con TTL en `storage/cache/`. |
| `App\Core\Logger` | Log diario en `storage/logs/app-AAAA-MM-DD.log` con redacción de claves sensibles. |
| `App\Core\SqlSplitter` | Divide migraciones respetando `$$`, comillas y comentarios. |

## 3. Flujo de una petición

1. Apache o LiteSpeed recibe la petición. Si el documento raíz es `public_html`, el `.htaccess` raíz reescribe hacia `public/`; si el documento raíz ya es `public/`, el `.htaccess` de `public/` envía las rutas no existentes a `index.php`.
2. `public/index.php` carga `app/bootstrap.php`, que define `BASE_PATH`, registra el autoloader de `App\`, carga `.env`, fija la zona horaria y la configuración, configura sesión y base de datos.
3. `App::run()` captura la petición con `Request::capture()` y valida el token CSRF de cualquier método distinto de GET, HEAD u OPTIONS.
4. Se carga `routes/web.php`, que recibe el `Router` y registra las rutas.
5. `Router::dispatch()` compara método y expresión regular; si encuentra la ruta, instancia el controlador y le pasa la petición y los parámetros.
6. El controlador valida permisos, llama a uno o varios repositorios y prepara los datos de la vista.
7. `BaseController::view()` renderiza la plantilla con `View::render()`, que inyecta `currentUser`, `flashMessages` y `currentPath`, y envuelve el contenido en el layout.
8. `Response::send()` agrega las cabeceras de seguridad, imprime el cuerpo y, si la petición es HTTPS, añade HSTS. Las excepciones `HttpException` se convierten en la página de error correspondiente; el resto se registra y devuelve un 500 genérico.

## 4. Front controller, bootstrap y router

### Bootstrap

`app/bootstrap.php` hace, en orden:

- Define `BASE_PATH` como la raíz del proyecto.
- Carga helpers e iconos.
- Registra un autoloader que traduce `App\Algo\Clase` a `app/Algo/Clase.php`; no hay `vendor/`.
- Carga `.env` con `Env::load()`; las variables ya presentes en el entorno tienen prioridad, lo que permite sobreescribir la conexión sin editar el archivo.
- Carga `config/app.php` en `Config`.
- Configura zona horaria, `mb_internal_encoding`, locale y manejo de errores.
- Inicia la sesión en `storage/sessions` si el directorio es escribible; usa cookies `HttpOnly`, `SameSite=Lax` y `Secure` según configuración.
- Configura la fachada de base de datos.

### Router

Las rutas se declaran con métodos explícitos:

```php
$router->get('/casos', [CaseController::class, 'index']);
$router->get('/casos/{slug}', [CaseController::class, 'show']);
$router->post('/casos/{slug}/comentarios', [CaseController::class, 'comment']);
```

`Router::add()` normaliza el patrón, convierte `{param}` en un grupo nombrado y guarda método, patrón, regex y handler. En `dispatch()`:

- `HEAD` se trata como `GET`.
- Si la ruta coincide pero el método no, se acumulan los métodos permitidos y se lanza un 405.
- Si no hay coincidencia, se lanza un 404.
- Los parámetros nombrados se pasan al controlador en orden.

`Request::capture()` admite `_method` para simular PUT/PATCH/DELETE desde un formulario, aunque el portal usa casi siempre POST. También descuenta el directorio base del `SCRIPT_NAME`, lo que permite instalar la aplicación en un subdirectorio.

## 5. Controladores, repositorios y vistas

### Controladores

- `BaseController` concentra helpers de vista, redirección, errores, validación, auditoría, folio, slug, etiquetas, imágenes y permisos de edición.
- `AuthController`, `ProfileController` y `PanelController` gestionan cuenta y acceso.
- `CaseController` cubre listado, detalle, alta, edición, envío a revisión, comentarios, reacciones, imágenes, PDF, DOCX e IA por caso.
- `DiagnosticController` cubre apoyo por síntomas, fichas fitosanitarias y clasificador de riesgo.
- `ResourceController`, `AgroDatosController` y `PageController` cubren recursos, datos abiertos y páginas institucionales.
- `AdminController` cubre el panel editorial, usuarios, recursos y moderación.
- `HealthController` y `SitemapController` cubren infraestructura y SEO.

### Repositorios

| Repositorio | Contenido |
| --- | --- |
| `CaseRepository` | Búsqueda con filtros, detalle, similares, CRUD editorial, revisiones, comentarios, reacciones, estadísticas y tags. |
| `ProblemRepository` | Fichas, síntomas por cultivo, motor de similitud, problemas relacionados y destacados. |
| `ResourceRepository` | Directorio, guías, favoritos, recomendador, relacionados y CRUD administrativo. |
| `CatalogRepository` | Estados, cultivos, variedades y especialidades, con caché. |
| `UserRepository` | Cuentas, perfil, paginación, roles, estados y anonimización. |
| `AgroDatosRepository` | Vista de recursos de categorías `dataset`, `api` y `ml`. |

Los repositorios devuelven arrays asociativos. No hay entidades ni hidratación automática: el controlador y la vista conocen las claves que necesitan. Esto mantiene el SQL visible y evita una capa de mapeo que no aporta al tamaño del proyecto.

### Vistas

- `app/Views/layouts/` contiene los layouts `app`, `admin`, `panel` y `plain`.
- Cada vista se renderiza con `View::render('casos/show', $data, 'app')`; el layout recibe `$content`.
- La salida dinámica se escapa con `e()`; las vistas no ejecutan lógica de negocio.
- Los partials reutilizables son `case-card`, `pagination`, `flash`, `avatar` y `password-toggle`.

## 6. Capa de datos: doble driver

`App\Core\DatabaseInterface` define la API mínima:

```php
select(string $sql, array $params = []): array
first(string $sql, array $params = []): ?array
value(string $sql, array $params = []): mixed
execute(string $sql, array $params = []): int
insert(string $sql, array $params = []): ?array
transaction(callable $callback): mixed
raw(string $sql): void
driverName(): string
ping(): bool
```

`App\Core\Database` es una fachada estática que elige el driver una sola vez:

- `DB_DRIVER=pdo` fuerza `PdoDatabase`.
- `DB_DRIVER=http` fuerza `HttpDatabase`.
- `DB_DRIVER=auto` usa PDO si `pdo_pgsql` está cargado; si la conexión falla con `DatabaseException`, cae a HTTP.

### PdoDatabase

- Parsea `DATABASE_URL` con `parse_url()` y construye un DSN `pgsql:`.
- Lee `sslmode`, `channel_binding` y `options` de la query string y los agrega al DSN.
- Usa `PDO::ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false` y timeout de 15 segundos.
- Traduce los marcadores `$1`, `$2`, … a marcadores nombrados `:p1`, `:p2`, … porque `pdo_pgsql` no acepta `$n` directamente.
- Ofrece transacciones reales con `beginTransaction`, `commit` y `rollBack`.

El detalle de `options` es importante en Hostinger: el cliente libpq del hosting puede ser antiguo y no enviar SNI. Neon permite indicar el endpoint en el DSN con `options=endpoint=<endpoint-id>`, que `PdoDatabase` conserva. La URL debe llevar el valor codificado, por ejemplo `options=endpoint%3Dep-polished-thunder-b5980ml2`.

### HttpDatabase

- Toma el host de la URL y hace `POST https://HOST/sql`.
- Envía `Neon-Connection-String`, `Neon-Raw-Text-Output` y `Neon-Array-Mode`.
- Espera un JSON con `fields`, `rows` y `rowCount`; convierte filas posicionales en arrays asociativos.
- Reintenta una vez las lecturas ante error de red o 5xx; las escrituras no se reintentan.
- No admite transacciones interactivas: `transaction()` lanza `DatabaseException`.
- Los procesos que necesitan atomicidad usan una sola sentencia SQL, una CTE o una función de PostgreSQL, como `farm_create_sale`.

### Comparación

| Criterio | `pdo_pgsql` | HTTPS de Neon |
| --- | --- | --- |
| Requisito | Extensión `pdo_pgsql` y puerto 5432 | `curl` y salida HTTPS |
| Transacciones | Sí | No interactivas |
| Latencia | Menor | Una petición por consulta |
| SNI/libpq antiguo | Requiere `options=endpoint=...` | No aplica |
| Uso recomendado | Hostinger con PDO y Neon directo | Hosting sin `pdo_pgsql` o con 5432 bloqueado |

## 7. Modelo de datos

### Diagrama de relaciones

```text
states 1 ── N users
states 1 ── N cases
states 1 ── N farm_plots

crops 1 ── N varieties
crops 1 ── N cases
crops N ── M problems      (problem_crops)
problems N ── M symptoms   (problem_symptoms: weight, is_key)

users 1 ── N cases
users 1 ── N case_comments
users 1 ── N case_reviews
users 1 ── N case_reactions
users 1 ── N resource_favorites

cases 1 ── N case_images
cases 1 ── N case_tags
cases 1 ── N case_comments
cases 1 ── N case_reviews
cases 1 ── N case_reactions

resources 1 ── N resource_favorites

users 1 ── N farm_plots
farm_plots 1 ── N farm_visits
farm_plots 1 ── N farm_recommendations
farm_plots 1 ── N farm_sales
farm_sales 1 ── N farm_sale_items
farm_inputs 1 ── N farm_sale_items

users 1 ── N audit_log
```

### Grupos de tablas

**Catálogos**

- `states`: código de tres letras, nombre y región.
- `crops`: código, nombre, nombre científico, emoji, familia, ciclo y orden.
- `varieties`: variedad por cultivo, única por `(crop_id, name)`.
- `specialties`: especialidades profesionales.
- `symptoms`: síntomas con categoría, descripción y orden.

**Conocimiento fitosanitario**

- `problems`: ficha con slug, nombre, nombre científico, categoría, resumen, etiología, diagnóstico, manejo, prevención, pronóstico, `reportable`, `risk` y referencias.
- `problem_crops`: relación N:M entre problema y cultivo.
- `problem_symptoms`: relación N:M con `weight` de 1 a 5 e `is_key`; en los seeds, `is_key` se marca cuando el peso es mayor o igual a 4.

**Cuentas y acceso**

- `users`: identidad, rol, estado, cédula, institución, estado de la República, semblanza y campos `license_verified_*`.
- `login_attempts`: historial de intentos para el rate limit.
- `password_resets`: token hasheado, vigencia y uso único.

**Casos**

- `cases`: folio, slug, autor, cultivo, variedad, ciclo, etapa, superficie, incidencia, ubicación, sistema, relato, manejo, resultado, consentimiento, anonimización, licencia, estado editorial y `search_vector`.
- `case_images`, `case_tags`, `case_comments`, `case_reviews`, `case_reactions`: satélites del caso.

**Recursos**

- `resources`: título, descripción, URL única, categoría, organización, país, acceso, licencia, idioma, etiquetas, destacado y guía interna (`slug`, `what_for`, `how_to`, `requirements`, `guide`, `action_label`, `reviewed_at`).
- `resource_favorites`: favoritos por usuario.

**CRM**

- `farm_plots`: parcela y productor, aislada por `owner_id`.
- `farm_visits`: agenda y estado de la visita.
- `farm_recommendations`: diagnóstico, insumos e indicaciones; puede ligarse a una visita.
- `farm_inputs`: inventario con SKU, categoría, unidad, precio, existencias y mínimo.
- `farm_sales`, `farm_sale_items`: venta y partidas; `plot_id` e `input_id` pueden ser nulos si el registro original se elimina.

**Infraestructura**

- `schema_migrations`: versión aplicada y fecha.
- `audit_log`: usuario, acción, entidad, metadatos, IP y fecha.

### Convenciones y restricciones

- Claves primarias `bigint generated always as identity` o `smallint`/`integer` para catálogos.
- `public_id uuid` en `users` y `cases` para identificadores no secuenciales.
- `citext` en correos; `timestamptz` en fechas; `numeric` en superficies, precios y totales.
- Restricciones `CHECK` para roles, estados, ciclos, etapas, resultados, licencias, categorías y riesgos.
- Trigger `set_updated_at()` en tablas editables.
- Borrado lógico con `deleted_at` en `users` y `cases`; las consultas públicas lo filtran.
- `case_folio_seq` genera folios `AG-AAAA-NNNN` en el seed y `CV-AAAA-NNNN` desde el formulario.
- `cases_published_ready` impide publicar sin consentimiento, anonimización, diagnóstico y `published_at`.
- Índices parciales para casos publicados, destacados y verificaciones; índices GIN para búsqueda y trigramas.

## 8. Búsqueda

`cases.search_vector` es una columna generada y almacenada:

```sql
to_tsvector('spanish',
  coalesce(title, '') || ' ' || coalesce(chief_complaint, '') || ' ' ||
  coalesce(crop_history, '') || ' ' || coalesce(field_inspection, '') || ' ' ||
  coalesce(lab_tests, '') || ' ' || coalesce(differentials, '') || ' ' ||
  coalesce(final_diagnosis, '') || ' ' || coalesce(discussion, '') || ' ' ||
  coalesce(references_text, '')
)
```

La consulta combina búsqueda full-text y coincidencia parcial:

```sql
where c.search_vector @@ websearch_to_tsquery('spanish', $1)
   or c.title ilike $2
   or c.final_diagnosis ilike $3
order by ts_rank_cd(c.search_vector, websearch_to_tsquery('spanish', $1)) desc
```

- `websearch_to_tsquery` tolera la sintaxis que escribe un usuario y evita inyección en el motor de búsqueda.
- `ts_rank_cd` ordena por relevancia cuando se elige esa opción.
- Los índices GIN cubren `search_vector`, `title` y `final_diagnosis` con `pg_trgm`.
- Los filtros de cultivo, estado, resultado, año, etiqueta y destacado se agregan con parámetros posicionales.
- La paginación usa `count(*) over()` para obtener el total sin una segunda consulta.
- Los casos similares usan `similarity(final_diagnosis, $1)` de `pg_trgm`, el mismo cultivo y excluyen el caso actual; se muestran hasta tres.

## 9. Motor de similitud

El apoyo diagnóstico no es un modelo probabilístico: es una heurística de coincidencia entre los síntomas seleccionados y el perfil de cada problema.

### Procedimiento

1. El usuario elige un cultivo. `ProblemRepository::symptomsForCrops()` devuelve sólo los síntomas ligados a problemas de ese cultivo, agrupados por categoría (follaje, tallo, raíz, fruto, flor, planta, suelo, manejo).
2. Puede seleccionar hasta 20 síntomas; el controlador deduplica y descarta identificadores inválidos.
3. `ProblemRepository::match()` considera sólo problemas asociados al cultivo y que compartan al menos un síntoma seleccionado.
4. Para cada candidato calcula síntomas coincidentes, síntomas totales del perfil, peso coincidente, peso total, signos coincidentes y signos clave que faltan.
5. Ordena por `matched_weight desc, matched_count desc` y devuelve como máximo 12 candidatos.
6. Calcula el puntaje en PHP y lo redondea a entero.

### Fórmula

```text
cobertura = matched_count / selected_count
perfil    = matched_count / total_signs
score     = round(min(1, 0.65 * cobertura + 0.35 * perfil) * 100)
```

Donde:

- `matched_count`: síntomas seleccionados que también están en el perfil del problema.
- `selected_count`: síntomas que eligió el usuario.
- `total_signs`: síntomas del perfil del problema.

Ejemplo: si el usuario selecciona 8 síntomas, el problema tiene 10 y coinciden 5:

```text
cobertura = 5 / 8  = 0.625
perfil    = 5 / 10 = 0.500
score     = (0.65 * 0.625 + 0.35 * 0.500) * 100 = 58
```

El resultado se muestra como porcentaje de similitud, no como probabilidad. Los pesos de 1 a 5 influyen en el orden de los candidatos (`matched_weight`), pero no en la fórmula del porcentaje. Los síntomas clave se calculan con `problem_symptoms.is_key`; los que no fueron seleccionados se muestran como «faltan por explorar». Si el problema tiene `reportable = true`, la interfaz muestra una alerta cuarentenaria y recomienda acudir a la autoridad fitosanitaria.

## 10. Seguridad

### Sesión y autenticación

- Sesión PHP nativa con `use_strict_mode`, `use_only_cookies`, `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS.
- Regeneración de ID al iniciar sesión y al cerrar sesión.
- Contraseñas con `password_hash()` y rehash automático cuando PHP lo considera necesario.
- Cookies de «recordarme» extienden la sesión 30 días; el resto usa `SESSION_LIFETIME`.
- Rate limit: 12 intentos fallidos en 15 minutos por correo o IP, contados en `login_attempts`.
- Recuperación con token aleatorio, hash SHA-256 en base, vigencia de 60 minutos y uso único.

### Autorización

| Capacidad | Regla |
| --- | --- |
| Crear borrador o editar el propio borrador | Sesión iniciada y propiedad del caso. |
| Enviar a revisión | Cuenta `verificado` y propiedad del caso; el editor también puede. |
| Comentar | Cuenta `verificado`. |
| Reaccionar, guardar favoritos, usar el CRM | Sesión iniciada. |
| Revisar casos y moderar comentarios | Rol `editor` o `admin`. |
| Gestionar usuarios y recursos | Rol `admin`. |
| Editar cualquier caso | Rol `editor` o `admin`. |

El CRM no exige cuenta verificada, pero sí sesión. Cada consulta de `farm_*` filtra por `owner_id`; las acciones de venta validan que la parcela y los insumos pertenezcan a la cuenta.

### CSRF, XSS y cabeceras

- `Csrf::validate()` se ejecuta antes de despachar cualquier método distinto de GET, HEAD u OPTIONS; un token inválido produce 419.
- El token se regenera al iniciar y cerrar sesión y se imprime con `csrf_field()`.
- Toda salida dinámica pasa por `e()` (`htmlspecialchars` con `ENT_QUOTES` y UTF-8).
- `Response::send()` agrega CSP (`default-src 'self'`, `script-src 'self'`), `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy` y `Permissions-Policy`.
- HSTS se agrega sólo cuando `Request::isSecure()` es verdadero.

### Archivos

- `Upload::image()` valida error de subida, tamaño máximo (5 MB por defecto), MIME real con `finfo`, dimensiones mínimas de 20 × 20 y máximas de 9000 px.
- Formatos permitidos: JPG, PNG y WebP.
- Re-codifica con GD, reduce el lado mayor a 2400 px (600 px para avatares) y vuelve a escribir el archivo, lo que elimina metadatos y cargas maliciosas embebidas.
- El nombre se genera con `Str::filename()`; el archivo se guarda con permisos 0644.
- `public/uploads/.htaccess` bloquea la ejecución de PHP, PHTML, PHAR, CGI y Perl.
- Si GD no está disponible, se guarda el archivo original ya validado; por eso `gd` es un requisito de despliegue.

### Datos y privacidad

- El caso sólo se publica con `producer_consent` y `anonymized`; la restricción existe tanto en la validación como en la base.
- El formulario pide no incluir datos identificables del productor o de la parcela.
- Al eliminar la cuenta, `UserRepository::anonymizeAndDelete()` borra los datos del CRM, sustituye nombre, correo, cédula, institución, semblanza y avatar, y marca `deleted_at`.
- Los casos y comentarios publicados permanecen atribuidos a «Cuenta eliminada».
- El CURP que devuelve la verificación de cédula no se almacena.
- `Logger` redacta claves que contengan `password`, `token`, `secret`, `database_url` o `connection`.

## 11. Decisiones de diseño

| Decisión | Motivo | Consecuencia |
| --- | --- | --- |
| Sin Composer | Menos fricción en hosting compartido y auditoría sencilla. | Autoloader propio; no se instalan librerías de terceros. |
| Sin framework | El dominio es acotado y el equipo controla el ciclo HTTP. | Más código explícito en router, request y respuesta. |
| SQL explícito | Control de índices, full-text, trigramas y planes de consulta. | Las consultas viven en repositorios y requieren revisión manual. |
| Dos drivers | PostgreSQL en hostings que sólo ofrecen PHP. | Hay que probar ambos caminos y recordar que HTTP no tiene transacciones interactivas. |
| Endpoint directo de Neon en Hostinger | Evita el problema de SNI y de `search_path` vacío del pooler con libpq antiguo. | La URL incluye `options=endpoint=...`; no se usa `-pooler` en ese despliegue. |
| Contenido en seeds | El catálogo se versiona, se revisa y se reproduce. | Los cambios editoriales requieren volver a sembrar o editar desde el panel. |
| Vistas server-side | Menos JavaScript, mejor accesibilidad y SEO. | La interactividad se limita a mejoras progresivas. |
| Caché de archivos | No se requiere Redis ni Memcached. | El caché es local a cada servidor y se invalida con `Cache::flush()`. |
| Casos demo marcados | Mostrar el flujo sin confundirlos con productores reales. | Se excluyen de producción salvo que se siembre `--demo`. |

## 12. Integración con la IA

```text
Navegador
   │ POST /apoyo-diagnostico/ia  o  /casos/{slug}/ia-emergencia
   ▼
DiagnosticController / CaseController
   │ RiskAiClient::classify($texto)
   ▼
FastAPI (ai-service/)
   ├─ POST /classify
   ├─ motor de reglas en español
   ├─ reglas de seguridad (HLB, Fusarium R4T, cuarentena, pérdida total)
   └─ modelo TF-IDF + regresión logística opcional
   ▼
JSON: label, label_es, description, color, confidence, scores, safety_rule, evidence
```

- El portal no ejecuta Python ni carga modelos: sólo consume un servicio externo por HTTPS.
- `AI_SERVICE_URL` define la base; `AI_SERVICE_TOKEN` se envía como `Authorization: Bearer` cuando no está vacío.
- `RiskAiClient` usa 6 segundos de conexión, 20 de tiempo total y no sigue redirecciones.
- El texto se limita a 2,000 caracteres en el portal y a 4,000 en el servicio; hay un intervalo mínimo de 2 segundos entre solicitudes por sesión.
- Si el servicio responde 401, 503 o un error de red, el controlador muestra un mensaje y registra un warning; no bloquea el resto del portal.
- Si `AI_SERVICE_URL` está vacío, la vista muestra que la IA no está configurada y sugiere el apoyo diagnóstico por síntomas.
- El servicio es un prototipo educativo; sus reglas son deliberadamente conservadoras y no sustituyen la determinación de laboratorio ni la notificación oficial.

La documentación del servicio, sus endpoints y sus límites está en [`ai-service/README.md`](../ai-service/README.md) y su despliegue en [`ai-service/DEPLOY.md`](../ai-service/DEPLOY.md).

## 13. CRM de parcelas

El CRM comparte la autenticación del portal, pero tiene su propio layout y su propio flujo de entrada:

- `/panel/entrar` muestra el formulario; `/panel/registro` redirige a `/registro?next=panel`.
- `/panel` exige sesión; si no hay usuario, guarda la ruta pretendida y redirige a `/panel/entrar`.
- La sesión extendida se controla con la cookie `dv_remember` y el parámetro `remember`.

El seed `--demo` crea para la cuenta administradora dos parcelas con productor, dos visitas, dos recomendaciones, cuatro insumos y una venta con dos partidas (urea y fungicida), que descuenta existencias. Es contenido ficticio para mostrar el panel.

### Aislamiento y venta

- Todas las tablas `farm_*` tienen `owner_id` y todas las consultas lo incluyen.
- `farm_create_sale(p_owner_id, p_plot_id, p_payment_method, p_items jsonb)` valida que la parcela pertenezca al dueño, que los insumos existan, estén activos y tengan existencias suficientes; agrupa partidas repetidas, calcula el total, inserta la venta y las partidas, y descuenta el stock.
- La función se ejecuta en una sola sentencia, por lo que funciona también con `HttpDatabase`, que no admite transacciones interactivas.
- El panel muestra visitas del día, stock bajo (`stock <= min_stock`) y ventas del día.
- La recomendación se imprime desde el navegador con los datos de la parcela y la firma del agrónomo.

## 14. Recursos, kit y datos abiertos

- Cada recurso externo conserva su URL, pero el portal agrega una ficha interna con `what_for`, `how_to`, `requirements`, `guide` y `action_label`.
- `/recursos` filtra por texto, categoría y acceso; el recomendador ordena por objetivo, tema y categoría, y muestra fichas de problemas relacionadas cuando hay tema.
- `/recursos/kit` sirve siete DOCX y un ZIP desde `public/descargas/`; los archivos se regeneran con `tools/generar_kit_recursos.py`.
- `/recursos/glosario` y `/recursos/revistas` son páginas de contenido curado; las revistas se agrupan por directorios, México, Iberoamérica, internacionales y actualización.
- `/agrodatos` no es un repositorio separado: es una vista de los recursos de categorías `dataset`, `api` y `ml`, con filtros por categoría, fuente y texto.

## 15. Cómo extender

### 15.1 Agregar un cultivo

1. Añade la tupla en `database/seeds/catalog.php`, sección `crops`: `[code, nombre, nombre_científico, emoji, familia, ciclo, sort_order]`.
2. Agrega sus variedades en `catalog.php`, sección `varieties`, bajo la clave del código.
3. Si el cultivo participa en fichas existentes, agrega su código a `crops` en `database/seeds/problems.php`.
4. Ejecuta `php bin/seed.php` (sin `--demo` en producción).
5. Verifica `/apoyo-diagnostico?crops=<code>`, `/casos?crops=<code>` y el formulario de caso. No se requiere migración: `crops` y `varieties` ya existen.

### 15.2 Agregar un problema fitosanitario

1. Agrega un arreglo al final de `database/seeds/problems.php` con `slug`, `name`, `scientific_name`, `category`, `summary`, `etiology`, `diagnosis`, `management`, `prevention`, `prognosis`, `reportable`, `risk`, `reference_code`, `references_text`, `crops` y `symptoms`.
2. Si necesitas un síntoma nuevo, agrégalo en `catalog.php` con `[slug, nombre, categoría, descripción]`.
3. Asigna un peso de 1 a 5 a cada síntoma. En los seeds, un peso mayor o igual a 4 marca el síntoma como clave.
4. Marca `reportable => true` sólo para problemas cuarentenarios o de notificación obligatoria.
5. Ejecuta `php bin/seed.php`; el seeder actualiza la ficha y reemplaza sus relaciones de cultivos y síntomas.
6. Verifica `/enfermedades/<slug>` y el resultado del apoyo diagnóstico con el cultivo y los síntomas correspondientes.

### 15.3 Agregar una ruta

1. Declara la ruta en `routes/web.php` con el método correcto:

```php
$router->get('/mi-seccion', [MiController::class, 'index']);
$router->post('/mi-seccion', [MiController::class, 'store']);
```

2. Crea el controlador en `app/Controllers/` y extiéndelo de `BaseController` si necesitas vistas, validación, auditoría o imágenes.
3. Crea la vista en `app/Views/` y renderízala con `$this->view('mi-seccion/index', $data)`.
4. Si la ruta es pública, agrégala a `SitemapController` y, si aplica, a la navegación del layout.
5. Para formularios POST, incluye `csrf_field()`; el token se valida de forma global.
6. Agrega la ruta a `tests/smoke.sh` si debe cubrirse en CI.

### 15.4 Agregar una tabla o columna

1. Crea una migración nueva en `database/migrations/` con el siguiente prefijo numérico; no edites una migración ya aplicada.
2. Usa `if not exists`, `create index if not exists` y nombres descriptivos.
3. Aplica la migración desde local o SSH con `php bin/migrate.php`; la aplicación no la ejecuta desde el navegador.
4. Agrega el repositorio o los métodos necesarios y actualiza las vistas.
5. Prueba en una rama de Neon o en una base local antes de producción.

## 16. Operación y observabilidad

- **Salud:** `GET /salud` devuelve `status`, versión de PHP, driver, resultado del ping, permisos de uploads, hora y zona horaria. Responde 503 si el ping falla. No comprueba que las tablas existan, así que un `/salud` en verde no sustituye una prueba de `/casos`.
- **Chequeo:** `php bin/check.php` valida PHP, extensiones, `.env`, conexión, migraciones, conteos, directorios y configuración de cédula/IA.
- **Logs:** `storage/logs/app-AAAA-MM-DD.log`; los errores no controlados se registran y el usuario recibe un mensaje genérico.
- **Caché:** `storage/cache/`; los seeds y las acciones editoriales llaman a `Cache::flush()`. Si se modifica contenido directamente en la base, hay que limpiar el caché.
- **Pruebas:** `tests/smoke.sh` recorre las rutas públicas, descargas, `/salud`, `/sitemap.xml` y `/robots.txt`; el CI usa PostgreSQL 16 y PHP 8.3.

## 17. Notas de implementación

Estas notas documentan diferencias entre el comportamiento actual y algunas suposiciones habituales. No son fallos de arquitectura, pero conviene tenerlas presentes al operar el portal.

- **URL de migraciones.** `config/app.php` expone `DATABASE_URL_UNPOOLED`, pero `Database` y `bin/migrate.php` usan `DATABASE_URL`. Para migrar contra Neon hay que apuntar `DATABASE_URL` al endpoint directo; cambiar sólo `DATABASE_URL_UNPOOLED` no mueve las migraciones.
- **Reset de verificación.** `UserRepository::updateProfile()` limpia los campos `license_verified_*` cuando cambian el nombre o la cédula y, si la cuenta tiene el rol `agronomo`, la devuelve a `pendiente` para una nueva revisión. El estado puede verificarse en `/admin/usuarios`.
- **Categorías de recursos en el panel.** El formulario editorial y la base comparten la misma lista: `dataset`, `api`, `repositorio`, `vigilancia`, `guia`, `ml`, `institucion`, `biblioteca`, `directorio`, `revista` y `plataforma`.
- **Recomendador y cultivo.** `ResourceRepository::recommend()` recibe el cultivo y lo usa en la clave de caché y en el texto de la recomendación, pero `cropsMap` está vacío, así que hoy no filtra por cultivo; el filtrado efectivo es por categoría y coincidencia de texto.
- **Textos heredados.** Algunas vistas, el PDF y el DOCX conservan expresiones de una versión anterior del proyecto. No afectan el esquema ni las consultas, pero conviene una pasada editorial antes de una publicación definitiva.

## 18. Lecturas relacionadas

- [README del proyecto](../README.md)
- [Despliegue en Hostinger](DEPLOY-HOSTINGER.md)
- [Verificación de cédula profesional](VERIFICACION-CEDULA.md)
- [Esquema inicial](../database/migrations/001_schema.sql)
- [Migración del CRM](../database/migrations/005_farm_crm.sql)
- [Servicio de IA](../ai-service/README.md)

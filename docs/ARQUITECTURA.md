# Arquitectura

## Principios

1. **Operar en Hostinger sin fricción.** PHP puro, sin Composer, con un front controller y assets estáticos.
2. **PostgreSQL real.** Sin ORM que oculte el SQL; consultas parametrizadas y esquema versionado.
3. **Dos transportes, una API de datos.** `pdo_pgsql` cuando existe; endpoint HTTPS de Neon cuando el hosting no expone PostgreSQL.
4. **Clínica primero.** El caso es la unidad editorial; el resto (búsqueda, diferenciales, recursos) gira alrededor de él.
5. **Privacidad por diseño.** Anonimización obligatoria, consentimiento explícito y mínimo dato personal.

## Capas

```text
public/index.php
   └─ App\Core\App
        ├─ Request / Response
        ├─ Router (routes/web.php)
        ├─ Csrf
        └─ Controller
             ├─ Repository
             │    └─ Database
             │         ├─ PdoDatabase
             │         └─ HttpDatabase
             └─ View (layouts + plantillas)
```

### `app/Core`

| Clase | Responsabilidad |
| --- | --- |
| `App` | Ciclo de petición, manejo de errores, cabeceras. |
| `Router` | Rutas con parámetros `{slug}`, 404/405. |
| `Request` / `Response` | Entrada normalizada y salida con cabeceras de seguridad. |
| `Database` | Fachada diferida; elige PDO o HTTP. |
| `PdoDatabase` | `PDO_PGSQL`, placeholders `$n` traducidos a `:pN`, transacciones. |
| `HttpDatabase` | POST a `https://HOST/sql` con `Neon-Connection-String`; sin transacciones interactivas. |
| `Auth` | Sesión, roles, verificación, rate limit de login. |
| `Csrf` | Token por sesión y validación global de `POST`. |
| `Validator` | Reglas declarativas y mensajes en español. |
| `Upload` | Validación MIME/tamaño/dimensiones y re-codificación con GD. |
| `View` | Render de plantillas con layout. |
| `SqlSplitter` | Divide migraciones respetando `$$`, comillas y comentarios. |

### `app/Repositories`

- `CaseRepository`: búsqueda full-text, detalle, casos similares, CRUD editorial, comentarios, reacciones.
- `DiseaseRepository`: fichas, signos por especie y cálculo de diferenciales.
- `UserRepository`, `ResourceRepository`, `CatalogRepository`.

### `app/Controllers`

- `HomeController`, `CaseController`, `AuthController`, `ProfileController`.
- `DiagnosticController`, `ResourceController`, `PageController`.
- `AdminController`, `HealthController`, `SitemapController`.

## Flujo editorial

```text
borrador ──► en_revision ──► publicado
   ▲              │
   │              ├──► cambios_solicitados ──► en_revision
   │              └──► rechazado ───────────► en_revision
   └──────────────────────────────────────────┘
```

- El autor puede editar `borrador`, `cambios_solicitados` y `rechazado`.
- Un editor/admin puede editar y revisar cualquier estado.
- La publicación exige consentimiento, anonimización, diagnóstico final y `published_at`.
- Cada decisión se registra en `case_reviews` y `audit_log`.

## Búsqueda

`cases.search_vector` es una columna generada con `to_tsvector('spanish', …)` sobre título, motivo, anamnesis, exploración, estudios, diferenciales, diagnóstico, discusión y referencias.

```sql
where c.search_vector @@ websearch_to_tsquery('spanish', $1)
   or c.title ilike $2
   or c.final_diagnosis ilike $3
order by ts_rank_cd(c.search_vector, websearch_to_tsquery('spanish', $1)) desc
```

Índices:

- GIN sobre `search_vector`.
- GIN trigram sobre `title` y `final_diagnosis`.
- Índices parciales para casos publicados y destacados.

## Apoyo diagnóstico

Cada enfermedad tiene un perfil de signos con peso 1–5 y una marca de signo clave. El usuario selecciona hasta 20 signos.

Para cada enfermedad candidata:

```text
cobertura = signos coincidentes / signos seleccionados
perfil    = signos coincidentes / signos totales de la enfermedad
score     = 0.65 * cobertura + 0.35 * perfil
```

Se muestran:

- Coincidencia (%) y número de signos del perfil.
- Signos que coinciden.
- Signos clave que faltan por explorar.
- Urgencia y riesgo zoonótico.

El puntaje es una **heurística de similitud**, no una probabilidad diagnóstica. La interfaz lo declara explícitamente.

## Base de datos

- PostgreSQL 15+; probado en Neon PostgreSQL 18.
- Extensiones: `pg_trgm`, `citext`.
- Claves primarias `bigint generated always as identity`.
- `timestamptz` en todas las fechas.
- Restricciones `CHECK` para estados, roles, sexo, desenlace y licencias.
- Trigger `set_updated_at()` en tablas editables.
- Folio de caso con secuencia `case_folio_seq`.
- Búsqueda y similitud con índices GIN.

### Conexión

- **Aplicación:** `DATABASE_URL` agrupada (`-pooler`), recomendada para muchas conexiones cortas.
- **Migraciones:** `DATABASE_URL_UNPOOLED`, sin PgBouncer.
- **HTTP:** `DB_DRIVER=http`; cada consulta es una petición HTTPS independiente. Para operaciones multi-tabla se usan CTEs o escrituras secuenciales idempotentes.

## Recursos para no especialistas

Cada recurso externo tiene una ficha interna (`/recursos/{slug}`) con:

- `what_for`: uso concreto en lenguaje clínico.
- `how_to`: pasos ordenados.
- `requirements`: cuenta, solicitud, idioma, conocimientos.
- `guide`: contexto, limitaciones y advertencias.
- `action_label`: texto del botón externo.

Además, `/recursos/kit` ofrece plantillas DOCX generadas con `tools/generar_kit_recursos.py`, y `/recursos/glosario` explica términos técnicos. El recomendador acepta objetivo, especie y tema/enfermedad: busca fichas de enfermedad y casos del portal, y ordena los recursos externos por categoría, especie y coincidencia de texto. El objetivo es que un veterinario pueda decidir y actuar sin clonar repositorios ni instalar software.

## PDF de casos

`App\Support\SimplePdf` genera un PDF A4 sin dependencias externas (Helvetica, codificación Windows-1252, saltos de página e imágenes JPEG embebidas). `CasePdf` compone el documento con datos del paciente, secciones clínicas, referencias, licencia y aviso educativo; incluye las imágenes del caso cuando existen. `CaseDocx` genera una versión Word editable con OOXML y `ZipArchive`. Se descargan desde `/casos/{slug}/pdf` y `/casos/{slug}/docx`.

## VetDataHub

El catálogo de [Mikato8/VetDataHub](https://github.com/Mikato8/VetDataHub) se importa a `database/data/vetdatahub.json` con `tools/import_vetdatahub.py`. El portal lo sirve en `/vetdatahub` con búsqueda, filtros por categoría y fuente, y enlaces a la fuente original de cada dataset. No se copian los archivos de datos: se conserva la atribución y se remite a Kaggle, GitHub, figshare u otras fuentes.

## PetEVAL

El split de prueba del bucket [Mikato8/PetEVAL-bucket](https://huggingface.co/buckets/Mikato8/PetEVAL-bucket) se importa a `database/data/peteval.json` con `tools/import_peteval_bucket.py` usando pandas/pyarrow. El portal lo sirve en `/peteval` con búsqueda, filtros ICD-11, filtros de contenido y paginación; `/peteval/{id}` muestra el texto completo y resalta entidades de enfermedad y anonimización. Los archivos originales (Parquet, PDF y README) se copian a `public/descargas/peteval` para descarga directa, con atribución Apache-2.0.

## IA de urgencia (SetFit + ONNX)

El portal incluye una interfaz en `/apoyo-diagnostico/ia`, un panel por caso en `/casos/{slug}/ia-urgencia` y un cliente PHP (`App\Services\UrgencyAiClient`) que llama por HTTPS a un servicio Python externo. El servicio vive en `ai-service/`: FastAPI carga un modelo SetFit exportado a ONNX int8, tokeniza el relato y devuelve cuatro probabilidades (`emergencia`, `urgente`, `consulta`, `preventivo`). Una capa de reglas de seguridad prioriza `emergencia` ante signos de alarma evidentes. El modelo se entrenó con ejemplos sintéticos; es un prototipo educativo y no un dispositivo clínico.

Hostinger no ejecuta Python/onnxruntime, por lo que el servicio se despliega aparte (Hugging Face Space). Ver `ai-service/DEPLOY.md`. Si `AI_SERVICE_URL` está vacío, la interfaz se muestra desactivada.

## Verificación de cédula

`App\Services\CedulaVerifier` consulta API Market/SEP con la cédula como parámetro de consulta y compara el nombre capturado con el nombre del Registro Nacional de Profesionistas. Si el nombre y el título veterinario coinciden, la cuenta se marca `verificado` y se guardan nombre, título e institución; el CURP no se almacena. Si el servicio no está configurado, el registro sigue el flujo manual. Al cambiar nombre o cédula, la verificación se invalida y la cuenta vuelve a `pendiente`.

## CRM de clínica

El módulo `/panel` usa las tablas `clinic_patients`, `clinic_appointments`, `clinic_prescriptions`, `clinic_products`, `clinic_sales` y `clinic_sale_items`. Todas las consultas se filtran por `owner_id`, por lo que cada cuenta ve sólo su propia clínica. La venta se crea con la función PostgreSQL `clinic_create_sale`, que valida existencias, inserta la venta y sus partidas y descuenta stock en una sola operación, compatible con el transporte HTTP de Neon. El acceso `/panel/entrar` reutiliza `Auth` y ofrece sesión extendida de 30 días.

## Seguridad

- Sesión PHP con `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS.
- Regeneración de ID al iniciar sesión.
- CSRF global en métodos mutantes.
- Rate limit: 12 fallos / 15 minutos por correo o IP.
- Autorización por rol y ownership en cada acción.
- Consultas siempre parametrizadas.
- Salida escapada con `e()`.
- Uploads: MIME real, tamaño, dimensiones, re-codificación con GD, nombre aleatorio y bloqueo de PHP.
- CSP: `default-src 'self'`; sin scripts inline ni CDNs.
- `APP_DEBUG=false` en producción; los errores se registran sin exponer detalles.

## Decisiones

| Decisión | Motivo |
| --- | --- |
| Sin Composer | Menos fricción en hosting compartido; auditoría sencilla. |
| Sin framework | El dominio es acotado; se evita peso y magia. |
| SQL explícito | Control de índices, FTS y planes de consulta. |
| HTTP de Neon | Permite PostgreSQL en hostings que sólo ofrecen PHP/MySQL. |
| Contenido en español | El portal está dirigido a médicos veterinarios de México. |
| Casos demo marcados | Permiten mostrar el flujo sin confundirlos con pacientes reales. |

## Extensiones futuras

- Revisión por pares con asignación y notificaciones.
- Exportación PDF/BibTeX.
- Búsqueda semántica con `pgvector` o Lakebase Search.
- API pública con tokens.
- Panel epidemiológico por estado/especie.
- Integración con WAHIS/SENASICA.

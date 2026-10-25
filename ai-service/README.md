---
title: AgroDiagnóstico IA fitosanitaria
emoji: 🌱
colorFrom: green
colorTo: yellow
sdk: docker
app_port: 7860
pinned: false
---

# AgroDiagnóstico · IA de riesgo fitosanitario

Microservicio **FastAPI** que clasifica un relato de campo en español en cuatro
niveles de riesgo fitosanitario: `emergencia`, `urgente`, `consulta` y
`preventivo`.

Es un **prototipo educativo** para ingenieros agrónomos y productores. No es un
diagnóstico fitosanitario, no sustituye la visita de un profesional ni la
determinación de un laboratorio, y no debe usarse como única base para aplicar
plaguicidas o tomar decisiones de cuarentena.

El clasificador principal es un **motor determinista de reglas léxicas** escrito
solo con la biblioteca estándar de Python. No requiere un modelo entrenado, no
descarga pesos y no usa torch, transformers, ONNX ni GPU. Arranca en menos de un
segundo y cada resultado incluye las evidencias que lo explican.

## Endpoints

| Método | Ruta        | Descripción                                      |
| ------ | ----------- | ------------------------------------------------ |
| `GET`  | `/health`   | Estado del servicio. `503` mientras se carga.    |
| `POST` | `/classify` | Clasifica `{"text": "..."}`.                     |
| `GET`  | `/`         | Información mínima del servicio (no es la GUI).  |

## Los cuatro niveles

| Etiqueta     | `color`    | Significado agronómico                                                                 |
| ------------ | ---------- | -------------------------------------------------------------------------------------- |
| `emergencia` | `critical` | Brote cuarentenario o pérdida inminente: HLB, Fusarium R4T, mosca del Mediterráneo, langosta, síntomas generalizados, muerte de plantas, plaga nueva en la zona. |
| `urgente`    | `high`     | Problema que avanza en 24-72 h: manchas que se expanden, marchitez súbita, defoliación severa, pudrición con mal olor, granizo o helada reciente. |
| `consulta`   | `medium`   | Problema localizado que puede programarse: manchas leves, insectos aislados, deficiencias nutricionales, dudas de identificación. |
| `preventivo` | `low`      | Planeación y rutina: muestreo, fertilización, rotación, monitoreo, trampeo, poda, riego, certificación. |

## Arquitectura

```text
texto
  │
  ├─ 1. Normalización: minúsculas, NFKD sin acentos, espacios colapsados
  ├─ 2. Diccionarios ponderados por nivel (emergencia/urgente/consulta/preventivo)
  ├─ 3. Modificadores: intensificadores ("severa", "generalizado") y atenuadores ("leve", "aislado")
  ├─ 4. Negación simple: "sin HLB", "no hay síntomas", "descartó Fusarium"
  ├─ 5. Reglas de seguridad de prioridad alta (cuarentena, pérdida total, intoxicación, >50 %)
  ├─ 6. Saturación de evidencia + softmax → 4 probabilidades que suman 1
  └─ 7. Opcional: mezcla 70 % reglas + 30 % TF-IDF/regresión logística (si existe el JSON)
```

Decisiones de diseño:

- **Explicable**: cada término detectado se devuelve en `evidence`.
- **Determinista**: el mismo texto produce exactamente la misma salida.
- **Sin dependencias de IA**: `requirements.txt` solo instala FastAPI, uvicorn y
  pydantic. El motor de reglas usa `re`, `math`, `unicodedata` y `json`.
- **Seguridad primero**: una regla de seguridad eleva a `emergencia` aunque el
  resto de las señales apunten a otro nivel. Las negaciones evitan falsos
  positivos (`el laboratorio descartó HLB`).
- **Superficie afectada**: un porcentaje mayor al 50 %, "más de la mitad" o
  "toda la parcela" (con contexto de daño) activa emergencia.

### Reglas de seguridad

Se evalúan en orden y la primera coincidencia no negada gana. Devuelven un texto
en `safety_rule` y fijan la etiqueta en `emergencia`:

1. HLB / huanglongbing / dragón amarillo / greening.
2. Fusarium R4T / raza 4 tropical.
3. Mosca del Mediterráneo / mosca de la fruta / *Ceratitis capitata*.
4. Langosta / *Schistocerca*.
5. Cuarentena, brote fitosanitario, plaga nueva o primera detección.
6. SENASICA, aviso fitosanitario, declaratoria o emergencia fitosanitaria.
7. Pérdida total, "se está muriendo todo", "no hay cosecha", plantas muertas.
8. Intoxicación por pesticida o riesgo para trabajadores/consumidores.
9. Síntomas generalizados, erradicación o plaga que arrasó/devastó el cultivo.
10. Superficie afectada mayor al 50 % (o toda la parcela con daño).

### Evidencias

`evidence` es una lista de términos detectados, sin repetir y en orden de
aparición (máximo 12). Sirve para auditar el resultado. Ejemplo:

```json
"evidence": ["HLB", "SENASICA", "muerte de plantas", "superficie afectada: toda la parcela"]
```

Es un campo **adicional**: no sustituye ni rompe los campos que ya consume el
portal (`label`, `label_es`, `description`, `color`, `confidence`, `scores`,
`safety_rule`, `model`, `disclaimer`).

## Contrato HTTP

### `GET /health`

```json
{
  "status": "ok",
  "service": "agro-risk",
  "rules": "reglas-agro-v1",
  "labels": ["emergencia", "urgente", "consulta", "preventivo"],
  "auth_required": false,
  "max_chars": 4000,
  "optional_model": {
    "loaded": false,
    "path": "/app/model/tfidf_logreg.json",
    "labels": [],
    "metrics": {},
    "error": null
  },
  "started_at": "2026-10-24T18:00:00+00:00",
  "disclaimer": "..."
}
```

Mientras el proceso termina de arrancar responde `503` con
`{"status": "loading"}`. En la práctica el arranque es inmediato porque no hay
pesos que cargar.

### `POST /classify`

Solicitud:

```json
{"text": "Tengo 40 árboles de limón persa; sospecho HLB y quiero avisar a SENASICA."}
```

Respuesta (ejemplo real del motor de reglas):

```json
{
  "label": "emergencia",
  "label_es": "Emergencia fitosanitaria",
  "description": "Actúa de inmediato: aísla la zona, evita mover material vegetal y contacta a tu asesor o a la autoridad fitosanitaria (SENASICA). No esperes la clasificación.",
  "color": "critical",
  "confidence": 0.97,
  "scores": {
    "emergencia": 0.97,
    "urgente": 0.0165,
    "consulta": 0.009,
    "preventivo": 0.0045
  },
  "safety_rule": "Se detectó HLB (huanglongbing), plaga cuarentenaria: se prioriza emergencia y se recomienda aviso inmediato a SENASICA.",
  "model": "reglas-agro-v1",
  "disclaimer": "Herramienta educativa de apoyo: no es un diagnóstico fitosanitario ni sustituye la visita de un ingeniero agrónomo ni la determinación de un laboratorio.",
  "evidence": ["HLB", "SENASICA"]
}
```

Campos:

| Campo         | Tipo             | Notas                                                             |
| ------------- | ---------------- | ----------------------------------------------------------------- |
| `label`       | string           | `emergencia`, `urgente`, `consulta` o `preventivo`.               |
| `label_es`    | string           | Etiqueta legible para la interfaz.                                |
| `description` | string           | Recomendación breve en español.                                   |
| `color`       | string           | `critical`, `high`, `medium` o `low`.                             |
| `confidence`  | number 0-1       | Probabilidad de la etiqueta elegida.                              |
| `scores`      | objeto           | Las cuatro etiquetas, valores 0-1 que suman 1 (redondeo a 4 decimales). |
| `safety_rule` | string \| null   | Regla de seguridad aplicada, si hubo.                             |
| `model`       | string           | Identifica el motor; añade `+tfidf-logreg` si se usó el opcional. |
| `disclaimer`  | string           | Aviso de herramienta educativa.                                   |
| `evidence`    | string[]         | Campo adicional con los términos detectados.                     |

### Autenticación

Si `AI_SERVICE_TOKEN` está definido, todas las peticiones a `/classify` deben
enviar:

```http
Authorization: Bearer <token>
```

Si no está definido, el servicio queda abierto (útil solo en local). `/health`
no exige token para permitir monitoreo.

### Errores

| Código | Causa                                                       |
| ------ | ----------------------------------------------------------- |
| `401`  | Token ausente o incorrecto.                                 |
| `422`  | Texto vacío, solo espacios o mayor a `AI_MAX_CHARS`.        |
| `503`  | El servicio todavía se está cargando.                       |

## Correr en local

```bash
cd ai-service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app:app --host 0.0.0.0 --port 7860
```

Pruebas rápidas:

```bash
curl -s http://127.0.0.1:7860/health | python -m json.tool

curl -s -X POST http://127.0.0.1:7860/classify \
  -H 'Content-Type: application/json' \
  -d '{"text":"Detecté HLB en limón persa y necesito avisar a SENASICA."}' \
  | python -m json.tool
```

Con token:

```bash
export AI_SERVICE_TOKEN='cadena-larga-aleatoria'
curl -s -X POST http://127.0.0.1:7860/classify \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $AI_SERVICE_TOKEN" \
  -d '{"text":"Manchas que se expanden rápido en aguacate; defoliación severa."}'
```

### Variables de entorno

| Variable           | Por defecto                  | Uso                                                              |
| ------------------ | ---------------------------- | ---------------------------------------------------------------- |
| `AI_SERVICE_TOKEN` | vacío                        | Si se define, exige `Authorization: Bearer <token>`.             |
| `AI_MAX_CHARS`     | `4000`                       | Longitud máxima del relato aceptado. El portal limita a 2000.    |
| `AI_MODEL_PATH`    | `model/tfidf_logreg.json`    | Ruta del modelo supervisado opcional.                            |
| `PORT`             | `7860`                       | Puerto usado por `python app.py` y el healthcheck del Dockerfile. |
| `LOG_LEVEL`        | `INFO`                       | Nivel de logging (solo biblioteca estándar).                     |

### Límites y alcance

- Máximo 4,000 caracteres por relato; el portal envía como máximo 2,000.
- Español de México; funciona con vocabulario agronómico común, no con
  regionalismos extremos ni con texto en otros idiomas.
- No guarda textos, no entrena en línea y no consulta bases externas.
- No reemplaza el diagnóstico de laboratorio ni la notificación oficial a
  SENASICA.
- Las reglas son deliberadamente conservadoras: ante una señal de cuarentena,
  pérdida total o intoxicación, prefieren `emergencia` aunque el contexto sea
  ambiguo. Revisa siempre `safety_rule` y `evidence`.
- El endpoint no envía cabeceras CORS: el portal PHP lo llama desde el servidor
  con cURL, no desde el navegador.

## Modelo supervisado opcional

No es necesario para funcionar. Sirve para documentar el problema y experimentar
con un modelo supervisado. El servicio **nunca** entrena en tiempo de ejecución:
solo lee un JSON si existe.

```bash
# 1. Dependencias de entrenamiento (aparte de las del servicio)
python -m venv .venv-train
source .venv-train/bin/activate
pip install -r requirements.txt -r training/requirements.txt

# 2. Dataset sintético reproducible (~600 ejemplos, 150 por clase)
python training/generar_dataset.py --per-class 150 --seed 2026

# 3. Entrenamiento TF-IDF + regresión logística y exportación de coeficientes
python training/train_model.py
```

Salida por defecto:

- `training/risk_examples.csv`: columnas `text,label`, 100 % sintético.
- `model/tfidf_logreg.json`: vocabulario, IDF, coeficientes e interceptos.

Si el JSON existe al arrancar, el servicio calcula probabilidades TF-IDF con
biblioteca estándar y mezcla **70 % reglas + 30 % modelo**. Las reglas de
seguridad siguen teniendo prioridad absoluta y, cuando se aplica la mezcla, el
campo `model` pasa a ser `reglas-agro-v1+tfidf-logreg`. Si el archivo falta o
está corrupto, el servicio sigue funcionando solo con reglas y lo reporta en
`/health`.

Formato del JSON (inferencia sin scikit-learn):

```json
{
  "format": "tfidf-logreg-v1",
  "labels": ["consulta", "emergencia", "preventivo", "urgente"],
  "vocabulary": {"manchas": 0},
  "idf": [1.0],
  "coef": [[0.0], [0.0], [0.0], [0.0]],
  "intercept": [0.0, 0.0, 0.0, 0.0],
  "metrics": {"accuracy_holdout": 0.9}
}
```

Advertencia: el dataset es sintético y las métricas de validación son
optimistas; no las presentes como rendimiento en campo.

## Conectar el portal PHP

El portal **Hostinger no ejecuta Python**. El microservicio se despliega aparte
(Hugging Face Space, VPS, Fly.io, Railway o cualquier host con Docker) y el
portal lo consume por HTTPS. Ver [`DEPLOY.md`](DEPLOY.md).

En el `.env` del portal:

```dotenv
AI_SERVICE_URL=https://tu-servicio.example.com
AI_SERVICE_TOKEN=la-misma-cadena-que-definiste-en-el-servicio
```

`app/Services/RiskAiClient.php` llama a `POST {AI_SERVICE_URL}/classify` con
timeout de conexión de 6 s y total de 20 s. Si `AI_SERVICE_URL` está vacío, la
vista muestra la sección desactivada; si el servicio responde `503`, muestra que
el modelo todavía se está cargando.

## Docker

```bash
docker build -t agro-risk .
docker run --rm -p 7860:7860 \
  -e AI_SERVICE_TOKEN='cadena-larga-aleatoria' \
  agro-risk
```

La imagen usa `python:3.12-slim`, instala solo las tres dependencias del
servicio, corre con un usuario sin privilegios, expone `7860` y define un
`HEALTHCHECK` contra `/health`. Para habilitar el modelo supervisado opcional,
monta `model/` como volumen de solo lectura (`-v "$PWD/model:/app/model:ro"`) o
define `AI_MODEL_PATH`.

## Estructura

```text
ai-service/
├── app.py                    # FastAPI + motor de reglas + modelo opcional
├── requirements.txt          # fastapi, uvicorn, pydantic
├── Dockerfile                # python:3.12-slim, usuario no root, 7860
├── .dockerignore
├── README.md
├── DEPLOY.md
└── training/
    ├── generar_dataset.py    # CSV sintético etiquetado
    ├── train_model.py        # TF-IDF + LogisticRegression OPCIONAL
    ├── requirements.txt      # scikit-learn (solo entrenamiento)
    └── risk_examples.csv     # generado, no se versiona necesariamente
```

## Validación

```bash
# Sintaxis
python3 -m py_compile app.py training/generar_dataset.py training/train_model.py

# Dataset reproducible
python training/generar_dataset.py --output /tmp/risk_examples.csv --per-class 30

# Servicio
uvicorn app:app --port 7860
curl -s http://127.0.0.1:7860/health
```

Casos de humo recomendados (uno por nivel):

1. **Emergencia**: `Detecté HLB en limón persa y debo avisar a SENASICA.`
2. **Urgente**: `Manchas que se expanden rápido; en 48 horas la defoliación es severa.`
3. **Consulta**: `Algunas manchas leves en dos plantas; pocos insectos aislados.`
4. **Preventivo**: `Estoy planeando muestreo de suelo, fertilización y rotación.`

Comprueba que `scores` suma 1, que `color` corresponde al nivel y que
`evidence` contiene los términos que justifican la decisión.

## Licencia y aviso

Prototipo educativo dentro del proyecto AgroDiagnóstico. Revisa la licencia y
los avisos del repositorio principal. No uses este servicio para emitir
diagnósticos oficiales, recetas fitosanitarias ni notificaciones a la autoridad.

# Desplegar la IA de riesgo fitosanitario

El portal **AgroDiagnóstico** está hecho en PHP y corre en Hostinger. Hostinger
**no ejecuta Python**, así que este servicio FastAPI se despliega **aparte** y el
portal lo consume por HTTPS. El portal nunca importa el código Python: solo hace
`POST {AI_SERVICE_URL}/classify` con cURL desde `RiskAiClient.php`.

Opciones recomendadas:

1. **Hugging Face Space (Docker)** — sencillo y gratuito para un prototipo.
2. **Cualquier host con Docker** — VPS, Fly.io, Railway, Render, un contenedor
   en un servidor propio, etc.

No se necesita GPU, modelo ONNX ni descarga de pesos. La imagen pesa poco y
arranca en segundos.

---

## Opción A · Hugging Face Space (Docker)

### 1. Crear el Space

En [huggingface.co/new-space](https://huggingface.co/new-space):

- SDK: **Docker**.
- Plantilla: **Blank**.
- Visibilidad: pública o privada. Si es privada, el portal debe enviar token.
- Hardware: CPU básica es suficiente (el servicio no usa GPU).

Por CLI:

```bash
hf auth login
hf repo create TU_USUARIO/agro-risk-ia --repo-type space --space_sdk docker
```

### 2. Subir el servicio

Desde la raíz del repositorio:

```bash
cd ai-service
hf upload TU_USUARIO/agro-risk-ia . --repo-type space
```

El `README.md` de `ai-service/` ya trae el front matter que Hugging Face necesita
(`sdk: docker`, `app_port: 7860`). El `Dockerfile` expone el puerto `7860` y
ejecuta uvicorn con un usuario sin privilegios.

### 3. Configurar el token

En **Settings → Variables and secrets** del Space, crea un **secret**:

```text
AI_SERVICE_TOKEN=<cadena-larga-aleatoria>
```

Genera la cadena, por ejemplo, con:

```bash
python3 -c "import secrets; print(secrets.token_urlsafe(32))"
```

Si el Space es público y decides no usar token, deja la variable vacía; el
portal también debe dejarla vacía. Para cualquier despliegue real se recomienda
token.

### 4. Comprobar el Space

La primera vez puede tardar en arrancar (construcción de la imagen). Cuando
`/health` responda `status: ok`:

```bash
curl -s https://TU_USUARIO-agro-risk-ia.hf.space/health | python3 -m json.tool

curl -s -X POST https://TU_USUARIO-agro-risk-ia.hf.space/classify \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer TU_TOKEN' \
  -d '{"text":"Detecté HLB en limón persa y necesito avisar a SENASICA."}' \
  | python3 -m json.tool
```

Los Spaces gratuitos pueden “dormir” o reiniciarse; el healthcheck y el cliente
del portal ya contemplan un `503` transitorio.

---

## Opción B · Docker en un VPS o PaaS

### Construir y ejecutar

```bash
cd ai-service
docker build -t agro-risk:latest .

docker run -d --name agro-risk \
  --restart unless-stopped \
  -p 127.0.0.1:7860:7860 \
  -e AI_SERVICE_TOKEN='<cadena-larga-aleatoria>' \
  -e AI_MAX_CHARS=4000 \
  agro-risk:latest
```

Se publica solo en `127.0.0.1` para que el proxy inverso (Caddy, Nginx, Traefik)
termine TLS. No expongas el puerto `7860` directamente a Internet sin HTTPS ni
token.

Comprobar:

```bash
curl -s http://127.0.0.1:7860/health | python3 -m json.tool
docker logs --tail 100 agro-risk
```

La imagen:

- Usa `python:3.12-slim`.
- Instala solo `fastapi`, `uvicorn[standard]` y `pydantic`.
- Corre como usuario `app` sin privilegios.
- Expone `7860` y define un `HEALTHCHECK` contra `/health`.
- No necesita GPU ni volúmenes para funcionar.

### Proxy inverso con HTTPS

Ejemplo mínimo de Nginx (el certificado se obtiene con Certbot o el proveedor):

```nginx
server {
    listen 443 ssl;
    server_name ia.tu-dominio.mx;

    ssl_certificate     /etc/letsencrypt/live/ia.tu-dominio.mx/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ia.tu-dominio.mx/privkey.pem;

    client_max_body_size 32k;

    location / {
        proxy_pass http://127.0.0.1:7860;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 30s;
    }
}
```

No hace falta configurar CORS: el navegador no llama al servicio; lo hace el
servidor PHP con cURL. Si quieres limitar abuso, aplica rate limiting en el
proxy (por ejemplo, 30 peticiones por minuto por IP).

### Docker Compose (opcional)

```yaml
services:
  agro-risk:
    build: ./ai-service
    restart: unless-stopped
    ports:
      - "127.0.0.1:7860:7860"
    environment:
      AI_SERVICE_TOKEN: "${AI_SERVICE_TOKEN}"
      AI_MAX_CHARS: "4000"
    healthcheck:
      test: ["CMD", "python", "-c", "import urllib.request; urllib.request.urlopen('http://127.0.0.1:7860/health', timeout=3)"]
      interval: 30s
      timeout: 5s
      retries: 3
```

---

## Conectar el portal en Hostinger

En el `.env` del portal (nunca en `public_html` ni en el repositorio):

```dotenv
AI_SERVICE_URL=https://ia.tu-dominio.mx
AI_SERVICE_TOKEN=<la-misma-cadena-del-servicio>
```

- `AI_SERVICE_URL` debe ser la base **sin** `/classify` al final y con HTTPS.
- `AI_SERVICE_TOKEN` debe coincidir exactamente con el del servicio. Si el
  servicio no tiene token, deja esta variable vacía.
- Si cambias el token, actualiza ambos lados y reinicia/redeploya.

Limpia la caché de configuración del portal si aplica y verifica:

```bash
# Desde el servidor del portal, no desde tu computadora
curl -s "$AI_SERVICE_URL/health"
```

Luego abre en el navegador:

- `/apoyo-diagnostico/ia` — clasificador general.
- `/casos/{slug}/ia-emergencia` — clasificador dentro de un caso.

Si `AI_SERVICE_URL` está vacío, la vista muestra “La IA aún no está conectada”.
Si el servicio responde `503`, el portal muestra que todavía se está cargando.
Si responde `401`, el token no coincide.

---

## Actualizar el servicio

### Hugging Face

```bash
cd ai-service
hf upload TU_USUARIO/agro-risk-ia . --repo-type space
```

El Space reconstruye la imagen automáticamente.

### Docker

```bash
cd ai-service
docker build -t agro-risk:latest .
docker stop agro-risk && docker rm agro-risk
docker run -d --name agro-risk --restart unless-stopped \
  -p 127.0.0.1:7860:7860 \
  -e AI_SERVICE_TOKEN='<token>' \
  agro-risk:latest
```

No hay estado persistente que migrar: el servicio no guarda textos ni bases de
datos.

---

## Modelo supervisado opcional

El despliegue normal **no** lo necesita. Si decides experimentar con el modelo
TF-IDF + regresión logística:

```bash
# En tu máquina, dentro de ai-service/
python -m venv .venv-train
source .venv-train/bin/activate
pip install -r requirements.txt -r training/requirements.txt
python training/generar_dataset.py --per-class 150
python training/train_model.py
```

Esto crea `model/tfidf_logreg.json`. Opciones para llevarlo al servicio:

- **Hugging Face**: subir el archivo dentro de `ai-service/model/` antes de
  `hf upload` (el `.dockerignore` no lo excluye; sí excluye `.onnx` y binarios
  pesados).
- **Docker**: montarlo como volumen de solo lectura:

  ```bash
  docker run -d --name agro-risk \
    -p 127.0.0.1:7860:7860 \
    -e AI_SERVICE_TOKEN='<token>' \
    -v "$PWD/model:/app/model:ro" \
    agro-risk:latest
  ```

- **Ruta distinta**: define `AI_MODEL_PATH=/ruta/al/tfidf_logreg.json`.

El servicio lo detecta al arrancar y mezcla 70 % reglas + 30 % modelo. Si el
JSON falta, está corrupto o no tiene las cuatro etiquetas, sigue funcionando con
reglas y lo indica en `/health` (`optional_model.error`).

> El dataset es sintético. No presentes la exactitud de validación como
> rendimiento real en campo.

---

## Seguridad y límites

- Usa siempre HTTPS. El token viaja en la cabecera `Authorization`.
- No publiques el puerto del contenedor sin proxy/TLS y sin token.
- El servicio no guarda el texto del relato ni datos personales; responde en
  memoria y no escribe logs con el contenido completo.
- `AI_MAX_CHARS` limita el tamaño de entrada (4,000 por defecto). El portal
  limita a 2,000 caracteres desde el formulario.
- El portal usa timeout de conexión de 6 s y total de 20 s; el servicio responde
  en milisegundos, así que ese margen es suficiente.
- Aplica rate limiting en el proxy si el servicio queda expuesto a Internet.
- Rota el token si sospechas que se filtró: cambia el secret/variable en el
  servicio y `AI_SERVICE_TOKEN` en Hostinger.
- Este es un prototipo educativo, no un sistema oficial de alerta fitosanitaria.
  Las decisiones de cuarentena y notificación corresponden a SENASICA y a un
  ingeniero agrónomo responsable.

---

## Solución de problemas

| Síntoma | Causa probable | Acción |
| ------- | -------------- | ------ |
| El portal dice “La IA no está configurada” | `AI_SERVICE_URL` vacío o caché de configuración | Definir la variable y limpiar caché. |
| `401` en el portal | Token distinto o ausente | Igualar `AI_SERVICE_TOKEN` en ambos lados. |
| `503` al clasificar | El servicio está arrancando o se reinició | Esperar unos segundos y reintentar; revisar `/health`. |
| `No pudimos conectar` | URL incorrecta, DNS, firewall o HTTP en vez de HTTPS | Probar `curl` desde el servidor del portal. |
| `El servicio respondió con estado HTTP 404` | `AI_SERVICE_URL` incluye `/classify` o una ruta extra | Usar solo la base, sin `/classify`. |
| `422` | Texto vacío o demasiado largo | Enviar entre 1 y `AI_MAX_CHARS` caracteres. |
| Clasificación inesperada | Relato ambiguo o fuera del vocabulario | Revisar `safety_rule` y `evidence`; reformular con síntomas concretos. |
| El contenedor no arranca | Puerto ocupado o dependencia no instalada | `docker logs agro-risk`; reconstruir la imagen. |
| `/health` tarda en responder | Arranque en frío del Space gratuito | Reintentar; considerar hardware o mantener el servicio activo. |

Para ver los logs:

```bash
docker logs -f agro-risk
```

En Hugging Face: pestaña **Logs** del Space.

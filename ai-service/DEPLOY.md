# Desplegar el servicio de IA en Hugging Face Spaces

El portal PHP no ejecuta Python. El clasificador vive en un Space Docker y el portal lo llama por HTTPS.

## 1. Crear el Space

En [huggingface.co/new-space](https://huggingface.co/new-space):

- Owner: `Mikato8`.
- Nombre: `vet-urgency-setfit`.
- SDK: **Docker**.
- Plantilla: **Blank**.
- Visibilidad: pública o privada (el portal usa un token si es privada).

También por CLI:

```bash
hf repo create Mikato8/vet-urgency-setfit --repo-type space --space_sdk docker
```

## 2. Autenticarse y subir

```bash
hf auth login
cd ai-service
hf upload Mikato8/vet-urgency-setfit . --repo-type space
```

El modelo int8 pesa ~113 MB. El Space gratuito puede tardar en arrancar la primera vez.

## 3. Configurar el token

En **Settings → Variables and secrets** del Space, crea el secreto:

```text
AI_SERVICE_TOKEN=<cadena-larga-aleatoria>
```

Si el Space es público y no quieres token, déjalo vacío; el portal también debe dejarlo vacío.

## 4. Conectar el portal

En el `.env` de Hostinger:

```dotenv
AI_SERVICE_URL=https://mikato8-vet-urgency-setfit.hf.space
AI_SERVICE_TOKEN=<el-mismo-token>
```

Limpia la caché y prueba:

```bash
curl https://TU-SPACE.hf.space/health
curl -X POST https://TU-SPACE.hf.space/classify \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer TU-TOKEN' \
  -d '{"text":"Perro con vómitos y diarrea desde ayer, decaído."}'
```

Luego abre `/apoyo-diagnostico/ia` en el portal.

## 5. Actualizar el modelo

```bash
cd ai-service
python training/train_urgency.py --output model
python training/quantize_onnx.py --input model/model.onnx --output model/model-int8.onnx
mv model/model-int8.onnx model/model.onnx
hf upload Mikato8/vet-urgency-setfit model model --repo-type space
```

Revisa `model/metrics.json` y valida con casos reales antes de presentar el resultado como clínicamente útil.

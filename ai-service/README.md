---
title: Vet Urgency SetFit ONNX
emoji: 🩺
colorFrom: green
colorTo: yellow
sdk: docker
app_port: 7860
pinned: false
---

# Vet Urgency SetFit ONNX

Clasificador de urgencia veterinaria en español construido con **SetFit** y exportado a **ONNX** para inferencia en CPU.

## Endpoints

- `GET /health`
- `POST /classify` con `{"text": "..."}`.

## Variables

- `AI_SERVICE_TOKEN`: token opcional compartido con el portal.
- `AI_MAX_LENGTH`: longitud máxima de tokens (128 por defecto).

## Estructura del modelo

`model/` debe contener:

- `model.onnx`: modelo completo (cuerpo + cabeza) cuantizado a int8.
- `tokenizer/`: tokenizer de Hugging Face.
- `labels.json`: orden de las cuatro clases.
- `metrics.json`: métricas del entrenamiento.

El modelo se entrena con `training/train_urgency.py` y se cuantiza con `training/quantize_onnx.py`. Los datos de entrenamiento son sintéticos y el clasificador es **orientativo**: no es un dispositivo médico ni sustituye la valoración clínica.

## Reglas de seguridad

Si el texto contiene signos de alarma como “no respira”, “convulsión”, “sangrado abundante” o “no puede orinar”, el servicio prioriza `emergencia` aunque el modelo dude. La respuesta incluye `safety_rule`.

## Despliegue

Ver [`DEPLOY.md`](DEPLOY.md).

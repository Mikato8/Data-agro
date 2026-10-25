# Imagen del microservicio de riesgo fitosanitario.
# python:3.12-slim + FastAPI/uvicorn; sin torch, transformers ni ONNX.
FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PORT=7860

WORKDIR /app

# Usuario sin privilegios desde el primer arranque.
RUN groupadd --system app \
    && useradd --system --gid app --home-dir /app --shell /usr/sbin/nologin app

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# COPY . . funciona aunque model/ no exista (el modelo supervisado es opcional).
# .dockerignore excluye entrenamiento, entornos virtuales y artefactos pesados.
COPY . .

USER app

EXPOSE 7860

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import os,urllib.request; urllib.request.urlopen('http://127.0.0.1:' + os.getenv('PORT','7860') + '/health', timeout=3)"

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "7860"]

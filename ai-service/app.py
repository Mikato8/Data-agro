from __future__ import annotations

import json
import os
import unicodedata
from contextlib import asynccontextmanager
from pathlib import Path

import numpy as np
import onnxruntime as ort
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from transformers import AutoTokenizer

BASE_DIR = Path(__file__).resolve().parent
MODEL_DIR = BASE_DIR / "model"
TOKEN = os.getenv("AI_SERVICE_TOKEN", "").strip()
MAX_LENGTH = int(os.getenv("AI_MAX_LENGTH", "128"))

# Red flags de seguridad: si aparecen, la orientación never debe bajar de emergencia.
EMERGENCY_PATTERNS = (
    "no respira",
    "dificultad para respirar",
    "encias azuladas",
    "convulsion",
    "sangrado abundante",
    "sangra mucho",
    "atropell",
    "inconsciente",
    "no puede orinar",
    "no orina",
    "intenta orinar",
    "no sale ni una gota",
    "dificultad para orinar",
    "obstruccion",
    "intoxic",
    "veneno",
    "golpe de calor",
    "quemadura",
    "paralisis",
    "abdomen hinchado",
    "shock",
)

LABELS = {
    "emergencia": {
        "label_es": "Emergencia",
        "description": "Requiere atención veterinaria inmediata. Estabiliza al paciente y contacta al hospital o servicio de urgencias.",
        "color": "critical",
    },
    "urgente": {
        "label_es": "Urgente",
        "description": "Debe ser valorado el mismo día o en las próximas 24 horas. Vigila signos de deterioro.",
        "color": "high",
    },
    "consulta": {
        "label_es": "Consulta programada",
        "description": "Puede programarse en los próximos días. No sustituye la valoración presencial.",
        "color": "medium",
    },
    "preventivo": {
        "label_es": "Preventivo",
        "description": "Atención rutinaria de prevención, control o educación sanitaria.",
        "color": "low",
    },
}

state: dict[str, object] = {"session": None, "tokenizer": None, "labels": [], "input_names": [], "error": None}


def load_model() -> None:
    labels = json.loads((MODEL_DIR / "labels.json").read_text(encoding="utf-8"))
    tokenizer = AutoTokenizer.from_pretrained(str(MODEL_DIR / "tokenizer"))
    session = ort.InferenceSession(str(MODEL_DIR / "model.onnx"), providers=["CPUExecutionProvider"])
    state["session"] = session
    state["tokenizer"] = tokenizer
    state["labels"] = labels
    state["input_names"] = [item.name for item in session.get_inputs()]


@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        load_model()
    except Exception as exc:  # noqa: BLE001
        state["error"] = f"{type(exc).__name__}: {exc}"
    yield


app = FastAPI(title="Vet Urgency SetFit ONNX", version="1.0.0", lifespan=lifespan)


def require_token(authorization: str | None = Header(default=None)) -> None:
    if not TOKEN:
        return
    if authorization != f"Bearer {TOKEN}":
        raise HTTPException(status_code=401, detail="Token inválido")


class ClassifyRequest(BaseModel):
    text: str = Field(min_length=10, max_length=4000)


class ClassifyResponse(BaseModel):
    label: str
    label_es: str
    description: str
    color: str
    confidence: float
    scores: dict[str, float]
    safety_rule: str | None = None
    model: str
    disclaimer: str


def normalize_text(value: str) -> str:
    return "".join(
        char for char in unicodedata.normalize("NFKD", value.lower()) if not unicodedata.combining(char)
    )


def probabilities_from_output(outputs: list, labels: list[str]) -> np.ndarray:
    """Normaliza las salidas de skl2onnx/ONNX a un vector de 4 probabilidades."""
    for output in outputs:
        array = np.asarray(output)
        if array.ndim == 2 and array.shape[0] == 1:
            row = array[0]
            if row.shape[0] == len(labels):
                row = row.astype(float)
                total = float(row.sum())
                if total > 0 and not np.isclose(total, 1.0, atol=1e-3):
                    row = row / total
                return row
        if isinstance(output, list) and len(output) == 1 and isinstance(output[0], dict):
            mapping = output[0]
            row = np.zeros(len(labels), dtype=float)
            for key, value in mapping.items():
                key_text = str(key)
                if key_text.isdigit() and int(key_text) < len(labels):
                    row[int(key_text)] = float(value)
                elif key_text in labels:
                    row[labels.index(key_text)] = float(value)
            if row.sum() > 0:
                return row / row.sum()
    raise ValueError("No se encontró una salida de probabilidades compatible")


@app.get("/")
def root() -> dict:
    return {
        "service": "Vet Urgency SetFit ONNX",
        "status": "ok" if state["session"] is not None else "loading",
        "endpoints": ["/health", "/classify"],
    }


@app.get("/health")
def health() -> dict:
    return {
        "ok": state["session"] is not None,
        "labels": state["labels"],
        "inputs": state["input_names"],
        "error": state["error"],
    }


@app.post("/classify", response_model=ClassifyResponse, dependencies=[Depends(require_token)])
def classify(payload: ClassifyRequest) -> ClassifyResponse:
    session = state["session"]
    tokenizer = state["tokenizer"]
    labels = state["labels"]
    if session is None or tokenizer is None or not labels:
        raise HTTPException(status_code=503, detail="Modelo todavía no disponible")

    text = " ".join(payload.text.split()).strip()
    encoded = tokenizer(
        text,
        return_tensors="np",
        padding=True,
        truncation=True,
        max_length=MAX_LENGTH,
    )
    feeds = {name: encoded[name] for name in state["input_names"] if name in encoded}
    try:
        outputs = session.run(None, feeds)
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"Error de inferencia: {type(exc).__name__}") from exc

    try:
        probabilities = probabilities_from_output(outputs, labels)
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"Salida inesperada del modelo: {exc}") from exc

    index = int(np.argmax(probabilities))
    label = str(labels[index])
    safety_rule = None
    normalized = normalize_text(text)
    if any(pattern in normalized for pattern in EMERGENCY_PATTERNS):
        emergency_index = labels.index("emergencia") if "emergencia" in labels else 0
        probabilities = np.full(len(labels), 0.006, dtype=float)
        probabilities[emergency_index] = 0.982
        probabilities = probabilities / probabilities.sum()
        index = emergency_index
        label = "emergencia"
        safety_rule = "Se detectó un signo de alarma; se prioriza emergencia aunque el modelo dude."

    info = LABELS.get(label, LABELS["consulta"])
    scores = {str(labels[i]): round(float(probabilities[i]), 4) for i in range(len(labels))}
    return ClassifyResponse(
        label=label,
        label_es=info["label_es"],
        description=info["description"],
        color=info["color"],
        confidence=round(float(probabilities[index]), 4),
        scores=scores,
        safety_rule=safety_rule,
        model="setfit-paraphrase-multilingual-MiniLM-L12-v2-onnx",
        disclaimer="Orientación educativa generada por un clasificador. No es un diagnóstico ni sustituye la valoración clínica.",
    )

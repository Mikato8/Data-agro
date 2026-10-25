#!/usr/bin/env python3
"""Entrena un SetFit de urgencia veterinaria y exporta el cuerpo a ONNX.

Uso:
    python train_urgency.py --output ../model

Requiere setfit, datasets, onnx, skl2onnx, transformers, torch y scikit-learn.
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from datasets import ClassLabel, Dataset
from setfit import SetFitModel, SetFitTrainer
from setfit.exporters.onnx import export_onnx
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from transformers import AutoTokenizer

BASE_MODEL = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
LABELS = ["emergencia", "urgente", "consulta", "preventivo"]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", default=str(Path(__file__).parent / "urgency_examples.csv"))
    parser.add_argument("--output", default=str(Path(__file__).parent.parent / "model"))
    parser.add_argument("--base", default=BASE_MODEL)
    parser.add_argument("--iterations", type=int, default=30)
    args = parser.parse_args()

    output = Path(args.output).resolve()
    output.mkdir(parents=True, exist_ok=True)

    frame = pd.read_csv(args.data)
    frame["text"] = frame["text"].astype(str).str.strip()
    frame["label"] = frame["label"].astype(str).str.strip()
    frame = frame[frame["label"].isin(LABELS)].reset_index(drop=True)
    if frame["label"].nunique() != len(LABELS):
        raise SystemExit("El CSV debe contener las cuatro etiquetas: " + ", ".join(LABELS))

    dataset = Dataset.from_dict({
        "text": frame["text"].tolist(),
        "label": [LABELS.index(value) for value in frame["label"].tolist()],
    })
    dataset = dataset.cast_column("label", ClassLabel(names=LABELS))
    split = dataset.train_test_split(test_size=0.2, seed=42, stratify_by_column="label")
    train_ds, eval_ds = split["train"], split["test"]
    print(f"Entrenamiento: {len(train_ds)} · Evaluación: {len(eval_ds)}")

    model = SetFitModel.from_pretrained(args.base, labels=LABELS)
    trainer = SetFitTrainer(
        model=model,
        train_dataset=train_ds,
        eval_dataset=eval_ds,
        batch_size=16,
        num_iterations=args.iterations,
        num_epochs=2,
        column_mapping={"text": "text", "label": "label"},
    )
    trainer.train()

    predictions = model.predict(eval_ds["text"])
    truth = [LABELS[int(value)] for value in eval_ds["label"]]
    accuracy = float(accuracy_score(truth, predictions))
    report = classification_report(truth, predictions, output_dict=True, zero_division=0)
    matrix = confusion_matrix(truth, predictions, labels=LABELS).tolist()
    print(f"Accuracy holdout: {accuracy:.3f}")

    head = model.model_head
    joblib.dump(head, output / "head.joblib")
    (output / "labels.json").write_text(json.dumps(LABELS, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    tokenizer = AutoTokenizer.from_pretrained(args.base)
    tokenizer.save_pretrained(output / "tokenizer")

    print("Exportando el modelo completo a ONNX...")
    export_onnx(
        model.model_body,
        model.model_head,
        opset=17,
        output_path=str(output / "model.onnx"),
    )

    metrics = {
        "base_model": args.base,
        "labels": LABELS,
        "train_examples": len(train_ds),
        "eval_examples": len(eval_ds),
        "accuracy": accuracy,
        "classification_report": report,
        "confusion_matrix": matrix,
        "note": "Datos sintéticos de demostración; validar con casos reales antes de uso clínico.",
    }
    (output / "metrics.json").write_text(json.dumps(metrics, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Modelo exportado en {output}")


if __name__ == "__main__":
    main()

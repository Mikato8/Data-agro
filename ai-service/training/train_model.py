#!/usr/bin/env python3
"""Entrena un modelo OPCIONAL TF-IDF + regresión logística y exporta sus coeficientes.

El servicio funciona sin este paso: ``app.py`` usa el motor de reglas léxicas.
Si existe ``model/tfidf_logreg.json``, el servicio combina 70 % reglas + 30 %
modelo (las reglas de seguridad siempre tienen la última palabra).

Este script NO es necesario para desplegar. Requiere scikit-learn:

    python -m pip install -r training/requirements.txt
    python training/generar_dataset.py
    python training/train_model.py

Salida por defecto: ``model/tfidf_logreg.json``. Para usarlo, reinicia el
servicio o monta el archivo y define ``AI_MODEL_PATH``.

Formato exportado (biblioteca estándar en inferencia):

    {
      "format": "tfidf-logreg-v1",
      "labels": ["consulta", "emergencia", "preventivo", "urgente"],
      "vocabulary": {"manchas": 0, ...},
      "idf": [...],
      "coef": [[...], [...], [...], [...]],
      "intercept": [...],
      "metrics": {...}
    }
"""

from __future__ import annotations

import argparse
import csv
import json
from datetime import datetime, timezone
from pathlib import Path

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report
from sklearn.model_selection import train_test_split

LABELS = ("emergencia", "urgente", "consulta", "preventivo")
BASE_DIR = Path(__file__).resolve().parent.parent


def load_rows(path: Path) -> tuple[list[str], list[str]]:
    if not path.is_file():
        raise SystemExit(
            f"No existe {path}. Genera primero el CSV con "
            "`python training/generar_dataset.py`."
        )
    texts: list[str] = []
    labels: list[str] = []
    with path.open(encoding="utf-8", newline="") as handle:
        reader = csv.DictReader(handle)
        if not reader.fieldnames or not {"text", "label"}.issubset(reader.fieldnames):
            raise SystemExit("El CSV debe tener las columnas text,label.")
        for number, row in enumerate(reader, start=2):
            text = (row.get("text") or "").strip()
            label = (row.get("label") or "").strip()
            if not text or not label:
                continue
            if label not in LABELS:
                raise SystemExit(f"Etiqueta desconocida en la fila {number}: {label!r}")
            texts.append(text)
            labels.append(label)
    if not texts:
        raise SystemExit("El CSV no contiene ejemplos válidos.")
    counts = {label: labels.count(label) for label in LABELS}
    missing = [label for label, count in counts.items() if count < 4]
    if missing:
        raise SystemExit(
            "Se necesitan al menos 4 ejemplos por etiqueta; faltan: "
            + ", ".join(missing)
            + f". Conteos actuales: {counts}"
        )
    return texts, labels


def export_model(
    output: Path,
    vectorizer: TfidfVectorizer,
    classifier: LogisticRegression,
    metrics: dict,
    source: str,
) -> None:
    labels = [str(label) for label in classifier.classes_]
    payload = {
        "format": "tfidf-logreg-v1",
        "version": "1.0",
        "created_at": datetime.now(timezone.utc).isoformat(),
        "labels": labels,
        "vocabulary": {str(term): int(index) for term, index in vectorizer.vocabulary_.items()},
        "idf": [float(value) for value in vectorizer.idf_],
        "coef": [[float(value) for value in row] for row in classifier.coef_],
        "intercept": [float(value) for value in classifier.intercept_],
        "metrics": metrics,
        "source": source,
    }
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(
        json.dumps(payload, ensure_ascii=False, separators=(",", ":")),
        encoding="utf-8",
    )
    size_mb = output.stat().st_size / (1024 * 1024)
    print(f"Modelo exportado en {output} ({size_mb:.2f} MB, {len(labels)} clases).")
    print(
        "Para usarlo, reinicia el servicio. El archivo se lee desde "
        f"{output} o desde AI_MODEL_PATH."
    )


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--data",
        default=str(BASE_DIR / "training" / "risk_examples.csv"),
        help="CSV con columnas text,label.",
    )
    parser.add_argument(
        "--output",
        default=str(BASE_DIR / "model" / "tfidf_logreg.json"),
        help="Ruta del JSON exportado.",
    )
    parser.add_argument("--seed", type=int, default=2026)
    parser.add_argument("--test-size", type=float, default=0.2)
    parser.add_argument("--min-df", type=int, default=2)
    parser.add_argument("--max-features", type=int, default=6000)
    parser.add_argument("--c", type=float, default=4.0, help="Regularización C.")
    args = parser.parse_args()

    data_path = Path(args.data).resolve()
    texts, labels = load_rows(data_path)
    counts = {label: labels.count(label) for label in LABELS}
    print(f"Ejemplos: {len(texts)} · {counts}")

    train_texts, test_texts, train_labels, test_labels = train_test_split(
        texts,
        labels,
        test_size=args.test_size,
        random_state=args.seed,
        stratify=labels,
    )

    vectorizer = TfidfVectorizer(
        lowercase=True,
        strip_accents="unicode",
        ngram_range=(1, 2),
        min_df=args.min_df,
        max_features=args.max_features,
    )
    matrix = vectorizer.fit_transform(train_texts)
    classifier = LogisticRegression(
        max_iter=3000,
        C=args.c,
        class_weight="balanced",
    )
    classifier.fit(matrix, train_labels)
    predicted = classifier.predict(vectorizer.transform(test_texts))
    accuracy = accuracy_score(test_labels, predicted)
    report = classification_report(
        test_labels,
        predicted,
        output_dict=True,
        zero_division=0,
    )
    print(f"Exactitud en validación ({len(test_texts)} ejemplos): {accuracy:.3f}")
    print(classification_report(test_labels, predicted, zero_division=0))

    # Reentrena con todo el dataset para exportar el modelo final.
    final_vectorizer = TfidfVectorizer(
        lowercase=True,
        strip_accents="unicode",
        ngram_range=(1, 2),
        min_df=args.min_df,
        max_features=args.max_features,
    )
    full_matrix = final_vectorizer.fit_transform(texts)
    final_classifier = LogisticRegression(
        max_iter=3000,
        C=args.c,
        class_weight="balanced",
    )
    final_classifier.fit(full_matrix, labels)

    metrics = {
        "accuracy_holdout": round(float(accuracy), 4),
        "holdout_rows": len(test_texts),
        "training_rows": len(texts),
        "per_class": {
            label: {
                key: round(float(value), 4)
                for key, value in report.get(label, {}).items()
                if key in ("precision", "recall", "f1-score")
            }
            for label in LABELS
        },
        "features": int(full_matrix.shape[1]),
        "seed": args.seed,
    }
    export_model(
        Path(args.output).resolve(),
        final_vectorizer,
        final_classifier,
        metrics,
        source=str(data_path),
    )


if __name__ == "__main__":
    main()

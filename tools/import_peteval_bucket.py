#!/usr/bin/env python3
"""Genera database/data/peteval.json desde el split de prueba de PetEVAL.

Requiere pandas y pyarrow. Ejemplo:
    python3 tools/import_peteval_bucket.py /ruta/test.parquet database/data/peteval.json
"""

from __future__ import annotations

import ast
import json
import sys
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

import pandas as pd


def parse_list(value: object) -> list:
    if isinstance(value, list):
        return value
    if not isinstance(value, str) or not value.strip():
        return []
    try:
        parsed = ast.literal_eval(value)
        return parsed if isinstance(parsed, list) else []
    except (ValueError, SyntaxError):
        return []


def main() -> None:
    source = Path(sys.argv[1] if len(sys.argv) > 1 else "/tmp/peteval_bucket/test.parquet").resolve()
    output = Path(sys.argv[2] if len(sys.argv) > 2 else "database/data/peteval.json").resolve()
    frame = pd.read_parquet(source)

    records = []
    label_counts: Counter[str] = Counter()
    disease_counts: Counter[str] = Counter()
    anonymisation_counts: Counter[str] = Counter()
    with_disease = 0
    with_anonymisation = 0

    for row in frame.itertuples(index=False):
        sentence = str(row.sentence)
        icd_labels = [str(label) for label in parse_list(row.icd_label)]
        diseases = []
        for entity in parse_list(row.disease):
            if isinstance(entity, dict) and entity.get("entity"):
                diseases.append({
                    "entity": str(entity["entity"]),
                    "start": int(entity.get("start", 0)),
                    "end": int(entity.get("end", 0)),
                })
                disease_counts[str(entity["entity"])] += 1
        anonymisation = []
        for entity in parse_list(row.annonymisation):
            if isinstance(entity, dict) and entity.get("label"):
                anonymisation.append({
                    "label": str(entity["label"]),
                    "start": int(entity.get("start", 0)),
                    "end": int(entity.get("end", 0)),
                })
                anonymisation_counts[str(entity["label"])] += 1
        for label in icd_labels:
            label_counts[label] += 1
        if diseases:
            with_disease += 1
        if anonymisation:
            with_anonymisation += 1

        records.append({
            "id": int(row.id),
            "sentence": sentence,
            "icd_labels": icd_labels,
            "diseases": diseases,
            "anonymisation": anonymisation,
        })

    payload = {
        "source_bucket": "https://huggingface.co/buckets/Mikato8/PetEVAL-bucket",
        "source_repository": "Mikato8/PetEVAL-bucket",
        "paper_url": "https://aclanthology.org/2025.bionlp-1.29/",
        "license": "Apache-2.0",
        "imported_at": datetime.now(timezone.utc).strftime("%Y-%m-%d"),
        "split": "test",
        "stats": {
            "records": len(records),
            "with_disease": with_disease,
            "with_anonymisation": with_anonymisation,
            "icd_labels": len(label_counts),
            "disease_entities": len(disease_counts),
            "anonymisation_entities": sum(anonymisation_counts.values()),
        },
        "icd_labels": [
            {"label": label, "count": count}
            for label, count in label_counts.most_common()
        ],
        "top_diseases": [
            {"entity": entity, "count": count}
            for entity, count in disease_counts.most_common(20)
        ],
        "anonymisation_types": [
            {"label": label, "count": count}
            for label, count in anonymisation_counts.most_common()
        ],
        "records": records,
    }
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(payload, ensure_ascii=False, separators=(",", ":")) + "\n", encoding="utf-8")
    print(f"{len(records)} registros · {len(label_counts)} etiquetas ICD · {len(disease_counts)} entidades · {output} ({output.stat().st_size} bytes)")


if __name__ == "__main__":
    main()

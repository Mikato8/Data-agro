#!/usr/bin/env python3
"""Convierte los archivos Markdown de VetDataHub en un catálogo JSON para el portal.

Uso:
    python3 tools/import_vetdatahub.py /ruta/a/VetDataHub database/data/vetdatahub.json
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlparse

CATEGORIES = {
    "anatomy": "Anatomía",
    "animal_condition": "Condición animal",
    "animal_welfare": "Bienestar animal",
    "avian": "Aves",
    "behavioral": "Conducta",
    "biochemistry": "Bioquímica",
    "canine": "Caninos",
    "clinical_records": "Registros clínicos",
    "diagnostic_imaging": "Imagen diagnóstica",
    "epidermiology": "Epidemiología",
    "equine": "Equinos",
    "farm": "Producción animal",
    "feline": "Felinos",
    "genetics": "Genética",
    "immunology": "Inmunología",
    "laboratory": "Laboratorio",
    "microbiology": "Microbiología",
    "others": "Otros",
    "pathology": "Patología",
    "pharmacology": "Farmacología",
    "physiological": "Fisiología",
    "rabbit-disorder": "Conejos",
    "research_papers": "Artículos de investigación",
    "veterinary_images": "Imágenes veterinarias",
    "wildlife": "Fauna silvestre",
}

URL_RE = re.compile(r"\((https?://[^)]+)\)|Download Link:\s*(https?://\S+)", re.I)


def infer_source(url: str) -> str:
    host = urlparse(url).netloc.lower()
    if "kaggle.com" in host:
        return "Kaggle"
    if "github.com" in host:
        return "GitHub"
    if "figshare.com" in host:
        return "figshare"
    if "zenodo.org" in host:
        return "Zenodo"
    if "dryad" in host:
        return "Dryad"
    if "doi.org" in host:
        return "DOI"
    return host.replace("www.", "") or "Fuente original"


def normalize_source(value: str) -> str:
    value = value.strip()
    mapping = {"github": "GitHub", "kaggle": "Kaggle", "figshare": "figshare", "zenodo": "Zenodo"}
    return mapping.get(value.lower(), value)


def clean_title(value: str) -> str:
    value = re.sub(r"^[#*\-\s]+", "", value).strip()
    value = re.sub(r"\s+", " ", value)
    return value.strip(" .")


def parse_file(path: Path) -> list[dict]:
    text = path.read_text(encoding="utf-8", errors="replace")
    entries: list[dict] = []
    buffer: list[str] = []

    def flush(lines: list[str]) -> dict | None:
        url = None
        source = ""
        for line in lines:
            match = URL_RE.search(line)
            if match:
                url = match.group(1) or match.group(2)
            source_match = re.search(r"Source\s*:\s*(.+)", line, re.I)
            if source_match:
                source = source_match.group(1).strip()

        if not url:
            return None

        title = ""
        description: list[str] = []
        for line in lines:
            stripped = line.strip()
            if not stripped:
                continue
            if re.match(r"^(Download Link\s*:|\[Download\]|Source\s*:)", stripped, re.I):
                continue
            if stripped.startswith("#") and not title:
                title = clean_title(stripped.lstrip("#"))
                continue
            if not title:
                title = clean_title(stripped)
                continue
            description.append(stripped)

        title = clean_title(title)
        if not title or title.lower().startswith(("source", "download")):
            title = "Dataset sin título"
        description_text = re.sub(r"\s+", " ", " ".join(description)).strip()
        if len(description_text) > 1200:
            description_text = description_text[:1197].rstrip() + "..."

        return {
            "title": title,
            "description": description_text,
            "url": url,
            "source": normalize_source(source) if source else infer_source(url),
        }

    for line in text.splitlines():
        if re.search(r"\[Download\]\(|Download Link\s*:", line, re.I):
            buffer.append(line)
            entry = flush(buffer)
            if entry:
                entries.append(entry)
            buffer = []
        else:
            buffer.append(line)

    return entries


def main() -> None:
    repo = Path(sys.argv[1] if len(sys.argv) > 1 else "/tmp/VetDataHub").resolve()
    output = Path(sys.argv[2] if len(sys.argv) > 2 else "database/data/vetdatahub.json").resolve()
    datasets_dir = repo / "Datasets"
    if not datasets_dir.is_dir():
        raise SystemExit(f"No se encontró {datasets_dir}")

    try:
        commit = subprocess.check_output(["git", "-C", str(repo), "rev-parse", "--short", "HEAD"], text=True).strip()
    except Exception:
        commit = "desconocido"

    datasets = []
    seen_urls: set[str] = set()
    categories = []
    for path in sorted(datasets_dir.glob("*.md")):
        key = path.stem
        label = CATEGORIES.get(key, key.replace("-", " ").title())
        entries = parse_file(path)
        unique_entries = []
        for entry in entries:
            normalized = entry["url"].rstrip("/")
            if normalized in seen_urls:
                continue
            seen_urls.add(normalized)
            entry["category"] = key
            entry["category_label"] = label
            unique_entries.append(entry)
        if unique_entries:
            categories.append({"key": key, "label": label, "count": len(unique_entries)})
            datasets.extend(unique_entries)

    payload = {
        "source_repository": "https://github.com/Mikato8/VetDataHub",
        "source_commit": commit,
        "imported_at": datetime.now(timezone.utc).strftime("%Y-%m-%d"),
        "categories": categories,
        "datasets": datasets,
    }
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"{len(datasets)} datasets en {len(categories)} categorías · commit {commit} · {output}")


if __name__ == "__main__":
    main()

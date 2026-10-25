#!/usr/bin/env bash
set -euo pipefail

# Sincroniza el bucket público de PetEVAL con el explorador del portal.
# Requisitos: hf CLI, Python con pandas y pyarrow.
#
# Uso:
#   ./tools/sync_peteval_bucket.sh
#   PYTHON=/ruta/python ./tools/sync_peteval_bucket.sh

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PYTHON="${PYTHON:-python3}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

export HF_HOME="${HF_HOME:-$WORK/hf-home}"
export HF_HUB_CACHE="${HF_HUB_CACHE:-$WORK/hf-cache}"
mkdir -p "$HF_HOME" "$HF_HUB_CACHE"

BUCKET="hf://buckets/Mikato8/PetEVAL-bucket"
DEST="$ROOT/public/descargas/peteval"
mkdir -p "$DEST"

echo "→ Descargando README, artículo y Parquet..."
hf buckets cp "$BUCKET/README.md" "$WORK/README.md"
hf buckets cp "$BUCKET/sean_farrell_peteval.pdf" "$WORK/peteval-paper.pdf"
hf buckets cp "$BUCKET/data/test-00000-of-00001.parquet" "$WORK/test.parquet"

cp "$WORK/README.md" "$DEST/README.md"
cp "$WORK/peteval-paper.pdf" "$DEST/peteval-paper.pdf"
cp "$WORK/test.parquet" "$DEST/test-00000-of-00001.parquet"

echo "→ Regenerando database/data/peteval.json..."
"$PYTHON" "$ROOT/tools/import_peteval_bucket.py" "$WORK/test.parquet" "$ROOT/database/data/peteval.json"

echo
echo "Listo. Revisa los cambios con:"
echo "  git status --short"
echo "  git diff --stat"
echo
echo "Si hay cambios, prueba en local y despliega con tu flujo habitual."

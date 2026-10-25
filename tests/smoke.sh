#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
fail=0

check() {
    local path="$1"
    local expected="${2:-200}"
    local code
    code=$(curl -sS -o /tmp/dv_smoke_body.html -w '%{http_code}' --max-time 20 "${BASE_URL}${path}" || true)
    if [ "$code" != "$expected" ]; then
        echo "✗ ${path}: esperado ${expected}, recibido ${code}"
        fail=1
    else
        echo "✓ ${path} (${code})"
    fi
}

check "/"
check "/casos"
check "/casos/parvovirus-canino-cachorro-diarrea-hemorragica"
check "/casos/parvovirus-canino-cachorro-diarrea-hemorragica/pdf"
check "/casos/parvovirus-canino-cachorro-diarrea-hemorragica/docx"
check "/casos/parvovirus-canino-cachorro-diarrea-hemorragica/ia-urgencia"
check "/apoyo-diagnostico"
check "/apoyo-diagnostico/ia"
check "/panel/entrar"
check "/panel" 302
check "/enfermedades"
check "/enfermedades/parvovirus-canino"
check "/recursos"
check "/recursos/kit"
check "/recursos/glosario"
check "/recursos/revistas"
check "/vetdatahub"
check "/peteval"
check "/peteval/1111025"
check "/descargas/peteval/test-00000-of-00001.parquet"
check "/recursos/favoritos" 302
check "/descargas/plantilla-caso-clinico.docx"
check "/descargas/hoja-de-alta.docx"
check "/descargas/consentimiento-informado.docx"
check "/acceder"
check "/registro"
check "/salud"
check "/sitemap.xml"
check "/robots.txt"

if ! grep -q '"status":"ok"' /tmp/dv_smoke_body.html 2>/dev/null; then
    # El último check es robots.txt; se consulta salud de nuevo para validar JSON.
    curl -sS --max-time 20 "${BASE_URL}/salud" -o /tmp/dv_health.json
    if ! grep -q '"status":"ok"' /tmp/dv_health.json; then
        echo "✗ /salud no reporta estado ok"
        fail=1
    fi
fi

if [ "$fail" -ne 0 ]; then
    echo "Pruebas de humo fallidas."
    exit 1
fi

echo "Pruebas de humo completadas."

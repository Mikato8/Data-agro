#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
fail=0

check() {
    local path="$1"
    local expected="${2:-200}"
    local code
    code=$(curl -sS -o /tmp/agro_smoke_body.html -w '%{http_code}' --max-time 20 "${BASE_URL}${path}" || true)
    if [ "$code" != "$expected" ]; then
        echo "✗ ${path}: esperado ${expected}, recibido ${code}"
        fail=1
    else
        echo "✓ ${path} (${code})"
    fi
}

check "/"
check "/casos"
check "/casos/cogollero-maiz-temporal-sinaloa"
check "/casos/cogollero-maiz-temporal-sinaloa/pdf"
check "/casos/cogollero-maiz-temporal-sinaloa/docx"
check "/casos/cogollero-maiz-temporal-sinaloa/ia-emergencia"
check "/apoyo-diagnostico"
check "/apoyo-diagnostico?crops=maiz"
check "/apoyo-diagnostico/ia"
check "/panel/entrar"
check "/panel" 302
check "/panel/parcelas" 302
check "/enfermedades"
check "/enfermedades/gusano-cogollero"
check "/recursos"
check "/recursos/kit"
check "/recursos/glosario"
check "/recursos/revistas"
check "/agrodatos"
check "/recursos/favoritos" 302
check "/descargas/plantilla-caso-agronomico.docx"
check "/descargas/hoja-de-recomendacion.docx"
check "/descargas/consentimiento-informado.docx"
check "/descargas/kit-recursos-agronomicos.zip"
check "/acceder"
check "/registro"
check "/salud"
check "/sitemap.xml"
check "/robots.txt"

if ! grep -q '"status":"ok"' /tmp/agro_smoke_body.html 2>/dev/null; then
    # El último check es robots.txt; se consulta salud de nuevo para validar JSON.
    curl -sS --max-time 20 "${BASE_URL}/salud" -o /tmp/agro_health.json
    if ! grep -q '"status":"ok"' /tmp/agro_health.json; then
        echo "✗ /salud no reporta estado ok"
        fail=1
    fi
fi

if ! grep -qi "AgroDiagnóstico" /tmp/agro_smoke_body.html 2>/dev/null; then
    curl -sS --max-time 20 "${BASE_URL}/" -o /tmp/agro_home.html
    if ! grep -qi "AgroDiagnóstico" /tmp/agro_home.html; then
        echo "✗ La portada no muestra el nombre del portal"
        fail=1
    fi
fi

if [ "$fail" -ne 0 ]; then
    echo "Pruebas de humo fallidas."
    exit 1
fi

echo "Pruebas de humo completadas."

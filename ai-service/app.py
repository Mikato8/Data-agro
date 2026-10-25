#!/usr/bin/env python3
"""AgroDiagnóstico · clasificador de riesgo fitosanitario.

Microservicio FastAPI que clasifica un relato de campo en cuatro niveles de
riesgo fitosanitario:

    emergencia | urgente | consulta | preventivo

El motor principal es determinista y explicable: normaliza el español (acentos,
mayúsculas), busca términos agronómicos ponderados por nivel, aplica
intensificadores/atenuadores, descarta términos negados y ejecuta reglas de
seguridad de prioridad alta (cuarentena, pérdida total, intoxicaciones...).

Opcionalmente puede combinar sus puntuaciones con un modelo TF-IDF +
regresión logística exportado a JSON (ver ``training/train_model.py``). Si ese
archivo no existe, el servicio funciona igual: solo con reglas. No usa torch,
transformers, ONNX ni descarga modelos en tiempo de ejecución.

Contrato HTTP consumido por ``app/Services/RiskAiClient.php`` y las vistas del
portal:

    POST /classify  {"text": "..."} -> label, label_es, description, color,
                       confidence, scores, safety_rule, model, disclaimer y
                       evidence.
    GET  /health -> {"status": "ok", ...} o 503 mientras arranca.

Este es un prototipo educativo. No es un diagnóstico fitosanitario ni
sustituye la valoración de un ingeniero agrónomo o un laboratorio.
"""

from __future__ import annotations

import json
import logging
import math
import os
import re
import secrets
import unicodedata
from contextlib import asynccontextmanager
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable

from fastapi import Depends, FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

BASE_DIR = Path(__file__).resolve().parent
DEFAULT_MODEL_PATH = BASE_DIR / "model" / "tfidf_logreg.json"

LABELS: tuple[str, ...] = ("emergencia", "urgente", "consulta", "preventivo")

LABEL_INFO: dict[str, dict[str, str]] = {
    "emergencia": {
        "label_es": "Emergencia fitosanitaria",
        "description": (
            "Actúa de inmediato: aísla la zona, evita mover material vegetal y "
            "contacta a tu asesor o a la autoridad fitosanitaria (SENASICA). No "
            "esperes la clasificación."
        ),
        "color": "critical",
    },
    "urgente": {
        "label_es": "Urgente",
        "description": (
            "Revisa la parcela en las próximas 24-72 horas, confirma el problema y "
            "aplica control dirigido. Documenta la evolución con fotos y fechas."
        ),
        "color": "high",
    },
    "consulta": {
        "label_es": "Consulta programada",
        "description": (
            "Puede programarse: recolecta muestras representativas y agenda revisión "
            "con un ingeniero agrónomo en los próximos días."
        ),
        "color": "medium",
    },
    "preventivo": {
        "label_es": "Preventivo",
        "description": (
            "Mantén el plan de manejo: muestreo, fertilización, rotación y monitoreo. "
            "Registra la información para la próxima temporada."
        ),
        "color": "low",
    },
}

RULES_VERSION = "reglas-agro-v1"
DISCLAIMER = (
    "Herramienta educativa de apoyo: no es un diagnóstico fitosanitario ni "
    "sustituye la visita de un ingeniero agrónomo ni la determinación de un laboratorio."
)


def _env_int(name: str, default: int) -> int:
    raw = os.getenv(name, "").strip()
    if not raw:
        return default
    try:
        return max(1, int(raw))
    except ValueError:
        return default


TOKEN = os.getenv("AI_SERVICE_TOKEN", "").strip()
MAX_CHARS = _env_int("AI_MAX_CHARS", 4000)
MODEL_PATH = Path(os.getenv("AI_MODEL_PATH", str(DEFAULT_MODEL_PATH))).expanduser()

logging.basicConfig(level=os.getenv("LOG_LEVEL", "INFO").upper())
LOGGER = logging.getLogger("agro-risk")


# ---------------------------------------------------------------------------
# Normalización de texto
# ---------------------------------------------------------------------------

_WHITESPACE_RE = re.compile(r"\s+")


def normalize_text(value: str) -> str:
    """Minúsculas, sin acentos y con espacios colapsados.

    ``NFKD`` separa los acentos de las vocales y ``combining`` los elimina, de
    modo que "Manchás" y "manchas" caen en la misma forma. La letra ñ se
    convierte en n, igual que hace scikit-learn con ``strip_accents="unicode"``.
    """

    decomposed = unicodedata.normalize("NFKD", value.lower())
    plain = "".join(char for char in decomposed if not unicodedata.combining(char))
    return _WHITESPACE_RE.sub(" ", plain).strip()


# ---------------------------------------------------------------------------
# Léxico ponderado por nivel
# ---------------------------------------------------------------------------


@dataclass(frozen=True)
class Term:
    """Término explicable con su patrón y peso dentro de un nivel."""

    name: str
    pattern: re.Pattern[str]
    weight: float


def _rx(pattern: str) -> re.Pattern[str]:
    return re.compile(pattern)


def _terms(items: Iterable[tuple[str, str, float]]) -> tuple[Term, ...]:
    return tuple(Term(name, _rx(pattern), weight) for name, pattern, weight in items)


# Cada patrón se aplica sobre texto normalizado (minúsculas, sin acentos).
# Los pesos están calibrados para que una señal clara domine, pero dos señales
# medias no alcancen por sí solas el nivel de emergencia.
LEXICON: dict[str, tuple[Term, ...]] = {
    "emergencia": _terms(
        (
            (
                "HLB",
                r"\bhlb\b|\bhuanglongbing\b|\bdragon amarillo\b|\bgreening\b",
                4.5,
            ),
            (
                "Fusarium R4T",
                r"\bfusarium\b|\br4t\b|\braza 4 tropical\b",
                4.3,
            ),
            (
                "mosca del Mediterráneo",
                r"\bmosca del mediterraneo\b|\bmosca de la fruta\b|\bceratitis capitata\b",
                4.3,
            ),
            (
                "langosta",
                r"\blangosta\b|\bschistocerca\b",
                4.0,
            ),
            (
                "cuarentena",
                r"\bcuarentena\b|\bcuarentenari[oa]s?\b",
                4.0,
            ),
            (
                "brote fitosanitario",
                r"\bbrote (?:de|por) (?:plaga|enfermedad|virus|hongo|bacteria|langosta"
                r"|mosca|pulgon|roya|hlb)\b"
                r"|\bbrote (?:fitosanitario|epidemico|activo)\b"
                r"|\b(?:hay|detect\w*|confirm\w*) (?:un|el) brote\b"
                r"(?!\s+(?:nuevo|nueva|nuevos|nuevas|tierno|tiernos|tierna|tiernas"
                r"|foliar|vegetativo|joven|jovenes|verde|verdes))"
                r"|\bbrote en (?:la|el) (?:zona|parcela|lote|huerta|region|municipio)\b",
                3.4,
            ),
            (
                "plaga nueva",
                r"\bplaga (?:nueva|emergente)\b|\bnueva plaga\b|\bprimera deteccion\b"
                r"|\bplaga detectada en zona nueva\b",
                3.5,
            ),
            (
                "SENASICA",
                r"\bsenasica\b|\baviso fitosanitario\b|\bdeclaratoria\b"
                r"|\bemergencia fitosanitaria\b",
                3.8,
            ),
            (
                "muerte de plantas",
                r"\bmuerte de (?:las )?plantas\b|\bplantas muertas\b|\bse murieron\b"
                r"|\bmurieron las plantas\b|\bse esta muriendo\b|\bse estan muriendo\b"
                r"|\bse murio todo\b",
                3.6,
            ),
            (
                "pérdida total",
                r"\bperdida total\b|\bperdida del 100\b|\bperdi toda la cosecha\b"
                r"|\bno hay cosecha\b|\bse perdio la cosecha\b",
                3.6,
            ),
            (
                "síntomas generalizados",
                r"\bsintomas generalizados\b|\bafectacion generalizada\b"
                r"|\bproblema generalizado\b",
                3.4,
            ),
            (
                "erradicación",
                r"\berradicacion\b|\berradicar\b|\barras\w*\b|\bdevast\w*\b",
                3.2,
            ),
            (
                "intoxicación por pesticida",
                r"\bintoxic\w*\b|\benvenen\w*\b"
                r"|\bderiva de (?:pesticida|herbicida|plaguicida)\b",
                4.0,
            ),
            (
                "riesgo para trabajadores o consumidores",
                r"\briesgo para (?:los )?(?:trabajadores|consumidores|personas|ninos|familias)\b"
                r"|\btrabajadores (?:enfermos|intoxicados)\b"
                r"|\bconsumidores (?:enfermos|intoxicados)\b",
                3.8,
            ),
            (
                "pérdida inminente",
                r"\bpeligro de perder\b|\bperdida inminente\b|\ben riesgo la cosecha\b",
                3.2,
            ),
        )
    ),
    "urgente": _terms(
        (
            (
                "manchas que se expanden",
                r"\bmanchas? (?:que )?(?:se )?expand\w*\b|\bse expand\w*\b"
                r"|\bexpandiend\w*\b",
                2.6,
            ),
            (
                "avance rápido",
                r"\bavanz\w* (?:rapido|acelerad\w*)\b|\bavanzando\b|\bprogresando\b",
                2.4,
            ),
            (
                "avance de síntomas",
                r"\b(?:manchas?|sintomas?|lesiones?|danio|enfermedad|plaga|pudricion)\b"
                r"[^.;]{0,30}\bavanz\w*\b"
                r"|\bavanz\w*\b[^.;]{0,30}\b(?:manchas?|sintomas?|lesiones?|danio"
                r"|enfermedad|plaga)\b"
                r"|\bse extend\w*\b",
                2.3,
            ),
            (
                "marchitez súbita",
                r"\bmarchitez (?:subita|repentina|rapida)\b"
                r"|\bmarchitamiento (?:subito|repentino|rapido)\b|\bse marchito\b"
                r"|\bse doblaron\b|\bcolapso\b",
                2.7,
            ),
            (
                "defoliación severa",
                r"\bdefoliacion\b|\bdesfoliacion\b|\bse quedo sin hojas\b"
                r"|\bperdida (?:severa|total) de hojas\b|\bsin hojas\b",
                2.4,
            ),
            (
                "pudrición con mal olor",
                r"\bpudricion\b|\bpodredumbre\b|\bmal olor\b|\bolor fetido\b"
                r"|\bpudricion blanda\b",
                2.5,
            ),
            (
                "daño por granizo o helada",
                r"\bgranizo\b|\bpedrisco\b|\bhelada\b|\bhelo\b"
                r"|\bsequia (?:severa|prolongada)\b|\binundacion\b|\bviento fuerte\b",
                2.4,
            ),
            (
                "propagación",
                r"\bpropag\w*\b|\bdisemin\w*\b|\bcontagio\b",
                2.2,
            ),
            (
                "roya",
                r"\broya\b",
                1.7,
            ),
            (
                "tizón o mildiu",
                r"\btizon\b|\bmildiu\b|\bantracnosis\b|\bbotritis\b",
                1.5,
            ),
            (
                "plaga activa",
                r"\binfestacion\b|\balta (?:poblacion|incidencia)\b"
                r"|\bplaga (?:activa|severa)\b",
                2.3,
            ),
            (
                "cogollero o barrenador",
                r"\bcogollero\b|\bbarrenador\b|\bpicudo\b|\boruga\b|\blarvas\b",
                1.6,
            ),
            (
                "secado rápido",
                r"\bse esta secando\b|\bse seca\b|\bsecado (?:rapido|repentino)\b"
                r"|\bramas secas\b",
                2.2,
            ),
            (
                "clorosis severa",
                r"\bamarillamiento (?:repentino|severo)\b|\bclorosis severa\b",
                2.0,
            ),
        )
    ),
    "consulta": _terms(
        (
            (
                "manchas leves",
                r"\bmanchas? (?:leves?|ligeras?|aisladas?|incipientes?)\b"
                r"|\balgunas manchas\b|\bmanchas foliares\b",
                1.7,
            ),
            (
                "manchas",
                r"\bmanchas?\b",
                0.7,
            ),
            (
                "clorosis o amarillamiento",
                r"\bclorosis\b|\bamarillamiento\b|\bhojas amarillas\b|\bamarilleo\b",
                1.2,
            ),
            (
                "deficiencia nutricional",
                r"\bdeficiencia de (?:nitrogeno|hierro|zinc|magnesio|potasio|fosforo|calcio|boro)\b"
                r"|\bdeficiencia nutricional\b|\bfalta de nutrientes\b",
                1.8,
            ),
            (
                "insectos aislados",
                r"\binsectos? (?:aislad\w*|pocos?)\b|\bpocos insectos\b"
                r"|\bpresencia leve\b|\buna (?:sola )?planta\b|\bdos plantas\b"
                r"|\bplantas aisladas\b",
                2.0,
            ),
            (
                "plagas comunes localizadas",
                r"\bpulgon\w*\b|\bmosca blanca\b|\barana roja\b|\btrips\b"
                r"|\bminador\b|\bcochinilla\b|\bcaracol\w*\b",
                1.2,
            ),
            (
                "malezas",
                r"\bmaleza\w*\b|\bhierba\w*\b",
                1.0,
            ),
            (
                "consulta o duda",
                r"\bque puede ser\b|\bque tiene\b|\bquiero identificar\b"
                r"|\bnecesito identificar\b|\btengo una duda\b|\bconsulta\b"
                r"|\brevision\b|\bdiagnostico\b",
                0.9,
            ),
            (
                "vivero o injerto",
                r"\bvivero\b|\binjerto\b|\bsemilla\w*\b",
                0.6,
            ),
        )
    ),
    "preventivo": _terms(
        (
            (
                "fertilización",
                r"\bfertilizacion\b|\bfertilizar\b|\babonado\b|\babono\w*\b"
                r"|\bcompost\b|\bnutricion vegetal\b",
                2.3,
            ),
            (
                "muestreo",
                r"\bmuestreo\b|\bmuestras? de suelo\b|\banalisis de suelo\b"
                r"|\btoma de muestras\b",
                2.5,
            ),
            (
                "monitoreo",
                r"\bmonitoreo\b|\bmonitorear\b|\btrampeo\b|\btrampas?\b|\bvigilancia\b",
                2.4,
            ),
            (
                "rotación de cultivos",
                r"\brotacion(?: de cultivos)?\b|\bcultivo de cobertura\b"
                r"|\basociacion de cultivos\b",
                2.4,
            ),
            (
                "prevención",
                r"\bprevencion\b|\bprevenir\b|\bpreventiv\w*\b"
                r"|\bmanejo integrado\b|\bmip\b",
                2.3,
            ),
            (
                "planificación",
                r"\bplanificacion\b|\bplaneacion\b|\bplaneando\b"
                r"|\bplan de (?:manejo|fertilizacion|riego|siembra)\b|\bcalendario\b"
                r"|\bprograma de\b",
                2.0,
            ),
            (
                "riego",
                r"\briego\b|\briego por goteo\b|\binstalacion de riego\b",
                1.6,
            ),
            (
                "poda",
                r"\bpoda sanitaria\b|\bpoda de formacion\b|\bpoda\b",
                1.8,
            ),
            (
                "certificación o bitácora",
                r"\bcertificacion\b|\btrazabilidad\b|\bbitacora\b|\bcapacitacion\b"
                r"|\bbuenas practicas\b",
                1.8,
            ),
            (
                "próxima temporada",
                r"\bproxima temporada\b|\bsiguiente ciclo\b|\bantes de sembrar\b"
                r"|\bpreparacion del terreno\b|\bplan de siembra\b",
                2.2,
            ),
            (
                "orgánico",
                r"\borganic\w*\b",
                1.3,
            ),
        )
    ),
}


# ---------------------------------------------------------------------------
# Modificadores, negación y superficie afectada
# ---------------------------------------------------------------------------

_CLAUSE_SPLIT_RE = re.compile(r"[.;,:!?]")
_NEGATION_RE = re.compile(
    r"\b(?:no|sin|ni|ningun\w*|ausencia(?: de)?|descart\w*|niega"
    r"|negativ\w*|libre de)\b(?:\s+\w+){0,4}\s*$"
)
_INTENSIFIER_RE = re.compile(
    r"\b(?:muy|mucho|mucha|sever[oa]s?|grave|generalizad[oa]|total|complet[oa]"
    r"|rapid[oa]|acelerad[oa]|drastic[oa]|fuerte|intens[oa]|todo|toda|todos|todas"
    r"|masiv[oa]|extrem[oa])\b"
)
_DIMINISHER_RE = re.compile(
    r"\b(?:leve|leves|liger[oa]s?|poc[oa]s?|aislad[oa]s?|incipiente|minim[oa]"
    r"|apenas|escas[oa]|solo|unicamente|una sola|un solo)\b"
)
_SPEED_RE = re.compile(
    r"\b(?:rapid\w*|acelerad\w*|subit\w*|repentin\w*|de un dia para otro"
    r"|en 24 horas|en 48 horas|en 72 horas|desde ayer|desde antier|en dos dias"
    r"|en tres dias|esta semana|avanz\w*|expand\w*|propag\w*)\b"
)
_PERCENT_RE = re.compile(
    r"(?<!\d)(\d{1,3}(?:[.,]\d+)?)\s*(?:%|por\s*ciento|porciento)(?!\d)"
)
_HALF_RE = re.compile(
    r"\bmas de la mitad\b|\bla mayoria de (?:las )?plantas\b"
    r"|\bmas (?:del|de) 50\s*(?:%|por\s*ciento)(?!\d)"
)
_WHOLE_RE = re.compile(
    r"\b(?:toda|todo)\s+(?:la\s+|el\s+)?"
    r"(?:parcela|huerta|lote|predio|invernadero|superficie|cultivo)\b"
    r"|\btodas las plantas\b"
)
_DAMAGE_RE = re.compile(
    r"\b(?:afect\w*|danad\w*|dan[oa]\w*|perd\w*|muert\w*|muer\w*|enferm\w*"
    r"|plag\w*|manch\w*|sintom\w*|atac\w*|infest\w*|sever\w*|problem\w*"
    r"|deficien\w*|pudric\w*|marchit\w*|defoli\w*)\b"
)


def _clause_tail(text: str, start: int, size: int) -> str:
    window = text[max(0, start - size):start]
    return _CLAUSE_SPLIT_RE.split(window)[-1]


def _is_negated(text: str, start: int) -> bool:
    """Detecta negaciones simples antes del término (``sin HLB``, ``no hay``)."""

    return bool(_NEGATION_RE.search(_clause_tail(text, start, 60)))


def _intensity_factor(text: str, start: int, end: int) -> float:
    """Ajusta el peso según palabras cercanas: "severa" sube, "leve" baja."""

    before = _clause_tail(text, start, 50)
    after = _CLAUSE_SPLIT_RE.split(text[end:end + 40])[0]
    strong = len(_INTENSIFIER_RE.findall(before + " " + after))
    mild = len(_DIMINISHER_RE.findall(before + " " + after))
    factor = 1.0
    if strong:
        factor *= min(1.6, 1.0 + 0.3 * strong)
    if mild:
        factor *= max(0.4, 1.0 - 0.35 * mild)
    return max(0.3, min(1.8, factor))


def _affected_area(text: str) -> tuple[float | None, bool, bool]:
    percents: list[float] = []
    for match in _PERCENT_RE.finditer(text):
        try:
            value = float(match.group(1).replace(",", "."))
        except ValueError:
            continue
        if 0 <= value <= 100:
            percents.append(value)
    max_percent = max(percents) if percents else None
    return max_percent, bool(_HALF_RE.search(text)), bool(_WHOLE_RE.search(text))


def _has_damage_context(text: str) -> bool:
    """True si hay una palabra de daño que no esté negada ("no muestra síntomas")."""

    return any(not _is_negated(text, match.start()) for match in _DAMAGE_RE.finditer(text))


# ---------------------------------------------------------------------------
# Reglas de seguridad: elevan a emergencia sin depender del modelo
# ---------------------------------------------------------------------------


@dataclass(frozen=True)
class SafetyRule:
    key: str
    term: str
    pattern: re.Pattern[str]
    message: str
    confidence: float


SAFETY_RULES: tuple[SafetyRule, ...] = (
    SafetyRule(
        "hlb",
        "HLB",
        _rx(r"\bhlb\b|\bhuanglongbing\b|\bdragon amarillo\b|\bgreening\b"),
        "Se detectó HLB (huanglongbing), plaga cuarentenaria: se prioriza emergencia "
        "y se recomienda aviso inmediato a SENASICA.",
        0.97,
    ),
    SafetyRule(
        "fusarium_r4t",
        "Fusarium R4T",
        _rx(r"\bfusarium\b|\br4t\b|\braza 4 tropical\b"),
        "Se detectó Fusarium R4T, patógeno cuarentenario: se prioriza emergencia y se "
        "recomienda contención y aviso a SENASICA.",
        0.97,
    ),
    SafetyRule(
        "mosca_mediterraneo",
        "mosca del Mediterráneo",
        _rx(r"\bmosca del mediterraneo\b|\bmosca de la fruta\b|\bceratitis capitata\b"),
        "Se detectó mosca del Mediterráneo, plaga cuarentenaria: se prioriza emergencia "
        "y se recomienda aviso fitosanitario inmediato.",
        0.97,
    ),
    SafetyRule(
        "langosta",
        "langosta",
        _rx(r"\blangosta\b|\bschistocerca\b"),
        "Se detectó langosta o un brote de plaga emergente: se prioriza emergencia "
        "fitosanitaria y coordinación con la autoridad.",
        0.96,
    ),
    SafetyRule(
        "cuarentena",
        "cuarentena",
        _rx(r"\bcuarentena\b|\bcuarentenari[oa]s?\b"),
        "Se menciona cuarentena: se prioriza emergencia y aviso inmediato a la "
        "autoridad fitosanitaria.",
        0.95,
    ),
    SafetyRule(
        "plaga_nueva",
        "plaga nueva",
        _rx(
            r"\bplaga (?:nueva|emergente)\b|\bnueva plaga\b|\bprimera deteccion\b"
            r"|\bplaga detectada en zona nueva\b"
        ),
        "Se reporta una plaga nueva o una primera detección: se prioriza emergencia y "
        "aviso fitosanitario inmediato.",
        0.96,
    ),
    SafetyRule(
        "brote",
        "brote fitosanitario",
        _rx(
            r"\bbrote (?:de|por) (?:plaga|enfermedad|virus|hongo|bacteria|langosta"
            r"|mosca|pulgon|roya|hlb)\b"
            r"|\bbrote (?:fitosanitario|epidemico|activo)\b"
            r"|\b(?:hay|detect\w*|confirm\w*) (?:un|el) brote\b"
            r"(?!\s+(?:nuevo|nueva|nuevos|nuevas|tierno|tiernos|tierna|tiernas"
            r"|foliar|vegetativo|joven|jovenes|verde|verdes))"
            r"|\bbrote en (?:la|el) (?:zona|parcela|lote|huerta|region|municipio)\b"
        ),
        "Se reporta un brote fitosanitario: se prioriza emergencia y aviso a la "
        "autoridad.",
        0.95,
    ),
    SafetyRule(
        "senasica",
        "SENASICA",
        _rx(r"\bsenasica\b"),
        "Se menciona a SENASICA: se prioriza emergencia y coordinación con la autoridad "
        "fitosanitaria.",
        0.96,
    ),
    SafetyRule(
        "aviso_fitosanitario",
        "aviso fitosanitario",
        _rx(
            r"\baviso fitosanitario\b|\bdeclaratoria\b|\bemergencia fitosanitaria\b"
        ),
        "Se menciona un aviso o una declaratoria fitosanitaria: se prioriza emergencia.",
        0.95,
    ),
    SafetyRule(
        "perdida_total",
        "pérdida total",
        _rx(
            r"\bperdida total\b|\bperdida del 100\b|\bperdi toda la cosecha\b"
            r"|\bno hay cosecha\b|\bse perdio la cosecha\b|\bse esta muriendo todo\b"
            r"|\bse murio todo\b|\bplantas muertas\b|\bmuerte de (?:las )?plantas\b"
            r"|\bse murieron\b|\bmurieron las plantas\b|\bpeligro de perder\b"
        ),
        "El relato indica muerte generalizada de plantas o pérdida total de la cosecha: "
        "se prioriza emergencia.",
        0.95,
    ),
    SafetyRule(
        "intoxicacion",
        "intoxicación por pesticida",
        _rx(
            r"\bintoxic\w*\b|\benvenen\w*\b"
            r"|\bderiva de (?:pesticida|herbicida|plaguicida)\b"
            r"|\briesgo para (?:los )?(?:trabajadores|consumidores|personas|ninos|familias)\b"
            r"|\btrabajadores (?:enfermos|intoxicados)\b"
            r"|\bconsumidores (?:enfermos|intoxicados)\b"
        ),
        "Se mencionan intoxicaciones o riesgo para trabajadores/consumidores: se prioriza "
        "emergencia y atención inmediata.",
        0.96,
    ),
    SafetyRule(
        "generalizado",
        "síntomas generalizados",
        _rx(
            r"\bsintomas generalizados\b|\bafectacion generalizada\b"
            r"|\bproblema generalizado\b|\btodas las plantas\b"
        ),
        "Se describen síntomas generalizados en la parcela: se prioriza emergencia.",
        0.93,
    ),
    SafetyRule(
        "erradicacion",
        "erradicación",
        _rx(r"\berradicacion\b|\berradicar\b"),
        "Se plantea erradicación de plantas: se prioriza emergencia y coordinación "
        "fitosanitaria.",
        0.93,
    ),
    SafetyRule(
        "devastacion",
        "pérdida total",
        _rx(r"\barras\w*\b|\bdevast\w*\b"),
        "El relato indica que la plaga arrasó o devastó el cultivo: se prioriza "
        "emergencia.",
        0.95,
    ),
)

# Reparto del resto de probabilidad cuando se activa una regla de seguridad.
_SAFETY_REST = {"urgente": 0.55, "consulta": 0.30, "preventivo": 0.15}

# Se activa por lógica numérica (porcentaje > 50 %, "más de la mitad" o toda la
# parcela), no por un patrón de texto; se usa el mismo formato que las demás.
AREA_SAFETY_RULE = SafetyRule(
    "superficie",
    "superficie afectada",
    _rx(r"(?!x)x"),
    "Se reporta una superficie afectada mayor al 50 %: se prioriza emergencia.",
    0.94,
)


def _safety_scores(confidence: float) -> dict[str, float]:
    rest = 1.0 - confidence
    scores = {"emergencia": confidence}
    for label, share in _SAFETY_REST.items():
        scores[label] = rest * share
    return scores


# ---------------------------------------------------------------------------
# Puntuación del motor de reglas
# ---------------------------------------------------------------------------

# Log-priors: sin evidencia, "consulta" y "preventivo" son más probables que
# una emergencia. Estos valores se suman a la evidencia saturada.
PRIOR_LOGITS: dict[str, float] = {
    "emergencia": -3.45,
    "urgente": -2.15,
    "consulta": -0.65,
    "preventivo": -1.05,
}
TEMPERATURE = 1.6
SATURATION_MAX = 6.0
SATURATION_SCALE = 3.0


def _saturate(weight: float) -> float:
    """Evita que sumar muchos términos convierta la salida en 99.99 %."""

    return SATURATION_MAX * (1.0 - math.exp(-max(0.0, weight) / SATURATION_SCALE))


def _softmax(logits: dict[str, float]) -> dict[str, float]:
    peak = max(logits.values())
    exps = {label: math.exp(value - peak) for label, value in logits.items()}
    total = sum(exps.values())
    return {label: value / total for label, value in exps.items()}


def _detect_terms(text: str) -> tuple[dict[str, float], list[tuple[int, str]], float]:
    """Devuelve pesos por nivel, evidencias (posición, término) y peso de urgencia."""

    weights = {label: 0.0 for label in LABELS}
    evidence: list[tuple[int, str]] = []
    problem_weight = 0.0

    for level, terms in LEXICON.items():
        for term in terms:
            best: tuple[float, int] | None = None
            for match in term.pattern.finditer(text):
                if _is_negated(text, match.start()):
                    continue
                # En frases como "manchas que no avanzan" la negación queda
                # dentro de la coincidencia; se descarta la señal de avance.
                if term.name == "avance de síntomas" and re.search(
                    r"\b(?:no|sin|ni)\b", match.group(0)
                ):
                    continue
                factor = _intensity_factor(text, match.start(), match.end())
                if best is None or factor > best[0]:
                    best = (factor, match.start())
            if best is None:
                continue
            factor, position = best
            weights[level] += term.weight * factor
            evidence.append((position, term.name))
            if level in ("emergencia", "urgente"):
                problem_weight += term.weight * factor

    return weights, evidence, problem_weight


def _rule_scores(text: str) -> tuple[dict[str, float], list[str], bool]:
    """Calcula probabilidades solo con reglas, más evidencias y señales agronómicas."""

    weights, evidence, problem_weight = _detect_terms(text)
    bonuses = {label: 0.0 for label in LABELS}

    if problem_weight > 0 and _SPEED_RE.search(text):
        bonuses["urgente"] += 0.8
        if weights["emergencia"] > 0:
            bonuses["emergencia"] += 0.35

    max_percent, half, whole = _affected_area(text)
    damage_context = _has_damage_context(text)
    if damage_context:
        if whole or half or (max_percent is not None and max_percent >= 50):
            bonuses["emergencia"] += 0.6
        elif max_percent is not None and max_percent >= 20:
            bonuses["urgente"] += 0.6

    sat = {label: _saturate(weights[label]) + bonuses[label] for label in LABELS}
    logits = {label: (PRIOR_LOGITS[label] + sat[label]) / TEMPERATURE for label in LABELS}
    return _softmax(logits), [name for _, name in sorted(evidence)], problem_weight > 0


def _apply_safety_rule(text: str) -> tuple[SafetyRule | None, list[str]]:
    """Devuelve la primera regla de seguridad no negada y sus evidencias."""

    for rule in SAFETY_RULES:
        for match in rule.pattern.finditer(text):
            if not _is_negated(text, match.start()):
                return rule, [rule.term]
    return None, []


# ---------------------------------------------------------------------------
# Modelo supervisado opcional (TF-IDF + regresión logística exportada a JSON)
# ---------------------------------------------------------------------------

_TOKEN_RE = re.compile(r"[a-z0-9]{2,}")


@dataclass(frozen=True)
class LinearModel:
    labels: tuple[str, ...]
    vocabulary: dict[str, int]
    idf: tuple[float, ...]
    coef: tuple[tuple[float, ...], ...]
    intercept: tuple[float, ...]
    metrics: dict[str, Any]

    @property
    def features(self) -> int:
        return len(self.idf)


def load_optional_model(path: Path) -> LinearModel | None:
    """Carga el JSON si existe; cualquier problema se resuelve con reglas."""

    if not path.is_file():
        return None
    try:
        payload = json.loads(path.read_text(encoding="utf-8"))
        if payload.get("format") != "tfidf-logreg-v1":
            raise ValueError("formato desconocido")
        labels = tuple(str(label) for label in payload["labels"])
        if set(labels) != set(LABELS):
            raise ValueError("el modelo no contiene exactamente las cuatro etiquetas")
        vocabulary = {str(term): int(index) for term, index in payload["vocabulary"].items()}
        idf = tuple(float(value) for value in payload["idf"])
        coef = tuple(tuple(float(value) for value in row) for row in payload["coef"])
        intercept = tuple(float(value) for value in payload["intercept"])
        if len(coef) != len(labels) or any(len(row) != len(idf) for row in coef):
            raise ValueError("dimensiones de coeficientes inconsistentes")
        if len(intercept) != len(labels):
            raise ValueError("intercept inconsistente")
        if vocabulary and max(vocabulary.values()) >= len(idf):
            raise ValueError("vocabulario fuera de rango")
        return LinearModel(
            labels=labels,
            vocabulary=vocabulary,
            idf=idf,
            coef=coef,
            intercept=intercept,
            metrics=dict(payload.get("metrics") or {}),
        )
    except Exception as exc:  # noqa: BLE001 - es un extra opcional
        LOGGER.warning("No se pudo cargar el modelo opcional %s: %s", path, exc)
        return None


def _model_probabilities(model: LinearModel, text: str) -> dict[str, float] | None:
    """Reproduce en biblioteca estándar el TF-IDF y la regresión logística."""

    tokens = _TOKEN_RE.findall(text)
    if not tokens:
        return None
    counts: dict[str, int] = {}
    for token in tokens:
        counts[token] = counts.get(token, 0) + 1
    for first, second in zip(tokens, tokens[1:]):
        bigram = f"{first} {second}"
        counts[bigram] = counts.get(bigram, 0) + 1

    vector: dict[int, float] = {}
    for term, count in counts.items():
        index = model.vocabulary.get(term)
        if index is None:
            continue
        vector[index] = count * model.idf[index]
    norm = math.sqrt(sum(value * value for value in vector.values()))
    if norm == 0:
        return None

    logits: dict[str, float] = {}
    for row, label in enumerate(model.labels):
        total = model.intercept[row]
        coefficients = model.coef[row]
        for index, value in vector.items():
            total += (value / norm) * coefficients[index]
        logits[label] = total
    return _softmax(logits)


def _blend_with_model(
    rule_scores: dict[str, float], model: LinearModel | None, text: str
) -> tuple[dict[str, float], bool]:
    if model is None:
        return rule_scores, False
    model_scores = _model_probabilities(model, text)
    if model_scores is None:
        return rule_scores, False
    blended = {
        label: 0.70 * rule_scores[label] + 0.30 * model_scores.get(label, 0.0)
        for label in LABELS
    }
    return blended, True


# ---------------------------------------------------------------------------
# Salida
# ---------------------------------------------------------------------------


def _finalize_scores(raw: dict[str, float]) -> dict[str, float]:
    """Normaliza, redondea a 4 decimales y ajusta el residuo en la etiqueta top."""

    clamped = {label: max(0.0, float(raw.get(label, 0.0))) for label in LABELS}
    total = sum(clamped.values())
    if total <= 0:
        clamped = {label: 1.0 / len(LABELS) for label in LABELS}
        total = 1.0
    normalized = {label: value / total for label, value in clamped.items()}
    rounded = {label: round(value, 4) for label, value in normalized.items()}
    diff = round(1.0 - sum(rounded.values()), 4)
    top = max(rounded, key=lambda label: rounded[label])
    rounded[top] = round(max(0.0, rounded[top] + diff), 4)
    return rounded


def classify_text(text: str, model: LinearModel | None = None) -> dict[str, Any]:
    """Clasifica un relato. Función pura, sin dependencia de FastAPI."""

    clean = " ".join(text.split()).strip()
    if not clean:
        raise ValueError("El relato está vacío.")

    normalized = normalize_text(clean)
    rule_scores, evidence, _ = _rule_scores(normalized)
    safety_rule, safety_evidence = _apply_safety_rule(normalized)
    max_percent, half, whole = _affected_area(normalized)
    damage_context = _has_damage_context(normalized)

    # Regla de superficie: > 50 %, "más de la mitad" o toda la parcela.
    if (
        safety_rule is None
        and damage_context
        and (whole or half or (max_percent is not None and max_percent > 50))
    ):
        safety_rule = AREA_SAFETY_RULE
        safety_evidence = [
            "superficie afectada > 50 %"
            if max_percent is not None
            else (
                "superficie afectada: toda la parcela"
                if whole
                else "superficie afectada: más de la mitad"
            )
        ]

    scores_are_safety = safety_rule is not None
    if scores_are_safety:
        scores = _finalize_scores(_safety_scores(safety_rule.confidence))
        evidence = safety_evidence + [name for name in evidence if name not in safety_evidence]
        model_used = False
    else:
        blended, model_used = _blend_with_model(rule_scores, model, normalized)
        scores = _finalize_scores(blended)

    # Evidencias únicas, en orden de aparición, con tope para no saturar la vista.
    seen: set[str] = set()
    unique_evidence: list[str] = []
    for name in evidence:
        if name not in seen:
            seen.add(name)
            unique_evidence.append(name)
    unique_evidence = unique_evidence[:12]

    if damage_context and (whole or half or (max_percent is not None and max_percent > 50)):
        area_note = (
            "superficie afectada > 50 %"
            if max_percent is not None
            else (
                "superficie afectada: toda la parcela"
                if whole
                else "superficie afectada: más de la mitad"
            )
        )
        if area_note not in unique_evidence:
            unique_evidence.append(area_note)

    label = max(scores, key=lambda item: scores[item])
    info = LABEL_INFO[label]
    model_name = RULES_VERSION + ("+tfidf-logreg" if model_used else "")

    return {
        "label": label,
        "label_es": info["label_es"],
        "description": info["description"],
        "color": info["color"],
        "confidence": round(scores[label], 4),
        "scores": scores,
        "safety_rule": safety_rule.message if safety_rule else None,
        "model": model_name,
        "disclaimer": DISCLAIMER,
        "evidence": unique_evidence,
    }


# ---------------------------------------------------------------------------
# FastAPI
# ---------------------------------------------------------------------------

state: dict[str, Any] = {
    "ready": False,
    "model": None,
    "model_error": None,
    "started_at": None,
}


@asynccontextmanager
async def lifespan(_: FastAPI):
    state["model"] = load_optional_model(MODEL_PATH)
    state["model_error"] = None
    if MODEL_PATH.is_file() and state["model"] is None:
        state["model_error"] = "El modelo opcional existe pero no se pudo cargar; se usan reglas."
    state["started_at"] = datetime.now(timezone.utc).isoformat()
    state["ready"] = True
    LOGGER.info(
        "Servicio listo. Reglas=%s modelo_opcional=%s",
        RULES_VERSION,
        "sí" if state["model"] else "no",
    )
    yield
    state["ready"] = False


app = FastAPI(
    title="AgroDiagnóstico · riesgo fitosanitario",
    description=(
        "Clasificador determinista y explicable de riesgo fitosanitario para "
        "relatos de campo en español. Prototipo educativo."
    ),
    version="2.0.0",
    lifespan=lifespan,
)


def require_token(authorization: str | None = Header(default=None)) -> None:
    """Exige ``Authorization: Bearer <token>`` solo si AI_SERVICE_TOKEN está definido."""

    if not TOKEN:
        return
    scheme, _, credentials = (authorization or "").partition(" ")
    if scheme.lower() != "bearer" or not secrets.compare_digest(credentials.strip(), TOKEN):
        raise HTTPException(
            status_code=401,
            detail="Token inválido",
            headers={"WWW-Authenticate": "Bearer"},
        )


class ClassifyRequest(BaseModel):
    text: str = Field(min_length=1, max_length=MAX_CHARS)


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
    evidence: list[str] = Field(default_factory=list)


@app.get("/")
def root() -> dict:
    return {
        "service": "AgroDiagnóstico · riesgo fitosanitario",
        "status": "ok" if state["ready"] else "loading",
        "endpoints": ["/health", "/classify"],
        "rules": RULES_VERSION,
        "educational": True,
    }


@app.get("/health")
def health():
    if not state["ready"]:
        return JSONResponse(
            status_code=503,
            content={"status": "loading", "detail": "El servicio todavía se está cargando."},
        )
    model = state["model"]
    return {
        "status": "ok",
        "service": "agro-risk",
        "rules": RULES_VERSION,
        "labels": list(LABELS),
        "auth_required": bool(TOKEN),
        "max_chars": MAX_CHARS,
        "optional_model": {
            "loaded": model is not None,
            "path": str(MODEL_PATH),
            "labels": list(model.labels) if model else [],
            "metrics": model.metrics if model else {},
            "error": state["model_error"],
        },
        "started_at": state["started_at"],
        "disclaimer": DISCLAIMER,
    }


@app.post("/classify", response_model=ClassifyResponse, dependencies=[Depends(require_token)])
def classify(payload: ClassifyRequest) -> ClassifyResponse:
    if not state["ready"]:
        raise HTTPException(status_code=503, detail="El servicio todavía se está cargando.")
    try:
        result = classify_text(payload.text, state["model"])
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    return ClassifyResponse(**result)


if __name__ == "__main__":  # pragma: no cover - conveniencia local
    import uvicorn

    uvicorn.run(
        "app:app",
        host="0.0.0.0",
        port=_env_int("PORT", 7860),
        reload=False,
    )

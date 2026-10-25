#!/usr/bin/env python3
"""Genera un CSV sintético de relatos de campo etiquetados por riesgo fitosanitario.

El objetivo es documentar el problema y permitir un futuro modelo supervisado.
No se usa para entrenar el servicio en tiempo de ejecución: el clasificador
principal es el motor de reglas de ``app.py``.

Uso:
    python training/generar_dataset.py
    python training/generar_dataset.py --per-class 300 --seed 7 \
        --output training/risk_examples.csv

Salida: CSV UTF-8 con columnas ``text,label``. Las etiquetas son
``emergencia``, ``urgente``, ``consulta`` y ``preventivo``.

Los relatos son sintéticos: no provienen de productores reales ni contienen
datos personales. Sirven para pruebas, documentación y experimentos.
"""

from __future__ import annotations

import argparse
import csv
import random
from pathlib import Path

LABELS = ("emergencia", "urgente", "consulta", "preventivo")

CROPS = (
    "maíz",
    "limón persa",
    "aguacate Hass",
    "tomate saladette",
    "chile habanero",
    "mango Ataulfo",
    "café arábigo",
    "plátano",
    "fresa",
    "vid",
    "naranja Valencia",
    "papaya",
    "agave azul",
    "sorgo",
    "frijol",
    "calabacita",
    "arándano",
    "jitomate",
    "caña de azúcar",
    "coco",
)

STATES = (
    "Michoacán",
    "Veracruz",
    "Sinaloa",
    "Jalisco",
    "Oaxaca",
    "Chiapas",
    "Guanajuato",
    "Sonora",
    "Puebla",
    "Yucatán",
    "Colima",
    "Nayarit",
    "Guerrero",
    "Tamaulipas",
    "Baja California",
    "Morelos",
    "Querétaro",
    "Hidalgo",
)

OPENERS = (
    "Soy productor de {crop} en {state}.",
    "Tengo una huerta de {crop} en {state}.",
    "Trabajo con {crop} en {state}.",
    "Asesoro a un productor de {crop} en {state}.",
    "En el ejido de {state} sembramos {crop}.",
    "Manejo un lote de {crop} en {state}.",
)

CONTEXTS = (
    "La parcela tiene {hectareas} hectáreas.",
    "Es mi primer ciclo con este cultivo.",
    "El riego es por goteo.",
    "Llevo varios años con la misma variedad.",
    "La siembra fue hace {semanas} semanas.",
    "Tengo fotografías del problema para compartir.",
)

TEMPLATES: dict[str, tuple[str, ...]] = {
    "emergencia": (
        "Detecté HLB (dragón amarillo) en {n} árboles de {crop}; es la primera vez "
        "que aparece en la zona y necesito avisar a SENASICA.",
        "Sospecho Fusarium R4T en {crop}: las plantas se están muriendo y la parcela "
        "quedó en cuarentena.",
        "Apareció mosca del Mediterráneo en trampas de mi huerta de {crop}; es una "
        "plaga cuarentenaria.",
        "Hay un brote de langosta que ya arrasó {n} hectáreas de {crop}.",
        "Se está muriendo todo el lote de {crop}: no hay cosecha y perdí el {pct} % "
        "de la parcela.",
        "Plaga nueva en la zona: se murieron las plantas de {crop} y no sabemos qué es.",
        "Los síntomas son generalizados en toda la parcela de {crop}; hay plantas "
        "muertas y pérdida total.",
        "Aplicaron pesticida cerca de la huerta de {crop} y los trabajadores están "
        "intoxicados; hay riesgo para los consumidores.",
        "El laboratorio confirmó HLB en {crop}; la autoridad fitosanitaria ya emitió "
        "una declaratoria.",
        "Detecté un brote de {quarantine_pest} en {n} hectáreas de {crop}; la zona "
        "estaba libre y ahora hay cuarentena.",
        "Se está muriendo todo: las plantas de {crop} se secaron en una semana y no "
        "hay cosecha.",
        "Primera detección de {quarantine_pest} en {state}; el lote de {crop} está "
        "afectado y necesito contactar a SENASICA.",
        "Hay una afectación generalizada en {crop}: el {pct} % de las plantas está "
        "muerto y el resto se está muriendo.",
        "Un vecino reportó un brote de {quarantine_pest}; mi huerta de {crop} está "
        "en la zona de cuarentena y no sé qué hacer.",
    ),
    "urgente": (
        "Las manchas en las hojas de {crop} se expanden rápido; en 48 horas avanzaron "
        "a toda la hilera.",
        "Hay marchitez súbita en {crop}; las plantas se doblaron de un día para otro.",
        "La defoliación es severa en {crop} después del granizo de ayer; quedaron "
        "pocas hojas.",
        "La pudrición en la base del tallo de {crop} tiene mal olor y se está "
        "propagando.",
        "La helada de anoche dañó los brotes de {crop}; el follaje se está secando "
        "rápido.",
        "La roya está avanzando en las hojas de {crop}; en tres días se diseminó a "
        "varias hileras.",
        "Detecté una infestación de {pest} en {crop} que avanza rápido y ya dañó "
        "varias plantas.",
        "Las ramas de {crop} se están secando de un día para otro y la marchitez "
        "avanza.",
        "El granizo destruyó el follaje de {crop}; hay defoliación severa y riesgo de "
        "perder la floración.",
        "La mancha foliar en {crop} se expande aceleradamente; en 72 horas pasó de una "
        "planta a toda la cama.",
        "El mildiu avanza rápido en {crop} y ya hay defoliación en varias hileras.",
        "La planta de {crop} amaneció marchita y la pudrición blanda con olor fétido "
        "se extendió a {n} plantas.",
        "Después de la inundación, {crop} presenta amarillamiento repentino y las "
        "hojas se están cayendo.",
        "La plaga está activa: {pest} se propagó por {n} surcos de {crop} en 24 horas.",
    ),
    "consulta": (
        "Veo algunas manchas leves en las hojas de {crop}; no avanzan y quisiera saber "
        "qué son.",
        "Hay pocos insectos aislados en el envés de dos plantas de {crop}; el resto "
        "está sano.",
        "Algunas hojas de {crop} tienen amarillamiento leve; sospecho deficiencia de "
        "hierro.",
        "Encontré dos plantas de {crop} con clorosis y manchas foliares localizadas.",
        "Tengo una duda: las hojas nuevas de {crop} salen un poco amarillas, ¿es "
        "deficiencia de zinc?",
        "Hay pulgón en una sola planta de {crop}; ¿conviene revisar antes de fumigar?",
        "Las manchas en {crop} son pequeñas y están en pocas hojas; quiero confirmar "
        "el diagnóstico.",
        "Vi cochinilla en una rama de {crop}; el resto del árbol se ve sano.",
        "Quiero identificar unas manchas ligeras que aparecieron en {crop} después del "
        "riego.",
        "Hay maleza y algunos caracoles en una esquina del lote de {crop}; ¿cómo lo "
        "manejo?",
        "El laboratorio descartó HLB en {crop}; solo quedan manchas leves en dos hojas.",
        "No hay síntomas generalizados en {crop}; solo unas manchas aisladas que quiero "
        "revisar.",
        "Tengo {n} plantas de {crop} con hojas amarillas; el resto del lote se ve bien.",
        "En una sola hilera de {crop} hay trips; quisiera saber si es necesario "
        "aplicar algo.",
    ),
    "preventivo": (
        "Estoy planeando la fertilización y el muestreo de suelo para la próxima "
        "temporada de {crop}.",
        "Quiero un calendario de rotación de cultivos y monitoreo de plagas para "
        "{crop}.",
        "Me interesa implementar manejo integrado y trampas para prevenir problemas en "
        "{crop}.",
        "Voy a instalar riego por goteo y llevar bitácora de las labores en {crop}.",
        "Busco un plan de abonado orgánico y poda sanitaria antes de sembrar {crop}.",
        "Necesito capacitación y buenas prácticas para la certificación de mi huerta "
        "de {crop}.",
        "Quiero hacer análisis de suelo y definir la fertilización del siguiente ciclo "
        "de {crop}.",
        "Estoy preparando el terreno y el plan de siembra de {crop} para la próxima "
        "temporada.",
        "Me interesa la trazabilidad y la poda de formación en {crop}; ¿por dónde "
        "empiezo?",
        "Quiero monitoreo con trampas y muestreo mensual en {crop} para prevenir.",
        "Tengo un plan de rotación de cultivos y abonos verdes para {crop}.",
        "Voy a sembrar {crop} con cultivo de cobertura y riego por goteo; busco "
        "asesoría preventiva.",
        "Estoy diseñando el programa de fertilización y el calendario de muestreo de "
        "{crop}.",
        "Quiero prevenir plagas con manejo integrado, trampas y monitoreo en {crop}.",
    ),
}

QUARANTINE_PESTS = ("HLB", "Fusarium R4T", "mosca del Mediterráneo", "langosta")
PESTS = ("pulgón", "mosca blanca", "trips", "cogollero", "picudo", "oruga")


def _fill(template: str, rng: random.Random, crop: str, state: str) -> str:
    return template.format(
        crop=crop,
        state=state,
        n=rng.choice((2, 3, 5, 8, 12, 20, 40, 80, 150, 300)),
        pct=rng.choice((60, 70, 80, 90, 100)),
        quarantine_pest=rng.choice(QUARANTINE_PESTS),
        pest=rng.choice(PESTS),
        hectareas=rng.choice((1, 2, 3, 5, 10, 20, 50)),
        semanas=rng.choice((2, 3, 4, 6, 8)),
    )


def generate(per_class: int, seed: int) -> list[dict[str, str]]:
    rng = random.Random(seed)
    rows: list[dict[str, str]] = []
    seen: set[str] = set()

    for label in LABELS:
        templates = TEMPLATES[label]
        attempts = 0
        produced = 0
        while produced < per_class:
            attempts += 1
            if attempts > per_class * 200:
                raise RuntimeError(
                    f"No se pudieron generar {per_class} ejemplos únicos para {label}. "
                    "Aumenta las plantillas o reduce --per-class."
                )
            crop = rng.choice(CROPS)
            state = rng.choice(STATES)
            template = rng.choice(templates)
            sentences = []
            if rng.random() < 0.85:
                sentences.append(rng.choice(OPENERS).format(crop=crop, state=state))
            sentences.append(_fill(template, rng, crop, state))
            if rng.random() < 0.45:
                sentences.append(rng.choice(CONTEXTS).format(
                    hectareas=rng.choice((1, 2, 3, 5, 10, 20, 50)),
                    semanas=rng.choice((2, 3, 4, 6, 8)),
                ))
            text = " ".join(sentences).strip()
            if text in seen:
                continue
            seen.add(text)
            rows.append({"text": text, "label": label})
            produced += 1

    rng.shuffle(rows)
    return rows


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--output",
        default=str(Path(__file__).resolve().parent / "risk_examples.csv"),
        help="Ruta del CSV de salida (por defecto training/risk_examples.csv).",
    )
    parser.add_argument("--per-class", type=int, default=150, help="Ejemplos por nivel.")
    parser.add_argument("--seed", type=int, default=2026, help="Semilla reproducible.")
    args = parser.parse_args()

    if args.per_class < 1:
        parser.error("--per-class debe ser mayor que cero")

    rows = generate(args.per_class, args.seed)
    output = Path(args.output).resolve()
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=["text", "label"])
        writer.writeheader()
        writer.writerows(rows)

    counts: dict[str, int] = {}
    for row in rows:
        counts[row["label"]] = counts.get(row["label"], 0) + 1
    print(f"Escrito {output} con {len(rows)} ejemplos sintéticos.")
    for label in LABELS:
        print(f"  {label:11s} {counts.get(label, 0)}")


if __name__ == "__main__":
    main()

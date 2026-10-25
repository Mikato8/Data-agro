#!/usr/bin/env python3
"""Genera el kit documental agronómico de AgroDiagnóstico.

Uso:
    python3 tools/generar_kit_recursos.py

Produce siete DOCX y un ZIP en public/descargas/. El script es idempotente:
sobrescribe los archivos y elimina los documentos heredados del kit anterior.
"""

from __future__ import annotations

import zipfile
from pathlib import Path

from docx import Document
from docx.enum.table import WD_ALIGN_VERTICAL, WD_ROW_HEIGHT_RULE, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

BASE = Path(__file__).resolve().parents[1]
OUT = BASE / "public" / "descargas"
OUT.mkdir(parents=True, exist_ok=True)

# Paleta oliva del portal.
OLIVE_DARK = RGBColor(0x1F, 0x3D, 0x2B)
OLIVE = RGBColor(0x4F, 0x6F, 0x52)
INK = RGBColor(0x1B, 0x24, 0x1E)
MUTED = RGBColor(0x5F, 0x6B, 0x60)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
OLIVE_LIGHT = "E8EDE4"
SAND = "F7F3EA"
BORDER = "C8D2C0"

CONTENT_WIDTH = 16.6  # cm útiles con márgenes laterales de 2.2 cm
HEADER_TEXT = "AGRODIAGNÓSTICO · KIT PARA INGENIEROS AGRÓNOMOS"
FOOTER_TEXT = "Documento de apoyo · No sustituye el juicio profesional ni la normativa aplicable"

# Documentos del kit anterior que deben desaparecer para no dejar enlaces muertos.
LEGACY_FILES = [
    "plantilla-caso-clinico.docx",
    "checklist-consentimiento.docx",
    "hoja-de-alta.docx",
    "guia-recursos-para-veterinarios.docx",
    "guia-recursos-para-agrónomos.docx",
    "kit-recursos-agrónomos.zip",
    "kit-recursos-veterinarios.zip",
]

P_BDR_SUCCESSORS = (
    "w:shd",
    "w:tabs",
    "w:suppressAutoHyphens",
    "w:kinsoku",
    "w:wordWrap",
    "w:overflowPunct",
    "w:topLinePunct",
    "w:autoSpaceDE",
    "w:autoSpaceDN",
    "w:bidi",
    "w:adjustRightInd",
    "w:snapToGrid",
    "w:spacing",
    "w:ind",
    "w:contextualSpacing",
    "w:mirrorIndents",
    "w:suppressOverlap",
    "w:jc",
    "w:textDirection",
    "w:textAlignment",
    "w:textboxTightWrap",
    "w:outlineLvl",
    "w:divId",
    "w:cnfStyle",
    "w:rPr",
    "w:sectPr",
    "w:pPrChange",
)


# --------------------------------------------------------------------------- #
# Utilidades de formato
# --------------------------------------------------------------------------- #


def shade(cell, color: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    for existing in tc_pr.findall(qn("w:shd")):
        tc_pr.remove(existing)
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), color)
    tc_pr.append(shd)


def set_cell_text(
    cell,
    text: str,
    *,
    bold: bool = False,
    italic: bool = False,
    color: RGBColor | None = None,
    size: float = 9,
    align=None,
) -> None:
    cell.text = ""
    paragraph = cell.paragraphs[0]
    paragraph.paragraph_format.space_before = Pt(1)
    paragraph.paragraph_format.space_after = Pt(1)
    paragraph.paragraph_format.line_spacing = 1.0
    if align is not None:
        paragraph.alignment = align
    run = paragraph.add_run(text)
    run.bold = bold
    run.italic = italic
    run.font.size = Pt(size)
    run.font.name = "Calibri"
    if color is not None:
        run.font.color.rgb = color


def set_paragraph_bottom_border(paragraph, color: str = "4F6F52", size: int = 6) -> None:
    p_pr = paragraph._p.get_or_add_pPr()
    for existing in p_pr.findall(qn("w:pBdr")):
        p_pr.remove(existing)
    p_bdr = OxmlElement("w:pBdr")
    bottom = OxmlElement("w:bottom")
    bottom.set(qn("w:val"), "single")
    bottom.set(qn("w:sz"), str(size))
    bottom.set(qn("w:space"), "2")
    bottom.set(qn("w:color"), color)
    p_bdr.append(bottom)
    p_pr.insert_element_before(p_bdr, *P_BDR_SUCCESSORS)


def set_character_spacing(run, twentieths: int) -> None:
    r_pr = run._element.get_or_add_rPr()
    for existing in r_pr.findall(qn("w:spacing")):
        r_pr.remove(existing)
    spacing = OxmlElement("w:spacing")
    spacing.set(qn("w:val"), str(twentieths))
    r_pr.insert_element_before(
        spacing,
        "w:w",
        "w:kern",
        "w:position",
        "w:sz",
        "w:szCs",
        "w:highlight",
        "w:u",
        "w:effect",
        "w:bdr",
        "w:shd",
        "w:fitText",
        "w:vertAlign",
        "w:rtl",
        "w:cs",
        "w:em",
        "w:lang",
        "w:eastAsianLayout",
        "w:specVanish",
        "w:oMath",
    )


def set_table_borders(table, color: str = BORDER, size: int = 4) -> None:
    tbl_pr = table._tbl.tblPr
    for existing in tbl_pr.findall(qn("w:tblBorders")):
        tbl_pr.remove(existing)
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        element = OxmlElement(f"w:{edge}")
        element.set(qn("w:val"), "single")
        element.set(qn("w:sz"), str(size))
        element.set(qn("w:space"), "0")
        element.set(qn("w:color"), color)
        borders.append(element)
    tbl_pr.insert_element_before(
        borders,
        "w:shd",
        "w:tblLayout",
        "w:tblCellMar",
        "w:tblLook",
        "w:tblCaption",
        "w:tblDescription",
        "w:tblPrChange",
    )


def set_cell_margins(table, vertical: int = 30, horizontal: int = 80) -> None:
    tbl_pr = table._tbl.tblPr
    for existing in tbl_pr.findall(qn("w:tblCellMar")):
        tbl_pr.remove(existing)
    margins = OxmlElement("w:tblCellMar")
    for side, value in (("top", vertical), ("bottom", vertical), ("left", horizontal), ("right", horizontal)):
        element = OxmlElement(f"w:{side}")
        element.set(qn("w:w"), str(value))
        element.set(qn("w:type"), "dxa")
        margins.append(element)
    tbl_pr.insert_element_before(
        margins,
        "w:tblLook",
        "w:tblCaption",
        "w:tblDescription",
        "w:tblPrChange",
    )


def new_table(document: Document, widths: list[float]):
    table = document.add_table(rows=0, cols=len(widths))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    for index, width in enumerate(widths):
        table.columns[index].width = Cm(width)
    set_table_borders(table)
    set_cell_margins(table)
    return table


def add_row(table, widths: list[float], *, height: float | None = None, header: bool = False):
    row = table.add_row()
    if height is not None:
        row.height = Cm(height)
        row.height_rule = WD_ROW_HEIGHT_RULE.AT_LEAST
    tr_pr = row._tr.get_or_add_trPr()
    cant_split = OxmlElement("w:cantSplit")
    tr_pr.insert(0, cant_split)
    if header:
        tr_pr.append(OxmlElement("w:tblHeader"))
    for index, cell in enumerate(row.cells):
        cell.width = Cm(widths[index])
    return row


def add_page_field(paragraph) -> None:
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = " PAGE "
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = "1"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    for element in (begin, instruction, separate, text, end):
        run._r.append(element)


def add_total_pages_field(paragraph) -> None:
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instruction = OxmlElement("w:instrText")
    instruction.set(qn("xml:space"), "preserve")
    instruction.text = " NUMPAGES "
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = "1"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    for element in (begin, instruction, separate, text, end):
        run._r.append(element)


# --------------------------------------------------------------------------- #
# Estructura del documento
# --------------------------------------------------------------------------- #


def _clean_theme_fonts(style) -> None:
    r_pr = style.element.get_or_add_rPr()
    r_fonts = r_pr.find(qn("w:rFonts"))
    if r_fonts is None:
        return
    for attribute in ("w:asciiTheme", "w:hAnsiTheme", "w:eastAsiaTheme", "w:cstheme"):
        key = qn(attribute)
        if key in r_fonts.attrib:
            del r_fonts.attrib[key]


def setup(document: Document, title: str, subtitle: str, eyebrow: str = "") -> None:
    section = document.sections[0]
    section.top_margin = Cm(2.3)
    section.bottom_margin = Cm(2.0)
    section.left_margin = Cm(2.2)
    section.right_margin = Cm(2.2)
    section.header_distance = Cm(1.0)
    section.footer_distance = Cm(0.9)

    normal = document.styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(9.5)
    normal.font.color.rgb = INK
    normal.paragraph_format.space_after = Pt(4)
    normal.paragraph_format.space_before = Pt(0)
    normal.paragraph_format.line_spacing = 1.06
    _clean_theme_fonts(normal)

    for style_name, size in (("Title", 19), ("Heading 1", 11.5), ("Heading 2", 10.5)):
        style = document.styles[style_name]
        style.font.name = "Calibri"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = OLIVE_DARK
        style.paragraph_format.space_before = Pt(9 if style_name == "Heading 1" else 2)
        style.paragraph_format.space_after = Pt(2)
        style.paragraph_format.keep_with_next = True
        _clean_theme_fonts(style)

    # Refresca PAGE y NUMPAGES al abrir el archivo para que el pie sea correcto.
    settings = document.settings.element
    if settings.find(qn("w:updateFields")) is None:
        update_fields = OxmlElement("w:updateFields")
        update_fields.set(qn("w:val"), "true")
        settings.insert_element_before(
            update_fields,
            "w:hdrShapeDefaults",
            "w:footnotePr",
            "w:endnotePr",
            "w:compat",
            "w:docVars",
            "w:rsids",
            "w:mathPr",
            "w:themeFontLang",
            "w:clrSchemeMapping",
            "w:shapeDefaults",
            "w:decimalSymbol",
            "w:listSeparator",
        )

    # El estilo Title trae un borde azul del tema; lo pasamos a verde oliva.
    title_style_p_pr = document.styles["Title"].element.get_or_add_pPr()
    style_border = title_style_p_pr.find(qn("w:pBdr"))
    if style_border is not None:
        bottom = style_border.find(qn("w:bottom"))
        if bottom is not None:
            bottom.set(qn("w:color"), "4F6F52")
            if qn("w:themeColor") in bottom.attrib:
                del bottom.attrib[qn("w:themeColor")]

    header = section.header.paragraphs[0]
    header.text = ""
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    header.paragraph_format.space_after = Pt(0)
    brand, tagline = HEADER_TEXT.split(" · ", 1)
    run = header.add_run(brand)
    run.bold = True
    run.font.size = Pt(7.5)
    run.font.name = "Calibri"
    run.font.color.rgb = OLIVE_DARK
    set_character_spacing(run, 20)
    run = header.add_run(" · " + tagline)
    run.bold = True
    run.font.size = Pt(7.5)
    run.font.name = "Calibri"
    run.font.color.rgb = OLIVE
    set_character_spacing(run, 20)
    set_paragraph_bottom_border(header, color="4F6F52", size=4)

    footer = section.footer.paragraphs[0]
    footer.text = ""
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = footer.add_run(f"{FOOTER_TEXT} · Página ")
    run.font.size = Pt(7)
    run.font.name = "Calibri"
    run.font.color.rgb = MUTED
    add_page_field(footer)
    run = footer.add_run(" de ")
    run.font.size = Pt(7)
    run.font.name = "Calibri"
    run.font.color.rgb = MUTED
    add_total_pages_field(footer)
    for run in footer.runs:
        run.font.size = Pt(7)
        run.font.name = "Calibri"
        run.font.color.rgb = MUTED

    if eyebrow:
        paragraph = document.add_paragraph()
        paragraph.paragraph_format.space_before = Pt(0)
        paragraph.paragraph_format.space_after = Pt(1)
        run = paragraph.add_run(eyebrow.upper())
        run.bold = True
        run.font.size = Pt(7.5)
        run.font.name = "Calibri"
        run.font.color.rgb = OLIVE
        set_character_spacing(run, 24)

    title_paragraph = document.add_paragraph(title, style="Title")
    title_paragraph.paragraph_format.space_before = Pt(0)
    title_paragraph.paragraph_format.space_after = Pt(3)
    set_paragraph_bottom_border(title_paragraph, color="4F6F52", size=8)

    paragraph = document.add_paragraph(subtitle)
    paragraph.paragraph_format.space_after = Pt(8)
    for run in paragraph.runs:
        run.font.size = Pt(9)
        run.font.name = "Calibri"
        run.font.color.rgb = MUTED
        run.italic = True


def add_hint(document: Document, text: str) -> None:
    paragraph = document.add_paragraph(text)
    paragraph.paragraph_format.space_after = Pt(3)
    paragraph.paragraph_format.space_before = Pt(0)
    for run in paragraph.runs:
        run.font.size = Pt(8)
        run.font.name = "Calibri"
        run.font.color.rgb = MUTED
        run.italic = True


def section(document: Document, number: int | None, title: str, hint: str = "") -> None:
    heading_text = f"{number}. {title}" if number is not None else title
    heading = document.add_heading(heading_text, level=1)
    heading.paragraph_format.space_before = Pt(8)
    if hint:
        add_hint(document, hint)


def label_table(
    document: Document,
    rows: list[tuple[str, str, bool]],
    *,
    label_width: float = 4.3,
    height: float = 0.58,
) -> None:
    """Tabla de dos columnas: etiqueta y valor. El tercer campo marca pistas grises."""
    widths = [label_width, CONTENT_WIDTH - label_width]
    table = new_table(document, widths)
    for label, value, is_hint in rows:
        row = add_row(table, widths, height=height)
        row.cells[0].vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        set_cell_text(row.cells[0], label, bold=True, color=OLIVE_DARK, size=8.5)
        shade(row.cells[0], OLIVE_LIGHT)
        row.cells[1].vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        set_cell_text(
            row.cells[1],
            value,
            size=8.5 if is_hint else 9,
            italic=is_hint,
            color=MUTED if is_hint else INK,
        )


def form_grid(
    document: Document,
    rows: list[tuple[str, str, str, str]],
    *,
    widths: tuple[float, float, float, float] = (3.7, 4.6, 3.8, 4.5),
    height: float = 0.62,
) -> None:
    table = new_table(document, list(widths))
    for values in rows:
        row = add_row(table, list(widths), height=height)
        for index, value in enumerate(values):
            cell = row.cells[index]
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            if index % 2 == 0:
                shade(cell, OLIVE_LIGHT)
                set_cell_text(cell, value, bold=True, color=OLIVE_DARK, size=8.5)
            else:
                set_cell_text(cell, value, size=9)


def field_block(document: Document, lines: int = 3) -> None:
    table = new_table(document, [CONTENT_WIDTH])
    row = add_row(table, [CONTENT_WIDTH], height=max(1.0, lines * 0.5))
    cell = row.cells[0]
    shade(cell, SAND)
    cell.vertical_alignment = WD_ALIGN_VERTICAL.TOP
    cell.paragraphs[0].paragraph_format.space_after = Pt(0)


def data_table(
    document: Document,
    headers: list[str],
    widths: list[float],
    *,
    rows: int = 3,
    height: float = 0.75,
    hints: list[str] | None = None,
) -> None:
    table = new_table(document, widths)
    header_row = add_row(table, widths, header=True)
    for index, header in enumerate(headers):
        cell = header_row.cells[index]
        shade(cell, "4F6F52")
        cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        set_cell_text(cell, header, bold=True, color=WHITE, size=7.5, align=WD_ALIGN_PARAGRAPH.CENTER)
    for row_index in range(rows):
        row = add_row(table, widths, height=height)
        for cell in row.cells:
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        if hints and row_index == 0:
            for column, hint in enumerate(hints):
                if hint and column < len(row.cells):
                    set_cell_text(row.cells[column], hint, italic=True, color=MUTED, size=7.5)


def bullet_list(document: Document, items: list[str], *, size: float = 9) -> None:
    for item in items:
        paragraph = document.add_paragraph(item, style="List Bullet")
        paragraph.paragraph_format.space_after = Pt(2)
        paragraph.paragraph_format.line_spacing = 1.03
        for run in paragraph.runs:
            run.font.size = Pt(size)
            run.font.name = "Calibri"


def numbered_list(document: Document, items: list[str]) -> None:
    for index, item in enumerate(items, start=1):
        paragraph = document.add_paragraph(f"{index}. {item}")
        paragraph.paragraph_format.left_indent = Cm(0.5)
        paragraph.paragraph_format.first_line_indent = Cm(-0.5)
        paragraph.paragraph_format.space_after = Pt(2)
        paragraph.paragraph_format.line_spacing = 1.03
        for run in paragraph.runs:
            run.font.size = Pt(9)
            run.font.name = "Calibri"


def checkbox_table(document: Document, items: list[str]) -> None:
    widths = [0.85, CONTENT_WIDTH - 0.85]
    table = new_table(document, widths)
    for item in items:
        row = add_row(table, widths, height=0.5)
        row.cells[0].vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        set_cell_text(row.cells[0], "☐", bold=True, color=OLIVE_DARK, size=11, align=WD_ALIGN_PARAGRAPH.CENTER)
        row.cells[1].vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        set_cell_text(row.cells[1], item, size=8.5)


def note_box(document: Document, title: str, body: str, fill: str = OLIVE_LIGHT) -> None:
    table = new_table(document, [CONTENT_WIDTH])
    cell = add_row(table, [CONTENT_WIDTH], height=0.9).cells[0]
    shade(cell, fill)
    cell.vertical_alignment = WD_ALIGN_VERTICAL.TOP
    paragraph = cell.paragraphs[0]
    paragraph.paragraph_format.space_after = Pt(2)
    run = paragraph.add_run(title.upper())
    run.bold = True
    run.font.size = Pt(7.5)
    run.font.name = "Calibri"
    run.font.color.rgb = OLIVE_DARK
    set_character_spacing(run, 16)
    paragraph = cell.add_paragraph()
    paragraph.paragraph_format.space_after = Pt(1)
    paragraph.paragraph_format.line_spacing = 1.03
    run = paragraph.add_run(body)
    run.font.size = Pt(8.5)
    run.font.name = "Calibri"
    run.font.color.rgb = INK


def signature_boxes(document: Document, boxes: list[tuple[str, list[str]]], *, height: float = 3.4) -> None:
    widths = [CONTENT_WIDTH / 2, CONTENT_WIDTH / 2]
    table = new_table(document, widths)
    row = add_row(table, widths, height=height)
    for cell, (title, captions) in zip(row.cells, boxes):
        cell.vertical_alignment = WD_ALIGN_VERTICAL.TOP
        paragraph = cell.paragraphs[0]
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        paragraph.paragraph_format.space_after = Pt(4)
        run = paragraph.add_run(title)
        run.bold = True
        run.font.size = Pt(8.5)
        run.font.name = "Calibri"
        run.font.color.rgb = OLIVE_DARK
        for _ in range(2):
            cell.add_paragraph()
        line = cell.add_paragraph()
        line.paragraph_format.space_after = Pt(2)
        set_paragraph_bottom_border(line, color="4F6F52", size=6)
        for caption in captions:
            paragraph = cell.add_paragraph()
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            paragraph.paragraph_format.space_after = Pt(0)
            run = paragraph.add_run(caption)
            run.font.size = Pt(7.5)
            run.font.name = "Calibri"
            run.font.color.rgb = MUTED


def new_document() -> Document:
    document = Document()
    document.core_properties.author = "AgroDiagnóstico"
    document.core_properties.language = "es-MX"
    document.core_properties.keywords = "agronomía, casos de campo, datos abiertos, México"
    document.core_properties.comments = "Kit para ingenieros agrónomos · Licencia CC BY-NC-SA 4.0"
    return document


# --------------------------------------------------------------------------- #
# 1. Plantilla de caso agronómico
# --------------------------------------------------------------------------- #


def build_case_template() -> Path:
    document = new_document()
    document.core_properties.title = "Plantilla para documentar un caso agronómico"
    setup(
        document,
        "Plantilla para documentar un caso agronómico",
        "Guía de redacción para publicar en AgroDiagnóstico. Describe lo que observaste, con unidades claras, y elimina cualquier dato que identifique al productor, al predio o a la empresa.",
        eyebrow="Plantilla de campo · DOCX editable",
    )
    note_box(
        document,
        "Cómo usarla",
        "Un caso útil responde qué se observó, cómo se llegó al diagnóstico y qué funcionó. Con datos ordenados, unidades claras y honestidad sobre las limitaciones, otro ingeniero puede aprender de tu experiencia.",
        fill=SAND,
    )

    section(document, 1, "Identificación del caso")
    label_table(document, [
        ("Título del caso", "Ej. Marchitez y caída de fruto en jitomate bajo malla sombra", True),
        ("Cultivo", "", False),
        ("Variedad o híbrido", "", False),
        ("Ciclo agrícola", "Ej. Primavera-Verano 2026", True),
        ("Etapa fenológica", "Ej. Floración y amarre de fruto", True),
        ("Superficie afectada (ha)", "", False),
        ("Municipio", "", False),
        ("Estado", "", False),
        ("Sistema de producción", "Temporal / Riego por goteo / Invernadero / Malla sombra", True),
        ("Fecha de siembra o trasplante", "", False),
        ("Fecha de la visita", "", False),
        ("Autor(es)", "", False),
        ("Institución o despacho", "", False),
        ("Correo de contacto", "", False),
    ])

    section(
        document, 2, "Motivo de consulta",
        "¿Qué observó el productor, desde cuándo y en qué parte del predio? Anota sus palabras y lo que espera resolver.",
    )
    field_block(document, 2)

    section(
        document, 3, "Historial de manejo",
        "Cultivo anterior, preparación del suelo, siembra, fertilización, riego, poda, aplicaciones (ingrediente activo, dosis y fecha) y eventos climáticos relevantes.",
    )
    field_block(document, 3)

    section(
        document, 4, "Inspección de campo",
        "Patrón espacial (manchones, hileras, bordes), incidencia y severidad estimadas (%), órganos afectados, malezas, textura y humedad del suelo y estado de las parcelas vecinas.",
    )
    field_block(document, 3)

    section(
        document, 5, "Análisis de laboratorio",
        "Análisis de suelo, agua o tejido vegetal y diagnóstico fitosanitario. Indica laboratorio, método, unidad y valor de referencia.",
    )
    data_table(
        document,
        ["Prueba o análisis", "Resultado", "Valor de referencia"],
        [6.0, 5.3, 5.3],
        rows=3,
        height=0.7,
        hints=["Ej. Análisis de suelo", "pH, materia orgánica, N-P-K", ""],
    )
    add_hint(document, "Si no hubo análisis, escríbelo: la ausencia de datos también es información.")

    section(
        document, 6, "Diagnósticos diferenciales",
        "Hipótesis principal y alternativas. Explica qué evidencia descartó cada una.",
    )
    field_block(document, 3)

    section(
        document, 7, "Diagnóstico final",
        "Sustenta el diagnóstico con la inspección y los análisis. Indica agente causal y nivel de certeza: confirmado, probable o presuntivo.",
    )
    field_block(document, 2)

    section(
        document, 8, "Manejo recomendado",
        "Prácticas culturales, manejo integrado y, si aplica, ingrediente activo, dosis, intervalo de seguridad y registro sanitario. No recomiendes dosis sin diagnóstico.",
    )
    field_block(document, 3)

    section(
        document, 9, "Resultado",
        "Respuesta a los 7, 15 y 30 días; rendimiento (t/ha) frente al testigo o al ciclo anterior; costo aproximado si lo conoces.",
    )
    field_block(document, 2)

    section(
        document, 10, "Discusión y aprendizaje",
        "Qué funcionó, qué limitó el caso y qué harías distinto. Reconoce la incertidumbre y las condiciones en que el resultado es válido.",
    )
    field_block(document, 2)

    section(document, 11, "Referencias")
    add_hint(document, "Cita en APA 7. Usa la plantilla de referencias del kit si necesitas el formato.")
    field_block(document, 2)

    section(document, 12, "Consentimiento y licencia")
    checkbox_table(document, [
        "Cuento con el consentimiento firmado del productor (formato del kit).",
        "Anonimicé nombres, teléfonos, domicilios, coordenadas exactas y datos de la empresa.",
        "Revisé que las imágenes no muestren personas, documentos, placas ni letreros con nombre.",
        "Declaré conflictos de interés si el caso menciona un producto o servicio.",
        "Elegí la licencia de publicación: CC BY-NC-SA 4.0 (predeterminada) u otra: ____________________",
    ])

    path = OUT / "plantilla-caso-agronomico.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 2. Checklist de consentimiento y anonimización
# --------------------------------------------------------------------------- #


def build_consent_checklist() -> Path:
    document = new_document()
    document.core_properties.title = "Checklist de consentimiento y anonimización"
    setup(
        document,
        "Checklist de consentimiento y anonimización",
        "Verificación previa a publicar un caso de campo. Consérvalo junto con el consentimiento firmado y los archivos originales.",
        eyebrow="Verificación interna · Imprimible",
    )
    note_box(
        document,
        "Antes de empezar",
        "Este checklist no sustituye el consentimiento informado de tu institución: es una comprobación interna. Si algún punto queda sin marcar, el caso no está listo para publicarse. Cuando tengas duda, protege al productor.",
        fill=SAND,
    )

    section(document, 1, "Antes de la visita")
    checkbox_table(document, [
        "Expliqué al productor el objetivo de la visita y qué haré con la información.",
        "Pedí permiso para recorrer el predio, tomar muestras y fotografiar.",
        "Acordé qué datos se publicarán y cuáles quedarán solo en mi expediente.",
        "Pregunté si hay información sensible: tenencia de la tierra, apoyos, conflictos o datos comerciales.",
        "Acordé cómo le devolveré los resultados y en qué plazo.",
    ])

    section(document, 2, "Consentimiento informado del productor")
    checkbox_table(document, [
        "Expliqué que el caso será público y podrá ser leído y citado por otras personas.",
        "Expliqué que la publicación tiene fines educativos y de divulgación, no comerciales.",
        "El productor autorizó el uso del texto, los datos técnicos y las imágenes.",
        "El productor sabe que puede solicitar correcciones o el retiro del contenido.",
        "Conservo el consentimiento firmado, con fecha y firma, en mi expediente.",
        "El consentimiento lo firmó el productor o su representante legal acreditado.",
    ])

    section(document, 3, "Anonimización de datos")
    checkbox_table(document, [
        "Eliminé el nombre del productor y de su familia.",
        "Eliminé teléfonos, correos, redes sociales y datos de contacto.",
        "Eliminé CURP, RFC, números de expediente y folios internos.",
        "Sustituí el domicilio y las coordenadas GPS exactas por el municipio y el estado.",
        "Oculté la razón social, marcas del predio y nombres de empacadoras o compradores.",
        "Quité nombres del equipo técnico que no sean relevantes para el caso.",
        "Revisé que la combinación de municipio, variedad, superficie y fecha no identifique al productor.",
    ])
    add_hint(
        document,
        "Prueba de reidentificación: si un vecino del municipio pudiera reconocer el predio con los datos publicados, todavía falta anonimizar.",
    )

    section(document, 4, "Imágenes y archivos")
    checkbox_table(document, [
        "No aparecen personas identificables, gafetes ni documentos.",
        "No se ven placas de vehículos, letreros con nombre ni números de lote.",
        "Recorté o difuminé cualquier identificador visual, incluidos metadatos EXIF.",
        "Las imágenes muestran el problema agronómico y su contexto, sin afectar la dignidad de nadie.",
        "Cuento con autorización expresa para publicar cada imagen o video.",
    ])

    section(document, 5, "Publicación")
    checkbox_table(document, [
        "El caso no contiene publicidad encubierta ni promoción de productos.",
        "El diagnóstico está sustentado y las limitaciones se explican con honestidad.",
        "Las dosis incluyen ingrediente activo y no solo el nombre comercial.",
        "Elegí la licencia de publicación (por defecto CC BY-NC-SA 4.0).",
        "Revisé ortografía, unidades y nombres científicos antes de enviar.",
    ])

    section(document, 6, "Firmas de verificación interna")
    signature_boxes(document, [
        ("Revisó el caso", ["Nombre y firma", "Cargo: ______________________"]),
        ("Fecha de verificación", ["____ / ____ / ________", "Próxima revisión: ____________"]),
    ], height=2.8)

    path = OUT / "checklist-consentimiento-productor.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 3. Hoja de recomendación técnica
# --------------------------------------------------------------------------- #


def build_recommendation_sheet() -> Path:
    document = new_document()
    document.core_properties.title = "Hoja de recomendación técnica"
    setup(
        document,
        "Hoja de recomendación técnica",
        "Formato imprimible para entregar al productor al cierre de la visita. Conserva una copia en tu expediente y respeta la normativa aplicable.",
        eyebrow="Formato imprimible · Entrega al productor",
    )

    section(document, 1, "Datos de la parcela y del productor")
    form_grid(document, [
        ("Productor", "", "Folio del caso", ""),
        ("Predio o lote", "", "Municipio y estado", ""),
        ("Cultivo", "", "Variedad o híbrido", ""),
        ("Etapa fenológica", "", "Superficie (ha)", ""),
        ("Sistema de producción", "", "Fecha de evaluación", ""),
        ("Ingeniero agrónomo", "", "Cédula profesional", ""),
    ])

    section(document, 2, "Diagnóstico")
    label_table(document, [
        ("Problema identificado", "", False),
        ("Agente causal o factor limitante", "", False),
        ("Nivel de certeza", "Confirmado / Probable / Presuntivo", True),
        ("Lotes o parcelas afectadas", "", False),
    ], label_width=5.0)

    section(
        document, 3, "Insumos y dosis indicadas",
        "Anota siempre el ingrediente activo y su concentración. Verifica la dosis, el intervalo de seguridad y el registro sanitario vigente antes de recomendar.",
    )
    data_table(
        document,
        ["Producto comercial", "Ingrediente activo y concentración", "Dosis (por ha o 100 L)", "Intervalo de seguridad (días)", "Registro sanitario", "Observaciones"],
        [3.2, 4.2, 3.0, 2.2, 2.2, 1.8],
        rows=4,
        height=0.95,
    )
    add_hint(
        document,
        "Si el manejo es solo cultural, escribe “sin insumos” y detalla las prácticas en el apartado 4. No dejes este cuadro en blanco.",
    )

    section(
        document, 4, "Prácticas culturales",
        "Riego, nutrición, poda, manejo de sombra, control de malezas, muestreo y eliminación de material afectado. Indica frecuencia y momento.",
    )
    field_block(document, 4)

    section(document, 5, "Seguimiento")
    data_table(
        document,
        ["Fecha de revisión", "Qué evaluar", "Umbral o criterio de decisión", "Responsable"],
        [3.2, 5.0, 5.2, 3.2],
        rows=3,
        height=0.85,
    )

    section(document, 6, "Advertencias de seguridad")
    bullet_list(document, [
        "Lee la etiqueta y la hoja de datos de seguridad; usa equipo de protección personal.",
        "No apliques con viento fuerte, lluvia inminente o temperaturas extremas.",
        "Respeta el intervalo de seguridad y el periodo de reingreso al cultivo.",
        "Dispón envases y residuos conforme al plan de manejo y a la autoridad local.",
        "Si aparecen síntomas nuevos o el problema se extiende, contacta antes de reaplicar.",
    ], size=8.5)

    section(document, 7, "Firmas")
    signature_boxes(document, [
        ("Ingeniero agrónomo responsable", ["Nombre y firma", "Cédula profesional: ______________"]),
        ("Recibí de conformidad", ["Productor o representante", "Fecha: ____ / ____ / ________"]),
    ], height=3.3)

    paragraph = document.add_paragraph(
        "Esta hoja resume una recomendación técnica para la parcela descrita. No sustituye el análisis de laboratorio, la asistencia de seguimiento ni la normativa en materia de sanidad, fertilizantes o plaguicidas.",
    )
    paragraph.paragraph_format.space_before = Pt(4)
    for run in paragraph.runs:
        run.font.size = Pt(7.5)
        run.font.name = "Calibri"
        run.font.color.rgb = MUTED
        run.italic = True

    path = OUT / "hoja-de-recomendacion.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 4. Consentimiento informado
# --------------------------------------------------------------------------- #


def build_consent_form() -> Path:
    document = new_document()
    document.core_properties.title = "Consentimiento informado del productor"
    setup(
        document,
        "Consentimiento informado del productor",
        "Autorización para publicar un caso de campo con fines educativos y de divulgación. Adáptalo a los requisitos legales de tu institución.",
        eyebrow="Formato imprimible · Firma del productor",
    )

    section(document, 1, "Datos del caso y del productor")
    form_grid(document, [
        ("Título del caso", "", "Cultivo y variedad", ""),
        ("Municipio y estado", "", "Superficie (ha)", ""),
        ("Nombre del productor", "", "Calidad", "Propietario / Ejidatario / Arrendatario"),
        ("Ingeniero responsable", "", "Cédula profesional", ""),
        ("Institución o despacho", "", "Fecha", ""),
    ])

    section(document, 2, "Declaración y autorización")
    paragraph = document.add_paragraph(
        "Declaro que recibí una explicación clara sobre el caso que se documentará, que mi participación es voluntaria y que no recibiré pago por autorizar su publicación. Autorizo el uso del texto, los datos técnicos y las imágenes descritas en el anexo, con las condiciones siguientes:",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)
        run.font.name = "Calibri"
    checkbox_table(document, [
        "Entiendo que el caso se publicará en AgroDiagnóstico y podrá ser leído y citado por otras personas.",
        "Autorizo que se publiquen datos del cultivo, municipio, superficie y manejo, sin mi nombre ni mis datos de contacto.",
        "Entiendo que se eliminarán mi nombre, teléfono, correo, domicilio y las coordenadas exactas del predio.",
        "Entiendo que las imágenes se revisarán para no mostrar personas, documentos, placas ni letreros con nombre.",
        "Autorizo la distribución del caso bajo la licencia CC BY-NC-SA 4.0, con atribución al autor y sin uso comercial.",
        "Sé que puedo solicitar correcciones o el retiro del contenido escribiendo al comité editorial del portal.",
        "Entiendo que la publicación no sustituye una recomendación técnica para mi parcela ni garantiza resultados.",
        "Declaro que la información que proporcioné es veraz y que puedo autorizar el uso de las imágenes adjuntas.",
    ])

    section(document, 3, "Límites y riesgos")
    paragraph = document.add_paragraph(
        "La publicación es voluntaria y no afecta la atención técnica que recibo. Aunque se eliminan los datos identificables, existe un riesgo residual de reconocimiento por la combinación de cultivo, municipio, variedad y fecha. El equipo editorial revisará el contenido antes de publicarlo y puede solicitar cambios o rechazarlo. No se publicará información comercial sensible, adeudos, apoyos gubernamentales ni detalles de tenencia de la tierra.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
        run.font.name = "Calibri"

    section(document, 4, "Anexo: imágenes y archivos autorizados")
    data_table(
        document,
        ["Archivo o imagen", "Descripción", "¿Se difumina algo?", "Autorizado"],
        [5.2, 5.8, 3.2, 2.4],
        rows=3,
        height=0.8,
        hints=["Ej. foto_01.jpg", "Síntomas en hoja", "No", "Sí"],
    )

    section(document, 5, "Declaración del ingeniero agrónomo")
    paragraph = document.add_paragraph(
        "Confirmo que expliqué al productor el alcance de la publicación, que cuento con su autorización y que el caso se documentó de forma veraz. Me comprometo a eliminar datos identificables, a no incluir publicidad encubierta y a resguardar el consentimiento firmado.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
        run.font.name = "Calibri"

    section(document, 6, "Firmas")
    signature_boxes(document, [
        ("Productor o representante legal", ["Nombre y firma", "Fecha: ____ / ____ / ________"]),
        ("Ingeniero agrónomo responsable", ["Nombre y firma", "Cédula profesional: ______________"]),
    ], height=3.4)
    paragraph = document.add_paragraph("Testigo (opcional): ______________________________________    Firma: ______________________________")
    paragraph.paragraph_format.space_before = Pt(6)
    for run in paragraph.runs:
        run.font.size = Pt(8)
        run.font.name = "Calibri"

    paragraph = document.add_paragraph(
        "Conserva una copia firmada en tu expediente y entrega otra al productor. Esta plantilla no constituye asesoría legal: verifica los requisitos de tu institución y la normativa aplicable en materia de datos personales.",
    )
    paragraph.paragraph_format.space_before = Pt(6)
    for run in paragraph.runs:
        run.font.size = Pt(7.5)
        run.font.name = "Calibri"
        run.font.color.rgb = MUTED
        run.italic = True

    path = OUT / "consentimiento-informado.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 5. Solicitud de acceso a datos
# --------------------------------------------------------------------------- #


def build_request_template() -> Path:
    document = new_document()
    document.core_properties.title = "Solicitud de acceso a datos agrícolas"
    setup(
        document,
        "Solicitud de acceso a datos agrícolas",
        "Carta modelo para pedir datos a SIAP, INIFAP, SENASICA u otra institución. Sustituye los campos entre corchetes y conserva una copia de la respuesta.",
        eyebrow="Carta modelo · Datos e información pública",
    )

    paragraph = document.add_paragraph("[Ciudad], [estado], a [día] de [mes] de [año]")
    paragraph.paragraph_format.space_after = Pt(2)
    paragraph = document.add_paragraph("A: [nombre de la dirección, unidad o institución responsable]")
    paragraph.paragraph_format.space_after = Pt(2)
    paragraph = document.add_paragraph("Asunto: Solicitud de acceso a [nombre del conjunto de datos o sistema]")
    paragraph.paragraph_format.space_after = Pt(8)
    for run in paragraph.runs:
        run.bold = True

    section(document, 1, "Presentación")
    paragraph = document.add_paragraph(
        "Mi nombre es [nombre completo]. Soy ingeniero agrónomo con cédula profesional [número] y colaboro en [institución, despacho o predio]. Mi correo es [correo] y mi teléfono es [teléfono]. Presento esta solicitud en mi carácter de [profesional independiente / investigador / docente / prestador de servicios].",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)

    section(document, 2, "Objetivo de la solicitud")
    paragraph = document.add_paragraph(
        "Solicito acceso a [nombre del recurso] para [describir el objetivo: tesis, artículo, docencia, análisis de casos, planeación de campaña o vigilancia fitosanitaria]. La pregunta principal es: [pregunta concreta y verificable].",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)
    form_grid(document, [
        ("Pregunta principal", "", "Cultivo o sistema productivo", ""),
        ("Cobertura geográfica", "Municipio / estado / nacional", "Periodo de interés", ""),
    ])

    section(document, 3, "Uso previsto")
    paragraph = document.add_paragraph(
        "Planeo utilizar los datos para [análisis descriptivo, comparación de rendimientos, validación de recomendaciones, docencia]. No los usaré para identificar productores o predios, ni para decisiones comerciales sobre personas o empresas específicas.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)
    checkbox_table(document, [
        "Análisis descriptivo o comparativo de rendimiento (t/ha) y superficie.",
        "Tesis, artículo o material de docencia con fines no comerciales.",
        "Planeación de campaña o priorización de visitas técnicas.",
        "Vigilancia fitosanitaria o evaluación de riesgo territorial.",
    ])

    section(document, 4, "Datos solicitados")
    data_table(
        document,
        ["Variable o archivo", "Periodo", "Cobertura", "Formato deseado"],
        [6.0, 3.2, 3.8, 3.6],
        rows=3,
        height=0.8,
        hints=["Ej. superficie sembrada, producción, rendimiento", "2018–2024", "Municipios de Puebla", "CSV o Excel"],
    )

    section(document, 5, "Compromisos")
    bullet_list(document, [
        "Cumpliré la licencia, las condiciones de acceso y las restricciones de uso que ustedes indiquen.",
        "No compartiré los datos con terceros ni los publicaré sin autorización.",
        "No intentaré reidentificar productores, predios o empresas.",
        "Resguardaré los archivos en un entorno seguro y los eliminaré al concluir el proyecto.",
        "Citaré la fuente, la versión y el DOI o la referencia que corresponda.",
        "Enviaré un resumen de resultados o una copia del producto final si lo solicitan.",
    ])

    section(document, 6, "Vigencia y resguardo")
    paragraph = document.add_paragraph(
        "Conservaré los datos hasta [fecha de conclusión o eliminación] y después los borraré de mis equipos y respaldos. El responsable del resguardo es [nombre], con correo [correo]. Si el proyecto se interrumpe, notificaré a la institución y eliminaré las copias.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)

    section(document, 7, "Cierre")
    paragraph = document.add_paragraph(
        "Quedo atento a los requisitos, tiempos y documentos que deba firmar. Agradezco su orientación para usar el recurso de forma ética y correcta, y para citarlo adecuadamente en los productos que se deriven.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)
    document.add_paragraph("Atentamente,")
    document.add_paragraph("[nombre completo]")
    paragraph = document.add_paragraph("[institución o despacho] · [correo] · [teléfono]")
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
        run.font.color.rgb = MUTED

    section(document, 8, "Anexo: verificación ética y documentos")
    checkbox_table(document, [
        "El proyecto cuenta con aprobación de comité de ética o investigación, si aplica.",
        "Tengo un plan escrito para proteger la confidencialidad y la seguridad de los datos.",
        "Entiendo que el acceso puede ser negado, condicionado o limitado a datos agregados.",
        "No usaré los datos para reidentificar productores, predios o empresas.",
        "Adjunto identificación oficial, cédula profesional y carta institucional, si se requieren.",
        "Adjunto el protocolo o resumen del proyecto con objetivos y productos esperados.",
    ])
    paragraph = document.add_paragraph("Firma: ______________________________    Nombre: ______________________________    Fecha: ____ / ____ / ________")
    paragraph.paragraph_format.space_before = Pt(6)
    for run in paragraph.runs:
        run.font.size = Pt(8.5)

    path = OUT / "solicitud-acceso-datos.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 6. Guía de recursos para agrónomos
# --------------------------------------------------------------------------- #


def build_guide() -> Path:
    document = new_document()
    document.core_properties.title = "Guía rápida: datos agrícolas para ingenieros agrónomos"
    setup(
        document,
        "Guía rápida: datos agrícolas para ingenieros agrónomos",
        "Qué es un conjunto de datos, una API y un repositorio; cómo elegir un recurso y solicitar acceso sin ser programador.",
        eyebrow="Guía de consulta · Lenguaje sencillo",
    )

    section(document, 1, "Para qué te sirven los datos abiertos")
    paragraph = document.add_paragraph(
        "Los datos públicos de agricultura te permiten comparar tu caso con el contexto del municipio, justificar una recomendación, preparar una clase o una tesis y detectar patrones que no se ven en una sola parcela. No necesitas programar: casi todo se consulta en un sitio web o se descarga en Excel.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(9)
    bullet_list(document, [
        "Comparar rendimiento (t/ha), superficie sembrada y siniestrada de un cultivo en tu municipio.",
        "Ubicar la normativa aplicable y los requisitos de movilización o sanidad vegetal.",
        "Documentar casos con contexto y citar la fuente de cada dato.",
        "Preparar material de docencia o una propuesta de asistencia técnica.",
    ])

    section(document, 2, "Cuatro conceptos básicos")
    label_table(document, [
        ("Conjunto de datos (dataset)", "Archivo organizado en filas y columnas, o en varios archivos, que se abre con Excel, Numbers o Google Sheets. Ejemplo: superficie y rendimiento por cultivo y municipio.", False),
        ("API", "Forma de consultar datos de un sistema desde otro. Tú puedes leer la documentación; para integrarla en un programa necesitas apoyo técnico. Ejemplo: una API que devuelve precios por fecha.", False),
        ("Repositorio", "Sitio donde se guardan y comparten datos, documentos o código, con versión y licencia. Ejemplo: el repositorio institucional del INIFAP o un archivo de datos de investigación.", False),
        ("Metadatos", "Información que describe el dato: quién lo generó, cuándo, dónde, con qué método y en qué unidad (t/ha, hectáreas, pesos por tonelada).", False),
    ], label_width=4.0, height=0.95)

    section(document, 3, "Dónde buscar información agrícola en México")
    data_table(
        document,
        ["Institución", "Qué información publica", "Cómo acceder"],
        [3.8, 8.3, 4.5],
        rows=4,
        height=1.15,
    )
    table = document.tables[-1]
    rows = [
        ("SIAP", "Superficie sembrada, cosechada y siniestrada, producción, rendimiento y precios al productor por cultivo, estado y municipio.", "Consultas y descargas en siap.gob.mx; microdatos por solicitud."),
        ("INIFAP", "Guías técnicas, variedades, resultados de ensayos y publicaciones de investigación agrícola y pecuaria.", "Publicaciones y repositorio institucional; solicitud de información."),
        ("SENASICA", "Avisos fitosanitarios, campañas, requisitos de movilización y registros de sanidad vegetal.", "Consultas en el portal; solicitud de información pública."),
        ("INEGI", "Censo Agropecuario, uso de suelo y datos sociodemográficos del medio rural.", "Descargas abiertas en inegi.org.mx."),
    ]
    for index, values in enumerate(rows):
        for column, value in enumerate(values):
            cell = table.rows[index + 1].cells[column]
            set_cell_text(cell, value, size=8 if column else 8.5, bold=(column == 0))
    add_hint(document, "Las cifras pueden cambiar de versión. Anota la fecha de consulta y la versión que usaste.")

    section(document, 4, "Cómo elegir un recurso")
    bullet_list(document, [
        "Define la pregunta antes de buscar: cultivo, problema, territorio y periodo.",
        "Revisa la cobertura geográfica y si el dato es municipal, estatal o nacional.",
        "Verifica la unidad: t/ha no es lo mismo que toneladas totales; superficie sembrada no es superficie cosechada.",
        "Lee la licencia y las condiciones de uso antes de descargar.",
        "Identifica quién publica, con qué método y si el recurso tiene revisión o documentación.",
        "Comprueba si hay diccionario de variables; sin él, una columna puede significar varias cosas.",
    ], size=8.5)

    section(document, 5, "Cómo solicitar acceso en cinco pasos")
    numbered_list(document, [
        "Abre la ficha del recurso y lee los requisitos, el formato y la licencia.",
        "Copia la carta modelo del kit y completa tus datos profesionales.",
        "Explica tu objetivo con una pregunta concreta y un periodo definido.",
        "Describe cómo protegerás la confidencialidad y qué harás con los resultados.",
        "Guarda la respuesta, las condiciones de uso y la versión descargada en tu expediente.",
    ])

    section(document, 6, "Señales de alerta")
    bullet_list(document, [
        "El recurso no indica autor, institución, fecha ni licencia.",
        "Piden datos personales de productores sin justificación ni aviso de privacidad.",
        "Ofrecen acceso inmediato a cambio de pagos informales o fuera de los canales institucionales.",
        "No permiten citar la fuente ni explican cómo se generaron o validaron los datos.",
        "Las cifras no coinciden con la fuente oficial y no hay nota metodológica.",
    ], size=8.5)

    section(document, 7, "Buenas prácticas al usar datos")
    bullet_list(document, [
        "Descarga el diccionario de variables y guárdalo junto con el archivo.",
        "Anota la versión, la fecha de consulta y el enlace exacto.",
        "Conserva una copia sin editar y trabaja sobre una copia nueva.",
        "No reidentifiques productores ni predios; publica datos agregados.",
        "Distingue correlación de causalidad al interpretar rendimiento y clima.",
        "Cita la fuente en el caso, la tesis o la presentación, con DOI o URL.",
        "Si detectas un error en la fuente, repórtalo a la institución.",
    ], size=8.5)

    section(document, 8, "Antes de usar los datos en un trabajo")
    checkbox_table(document, [
        "Tengo una pregunta clara y sé qué decisión técnica va a informar.",
        "Revisé la licencia, la versión y las condiciones de acceso.",
        "Contemplé la aprobación de un comité de ética si trabajo con productores.",
        "Tengo un plan para resguardar los archivos y eliminarlos al terminar.",
        "Sé cómo voy a citar la fuente y cómo presentaré las limitaciones.",
        "No voy a reidentificar personas, predios o empresas.",
    ])

    section(document, 9, "Dónde pedir ayuda")
    paragraph = document.add_paragraph(
        "Consulta el directorio de recursos y el glosario del portal. Si trabajas en una institución, acude a la biblioteca o a la oficina de investigación: suelen apoyar con búsqueda de datos, gestión de información y revisión de solicitudes. Para dudas sobre una parcela concreta, la recomendación sigue siendo tu criterio profesional y la visita de campo.",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)

    path = OUT / "guia-recursos-para-agronomos.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# 7. Formato de referencia bibliográfica (APA 7)
# --------------------------------------------------------------------------- #


def build_citation_guide() -> Path:
    document = new_document()
    document.core_properties.title = "Formato de referencias bibliográficas (APA 7)"
    setup(
        document,
        "Formato de referencias bibliográficas (APA 7)",
        "Ejemplos para citar artículos, informes técnicos, conjuntos de datos y normas oficiales en casos y publicaciones agronómicas.",
        eyebrow="Referencias · Estilo APA 7",
    )
    note_box(
        document,
        "Antes de copiar un ejemplo",
        "Los ejemplos son ilustrativos: sustituye autores, años, títulos y DOI por los datos reales de la fuente que consultaste. Si el documento no tiene fecha, usa (s. f.) y agrega la fecha de consulta solo cuando el contenido cambie con el tiempo.",
        fill=SAND,
    )

    section(document, 1, "Reglas generales")
    bullet_list(document, [
        "Ordena las referencias alfabéticamente por el primer apellido del autor o por el nombre de la institución.",
        "Usa sangría francesa de 1.27 cm y doble espacio si la revista o institución lo solicita.",
        "Cita autor y año en el texto: (Hernández-Ruiz & López-Bautista, 2023) o Hernández-Ruiz y López-Bautista (2023).",
        "Con tres o más autores, usa et al. en la cita del texto desde la primera mención.",
        "Incluye DOI como enlace completo (https://doi.org/...) cuando exista; si no, usa la URL estable.",
        "No inventes datos faltantes: si desconoces la editorial o el autor, indica lo que la fuente muestra.",
    ], size=8.5)

    section(document, 2, "Artículo científico")
    add_hint(document, "Autor, A. A., & Autor, B. B. (Año). Título del artículo. Nombre de la Revista, volumen(número), páginas. https://doi.org/xxxx")
    paragraph = document.add_paragraph(
        "Ejemplo: Hernández-Ruiz, J., & López-Bautista, M. (2023). Severidad de la roya del café y su relación con la altitud en la Sierra Norte de Puebla. Revista Fitotecnia Mexicana, 46(2), 145–153. https://doi.org/10.35196/rfm.2023.2.145",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
    add_hint(document, "El título del artículo va en minúscula (solo mayúscula inicial y nombres propios); el nombre de la revista conserva sus mayúsculas y va en cursiva en el original.")

    section(document, 3, "Informe técnico o folleto")
    add_hint(document, "Institución. (Año). Título del informe (No. de publicación) [Informe técnico]. Editorial o institución. URL")
    paragraph = document.add_paragraph(
        "Ejemplo: Instituto Nacional de Investigaciones Forestales, Agrícolas y Pecuarias. (2022). Guía técnica para el manejo de la roya del café en Chiapas (Folleto técnico No. 12) [Informe técnico]. INIFAP. https://www.gob.mx/inifap",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)

    section(document, 4, "Conjunto de datos (dataset)")
    add_hint(document, "Autor o institución. (Año). Título del conjunto de datos (Versión) [Conjunto de datos]. Repositorio. https://doi.org/xxxx")
    paragraph = document.add_paragraph(
        "Ejemplo: Servicio de Información Agroalimentaria y Pesquera. (2024). Producción agrícola por cultivo, municipio y ciclo (Versión 2024) [Conjunto de datos]. SIAP. https://www.gob.mx/siap",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
    add_hint(document, "Si el conjunto de datos tiene DOI, úsalo en lugar de la URL. Indica la versión y la fecha de consulta si los datos se actualizan con frecuencia.")

    section(document, 5, "Norma Oficial Mexicana")
    add_hint(document, "Dependencia. (Año). Norma Oficial Mexicana NOM-XXX-XXXX, título de la norma. Diario Oficial de la Federación. URL")
    paragraph = document.add_paragraph(
        "Ejemplo: Secretaría de Medio Ambiente y Recursos Naturales. (2002). Norma Oficial Mexicana NOM-021-RECNAT-2000, que establece las especificaciones de fertilidad, salinidad y clasificación de suelos. Estudios, muestreo y análisis. Diario Oficial de la Federación. https://www.dof.gob.mx",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
    add_hint(document, "Verifica la fecha de publicación en el DOF y la vigencia de la norma; una norma puede ser modificada o abrogada.")

    section(document, 6, "Caso publicado en AgroDiagnóstico")
    add_hint(document, "Autor. (Año). Título del caso. AgroDiagnóstico. URL del caso")
    paragraph = document.add_paragraph(
        "Ejemplo: Ramírez-Ortega, A. (2026). Marchitez y caída de fruto en jitomate bajo malla sombra en Tepeaca, Puebla. AgroDiagnóstico. https://agrodiagnostico.mx/casos/marchitez-jitomate-tepeaca",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)
    add_hint(document, "Sustituye el dominio y la ruta por la URL real del caso publicado.")

    section(document, 7, "Cómo citar este kit")
    add_hint(document, "AgroDiagnóstico. (2026). Kit de recursos para ingenieros agrónomos [Plantillas y guías descargables]. URL del kit")
    paragraph = document.add_paragraph(
        "Ejemplo: AgroDiagnóstico. (2026). Kit de recursos para ingenieros agrónomos [Plantillas y guías descargables]. https://agrodiagnostico.mx/recursos/kit",
    )
    for run in paragraph.runs:
        run.font.size = Pt(8.5)

    section(document, 8, "Citas en el texto")
    label_table(document, [
        ("Un autor", "(García, 2021) o García (2021)", False),
        ("Dos autores", "(García & Pérez, 2021)", False),
        ("Tres o más autores", "(García et al., 2021)", False),
        ("Institución", "(SIAP, 2024)", False),
        ("Cita textual", "(García, 2021, p. 45)", False),
    ], label_width=4.5, height=0.55)

    section(document, 9, "Checklist antes de entregar")
    checkbox_table(document, [
        "Cada afirmación tomada de otra fuente tiene una cita en el texto.",
        "Las citas del texto coinciden con la lista de referencias y viceversa.",
        "Incluí DOI o URL estable cuando está disponible.",
        "Verifiqué el estilo solicitado por la revista o la institución.",
        "No atribuí datos, frases o resultados a una fuente que no los contiene.",
        "Revisé que las referencias estén ordenadas alfabéticamente y con sangría francesa.",
    ])

    path = OUT / "formato-referencia-bibliografica.docx"
    document.save(path)
    return path


# --------------------------------------------------------------------------- #
# Ejecución
# --------------------------------------------------------------------------- #


def remove_legacy() -> None:
    for name in LEGACY_FILES:
        path = OUT / name
        if path.exists():
            path.unlink()
            print(f"eliminado · {path.relative_to(BASE)}")


def main() -> None:
    remove_legacy()
    files = [
        build_case_template(),
        build_consent_checklist(),
        build_recommendation_sheet(),
        build_consent_form(),
        build_request_template(),
        build_guide(),
        build_citation_guide(),
    ]
    zip_path = OUT / "kit-recursos-agronomicos.zip"
    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as archive:
        for file in files:
            archive.write(file, arcname=file.name)
    for file in files + [zip_path]:
        size = file.stat().st_size
        print(f"{file.relative_to(BASE)} · {size:,} bytes".replace(",", " "))


if __name__ == "__main__":
    main()

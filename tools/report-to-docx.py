#!/usr/bin/env python3
"""
Keeply — convert the markdown report into a Word document.

    python tools/report-to-docx.py
    python tools/report-to-docx.py docs/REPORT.md docs/Keeply-Submission-Report.docx

Handles the subset of Markdown the report actually uses: ATX headings, pipe
tables, fenced code blocks, ordered and unordered lists, block quotes,
horizontal rules, and inline bold / italic / code / links.

Mermaid blocks cannot be drawn by python-docx, so each becomes a clearly
labelled placeholder carrying its own source and instructions. Rendering them
to images needs either a local Mermaid toolchain or pasting the source into a
diagram editor, and neither should happen silently inside a converter.

Requires: python-docx==1.1.2
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.image.image import Image as DocxImage
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor

MONO = "Consolas"
BODY = "Calibri"
HEAD = "Cambria"

CODE_BG = "F2F3F5"
MERMAID_BG = "EEF2FE"
INK_SOFT = RGBColor(0x5B, 0x64, 0x71)

DIAGRAMS = Path(__file__).resolve().parent.parent / "docs" / "diagrams"


# --- low-level helpers ----------------------------------------------------


def shade(element, fill: str) -> None:
    """Apply a background fill to a paragraph or table cell."""
    shading = OxmlElement("w:shd")
    shading.set(qn("w:val"), "clear")
    shading.set(qn("w:color"), "auto")
    shading.set(qn("w:fill"), fill)
    element.append(shading)


def add_hyperlink(paragraph, url: str, text: str) -> None:
    part = paragraph.part
    r_id = part.relate_to(
        url,
        "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink",
        is_external=True,
    )

    link = OxmlElement("w:hyperlink")
    link.set(qn("r:id"), r_id)

    run = OxmlElement("w:r")
    props = OxmlElement("w:rPr")

    colour = OxmlElement("w:color")
    colour.set(qn("w:val"), "1F4ED8")
    props.append(colour)

    underline = OxmlElement("w:u")
    underline.set(qn("w:val"), "single")
    props.append(underline)

    run.append(props)

    node = OxmlElement("w:t")
    node.text = text
    run.append(node)
    link.append(run)

    paragraph._p.append(link)


INLINE = re.compile(
    r"(\*\*.+?\*\*"          # bold
    r"|`[^`]+`"              # code
    r"|\[[^\]]+\]\([^)]+\)"  # link
    r"|\*[^*]+\*"            # italic
    r"|_[^_]+_)"             # italic
)


def write_inline(paragraph, text: str, base_size: int | None = None) -> None:
    """Render inline markdown into an existing paragraph."""
    for piece in INLINE.split(text):
        if not piece:
            continue

        if piece.startswith("**") and piece.endswith("**"):
            run = paragraph.add_run(piece[2:-2])
            run.bold = True
        elif piece.startswith("`") and piece.endswith("`"):
            run = paragraph.add_run(piece[1:-1])
            run.font.name = MONO
            run.font.size = Pt((base_size or 10.5) - 1)
        elif piece.startswith("["):
            match = re.match(r"\[([^\]]+)\]\(([^)]+)\)", piece)
            if match:
                add_hyperlink(paragraph, match.group(2), match.group(1))
            else:
                paragraph.add_run(piece)
        elif (piece.startswith("*") and piece.endswith("*")) or (
            piece.startswith("_") and piece.endswith("_")
        ):
            run = paragraph.add_run(piece[1:-1])
            run.italic = True
        else:
            paragraph.add_run(piece)

        if base_size:
            for run in paragraph.runs:
                if run.font.size is None:
                    run.font.size = Pt(base_size)


# --- document setup -------------------------------------------------------


def configure(document: Document) -> None:
    section = document.sections[0]
    section.page_width = Inches(8.27)   # A4
    section.page_height = Inches(11.69)
    section.left_margin = Inches(1.0)
    section.right_margin = Inches(0.9)
    section.top_margin = Inches(0.9)
    section.bottom_margin = Inches(0.9)

    normal = document.styles["Normal"]
    normal.font.name = BODY
    normal.font.size = Pt(10.5)
    normal.paragraph_format.space_after = Pt(7)
    normal.paragraph_format.line_spacing = 1.15

    for level, size in ((1, 19), (2, 15), (3, 12.5), (4, 11)):
        style = document.styles[f"Heading {level}"]
        style.font.name = HEAD
        style.font.size = Pt(size)
        style.font.color.rgb = RGBColor(0x15, 0x18, 0x1D)
        style.paragraph_format.space_before = Pt(16 if level < 3 else 12)
        style.paragraph_format.space_after = Pt(6)
        style.paragraph_format.keep_with_next = True

    # Page numbers in the footer.
    footer = section.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = footer.add_run()
    fld = OxmlElement("w:fldSimple")
    fld.set(qn("w:instr"), "PAGE")
    run._r.append(fld)
    footer.runs[0].font.size = Pt(9)
    footer.runs[0].font.color.rgb = INK_SOFT


def add_toc(document: Document) -> None:
    paragraph = document.add_paragraph()
    run = paragraph.add_run()

    fld_begin = OxmlElement("w:fldChar")
    fld_begin.set(qn("w:fldCharType"), "begin")

    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = r'TOC \o "1-3" \h \z \u'

    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")

    placeholder = OxmlElement("w:t")
    placeholder.text = "Right-click here and choose Update Field to build the contents."

    fld_end = OxmlElement("w:fldChar")
    fld_end.set(qn("w:fldCharType"), "end")

    for node in (fld_begin, instr, separate, placeholder, fld_end):
        run._r.append(node)


def add_code_block(document: Document, lines: list[str], fill: str = CODE_BG) -> None:
    paragraph = document.add_paragraph()
    paragraph.paragraph_format.left_indent = Inches(0.12)
    paragraph.paragraph_format.space_before = Pt(6)
    paragraph.paragraph_format.space_after = Pt(10)
    paragraph.paragraph_format.line_spacing = 1.0
    shade(paragraph._p.get_or_add_pPr(), fill)

    for index, line in enumerate(lines):
        if index:
            paragraph.add_run().add_break()
        run = paragraph.add_run(line)
        run.font.name = MONO
        run.font.size = Pt(8.5)


def add_diagram(document: Document, lines: list[str], number: int) -> bool:
    """
    Insert a rendered diagram, or its source if no image exists yet.

    Returns True when an image was embedded. tools/render-diagrams.py produces
    the images; this keeps working either way, so the document never fails to
    build because a diagram has not been rendered.
    """
    image = DIAGRAMS / f"diagram-{number}.png"

    if image.is_file():
        try:
            measured = DocxImage.from_file(str(image))
            native_width = measured.px_width / (measured.horz_dpi or 96)
            native_height = measured.px_height / (measured.vert_dpi or 96)

            # Fit inside the text column and the page, preserving aspect ratio.
            max_width, max_height = 6.2, 8.4
            scale = min(max_width / native_width, max_height / native_height)

            paragraph = document.add_paragraph()
            paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
            paragraph.paragraph_format.space_before = Pt(8)
            paragraph.paragraph_format.space_after = Pt(2)
            paragraph.add_run().add_picture(str(image), width=Inches(native_width * scale))

            caption = document.add_paragraph()
            caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
            caption.paragraph_format.space_after = Pt(12)
            run = caption.add_run(f"Figure {number}")
            run.italic = True
            run.font.size = Pt(9)
            run.font.color.rgb = INK_SOFT

            return True
        except Exception as error:  # noqa: BLE001 - fall back rather than fail the build
            print(f"  warning: could not embed {image.name} ({error}); using the source instead")

    caption = document.add_paragraph()
    caption.paragraph_format.space_before = Pt(10)
    caption.paragraph_format.space_after = Pt(2)
    run = caption.add_run(f"Diagram {number} — render this before submitting")
    run.bold = True
    run.font.size = Pt(10)

    note = document.add_paragraph()
    note.paragraph_format.space_after = Pt(4)
    hint = note.add_run(
        "Run tools/render-diagrams.py, or paste the source below into a Mermaid "
        "renderer and replace this block with the image."
    )
    hint.italic = True
    hint.font.size = Pt(9)
    hint.font.color.rgb = INK_SOFT

    add_code_block(document, lines, MERMAID_BG)
    return False


def add_table(document: Document, rows: list[list[str]]) -> None:
    if not rows:
        return

    # A table written with an intentionally blank header row (as the title-page
    # metadata is) should not render a blank shaded band.
    has_header = any(cell.strip() for cell in rows[0])
    if not has_header:
        rows = rows[1:]
        if not rows:
            return

    columns = max(len(row) for row in rows)
    table = document.add_table(rows=0, cols=columns)
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True

    for position, row in enumerate(rows):
        cells = table.add_row().cells
        for index in range(columns):
            text = row[index] if index < len(row) else ""
            cell = cells[index]
            paragraph = cell.paragraphs[0]
            paragraph.paragraph_format.space_after = Pt(2)
            paragraph.paragraph_format.space_before = Pt(2)
            write_inline(paragraph, text, base_size=9)

            if position == 0 and has_header:
                shade(cell._tc.get_or_add_tcPr(), "EEF0F4")
                for run in paragraph.runs:
                    run.bold = True

    document.add_paragraph().paragraph_format.space_after = Pt(6)


# --- parsing --------------------------------------------------------------


def split_row(line: str) -> list[str]:
    stripped = line.strip()
    if stripped.startswith("|"):
        stripped = stripped[1:]
    if stripped.endswith("|"):
        stripped = stripped[:-1]
    return [cell.strip() for cell in stripped.split("|")]


def is_divider(line: str) -> bool:
    return bool(re.fullmatch(r"\|?[\s:|-]+\|?", line.strip())) and "-" in line


def convert(source: Path, target: Path) -> dict[str, int]:
    text = source.read_text(encoding="utf-8")
    lines = text.splitlines()

    document = Document()
    configure(document)

    stats = {
        "headings": 0,
        "tables": 0,
        "code": 0,
        "diagrams_embedded": 0,
        "diagrams_pending": 0,
        "paragraphs": 0,
    }
    diagram_number = 0

    index = 0
    total = len(lines)

    # --- title page, then the contents ---------------------------------
    #
    # Built before the body so the Contents sits where a reader expects it,
    # rather than wherever the loop happens to finish.

    while index < total and not lines[index].strip().startswith("# "):
        index += 1

    if index < total:
        title = document.add_paragraph()
        title.alignment = WD_ALIGN_PARAGRAPH.CENTER
        title.paragraph_format.space_before = Pt(150)
        title.paragraph_format.space_after = Pt(4)
        run = title.add_run(lines[index].strip()[2:].strip())
        run.font.name = HEAD
        run.font.size = Pt(36)
        run.bold = True
        stats["headings"] += 1
        index += 1

        # A "## " line immediately after the title is its subtitle.
        while index < total and lines[index].strip() == "":
            index += 1

        if index < total and lines[index].strip().startswith("## "):
            subtitle = document.add_paragraph()
            subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
            subtitle.paragraph_format.space_after = Pt(18)
            run = subtitle.add_run(lines[index].strip()[3:].strip())
            run.font.name = HEAD
            run.font.size = Pt(14)
            run.font.color.rgb = INK_SOFT
            index += 1

        while index < total and lines[index].strip() == "":
            index += 1

        # A bold-only line next is the project's tagline.
        if (
            index < total
            and lines[index].strip().startswith("**")
            and lines[index].strip().endswith("**")
        ):
            tagline = document.add_paragraph()
            tagline.alignment = WD_ALIGN_PARAGRAPH.CENTER
            tagline.paragraph_format.space_after = Pt(42)
            run = tagline.add_run(lines[index].strip().strip("*"))
            run.italic = True
            run.font.size = Pt(11)
            run.font.color.rgb = INK_SOFT
            index += 1

        # The submission metadata table belongs on page one, not after the
        # contents, so it is consumed here rather than by the body loop.
        while index < total and (
            lines[index].strip() == "" or lines[index].strip() in {"---", "***", "___"}
        ):
            index += 1

        if index < total and lines[index].strip().startswith("|"):
            rows: list[list[str]] = []
            while index < total and lines[index].strip().startswith("|"):
                if not is_divider(lines[index]):
                    rows.append(split_row(lines[index]))
                index += 1
            add_table(document, rows)
            stats["tables"] += 1

    document.add_page_break()
    document.add_heading("Contents", level=1)
    add_toc(document)
    document.add_page_break()

    while index < total:
        line = lines[index]
        stripped = line.strip()

        # --- fenced code ------------------------------------------------
        if stripped.startswith("```"):
            language = stripped[3:].strip().lower()
            index += 1
            block: list[str] = []
            while index < total and not lines[index].strip().startswith("```"):
                block.append(lines[index])
                index += 1
            index += 1  # closing fence

            if language == "mermaid":
                diagram_number += 1
                if add_diagram(document, block, diagram_number):
                    stats["diagrams_embedded"] += 1
                else:
                    stats["diagrams_pending"] += 1
            else:
                add_code_block(document, block)
                stats["code"] += 1
            continue

        # --- tables -----------------------------------------------------
        if stripped.startswith("|"):
            rows: list[list[str]] = []
            while index < total and lines[index].strip().startswith("|"):
                candidate = lines[index]
                if not is_divider(candidate):
                    rows.append(split_row(candidate))
                index += 1
            add_table(document, rows)
            stats["tables"] += 1
            continue

        # --- headings ---------------------------------------------------
        heading = re.match(r"^(#{1,6})\s+(.*)$", stripped)
        if heading:
            content = heading.group(2).strip()

            # The markdown reserves "#" for the document title, so a "##"
            # section becomes Heading 1 — otherwise the contents would start a
            # level too deep and every section would read as a subsection.
            level = max(1, min(len(heading.group(1)) - 1, 4))

            paragraph = document.add_heading(level=level)
            write_inline(paragraph, content)
            stats["headings"] += 1
            index += 1
            continue

        # --- horizontal rule --------------------------------------------
        if stripped in {"---", "***", "___"}:
            rule = document.add_paragraph()
            rule.paragraph_format.space_before = Pt(2)
            rule.paragraph_format.space_after = Pt(10)
            borders = OxmlElement("w:pBdr")
            bottom = OxmlElement("w:bottom")
            bottom.set(qn("w:val"), "single")
            bottom.set(qn("w:sz"), "6")
            bottom.set(qn("w:color"), "D8DCE3")
            borders.append(bottom)
            rule._p.get_or_add_pPr().append(borders)
            index += 1
            continue

        # --- block quote ------------------------------------------------
        if stripped.startswith(">"):
            quoted: list[str] = []
            while index < total and lines[index].strip().startswith(">"):
                quoted.append(lines[index].strip().lstrip(">").strip())
                index += 1
            paragraph = document.add_paragraph()
            paragraph.paragraph_format.left_indent = Inches(0.3)
            paragraph.paragraph_format.space_before = Pt(6)
            write_inline(paragraph, " ".join(q for q in quoted if q))
            for run in paragraph.runs:
                run.italic = True
                run.font.color.rgb = INK_SOFT
            continue

        # --- lists ------------------------------------------------------
        bullet = re.match(r"^[-*]\s+(.*)$", stripped)
        numbered = re.match(r"^\d+\.\s+(.*)$", stripped)
        if bullet or numbered:
            style = "List Bullet" if bullet else "List Number"
            while index < total:
                current = lines[index].strip()
                item = re.match(r"^[-*]\s+(.*)$", current) or re.match(r"^\d+\.\s+(.*)$", current)
                if not item:
                    break
                paragraph = document.add_paragraph(style=style)
                paragraph.paragraph_format.space_after = Pt(3)
                write_inline(paragraph, item.group(1))
                index += 1
            continue

        # --- blank ------------------------------------------------------
        if stripped == "":
            index += 1
            continue

        # --- paragraph (joining soft-wrapped lines) ---------------------
        buffer: list[str] = []
        while index < total:
            current = lines[index]
            candidate = current.strip()
            if (
                candidate == ""
                or candidate.startswith(("|", ">", "#", "```"))
                or candidate in {"---", "***", "___"}
                or re.match(r"^[-*]\s+", candidate)
                or re.match(r"^\d+\.\s+", candidate)
            ):
                break
            buffer.append(candidate)
            index += 1

        if buffer:
            paragraph = document.add_paragraph()
            write_inline(paragraph, " ".join(buffer))
            stats["paragraphs"] += 1

    target.parent.mkdir(parents=True, exist_ok=True)
    document.save(target)

    return stats


def main() -> int:
    root = Path(__file__).resolve().parent.parent
    source = Path(sys.argv[1]) if len(sys.argv) > 1 else root / "docs" / "REPORT.md"
    target = Path(sys.argv[2]) if len(sys.argv) > 2 else root / "docs" / "Keeply-Submission-Report.docx"

    if not source.is_file():
        print(f"Cannot read {source}", file=sys.stderr)
        return 1

    stats = convert(source, target)

    print(f"Wrote {target}")
    print(f"  {target.stat().st_size:,} bytes")
    for key, value in stats.items():
        print(f"  {key:<12} {value}")
    print()
    print("In Word: right-click the Contents placeholder and choose Update Field.")

    if stats["diagrams_pending"]:
        print(
            f"{stats['diagrams_pending']} diagram(s) are still source placeholders — "
            "run tools/render-diagrams.py."
        )
    else:
        print(f"All {stats['diagrams_embedded']} diagram(s) embedded as images.")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

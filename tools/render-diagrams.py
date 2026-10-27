#!/usr/bin/env python3
"""
Keeply — extract the report's Mermaid diagrams and render them to PNG.

    python tools/render-diagrams.py
    python tools/render-diagrams.py --extract-only

Writes docs/diagrams/diagram-N.mmd for each ```mermaid block in the report, then
renders each one with mermaid-cli (mmdc). report-to-docx.py embeds any PNG it
finds here and falls back to a source placeholder for any it does not, so the
Word document builds whether or not this script has been run.

Rendering is local: mermaid-cli drives a bundled headless Chromium on this
machine, so no diagram source leaves the host.

Requires mermaid-cli on PATH:
    npm install -g @mermaid-js/mermaid-cli@<version>
"""

from __future__ import annotations

import re
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
REPORT = ROOT / "docs" / "REPORT.md"
OUTPUT = ROOT / "docs" / "diagrams"

# A wide canvas at 2x scale keeps the text crisp when Word scales the image to
# the page width.
WIDTH = "1400"
SCALE = "2"


def extract(report: Path, destination: Path) -> list[Path]:
    text = report.read_text(encoding="utf-8")
    blocks = re.findall(r"```mermaid\r?\n(.*?)```", text, re.DOTALL)

    destination.mkdir(parents=True, exist_ok=True)
    written: list[Path] = []

    for number, source in enumerate(blocks, start=1):
        path = destination / f"diagram-{number}.mmd"
        path.write_text(source.rstrip() + "\n", encoding="utf-8")
        written.append(path)

    return written


def find_mmdc() -> list[str] | None:
    """Locate mermaid-cli, preferring a real binary over a shell shim."""
    for candidate in ("mmdc.cmd", "mmdc"):
        found = shutil.which(candidate)
        if found:
            return [found]
    return None


def render(source: Path, target: Path, command: list[str]) -> tuple[bool, str]:
    arguments = command + [
        "-i", str(source),
        "-o", str(target),
        "-b", "white",
        "-w", WIDTH,
        "-s", SCALE,
    ]

    try:
        completed = subprocess.run(
            arguments,
            capture_output=True,
            text=True,
            timeout=180,
            cwd=str(ROOT),
        )
    except subprocess.TimeoutExpired:
        return False, "timed out after 180s"
    except OSError as error:
        return False, str(error)

    if completed.returncode != 0 or not target.is_file():
        detail = (completed.stderr or completed.stdout or "no output").strip()
        return False, detail.splitlines()[-1] if detail else "unknown failure"

    return True, f"{target.stat().st_size:,} bytes"


def main() -> int:
    if not REPORT.is_file():
        print(f"Cannot read {REPORT}", file=sys.stderr)
        return 1

    sources = extract(REPORT, OUTPUT)
    print(f"Extracted {len(sources)} diagram source(s) to {OUTPUT}")

    for path in sources:
        first = path.read_text(encoding="utf-8").splitlines()[0]
        print(f"  {path.name:<16} {first[:52]}")

    if "--extract-only" in sys.argv:
        print("\nExtract only — nothing was rendered.")
        return 0

    command = find_mmdc()
    if command is None:
        print(
            "\nmermaid-cli (mmdc) is not on PATH. Install it with:\n"
            "  npm install -g @mermaid-js/mermaid-cli@<version>\n"
            "The Word document will use source placeholders until it is available.",
            file=sys.stderr,
        )
        return 2

    print(f"\nRendering with {command[0]}")

    failures = 0
    for path in sources:
        target = path.with_suffix(".png")
        ok, detail = render(path, target, command)
        print(f"  {path.name:<16} {'ok  ' if ok else 'FAIL'} {detail}")
        if not ok:
            failures += 1

    print()
    if failures:
        print(f"{failures} diagram(s) failed to render.")
        return 1

    print(f"All {len(sources)} diagram(s) rendered. Re-run tools/report-to-docx.py to embed them.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

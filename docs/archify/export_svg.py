import re
import sys
from pathlib import Path

here = Path(__file__).parent
name = sys.argv[1] if len(sys.argv) > 1 else "architecture"
theme = sys.argv[2] if len(sys.argv) > 2 else "light"
html = (here / f"{name}.html").read_text(encoding="utf-8")

start = html.index("<svg viewBox")
end = html.index("</svg>", start) + len("</svg>")
svg = html[start:end]

page_css = "\n".join(re.findall(r"(?s)<style[^>]*>(.*?)</style>", html))
page_css = re.sub(r"(?s)/\*.*?\*/", "", page_css)
page_css = re.sub(r"(?s)@font-face\s*\{.*?\}", "", page_css)


def top_level_rules(css):
    rules, depth, buf = [], 0, ""
    for ch in css:
        buf += ch
        if ch == "{":
            depth += 1
        elif ch == "}":
            depth -= 1
            if depth == 0:
                rules.append(buf.strip())
                buf = ""
    return rules


classes = set(" ".join(re.findall(r'class="([^"]*)"', svg)).split())
tags = {"svg", "text", "rect", "path", "line", "circle", "g", "marker", "polygon", "tspan"}
other = "dark" if theme == "light" else "light"

kept = []
variables = {}
for rule in top_level_rules(page_css):
    if rule.startswith("@"):
        continue
    selector, body = rule.split("{", 1)
    selector = selector.strip()
    if "--" in body and re.search(r"\s--[\w-]+\s*:", body):
        plain = re.sub(r"\s+", "", selector)
        if "data-preset" in plain:
            continue
        if plain in (":root,[data-theme=\"dark\"]", ":root") or plain == f'[data-theme="{theme}"]':
            if theme == "light" and plain.startswith(":root"):
                for k, v in re.findall(r"(--[\w-]+)\s*:\s*([^;]+);", body):
                    variables.setdefault(k, v.strip())
            else:
                for k, v in re.findall(r"(--[\w-]+)\s*:\s*([^;]+);", body):
                    variables[k] = v.strip()
            continue
    if f'data-theme="{other}"' in selector or "data-preset=" in selector:
        continue
    used = any(re.search(r"\." + re.escape(c) + r"(?![\w-])", selector) for c in classes)
    used = used or re.search(r"(^|[\s,>])(" + "|".join(tags) + r")([\s,.:\[>{]|$)", selector)
    if used and ("archify" in selector or "svg" in selector or any("." + c in selector for c in classes)):
        kept.append(rule)

root = "svg{" + "".join(f"{k}:{v};" for k, v in variables.items()) + "}"
font = "svg{font-family:ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}"
style = "<style>" + root + font + "\n".join(kept) + "</style>"

svg = svg.replace("<svg ", f'<svg xmlns="http://www.w3.org/2000/svg" data-theme="{theme}" ', 1)
svg = re.sub(r'data-animation="[^"]*"', 'data-animation="none"', svg, count=1)
svg = svg.replace(">", ">" + style, 1)

out = here / f"{name}-{theme}.svg"
out.write_text(svg, encoding="utf-8")
print(out, len(svg), "rules", len(kept), "vars", len(variables))

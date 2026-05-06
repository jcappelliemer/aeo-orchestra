"""
WordPress.org submission assets generator for AEO Orchestra plugin.

Generates 4 PNG files:
  - banner-1544x500.png  (large banner)
  - banner-772x250.png   (small banner / retina)
  - icon-256x256.png     (large icon)
  - icon-128x128.png     (small icon)

Re-run this script to regenerate after edits. No external deps beyond Pillow + DejaVu fonts.
"""
import os
from PIL import Image, ImageDraw, ImageFilter, ImageFont

OUT_DIR = os.path.dirname(os.path.abspath(__file__))

# Brand palette
NAVY = "#0A0E27"
BLUE = "#0055FF"
CYAN = "#00E5FF"
GREEN = "#10B981"
WHITE = "#FFFFFF"
SOFT = "#E0E7FF"

GRADIENT_COLORS = [NAVY, BLUE, CYAN]


def hex_to_rgb(h):
    h = h.lstrip("#")
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def make_diagonal_gradient(width, height, hex_colors):
    """135-degree diagonal multi-stop gradient. Vectorized via row math for speed."""
    img = Image.new("RGB", (width, height))
    px = img.load()
    rgbs = [hex_to_rgb(c) for c in hex_colors]
    n = len(rgbs) - 1
    denom = float(width + height - 2) if (width + height) > 2 else 1.0
    # Pre-compute color stops for every (x+y) sum
    sum_max = width + height - 2
    cache = [None] * (sum_max + 1)
    for s in range(sum_max + 1):
        t = s / denom
        if t <= 0:
            cache[s] = rgbs[0]
            continue
        if t >= 1:
            cache[s] = rgbs[-1]
            continue
        seg_f = t * n
        seg = int(seg_f)
        if seg >= n:
            seg = n - 1
        local = seg_f - seg
        c1, c2 = rgbs[seg], rgbs[seg + 1]
        cache[s] = (
            int(c1[0] + (c2[0] - c1[0]) * local),
            int(c1[1] + (c2[1] - c1[1]) * local),
            int(c1[2] + (c2[2] - c1[2]) * local),
        )
    for y in range(height):
        for x in range(width):
            px[x, y] = cache[x + y]
    return img


def find_font(size, bold=False):
    candidates = []
    if bold:
        candidates += [
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
        ]
    else:
        candidates += [
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
        ]
    for path in candidates:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size)
            except Exception:
                continue
    return ImageFont.load_default()


def draw_text_with_shadow(draw, pos, text, font, fill=WHITE, shadow_offset=2, shadow_alpha=120):
    """Draw text with subtle drop shadow for legibility on gradient."""
    x, y = pos
    # Shadow approximation in RGB (no alpha on RGB image): just draw a darker color slightly offset
    shadow_color = (0, 0, 0)
    for off in range(1, shadow_offset + 1):
        draw.text((x + off, y + off), text, font=font, fill=shadow_color)
    draw.text((x, y), text, font=font, fill=fill)


def draw_wave_decoration(img, cx, cy, max_radius, color_hex=CYAN, rings=4):
    """Draw concentric circle rings (sound wave abstraction) on the right side."""
    overlay = Image.new("RGBA", img.size, (0, 0, 0, 0))
    od = ImageDraw.Draw(overlay)
    base = hex_to_rgb(color_hex)
    for i in range(rings, 0, -1):
        r = int(max_radius * i / rings)
        alpha = int(60 + (rings - i) * 30)
        if alpha > 200:
            alpha = 200
        # Ring outline
        width_line = max(2, int(max_radius / 60))
        od.ellipse(
            [cx - r, cy - r, cx + r, cy + r],
            outline=(base[0], base[1], base[2], alpha),
            width=width_line,
        )
    # Inner solid dot
    dot_r = max(6, int(max_radius / 14))
    od.ellipse(
        [cx - dot_r, cy - dot_r, cx + dot_r, cy + dot_r],
        fill=(255, 255, 255, 230),
    )
    return Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")


def round_corners(img, radius):
    """Apply rounded-corner alpha mask, returns RGBA."""
    img = img.convert("RGBA")
    w, h = img.size
    mask = Image.new("L", (w, h), 0)
    md = ImageDraw.Draw(mask)
    md.rounded_rectangle([0, 0, w, h], radius=radius, fill=255)
    out = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    out.paste(img, (0, 0), mask)
    return out


# ──────────────────────────────────────────────────────────────
# Banner generation (shared layout)
# ──────────────────────────────────────────────────────────────
def make_banner(width, height, out_path,
                logo_size, tag_size, sub_size,
                pad_left, logo_y_ratio, line_gap):
    img = make_diagonal_gradient(width, height, GRADIENT_COLORS)
    # Decorative wave on the right side (sound waves / orchestra metaphor)
    wave_cx = int(width * 0.85)
    wave_cy = int(height * 0.5)
    wave_r = int(height * 0.42)
    img = draw_wave_decoration(img, wave_cx, wave_cy, wave_r, color_hex=CYAN, rings=5)

    draw = ImageDraw.Draw(img)
    font_logo = find_font(logo_size, bold=True)
    font_aeo_big = find_font(int(logo_size * 1.05), bold=True)
    font_tag = find_font(tag_size, bold=True)
    font_sub = find_font(sub_size, bold=False)

    # Logo: "AEO" highlight (cyan-ish via white) + " ORCHESTRA"
    # Render in two parts so we can color "AEO" differently.
    aeo_text = "AEO"
    rest_text = " ORCHESTRA"

    # Compute widths
    aeo_w = draw.textlength(aeo_text, font=font_aeo_big)
    rest_w = draw.textlength(rest_text, font=font_logo)

    logo_y = int(height * logo_y_ratio)
    x = pad_left
    # AEO in cyan accent
    draw_text_with_shadow(draw, (x, logo_y - int(logo_size * 0.05)), aeo_text,
                          font_aeo_big, fill=CYAN, shadow_offset=2)
    # ORCHESTRA in white
    draw_text_with_shadow(draw, (x + int(aeo_w), logo_y), rest_text,
                          font_logo, fill=WHITE, shadow_offset=2)

    # Tagline
    tag_y = logo_y + logo_size + line_gap
    draw_text_with_shadow(draw, (pad_left, tag_y),
                          "AI-powered SEO + Money-back guarantee",
                          font_tag, fill=WHITE, shadow_offset=1)

    # Sub-tagline
    sub_y = tag_y + tag_size + int(line_gap * 0.6)
    draw_text_with_shadow(draw, (pad_left, sub_y),
                          "Switch da Yoast in 3 click  -  Analytics, Calendar, Image SEO  -  14 giorni gratis",
                          font_sub, fill=SOFT, shadow_offset=1)

    img.save(out_path, "PNG", optimize=True)
    return out_path


# ──────────────────────────────────────────────────────────────
# Icon generation
# ──────────────────────────────────────────────────────────────
def make_icon(size, out_path, corner_radius, mono_size_ratio=0.55):
    img = make_diagonal_gradient(size, size, GRADIENT_COLORS)

    # Decorative concentric rings centered behind the monogram
    img = draw_wave_decoration(
        img,
        cx=int(size * 0.5),
        cy=int(size * 0.5),
        max_radius=int(size * 0.42),
        color_hex=CYAN,
        rings=3,
    )
    draw = ImageDraw.Draw(img)

    # Monogram "AO" centered
    mono = "AO"
    font_size = int(size * mono_size_ratio)
    font = find_font(font_size, bold=True)

    # Compute text bbox for centering
    bbox = draw.textbbox((0, 0), mono, font=font)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    tx = (size - tw) // 2 - bbox[0]
    ty = (size - th) // 2 - bbox[1]

    # Drop shadow
    shadow_off = max(2, size // 100)
    draw.text((tx + shadow_off, ty + shadow_off), mono, font=font, fill=(0, 0, 0))
    draw.text((tx, ty), mono, font=font, fill=WHITE)

    # Rounded corners (RGBA output, but WP.org accepts both; flatten on opaque white BG fallback)
    rounded = round_corners(img, corner_radius)
    # Save with alpha (PNG supports it; WP.org renders rounded mask itself, but ours works too)
    rounded.save(out_path, "PNG", optimize=True)
    return out_path


def main():
    os.makedirs(OUT_DIR, exist_ok=True)

    results = []

    # Banner large 1544x500
    p1 = os.path.join(OUT_DIR, "banner-1544x500.png")
    make_banner(
        1544, 500, p1,
        logo_size=110, tag_size=42, sub_size=24,
        pad_left=80, logo_y_ratio=0.22, line_gap=28,
    )
    results.append(p1)

    # Banner small 772x250
    p2 = os.path.join(OUT_DIR, "banner-772x250.png")
    make_banner(
        772, 250, p2,
        logo_size=56, tag_size=22, sub_size=13,
        pad_left=40, logo_y_ratio=0.22, line_gap=14,
    )
    results.append(p2)

    # Icon 256
    p3 = os.path.join(OUT_DIR, "icon-256x256.png")
    make_icon(256, p3, corner_radius=24)
    results.append(p3)

    # Icon 128
    p4 = os.path.join(OUT_DIR, "icon-128x128.png")
    make_icon(128, p4, corner_radius=12)
    results.append(p4)

    # Report
    for p in results:
        with Image.open(p) as im:
            sz = os.path.getsize(p)
            print(f"OK  {os.path.basename(p):26s}  {im.size[0]}x{im.size[1]}  {sz/1024:.1f} KB  mode={im.mode}")


if __name__ == "__main__":
    main()

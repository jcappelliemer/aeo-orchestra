# WordPress.org submission assets

This folder contains the four PNG assets required by the WordPress.org plugin directory, plus the Python script used to (re)generate them from the brand identity.

## Files

| File | Dimensions | Mode | Notes |
|---|---|---|---|
| `banner-1544x500.png` | 1544 x 500 | RGB (opaque) | Large banner shown on plugin listing page |
| `banner-772x250.png` | 772 x 250 | RGB (opaque) | Small banner / retina fallback |
| `icon-256x256.png` | 256 x 256 | RGBA (rounded corners) | Icon shown in directory grid (large) |
| `icon-128x128.png` | 128 x 128 | RGBA (rounded corners) | Icon shown in directory grid (small) |

The four PNGs were generated programmatically with Pillow using the Orchestra brand identity:

* Diagonal gradient `#0A0E27` -> `#0055FF` -> `#00E5FF` (135 degrees)
* Wordmark "AEO ORCHESTRA" with "AEO" in cyan accent and "ORCHESTRA" in white (DejaVu Sans Bold)
* Concentric rings (sound-wave / orchestra metaphor) on the right side
* "AO" monogram centered for the icons, with rounded corners (radius 24 / 12)

## Regeneration

If you tweak colors / typography / wording, edit `generate_assets.py` in this folder and re-run:

```bash
cd /opt/seo-orchestra/backend/plugin/seo-aeo-orchestra/assets/wp-org/
python3 generate_assets.py
```

The script overwrites the four PNGs in place. It depends only on Pillow and DejaVu fonts, both already present on the host. If you want a different typeface (Inter, Geist, SF Pro), drop the `.ttf` next to the script and update the `find_font()` candidate list.

## How WordPress.org finds these files

The WordPress.org plugin SVN repo expects these files in an `assets/` directory at the **repo root** (NOT inside the plugin code itself). When preparing the final submission, the SVN layout is:

```
trunk/                    <- the plugin code (this folder, minus assets/wp-org/)
tags/3.35.9/              <- tagged release
assets/                   <- repo-level: the 4 PNGs from here + screenshot-1..5.png
```

The PNGs in this `wp-org/` directory must be **copied** (not included in the plugin ZIP) into the SVN `assets/` folder at submission time.

## Screenshots (also required)

The `readme.txt` references 5 screenshots:

1. Analytics dashboard with the 5 proprietary KPIs
2. Content Calendar AI bulk wizard for 30 articles
3. Image SEO Manager with AI Vision pass
4. Article preview with money-back refund button
5. Migration Wizard 6 guided steps

Capture at 1280x800 minimum from the Codex VPS test site, save as `screenshot-1.png` through `screenshot-5.png`, and place them in the SVN `assets/` folder alongside the banners and icons.

# AEO Orchestra — Free version

> **AI specialists, perfectly orchestrated.**
> One conductor. Many AI specialists. One symphony of search visibility.

**Latest release**: [v3.35.44](https://github.com/jcappelliemer/aeo-orchestra/releases/tag/v3.35.44) — see [CHANGELOG](https://wordpress.org/plugins/aeo-orchestra/#developers) on WordPress.org.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org/)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

**The first WordPress plugin that combines classic SEO with AEO (Answer Engine Optimization)** — the practice of optimizing content to be cited by AI answer engines like ChatGPT, Claude, Gemini and Perplexity.

This repository hosts the source code of the **free version** distributed via [WordPress.org](https://wordpress.org/plugins/aeo-orchestra/).
The **Pro version** (with AI generation, Brand Voice Learning, Auto-Pilot Scheduler, Cannibalization detector, GSC Analytics) is distributed independently from [aeo-orchestra.com](https://aeo-orchestra.com).

---

## What's inside (Free version)

The free version is **fully self-contained**: zero external API calls, zero tracking, zero account required. It runs entirely on your WordPress installation.

- **Native `/llms.txt` and `/llms-full.txt`** — the LLM-readable standard. The only mainstream WordPress plugin that ships this natively.
- **Native SEO `<head>` output** — title, meta description, OpenGraph, Twitter Cards, canonical, robots. Replaces Yoast/RankMath/AIOSEO output.
- **Sitemap.xml automatic** — sub-sitemaps per post type, taxonomy, author archive. Cached + auto-invalidated on save/delete.
- **Schema.org JSON-LD `@graph`** — Organization, WebSite, WebPage, Article, BreadcrumbList, ProfilePage, ImageObject.
- **Redirect Manager** — 301/302/307/308 + regex backreferences + 404 log + `phpcs:disable` audit trail.
- **Migration Wizard** — 6-step from Yoast Premium / RankMath / AIOSEO. Backups + shadow-copy meta.

## What's NOT in this repo (Pro-only)

- AI content generation (article, meta tags, image SEO)
- Brand Voice Learning (auto-extracts tone profile from existing posts)
- Keyword Research Autopilot
- Auto-Pilot Scheduler (cron AI articles + media + meta)
- Cannibalization detector + AI fix proposals
- AI 404 Suggester
- GSC Analytics dashboard with Brand Voice impact tracking
- Editorial Calendar
- Image SEO bulk
- Full white-label

The Pro features require a SaaS backend and are physically removed from this open-source repo. To upgrade, install the Pro plugin separately from [aeo-orchestra.com](https://aeo-orchestra.com).

## Installation

### From WordPress.org (recommended)
WP Admin → Plugins → Add New → search **"AEO Orchestra"** → Install → Activate.

### From this repo (developers)
```bash
cd wp-content/plugins/
git clone https://github.com/jcappelliemer/aeo-orchestra.git
```
Then activate via WP Admin → Plugins.

## Coexistence with Pro version

If you already have **AEO Orchestra Pro** installed (folder `wp-content/plugins/seo-aeo-orchestra/`), the Free version detects it on activation and gracefully steps aside. You don't need both.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Bug reports and PRs welcome — for the free version only. Pro-feature requests go to support@aeo-orchestra.com.

## License

[GNU General Public License v2 or later](LICENSE).

Copyright © 2026 Solaris Code SL · [aeo-orchestra.com](https://aeo-orchestra.com)

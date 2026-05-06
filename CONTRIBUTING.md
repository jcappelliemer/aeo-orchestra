# Contributing to AEO Orchestra (Free)

This is the source code of the **free version** of AEO Orchestra distributed via [WordPress.org](https://wordpress.org/plugins/aeo-orchestra/). The Pro version is closed-source and distributed separately from [aeo-orchestra.com](https://aeo-orchestra.com).

## Repository scope

This repo contains **only** the free-tier code that runs entirely on the user's WordPress installation:

| File | Responsibility |
|---|---|
| `seo-aeo-orchestra.php` | Bootstrap, includes, init, coexistence guard with Pro version |
| `includes/class-admin-ui.php` | Admin menu + submenus + page rendering |
| `includes/class-ajax-handlers.php` | `wp_ajax_*` endpoints for free features |
| `includes/class-widget.php` | Front-end score badge widget |
| `includes/class-history.php` | Per-post change history index |
| `includes/class-snapshot-manager.php` | Compressed snapshot create/restore (UTF-8 self-heal) |
| `includes/class-seo-engine-bridge.php` | Detect + read/write Yoast / RankMath / AIOSEO meta keys |
| `includes/class-output-renderer.php` | Native `<head>` output (title, meta, OpenGraph, Twitter, canonical) |
| `includes/class-sitemap.php` | Dynamic sitemap.xml + sub-sitemaps + auto cache invalidation |
| `includes/class-llms-txt.php` | Native `/llms.txt` and `/llms-full.txt` generator |
| `includes/class-schema.php` | JSON-LD `@graph` builder (Org, WebSite, WebPage, Article, Breadcrumb) |
| `includes/class-redirect-manager.php` | DB-backed redirects + 404 log + regex |
| `includes/class-migration-wizard.php` | 6-step Yoast/RankMath/AIOSEO migration |
| `includes/class-translator.php` | Localization helpers |

Pro-only classes (`class-api-client.php`, `class-ai-helpers.php`, `class-brand-voice.php`, `class-autopilot.php`, `class-credits-bar.php`, `class-image-seo.php`, `class-calendar.php`, `class-usage-tracker.php`, `class-updater.php`) are **physically absent** from this repository and from the WP.org ZIP.

## Coding conventions

- WordPress coding standard
- All output must be escaped: `esc_html`, `esc_attr`, `esc_url`
- All input must be sanitized: `sanitize_text_field( wp_unslash( $_POST['x'] ) )`
- All AJAX handlers must check capability + nonce
- All DB queries must use `$wpdb->prepare()` for variable parameters
- Text-domain `seo-aeo-orchestra` for all `__()` / `_e()` / `esc_html__()` calls

## Pull requests

PRs are welcome for:
- Bug fixes
- Compatibility with new WordPress versions
- Performance improvements
- Translations (PO/MO files)
- Documentation

PRs that introduce **new features** to the Free version need prior discussion in an issue, since the Free/Pro split is intentional.

## Reporting bugs

Open an issue with:
- WP version + PHP version
- Other SEO plugins active (Yoast, RankMath, AIOSEO)
- Steps to reproduce
- Expected vs actual behavior
- Browser console + `debug.log` excerpt if relevant

## Pro feature requests

Email `support@aeo-orchestra.com`. The Pro plugin is closed-source and lives outside this repo.

## License

GNU GPL v2 or later. By contributing you agree your changes are licensed under the same terms.

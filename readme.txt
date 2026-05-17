=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.42.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SEO + AEO for WordPress: get cited by ChatGPT, Claude, Perplexity. Native head, sitemap, llms.txt, schema, redirect manager, Yoast migration.

== Description ==

AEO Orchestra is a WordPress plugin for SEO and AEO (Answer Engine Optimization) — optimizing content to be cited by AI answer engines such as ChatGPT, Claude and Perplexity.

The plugin generates the `<head>` of your pages (title, meta description, OpenGraph, Twitter Cards, canonical, robots), an XML sitemap index with sub-sitemaps per post type and taxonomy, structured JSON-LD `@graph` data, an `llms.txt` and `llms-full.txt` file, and a Redirect Manager with regex support and a 404 log. A 6-step Migration Wizard imports settings and redirects from existing SEO plugins without modifying the originals.

= Features =

* `<head>` output: title, meta description, OpenGraph, Twitter Cards, canonical, robots, article published_time / modified_time.
* Sitemap.xml index plus sub-sitemaps for every public post type, taxonomy and author archive.
* `llms.txt` and `llms-full.txt` generation (cached 6 hours, invalidated on save/delete).
* Linked Schema.org JSON-LD `@graph` covering Organization, WebSite, WebPage, Article, BreadcrumbList and ProfilePage.
* Redirect Manager with dedicated DB table, regex support, hit counter and 404 log.
* 6-step Migration Wizard with downloadable JSON backup, importing meta and redirects (including from Yoast Premium and the Redirection plugin).
* Optional Override Mode that silences a competing SEO plugin's `<head>` output without uninstalling it.
* Auto-detection of other SEO plugins to avoid duplicate output.

= Optional connected service =

Some advanced flows — AI content generation, Brand Voice profiling, Auto-Pilot scheduling, Keyword Research, GSC Analytics — are powered by the optional AEO Orchestra service at [aeo-orchestra.com](https://aeo-orchestra.com). Activating these flows requires a paid plan and a license key entered in the plugin settings; the service has its own [Terms](https://aeo-orchestra.com/terms) and [Privacy Policy](https://aeo-orchestra.com/privacy). The plugin works fully without the service for all the features listed above.

= Privacy =

The features listed under "Features" run locally on your WordPress installation. They do not call external services. Service-backed flows only run when you explicitly invoke them after entering a license key.

Source code: https://github.com/jcappelliemer/aeo-orchestra

== Installation ==

= From WordPress Admin =

1. Go to **Plugins → Add New**
2. Search for "AEO Orchestra"
3. Click "Install Now" then "Activate"

= From ZIP file =

1. Download the ZIP file
2. WordPress Admin → **Plugins → Add New → Upload Plugin**
3. Select the ZIP → "Install Now" → "Activate"

= Setup after activation =

1. Go to **AEO Orchestra → Dashboard** in the sidebar
2. Click "Migration Wizard" if you want to migrate from Yoast/RankMath/AIOSEO
3. Click "Native SEO Output" to enable the native stack (Output Renderer, Sitemap, llms.txt, Schema)
4. Click "Redirect Manager" to manage 301/302 redirects

== Frequently Asked Questions ==

= Is the plugin free to use? =

Yes. All features documented in the Description above run locally and do not require a license key or external service. AI generation, Brand Voice and Auto-Pilot are powered by the optional AEO Orchestra service and require a paid plan.

= What is AEO (Answer Engine Optimization)? =

AEO refers to optimizing content so AI answer engines such as ChatGPT, Claude and Perplexity can cite it accurately. The plugin's contribution to AEO is generating an `llms.txt` index, structured schema, and a clean `<head>` so an AI crawler can quickly understand the site.

= Does it work alongside Yoast / RankMath / AIOSEO? =

Yes. Auto-detection finds an active SEO plugin and disables the native `<head>` output by default to avoid duplicate tags. Override Mode (off by default) silences the other plugin's `<head>` output if you want to switch over without uninstalling it.

= Can I migrate from Yoast Premium? =

Yes. The Migration Wizard imports meta title, meta description and focus keyword as a shadow copy (Yoast data stays intact until you uninstall it), plus Yoast Premium redirects (`wpseo-premium-redirects-base` option) and Redirection plugin redirects (`wp_redirection_items` table).

= What is llms.txt? =

`llms.txt` is a proposal from [llmstxt.org](https://llmstxt.org) (Jeremy Howard, 2024) for declaring which pages an AI model should read. It is the conceptual equivalent of `robots.txt` for large language models.

= Does the plugin call external services? =

The features described under Description run locally on your WordPress installation. The optional AEO Orchestra service is only contacted when you enter a license key and invoke a service-backed flow (AI generation, Brand Voice, Auto-Pilot, Keyword Research, GSC Analytics). The service is documented at [aeo-orchestra.com](https://aeo-orchestra.com) with its own [Terms](https://aeo-orchestra.com/terms) and [Privacy Policy](https://aeo-orchestra.com/privacy).

= Does it support WordPress multisite? =

Not yet. Single-site only.

= How many AI agents and integrated tools does the plugin include? =

The connected service exposes 13 specialized AI agents coordinated by a central orchestrator, plus 22 integrated tools covering analysis, generation, optimization, and migration. The plugin itself ships every entry point; service-backed tools require a license key.

= How do I report bugs or request features? =

Open a ticket on the [WordPress.org support forum](https://wordpress.org/support/plugin/aeo-orchestra/) or contact us via [aeo-orchestra.com](https://aeo-orchestra.com).

== Screenshots ==

1. Main dashboard with feature grid and native stack status
2. Migration Wizard 6-step from Yoast/RankMath/AIOSEO
3. Native SEO Output: toggles for `<head>`, sitemap, llms.txt, schema
4. Redirect Manager with redirect table, regex support, 404 log
5. Service plans: tier comparison for AI generation, Brand Voice and analytics

== Changelog ==

= 3.42.4 (stable) =
* P0 wallet anomaly RESOLVED. Cache preview funziona end-to-end via HTTP admin-ajax. Clic ripetuti su "Mostra modifiche" della stessa card NON addebitano piu crediti.
* Cache layer Polylang-resilient: invalidation rispetta solo modifiche significative (post update, meta keys tracked). Plugin terze parti (Polylang, Yoast, analytics) non triggherano invalidation spuria.
* Architectural: $will_cache flag simplified (v3.42.3.5) + $post_id handler accept both nested + top-level shapes (v3.42.3.6) + allow-list cache architecture (v3.42.3.3).

= 3.42.0-3.42.2 =
* Strategy A architectural lock COMPLETE. Single dispatcher class (M2) + 66 PHPUnit fixtures (M3) + tier dot Prossimi passi (M4) make the preview/execute divergence bug class structurally impossible.
* Frontend gap closure: analysis_summary contract field + pricing_breakdown enrichment on action cards + modern execute surgical path snapshots with agent meta.
* UX patch: Modifiche recenti label by agent + tier+pricing coherence (recurring 3x = architectural single-source-of-truth) + summary banner separated from action list.

= 3.41.0-3.41.9 =
* Surgical editors batch (Elementor/Divi/WPBakery/Beaver/Bricks/Oxygen + Headless WPGraphQL + REST).
* Dedicated "Rigenera intera pagina" flow with typed_confirm triple-gate + double pre-write backup.
* Tier classification SAFE/CAUTION/DANGER with 403 guard for content_generator outside dedicated flow.
* M1 Surgical Editor dry_run primitive (foundation for M2 dispatch unification).

= 3.40.0-3.40.14 =
* Capability matrix + lightweight builder detection + manual-mode UX in preview modal.
* Surgical text-edit dispatch for REWRITE_INTRO/ADD_FAQ_SECTION/OPTIMIZE_FEATURED_SNIPPET.
* Per-action behavior switch in ajax_execute_action.
* AEO silent-fallback UI banner + refund + retry log + per-tier track.

= 3.39.0-3.39.9 =
* One AI-detected issue = one structured action. Backend ai.py prompts with anti-hallucination rules.
* Pydantic schemas + Gemini JSON mode + retry hardening (5 attempts).
* Action preview modal: review before apply with diff display.

For older changelog entries (3.38.x and earlier), see the project repository at https://github.com/jcappelliemer/aeo-orchestra .


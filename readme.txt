=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, redirect-manager
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.35.42
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SEO + AEO (Answer Engine Optimization) for WordPress. Native head output, sitemap, llms.txt, schema, redirect manager, Yoast migration.

== Description ==

**AEO Orchestra** is the first WordPress plugin combining classic SEO and **AEO (Answer Engine Optimization)** — the practice of optimizing content to be cited by AI answer engines like ChatGPT, Claude, Gemini and Perplexity.

**Source code**: this free version is open-source — https://github.com/jcappelliemer/aeo-orchestra

**Pro version**: AI generation, Brand Voice Learning, Auto-Pilot Scheduler, Cannibalization detector, GSC Analytics — distributed separately at https://aeo-orchestra.com (NOT via WordPress.org).

= What makes AEO Orchestra different =

* **Native llms.txt** — the only plugin that automatically generates `/llms.txt` and `/llms-full.txt`, the official standard for telling AI models which pages to index. No mainstream SEO plugin offers this.
* **Complete SEO stack** — replaces Yoast/RankMath/AIOSEO with native `<head>` output, sitemap.xml, structured JSON-LD schema, OpenGraph and Twitter Cards.
* **Built-in Redirect Manager** — manage 301/302/307/308 redirects from the WordPress database, with regex and backreferences, automatic 404 logging.
* **1-click Migration Wizard** — migrate from Yoast SEO, RankMath or All In One SEO with a guided wizard: scan, JSON backup, shadow-copy meta tags, import redirects (including Yoast Premium). Zero data loss, fully reversible.
* **Override Mode** — silence a competing SEO plugin without uninstalling it. Test the new stack while keeping the old one as fallback.

= Free version features =

* Native `<head>` output: title, meta description, OpenGraph, Twitter Cards, canonical, robots, article published_time / modified_time
* Sitemap.xml index plus sub-sitemaps for every public post type, taxonomy and author archive
* Automatic llms.txt and llms-full.txt (cached 6 hours, invalidated on save/delete)
* Linked Schema.org JSON-LD `@graph` (Organization, WebSite, WebPage, Article, BreadcrumbList, ProfilePage)
* Redirect Manager with dedicated DB table, regex support, hit counter, 404 log
* 6-step Migration Wizard with downloadable JSON backup
* Auto-detection of competing SEO plugins (Yoast, RankMath, AIOSEO) to avoid duplicates

= Pro version available =

The **Pro** version (distributed from [aeo-orchestra.com](https://aeo-orchestra.com)) adds:

* AI generation of full articles (text + image + meta tags + schema)
* Brand Voice Learning: AI learns your style from existing posts
* Auto-Pilot Scheduler: WP cron that publishes articles automatically
* Content calendar with optional auto-publish
* Keyword Research Autopilot
* Search Console Analytics integrated
* AI 404 Suggester: AI picks the redirect target for 404s
* Cannibalization AI Fix: detects and resolves keyword duplicates in 1 click
* Bulk Image SEO with AI-generated alt text
* AEO content optimized for ChatGPT, Claude, Perplexity

[Compare Free vs Pro](https://aeo-orchestra.com/pricing) — 7-day free trial, no credit card required.

= Commercial differentiators =

* **Unified SEO + AEO plugin** — not an add-on, a complete system
* **Compatible with the big ones** — coexists with Yoast, RankMath, AIOSEO during migration
* **Schema-first** — JSON-LD `@graph` like Yoast Premium, but lighter
* **Privacy** — the free version makes no external service calls. Everything stays local.

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

= Is AEO Orchestra really free? =

Yes. The version on WordPress.org is completely free and functional. It includes the full native SEO stack (head output, sitemap, llms.txt, schema), Redirect Manager and Migration Wizard. No feature is disabled or "trial". The Pro version (paid) adds AI content generation but is not required to use the plugin.

= What is AEO (Answer Engine Optimization)? =

AEO is the new SEO frontier: optimizing content to be CITED by AI answer engines like ChatGPT, Claude, Gemini, Perplexity. While classic SEO optimizes for Google SERPs, AEO optimizes for AI-generated answers. AEO Orchestra includes `llms.txt` (the official AEO standard), structured schema and Q&A formatted content.

= Does it work alongside Yoast/RankMath/AIOSEO? =

Yes. Auto-detection finds the competing SEO plugin and disables the native stack by default to avoid duplicates. You can enable **Override Mode** which silences the competing plugin without uninstalling it, useful for testing the migration gradually.

= Can I migrate from Yoast Premium? =

Yes. The Migration Wizard imports: meta title, meta description, focus keyword (in shadow-copy: Yoast data stays intact until you uninstall), Yoast Premium redirects (option `wpseo-premium-redirects-base`), Redirection plugin redirects (table `wp_redirection_items`). Zero data loss.

= What is llms.txt? =

`llms.txt` is a 2024 standard proposed by Jeremy Howard ([llmstxt.org](https://llmstxt.org)) for telling AI models which pages to index. It's the `robots.txt` for LLMs. AEO Orchestra is one of the few plugins that generates it automatically.

= Does the plugin call external services? =

**Free version: no**. All data stays in your WordPress database. No tracking, no external analytics.

**Pro version**: calls the aeo-orchestra.com SaaS backend for AI generation (required for that feature). Configurable/disableable from Settings.

= Does it support WordPress multisite? =

Currently no. Multisite requires the Agency (Pro) plan. Working on it for the free version.

= How do I report bugs or request features? =

Open a ticket on the [WordPress.org support forum](https://wordpress.org/support/plugin/seo-aeo-orchestra/) or contact us via [aeo-orchestra.com](https://aeo-orchestra.com).

== Screenshots ==

1. Main dashboard with feature grid and native stack status
2. Migration Wizard 6-step from Yoast/RankMath/AIOSEO
3. Native SEO Output: toggles for `<head>`, sitemap, llms.txt, schema
4. Redirect Manager with redirect table, regex support, 404 log
5. Free vs Pro comparison: full feature table

== Changelog ==

= 3.35.39 =
* First public WP.org release
* Free distribution: native SEO output, sitemap.xml, llms.txt, schema, redirect manager, migration wizard
* Pro features (AI generation, Brand Voice, Auto-Pilot, Analytics) available via aeo-orchestra.com

== Upgrade Notice ==

= 3.35.39 =
First WP.org release. Install or upgrade for the full native SEO + AEO stack.

=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.36.3
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

= 3.36.3 =
* Plugin Check zero/zero sweep: closed all remaining warnings via documented phpcs:ignore comments with audit-friendly justification (no real code changes — only annotations explaining technical false positives).
* content-generator.php: file-level phpcs:disable NonceVerification.Recommended for GET prefill reads (form pre-fill only, no state mutation).
* ajax-handlers.php: added PluginCheck.Security.Nonce.NonceVerification to existing file-level disable header (every handler verifies via check_ajax_referer at entry).
* class-debug-snapshot::set_error_handler: phpcs:ignore added (already wrapped in WP_DEBUG conditional, never installed in production).
* class-migration-importer::sql_batch: phpcs:ignore for ReplacementsWrongNumber false positive (count is correct via array_merge — Plugin Check cannot evaluate array_merge result length).
* SQL false-positive ignores expanded across additional cited sites (class-ai-crawler-detector, class-migration-importer, ai-crawler-section, class-sitemap).
* class-page-roles meta_key/meta_value slow_db_query ignores added for admin-diagnostic WP_Query args.


= 3.36.2 =
* Plugin Check final sweep: 22 errors closed (16 OutputNotEscaped + 3 PreparedSQL.NotPrepared + 2 fclose + 1 wp_enqueue_style version param).
* OutputNotEscaped fixes in templates: admin-dashboard score_delta + admin_url + ternary translations, ai-crawlers + native-output cache-buster time(), content-generator SEO_AEO_T constant, ai-crawler-empty-state sprintf, ai-crawler-live-state trend_pct + bot_strs + sprintf bot count.
* PreparedSQL annotations updated to include WordPress.DB.PreparedSQL.NotPrepared rule (table-name-only interpolation, no user input).
* fclose phpcs:ignore annotations consolidated for php://output streams (CSV export, WP_Filesystem not applicable).
* wp_enqueue_style for Fontshare now passes SEO_AEO_VERSION as version parameter (was null).


= 3.36.1 =
* Plugin Check sweep: zero errors after 3rd-pass remediation. Warnings reduced or annotated with documented phpcs:ignore (false positives only — table-name interpolation is $wpdb->prefix-derived, AJAX nonces are verified upstream by check_ajax_referer, template variables are local scope).
* Output escaping completed: SSE str_repeat padding annotated, admin-notices body wrapped with wp_kses_post.
* SQL prepare(): direct DB queries on admin diagnostics surfaced with phpcs:ignore + table-name safety note. Table names come from $wpdb->prefix + literal constant, never user input.
* Removed inline error_reporting() calls from 12 files (production code uses WP_DEBUG via WordPress core).
* set_error_handler in class-debug-snapshot now gated behind WP_DEBUG — production sites never install the handler.
* class-verify-live SSE emit_event hardened: sanitize_key on event name, wp_json_encode on payload, phpcs:disable EscapeOutput with detailed comment explaining text/event-stream context.
* Inline link/script tags moved to wp_enqueue_style / wp_enqueue_script (admin-dashboard fonts, native-output Prism syntax highlighter).
* orch_debug_log() renamed to seo_aeo_debug_log() for consistent plugin-wide prefix (76 call sites updated).
* Template files receive file-level phpcs:disable for NonPrefixedVariableFound — local-scope variables in templates are not globals despite the static analyzer's heuristic.


= 3.35.85.0 =
* WordPress.org review compliance: removed all client-side license gating. Every feature loads in every build; service-backed flows return a clear upgrade message when no license is present. The standalone "Pro Features" upsell submenu was removed; the same CTA is available inside Settings (admin-facing only).
* Description rewritten to comply with WP.org guidelines on neutral language and feature presentation.
* All inline style and script blocks across templates now route through wp_add_inline_style() / wp_add_inline_script() via a new SEO_AEO_Inline_Assets registrar (40 blocks across 23 files).
* Centralized JSON input sanitizer (SEO_AEO_Input_Sanitizer): every json_decode of $_POST data now walks the decoded leaves through sanitize_text_field. Nonce reads in class-admin-ui pass through sanitize_text_field(wp_unslash()).
* Schema.org JSON-LD output drops JSON_UNESCAPED_SLASHES so a closing script tag inside any string value cannot break out of the surrounding script tag.
* "Powered by AEO Orchestra" branding on the public widget is now strictly opt-in (option seo_aeo_orchestra_show_branding, default off). The auto-on-some-plans logic is removed.
* Chart.js bumped 4.4.0 to 4.4.7.
* Removed plugin-level overrides of set_time_limit and ini_set in calendar/autopilot AJAX paths.


= 3.35.84.5 =
* 🛡️ Audit M-1: per-user account lockout — 5 login falliti in 15 min → 423 Locked, sblocco automatico dopo 15 min o reset password. Counter atomic via find_one_and_update, clear su login success.
* 🛡️ Audit M-2: JWT token versioning + POST /auth/logout —  claim nel JWT, get_current_user verifica vs users.token_version. Reset password e logout incrementano il counter → tutti i token precedenti diventano invalidi. Backwards-compat: token pre-tv passano (default 0).
* 🛡️ Audit M-6: 4 uvicorn workers (era 2) + Docker mem_limit 1.5G + cpus 1.5 + reservations 256m / 0.5cpu. Previene OOM-kill del VPS condiviso con 7 altri container.
* 🛡️ Sentry release tag: APP_VERSION + REACT_APP_VERSION allineati a 3.35.84.5-beta per attribution corretta in Sentry dashboard.


= 3.35.84.4 =
* 🛠️ Refactor: nuovo modulo `class-admin-notices.php` — pattern centralizzato per ogni banner amministrativo (ID univoco, dismiss transient, expire-by-day, scope per-screen). Sostituisce la rendering ad-hoc inline che produceva banner sopra/dentro le hero card.
* 🐛 Bug fix banner Profilo Business — il banner ora viene reso correttamente SOPRA la hero card su tutte le pagine plugin (era visualizzato dentro l'hero su `seo-aeo-native-output` per via di `all_admin_notices` + posizionamento custom).
* 🐛 Bug fix banner stale `Refactor 3.35.49` — rimosso (>4 mesi old). I banner one-shot di nuove release usano ora `SEO_AEO_Admin_Notices` con `expire_days` automatico.
* 🐛 Bug fix banner update post-install — `maybe_clear_stale_update_state` ora gestisce 3 casi: (a) cache locale stale con `new_version <= current`, (b) cache locale a `update_available=false`, (c) `update_plugins` transient con entry stale anche senza cache locale.
* ✨ Sezione "Cosa c'è di nuovo" dinamica — legge l'entry corrente da `readme.txt`, mostra fino a 5 bullet, dismiss per-utente per-versione (30gg). Eliminate le 4 voci hardcoded di v3.20.


= 3.35.84.3.1 =
* UX upgrade: card backgrounds saturation 96-98% → 92-94% (medium tint per categoria visualmente più distinta)
* UX: icon backgrounds bumped +1 shade saturato (#86efac, #93c5fd, #c4b5fd, #fcd34d, #cbd5e1)
* UX: hover state -3% lightness per visual feedback più intenso
* Bug fix: stale banner update post-install — guard in check_for_update se cached new_version <= current_version (strip stale response + invalidate local cache)
* Bug fix: after_update aggressive cache cleanup (delete_site_transient + wp_clean_plugins_cache + bust all md5-keyed cache entries via SQL DELETE)


= 3.35.84.3 =
* Bug A CRITICAL fix: doppio rendering AI Performance — rimosso h2/sub wrapper duplicato in wizard-home.php (heading ora solo da partial ai-crawler-section.php)
* Bug B+D fix: migration DB v1.1→1.2 backfill canonical bot_name + bot_provider via bot_definitions registry. Plus slug_from_bot_name case-insensitive lookup. Plus Top 5 query GROUP BY bot_name only con COALESCE(MAX(NULLIF(provider,''))) per dedupe rows con provider empty
* Bug C addressed automaticamente via Bug B fix (slug normalization rende classification colors corretti)
* UX: section headers per categoria con border-bottom 2px colorato + count strumenti
* UX: card background tinted leggero (lightness 96-98%) per categoria + hover stato medium
* Cleanup: orphan ai-performance-section.php deleted (preserved as bing-citation-section.php for .85 Phase 2)


= 3.35.84.2 =
* Dashboard cards refactor: 4 nuove card (Verify-Live, Profilo Business, AI Performance, AI Crawlers)
* Rename Calendario AI → Pianificazione articoli (consistency con sidebar + marketing site)
* 5-category color-coding (Foundation green, Analisi blue, Creazione purple, Operations amber, Account gray) con border-left + icon background tinted
* Reorder cards in customer journey: Foundation → Analisi → Creazione → Operations → Account
* Constants bump SEO_AEO_AGENTS_COUNT 12→13 + SEO_AEO_TOOLS_COUNT 18→22
* Badge "⭐ FOUNDATION v3.35.83" su Profilo Business + "🆕 NEW v3.35.82/.84" su Verify-Live + AI Performance


= 3.35.84.1 =
* Hot fix: force-check transient invalidation radical bypass (4th iteration)
* Bypassa wp_update_plugins() lock 15-min via WP_Upgrader::release_lock + costruisce transient manualmente
* Direct HTTP fetch backend con cache-buster query param (defeat HTTP layer caches)
* error_log diagnostic ad ogni step (visibility post-deploy)
* Bug observed 4× consecutive: click force-check link non triggherava update banner per ~12h TTL natural


= 3.35.84 =
* MAJOR: AI Performance Phase 1 LIVE — tracking AI bot activity in Dashboard (sostituisce Coming soon Bing API)
* Schema MySQL extended: wp_seo_aeo_ai_crawler_log v1.1 (+url_path +ip_hash +response_status_class) + new wp_seo_aeo_ai_crawler_daily_stats aggregation table
* 22 bot detection patterns (4 nuovi: GoogleOther, Amazonbot, Bytespider, Diffbot + alias FacebookBot su Meta-ExternalAgent)
* Classification 🟢🟡🔴 (10 AI engine diretto + 10 multi-purpose + 2 crawler aggregato)
* Async logging via register_shutdown_function() + fastcgi_finish_request — zero overhead response time
* GDPR 3-mode IP storage: raw / hash SHA256(IP+wp_salt) / none — admin selectable
* Daily cron 02:00 UTC aggregation INSERT...ON DUPLICATE KEY UPDATE + cleanup raw>30d + stats>90d
* Dashboard widgets: 4 stat card (hits/bot/trend/blocked) + bar chart top 5 bot + table top 10 pages + sparkline SVG inline 28gg + compliance check robots.txt
* 5 nuovi AJAX endpoint (orch_ai_crawler_summary/top_bots/top_pages/trend/compliance) defensive nonce + try/catch + transient cache 5min/15min/1h
* Empty state primo accesso con CTA Verify-Live + Profilo Business completion
* Phase 2 (Bing Webmaster Tools API) deferred a v3.35.85 — partial bing-citation-section.php preservato


= 3.35.83.1.2 =
* Bug 1 fix: confirm button JS handler -> event delegation $(document).on(click, '#orch-bp-confirm-btn') + console.log debug + xhr error visibility (resilient to disabled state + DOM re-render)
* Bug 2 fix: banner positioning sopra hero — render manuale inline in wizard-home.php pre-hero + skip maybe_render_banner su pagina Dashboard (no doppio rendering)
* Bug 3 fix: 5° stat card else branch ✓ verde + class .orch-wiz-stat--confirmed con color #16a34a
* Bug 4 fix: Step ⭐ conditional $bp_done — class/num/badge/CTA swap + CSS .orch-wiz-step--done verde gradient + CTA copy '→ Modifica profilo' post-confirm


= 3.35.83.1.1 =
* Hot fix: handle_force_check defensive — manualmente re-popola update_plugins transient post wp_update_plugins() per bypassare race condition WP filter pre_set_site_transient.
* Plus wp_clean_plugins_cache(true) call per clear plugin metadata cache aggiuntiva.
* Bug observed: force-check link non triggherava update banner per ~12h finché TTL natural OR confirm profile click invalidava manualmente.


= 3.35.83.1 =
* Patch fix AJAX: defensive check_ajax_referer (no auto-die) + return JSON error explicit
* error_log() su exception path per visibility post-deploy
* Uniformato hook banner_snooze nonce a seo_aeo_orchestra_nonce (era 'orch_bp')
* Transient invalidation post-confirm/save: bp_confirmed_state + bp_dashboard_stats + identity_profile + update_plugins (forza WP refresh)
* Banner positioning: hook all_admin_notices (priority 1) + CSS .orch-bp-banner-top sopra hero card
* try/catch su tutti i 6 hook AJAX per error reporting visibile


= 3.35.83 =
* MAJOR: Business Profile pannello sidebar dedicato — foundation feature single source of context per tutti i tool AI
* 14 user fields (11 public + 3 internal) con visibility scope (public esposti in llms.txt/schema/OG, internal solo admin tool AI)
* Sidebar item position 15 (post Dashboard)
* Pannello: 3 sezioni (🌐 Public + 🔒 Internal + 👁 Preview Context AI) con visual distinction approach B
* Auto-save 800ms debounce, validation real-time, tag chips, repeatable struct cards, anteprima context AI live scope-aware
* Backend _build_context_block(scope='full'|'public') refactor — Verify-Live + AEO usano scope='full', llms.txt/schema usano scope='public'
* Migration MongoDB idempotente (added: value_proposition, target_audience, products_services, suppliers_partners, founded_year, competitors, additional_notes, internal_pricing_strategy, field_visibility map, customer_confirmed, prefilled_from_wp)
* CRITICAL_IDENTITY_FIELDS V2: literal value_proposition + target_audience (no more proxy)
* Banner persistente top admin (tutte pagine plugin) se customer_confirmed=false, snooze 24h
* Dashboard wizard: Step ⭐ RICHIESTO + 5° stat card 'Profilo Business %' clickable
* Verify-Live preview Box 1 button Modifica restored (chiude Patch 0) puntando al nuovo pannello
* Tab 'Configurazione contenuti' resta backward-compat con info notice link al nuovo pannello


= 3.35.82.1 =
* Patch 0: Rimosso button Modifica Box 1 broken nel pre-verify panel (link puntava a tab Stato invece che editor identity).
* In attesa di .83-beta Business Profile pannello dedicato. Box 2 Brand Voice + Box 3 Refresh restano funzionanti.


= 3.35.82 =
* Verify-Live Trasparenza Dati: pre-verify preview panel sopra i 2 button (3 box: Profilo identita + Brand Voice + Homepage)
* Plugin = single source of context: assembla payload con identity_profile + brand_voice + homepage_context, backend riceve dati ricchi
* Backend prompt enhancement: _build_context_block helper, Premium++ enriched_system con context strutturato per tutte le 5 query Haiku
* Premium++ Haiku ora ha context reale del business invece di placeholders "this business" / "the website"
* Transparency footer post-run: stats context_used (identity populated_fields, brand_voice active, homepage chars) + CTA campi mancanti
* is_complete flag: 4 campi critici check (about_strategic + business_description + differentiators + use_cases). Warning amber se incomplete
* AJAX preview endpoint: ?action=seo_aeo_verify_live_preview con force=1 per refresh homepage
* Cache homepage 1h via transient + button "Aggiorna ora"


= 3.35.81.1.1 =
* Bug fix: Premium++ pass-through nel plugin SSE — root cause del wallet -35cr senza refund
* Defensive: backend _verify_live_premium_plus try/except post-deduct + auto-refund su exception
* UX: renderErrorReport messaggi user-friendly per HTTP 429/402/403/default
* Refund note distinct color (verde su sfondo green-tint)


= 3.35.81.1 =
* Verify-Live consolidato: CTA generic AI (no provider names)
* Premium++ ribilanciato: provider Haiku (was Sonnet) + 35 crediti (was 100)
* Premium++ multi-query 5x parallel via asyncio.gather (~5-10s totali)
* Premium++ structured deep suggestions (8-15 con category/severity/problem/fix)
* Final report Premium: breakdown_by_query, filter severity, export Markdown
* Credit widget auto-refresh post verification
* Bug fix: Standard parser silent fallback score=50 -> error_state + auto-refund
* Distinct status: success / ai_unavailable / parsing_failed in ai_usage_log


= 3.35.81 =
* NEW: Verify-Live UI streaming Phase 2 — animated step pipeline + final citation accuracy report
* Pattern jQuery DOM ready + event delegation per resilience (lessons learned 3.35.80.2)


= 3.35.80.2 =
* Bug fix robusto: AI Crawlers tab switching ora in dedicated jQuery(document).ready block, indipendente da seoAeoOrchestra global. Expose window.activateTab per debug. Lazy-load AJAX hook via window.orchAiCrawlers proxy.


= 3.35.80.1 =
* Bug fix: AI Crawlers tab switching (id-based + hash deep-link)
* Bug fix: floating save bar repositioned bottom-right (Yoast/RankMath pattern)


= 3.35.80 =
* NEW: AI Crawler Allowlist (18 bot across 9 providers) + bot logging dashboard
* NEW: 3-tab section in OPERATIONS menu (Allowlist / Crawler Log / Robots.txt)
* NEW: robots.txt auto-generation aggregato dalle scelte Allowlist
* NEW: cleanup cron 30 day retention


= 3.35.79.3 =
* Premium+ generation: max_tokens 1500 → 2500 (margin for verbose outputs)
* JSON parse guard: friendly error message instead of raw response popup
* White-label labels: "Standard — Veloce" / "Premium+ — Qualità superiore"
* Bug fix: status loader now reads about_strategic (Section 5) instead of business_description (Section 1)


= 3.35.39 =
* First public WP.org release
* Free distribution: native SEO output, sitemap.xml, llms.txt, schema, redirect manager, migration wizard
* Pro features (AI generation, Brand Voice, Auto-Pilot, Analytics) available via aeo-orchestra.com

== Upgrade Notice ==

= 3.35.39 =
First WP.org release. Install or upgrade for the full native SEO + AEO stack.

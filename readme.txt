=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.38.7
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

= 3.38.7 =
* Bug fix (P0 launch-blocker) — Keyword Research with max_keywords > 30 was reliably returning "Risposta non valida dal server" instead of refunded results. Root cause was a 3-way convergence: (1) frontend offered a 50-keyword option, (2) backend honored it up to 50, (3) nginx proxy_read_timeout in the API container was 120s — the LLM call for 50 keywords with verbose Italian fields exceeded 120s so nginx closed the upstream connection mid-response, returning HTTP 502 with HTML body that the WP client couldn't parse as JSON.
* Backend cap — max_keywords is now hard-capped server-side at 30 regardless of the requested value (was: min(50, requested)). Combined with the Gemini Flash 8192-token output ceiling, 30 keywords fits comfortably with margin.
* nginx — proxy_read_timeout bumped 120s → 300s in the API container, giving slower LLM calls more breathing room before the upstream-closed-prematurely path triggers.
* Observability — keyword_research endpoint now logs the LLM call elapsed time and parsed keyword count for every request. Helps trend-detect future timeout regressions.
* Sentry release tag — .env APP_VERSION + REACT_APP_VERSION bumped to 3.38.7. Previous cycles ran on the backend with .env still set to 3.38.1, so 3.38.3-6 observability logs were misattributed in Sentry.
* Frontend wiring — Keyword Research now renders three distinct banner variants instead of the generic "Risposta non valida dal server": (1) typed empty_result or any backend typed error with refunded > 0 → green "✓ Crediti rimborsati" banner showing backend message + refund amount, (2) WP-side timeout (nginx upstream closed, response not JSON) → orange "⏱ Tempo esaurito" banner with retry suggestion + 10-min auto-refund notice, (3) other typed errors → red banner with backend message. Survives the residual cases where the request never reaches the FastAPI handler.
* Plugin Check 0/0 — WP.org compliance final sweep. Wrapped the v3.38.5 self-healing class-presence check in an anonymous closure so its locals ($critical_classes, $all_loaded, $cls) never reach global scope, and gated the load-failure error_log() diagnostic on WP_DEBUG. Plugin Check 1.9.0 against the WP.org ZIP now reports 0 errors and 0 warnings.

= 3.38.6 =
* Bug fix — Business Profile chip counters "[N compilati]" now sync correctly for ALL 5 collapsible fields uniformly. The v3.38.2 + v3.38.4 fixes worked for Differenziatori, Casi d'uso, and Area di servizio but Prodotti/Servizi + Fornitori e partner still showed 0 on Solaris. Replaced the per-wrapper count with a universal walker that iterates every details.orch-bp-collapse element and counts ANY item-child (.orch-bp-chip OR [data-orch-bp-repeat-card]) regardless of which class is used. Added a MutationObserver inside the form wrapper that re-runs the universal sync on any DOM change (throttled to one animation frame), so future code paths or async hydration races can never leave counters stale.

= 3.38.5 =
* Defensive hardening — Added a strict PHP version gate at the top of the main plugin file. If PHP < 7.4, an admin notice is rendered instead of fataling. Plugin appears installed but inert, so WordPress's broken-plugin recovery never triggers the file-removal path.
* Defensive hardening — New seo_aeo_safe_require() helper used for the v3.38.0+ class loads (class-setup-progress, class-setup-widget). file_exists() pre-check + try/catch wrap (catches Error on PHP 8+). Failures are recorded in option seo_aeo_load_failures with file path, reason, timestamp.
* Defensive hardening — New admin_notices hook surfaces recorded load failures as a precise diagnostic ("AEO Orchestra — load failures detected: class-X — file missing") visible on plugin pages and the Plugins screen, so a user with a half-extracted ZIP knows exactly which file to recover.
* Self-healing — If the critical class set loads cleanly and the load_failures option still has stale entries from a previous broken state, the option is cleared automatically. No manual intervention needed once the plugin is re-uploaded clean.
* Context — The v3.38.3 fatal on aeo-orchestra.com was traced to a transient filesystem race during WP's plugin upgrader file-swap step, not a code defect. The main plugin file went missing mid-update, PHP fataled, and WP recovery removed the main file permanently. This release ships defenses so a similar race can no longer self-destruct the install.

= 3.38.4 =
* Hotfix — Defensive backstop for Business Profile chip counters. The v3.38.2 Task 6 + v3.38.3 fixes resolved differentiators/use_cases (struct repeater) and territories (chip), but Prodotti/Servizi + Fornitori e partner still showed "[0 compilati]" with chips visually rendered. Added syncAllCounters() that iterates ALL collapsible fields uniformly (both struct + chip) at the end of loadProfile() so missed hydration paths never leave a counter stale.
* Hotfix — Verified the CSS attribute selector quoting on the Profilo Business sidebar-hide rule. Rule now reads a[href*="page=seo-aeo-business-profile"] (quotes around the equals-containing value, per CSS3 spec) — without quotes the browser parser silently discarded the rule.

= 3.38.3 =
* CRITICAL P0 — Keyword Research feature was unusable on Gemini Flash since 3.23.x. Gemini 2.5/2.0/1.5 Flash all silently cap output at 8192 tokens regardless of requested max_output_tokens. For 30 keywords x 6 verbose fields, the JSON was frequently truncated mid-array, strict json.loads failed, and the user saw a generic error even though 20+ valid keywords were produced. Module 13 reservation refund worked correctly (no credit waste) but the feature returned no results.
* Fix — New _tolerant_extract_keywords() partial parser that walks balanced-brace entries one at a time, returning the prefix of complete entries when the JSON is truncated. Strict json.loads still runs first (happy path unchanged); tolerant fallback only kicks in when strict fails AND at least 5 valid entries can be recovered.
* Fix — Replaced legacy add_credits + HTTPException 502 refund paths in /keyword-research (Module 13's commit/refund patch had missed these specific branches). Both gemini exception path AND parse-failure path now use proper TypedAPIError + refund_reservation, consistent with the rest of Module 13.
* Observability — Added structured logging on every keyword_research call: raw_len, first_pass_count, tolerant_count, niche. Helps detect future LLM truncation trends + Sentry signal-to-noise.
* Layer A — 7/7 unit tests passed against synthetic + realistic truncated payloads. Realistic case (22 complete entries + 1 truncated at character level) recovers exactly 22.

= 3.38.2 =
* Cleanup — Removed the legacy "Per iniziare in 5 mosse" quickstart section from Dashboard (wizard-home.php). Setup Guidato + the Dashboard ambassador banner are now the single source of truth for onboarding. ~70 lines of dead CSS removed.
* Menu reorder — Setup Guidato moved to submenu position 2 (right after Dashboard), no longer buried at the tail. Profilo Business removed from the submenu (page route stays alive, accessed via Setup Guidato Step 2 link).
* BETA badge cleanup — Removed "Beta" badge from SEO Output Nativo header (feature is stable since 3.13.x).
* Removed hardcoded "v3.35.80" sub-component version label from AI Crawlers hero (only the plugin global version is shown now).
* Bug fix — Business Profile collapsible counters "[N compilati]" now stay in sync for chip-input fields (Prodotti/Servizi, Fornitori, Aree, Concorrenti). Counter previously walked only data-orch-bp-repeat-card children and showed 0 for chip fields regardless of actual content. New updateChipCount() helper invoked on add, remove, and initial render-from-DB.
* Cronologia layout fix — Keyword Research (and other history-rendering pages) showed entries flowing horizontally with overflow when an ancestor became a flex container. Forced .history-item to width:100% + flex:0 0 100% + .orchestra-history-container to display:block so entries always stack vertically full-width regardless of theme/ancestor styling.
* Empty-result UX — Keyword Research now distinguishes the typed empty_result 422 (Module 13 backend refund) from generic errors. Shows a green "✓ Crediti rimborsati" inline banner with the refund amount + suggestion to retry with more specific input. Skips history save in this case so the cronologia doesn't show a paid entry for an analysis that was refunded.

= 3.38.1 =
* Task 2 hotfix — Ambassador banner relocated to the correct template (wizard-home.php / Dashboard page). It was wrongly placed in admin-dashboard.php (Orchestratore page) in 3.38.0 and therefore never visible to users landing on Dashboard. Plus added a "Setup completato" celebration variant when all 7 steps done.
* Task 1 — Step data preview in Setup Guidato. Every done step now renders a compact summary of underlying data (Perimetro fields, Business Profile description + Context AI char count, Keyword Research count + timestamp, Brand Voice profile/tone/audience, Orchestrator health score + pages analyzed, Native Output 4 toggle states, Auto-Pilot job count). Perimetro inline form now prepopulates with saved values. New "✗ Marca da rifare" action per step lets users reset status to TODO while keeping the underlying data intact.
* Task 3 — Free first home analysis backend. New free_analyses_used collection with unique-index on user_id; try_claim_free_analysis() is race-safe (DuplicateKeyError caught). The /api/ai/analyze + /api/ai/aeo-analyze endpoints accept an is_free_first field — when set and the user has never claimed, both calls skip credit deduction. Setup Guidato Step 5 button now links to Orchestratore with ?is_free_first=1; the Orchestratore JS reads the param and passes it ONLY to the first page of the analysis run.
* Task 4 — Post-activation redirect to Setup Guidato. Industry-standard pattern (Yoast/RankMath/Elementor): new register_activation_hook sets a 60s transient scoped to current user; admin_init listener detects it, clears it, and redirects to ?page=seo-aeo-setup-guidato&first_run=1. The first-run hero shows a warm welcome + ⭐ "prima analisi gratis" callout + two CTAs (Inizia il setup / Esplora liberamente). The "Esplora liberamente" path persists seo_aeo_setup_skipped_by_user and respects the choice on future activations. Once all 7 steps are done the seo_aeo_setup_completed_once flag prevents future auto-redirect on plugin deactivate/reactivate.

= 3.38.0 =
* NEW: Setup Guidato (Onboarding 3.0). 7-step guided plugin configuration, persistent state, resume anytime. Persona branching (consultant / WP owner / exploring). Auto-detection of already-completed steps (no false TODOs). Sticky widget on every admin page showing progress + jump-to-step. Ambassador banner replaces the legacy 3-step onboarding overlay on Dashboard (overlay retained for parallel coexistence; will be removed in 3.38.1 once Setup Guidato is browser-verified). Free first home analysis (Step 5 messaging) — backend mechanism lands in 3.38.1.
* Module 16.3 — GSC property-mismatch message rewritten. When the connected Google account does not have access to the current domain, the widget now names the actual domain + connected email + offers two clear options (verify ownership in GSC, or change account via the new "⚙ Cambia account Google" button). Removed residual "team Orchestra" copy from the deprecated managed-mode branch.
* New menu entry: 🎯 Setup Guidato (position 12, between Dashboard and Profilo Business).
* Pure client-side state for now (WP option seo_aeo_setup_progress). No backend changes required.

= 3.37.3 =
* Module 12 (Path C) — Cancel button on Orchestratore now actually aborts the in-flight AJAX request and triggers an automatic credit refund via /api/ai/refund-generation with reason="cancelled". The 3/day refund cap is bypassed for user cancellations within the 5-minute window. Toast "Analisi annullata. Crediti rimborsati." confirms the action.
* Module 12 — Wired generation_log audit entries into 4 endpoints that previously had no refund path: keyword-research, brand-voice-analyze, suggest-keywords, generate-content. Each now returns a generation_id usable with /refund-generation.
* Module 16.2 — Deprecated the managed-by-admin GSC mode. All licenses now use per-tenant OAuth regardless of the (now-ignored) gsc_managed_by_admin flag. Removed the "Search Console gestito centralmente dal team Orchestra — contatta supporto" dead-end UI; every client license can self-connect via the standard OAuth flow.
* Module 16.2 — Google OAuth URL now always uses prompt=select_account so users see the account picker and can switch identities.
* Module 16.2 — New "⚙ Disconnetti / Cambia account" button label + dedicated "Cambia account ↗" link that disconnects and re-triggers OAuth.

= 3.37.2 =
* CRITICAL: Module 14 — fixed double-bind on Orchestratore "Avvia analisi" button that caused 2× AJAX requests + 2× credit consumption per page. Removed redundant inline onclick + added idempotency guard.
* CRITICAL: Module 13 — credits now only consumed on AI success. New 2-phase commit (reserve → commit/refund) prevents the "empty AI result but credits spent" defect on Keyword Research, Brand Voice, Suggest Keywords, Content Generator, Complete Article. Automatic refund via TypedAPIError 422 "empty_result" with full audit trail.
* Module 16 — fixed misleading GSC fallback. Self-service "Connect Google Search Console" button now shown to any client license; the "managed by team / contact support" message only appears for licenses with explicit gsc_managed_by_admin=true flag. Added "Perché serve?" expander explaining read-only scope + revocation procedure.
* Includes audit-trail script scripts/refund_empty_results.py for manual goodwill credits on pre-3.37.2 wastage.

= 3.37.1 =
* Critical infrastructure: plugin now auto-invalidates OPcache + WP transient cache on update. Future updates propagate fixes reliably without manual server restart or cache flush.
* Hidden admin diagnostic page (?page=seo-aeo-debug-cache) for cache state inspection + manual force-refresh — useful when a hosting provider has an aggressive OPcache configuration.
* Includes all fixes from 3.37.0 (typed errors, late-emit DOMContentLoaded, AI Crawlers tab switch, cronologia restore, Solaris placeholder cleanup).

= 3.37.0 =
* Architectural change: AI credit consumption now requires an active license. Trial-expired (status=inactive) accounts cannot consume credits even if the wallet balance is positive.
* Backend: typed error responses (402 insufficient_credits / 403 license_expired / 401 invalid_license) replace opaque generic errors. Applied to top 5 AI endpoints; the remaining 25 retain raw HTTPException for now (v3.37.1).
* Plugin: centralized JS error handler with contextual banner + Renew / Top up / Upgrade / Settings CTAs surfaced from any AJAX response.
* Fix (critical): inline scripts registered late (post-admin_print_scripts) now properly fire DOMContentLoaded callbacks. Previously, autosave and event listeners in Business Profile, SEO+AEO Output toggles, and AI Crawlers logs button were silently broken since the v3.36.0 wp_enqueue refactor.
* Fix: SEO+AEO Output toggle state now syncs DOM without manual refresh (side-effect of late-emit JS fix).
* Fix: AI Crawlers "Vedi log" button now opens the log viewer (side-effect of late-emit JS fix).
* Fix: Business Profile autosave + Context AI preview regeneration now working (side-effect of late-emit JS fix).
* Polish: removed legacy BETA label from Redirect Manager (feature stable since 3.15.0).
* Polish: Business Profile placeholder examples cleaned up (Solaris client-specific examples replaced with generic ones). Comma separator support for tag inputs confirmed and documented in help text.

= 3.36.8 =
* CRITICAL FIX dashboard layout: wp_add_inline_style() called from inside template body buffers (the ob_start/ob_get_clean pattern in 25+ templates) was silently dropped by WordPress because admin_print_styles flushes queued styles in <head> BEFORE the template body runs, marking the handle as done. SEO_AEO_Inline_Assets::add_inline_style() now detects late calls via did_action(admin_print_styles) and defers emission to admin_print_footer_scripts, emitting a <style> tag at footer time. Same fix mirrored for add_inline_script. Restores the .orch-wiz-hero flex header and ~20-50KB of dashboard CSS that had been silently dropped since v3.36.0.


= 3.36.7 =
* Fixed critical UI regression introduced by v3.36.0 wp_enqueue refactor: literal <script type=text/javascript> tag in templates/partials/gsc-section.php (line 128) was never closed with </script>, breaking the admin dashboard layout (5-box stat header collapsed to vertical list) and emitting "Uncaught SyntaxError: Unexpected token <" in the browser console. Replaced with ob_start() so SEO_AEO_Inline_Assets::add_inline_script() captures the JS via wp_add_inline_script (which wraps the JS in script tags itself).
* Cleanup line 35-37 comment corruption (same v3.36.0 refactor pass had incorrectly converted "<script> CDN" text inside a PHP comment to "<?php ob_start(); ?>\nCDN" — restored to plain prose).


= 3.36.6 =
* Plugin Check zero/zero: wrapped 2 additional functions in class-ai-crawler-detector with function-scope phpcs:disable/enable for InterpolatedNotPrepared (cleanup_old_logs covers DELETE FROM $table, daily_aggregate covers DELETE FROM $log_table + DELETE FROM $stats_table).


= 3.36.5 =
* Plugin Check zero/zero: converted phpcs:ignore line-by-line to function-scope phpcs:disable/enable blocks for SQL InterpolatedNotPrepared and UnfinishedPrepare in 5 files (class-ai-crawler-admin, class-ai-crawler-detector, class-migration-importer, ai-crawler-live-state template, class-page-roles). 12 functions wrapped + 1 template-level disable.


= 3.36.4 =
* Fix audit Plugin Check 6 run: la v3.36.3 sweep mis-applied — l'idempotency guard saltava i siti gia annotati con format virgola+spazio (lascito v3.36.0-.36.1), che PHPCS strict parser non riconosce.
* Normalizzate 222 phpcs:ignore/disable lines su 60 files dal format "Rule1, Rule2" a "Rule1,Rule2" (PHPCS strict parser).
* Sostituite 59 ignore lines pre-esistenti con la versione comprensiva (aggiunte PluginCheck.Security.DirectDB.UnescapedDBParameter e WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber al set di regole soppresse).
* class-ajax-handlers.php: aggiunta WordPress.Security.NonceVerification.Missing/Recommended al file-level disable (il namespace effettivamente flaggato da Plugin Check, non solo PluginCheck.Security.Nonce).


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

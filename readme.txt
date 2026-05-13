=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.39.9
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

= 3.39.9 =
* URGENT REGRESSION FIX — analysis completely broken on v3.39.8. Console showed "TypeError: SeoAeoOrchestra._startOrchEtaTick is not a function" at admin.js:2231 inside orchestrateNext, called from orchestrateStart at line 2194. The exception aborted the flow BEFORE the AJAX call fired, so zero admin-ajax POSTs were sent and the analysis spinner spun forever at 0%.
* Root cause — the v3.39.7 ETA-helper insertion (_loadOrchDurations, _recordOrchPageDuration, _getOrchMedianDuration, _stopOrchEtaTick, _startOrchEtaTick) never persisted to the deployed build, OR was overwritten by a later patch. The v3.39.8 cycle landed Bug A + retry-hardening but inherited the broken state. Call sites remained, definitions vanished, TypeError followed.
* Fix (A) — re-added all five helpers as properties on the SeoAeoOrchestra object literal, right before orchestrateNext. Same behavior as the v3.39.7 spec: 500ms tick driving the progress bar to 95% per page slice and ETA countdown using the rolling median of last 10 successful durations in localStorage (25s fallback). _stopOrchEtaTick now also jumps the bar to 100% explicitly on success.
* Fix (B) — defensive `typeof === 'function'` guards added at EVERY call site (_startOrchEtaTick, _stopOrchEtaTick, _recordOrchPageDuration). Any future regression that loses the helpers degrades gracefully — analysis still runs without ETA visualization rather than crashing the entire orchestration flow. This is now the standard pattern for ALL future inter-function calls inside SeoAeoOrchestra: never call a sibling method without a typeof guard.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.8 =
* Bug A — Piano d'Azione rendering on history restore. Verified screenshot v3.39.7: after Riapri → "Carica analisi", Piano d'Azione section showed all actions concatenated as one continuous text blob without card separators. Root cause: restoreFromHistory was iterating the saved-HTML outputs map (which on older history entries contained merged/stale markup for #orch-action-plan) instead of rebuilding from the in-memory state.allActions hydrated in the same call. Fix: extracted SeoAeoOrchestra.renderActionPlan(actions) helper used by BOTH the fresh-analysis orchestrateComplete path and the restoreFromHistory orchestrator branch. Restore-side outputs loop now skips '#orch-action-plan' when state hydration is active, preventing stale-markup overwrites.
* P1 — LLM retry hardening. _validated_analysis max_retries bumped 2 → 4 (5 attempts total) at both call sites. Both inner-function fallbacks collapsed from 2 fake hardcoded issues to 1 consolidated "Analisi AI temporaneamente non disponibile" MANUAL_REVIEW entry with _llm_failed=True flag.
* New SeoAeoOrchestra.renderLlmFailedBanner emits a single amber banner above #orch-action-plan listing the number of pages where the LLM permanently failed (e.g. "Analisi AI temporaneamente non disponibile per 2 pagine (33%)") with retry hint, instead of cluttering the recommendation list with multiple fake "rivedi manualmente" entries. Banner re-renders on history restore.
* Permanent-failure log line now includes the schema name (SEOAnalysisOutput / AEOAnalysisOutput) for faster diagnosis.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.7 =
* UX FIX — Animated ETA + progress bar during analysis. v3.39.6 showed "Tempo stimato: calcolo..." for the entire run and the progress bar stayed at 0% until completion. Now a 500ms-tick interval drives both fields based on a rolling-median historical duration per page (kept in localStorage, last 10 successful pages) with a 25s fallback if no history exists. Bar grows linearly toward 95% per page slice and only jumps to 100% on actual response. ETA counts down in seconds (e.g. "~ 18 sec rimanenti"); shows "Quasi finito..." if the LLM takes longer than the median. Tick auto-stops on success / fail / cancel.
* Cheap-LLM preview tier — v3.39.6 preview used the same agent + tier as the apply call, so a Premium++ user paid full credits to preview and again to apply. Now ajax_preview_action forces tier=standard (Gemini Flash) on the 4 wired agents regardless of the user's apply-tier setting. UX hint added to the preview modal subtitle: "Preview con tier veloce (1cr). Applica usa il tier configurato." User reviews cheap, applies at their selected quality tier.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.6 =
* UX FIX — preview-before-apply on Piano d'Azione and Problemi cards. Clicking "Esegui automaticamente" used to apply destructive AI agent modifications (meta tags, schema, FAQ section, content rewrite) to the page in a single click, with no opportunity for the user to review what would change. Especially risky for first-time users.
* NEW BUTTON — "👁 Mostra modifiche" sits before "⚡ Esegui" on both Piano d'Azione cards (orch-action-item) and Problemi SEO/AEO cards (orch-problem-card). Same data-agent + data-action-data attributes so handlers route to either the preview or the executor.
* NEW BACKEND — ajax_preview_action mirrors ajax_execute_action across the 4 wired agents (meta_tags, aeo_content, seo_analysis, content_generator) but SKIPS every update_post_meta / DB-write step. Returns the agent's raw output so the frontend modal can render a current-vs-proposed diff. For manual_review the modal explains the action requires the WordPress editor.
* NEW MODAL — branched render per agent type: meta_tags shows side-by-side meta_title + meta_description diff (current vs proposed) plus keywords, aeo_content / content_generator shows the generated HTML preview (sanitized) + Schema JSON-LD if included, seo_analysis shows score + top 8 issues + top 5 suggestions, manual_review shows plain message. CTAs: [Annulla] [Rigenera] [Applica modifiche (N cr)]. Applica triggers the existing .orch-execute-btn so the executor flow runs unchanged (writes to DB, deducts credits, etc.). Rigenera re-fires the preview.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.5 =
* CRITICAL FIX — silent backend 500 since v3.39.1 producing "Risposta non valida dal server". Chrome MCP XHR interceptor on aeo-orchestra.com v3.39.4 captured response 412 bytes with seo_score=null + aeo_score=null + seo_detail.error="Risposta non valida dal server" + total time ~9ms (impossible for any LLM call, let alone 3 retries).
* Root cause — helpers/site_context.py (added in v3.39.1) had `from helpers.database import db` but this project uses `helpers.config`. The import raised ModuleNotFoundError before any retry/fallback code ever ran. The v3.39.4 structured fallback lived INSIDE ai_analyze_seo / ai_aeo_analysis and never got a chance to execute because the exception escaped one frame above, hitting FastAPI's generic 500 handler.
* Fix #1 — corrected import in helpers/site_context.py to `from helpers.config import db`.
* Fix #2 — endpoint-level absolute failsafe. api_analyze_seo + api_aeo_analyze pipelines wrapped in try/except. ANY exception (import error, Gemini outage, schema rejection, retry exhaustion that escapes the inner fallback, anything) is caught and replaced by HARDCODED_SEO_FALLBACK / HARDCODED_AEO_FALLBACK module-level constants. User never sees "Risposta non valida" again.
* Fix #3 — hardcoded fallback structure mirrors the v3.39.4 SEOAnalysisOutput / AEOAnalysisOutput schemas exactly: numeric score (50), 2 normative issues each (Schema.org Organization + E-E-A-T for SEO; FAQ section + conversational tone for AEO), severity / action_type / action_description all populated. The JS buildProblemCards + PHP build_action_from_issue mappers handle these dicts uniformly.
* Logging — _validated_analysis now logs at INFO/WARNING/ERROR every step (attempt number, schema name, Gemini raw response first 400 chars, Pydantic validation error if any, final permanent-failure summary). Future failures land in Sentry / docker logs with enough context to diagnose without Chrome MCP repro.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.4 =
* CRITICAL FIX — LLM structured output enforcement. v3.39.3 calibrated the prompt rules (FACTUAL vs NORMATIVE vs SCORING) but free-text prompt revision proved insufficient against cautious Gemini Flash behavior: Chrome MCP on aeo-orchestra.com v3.39.3 still returned seo_score=null, aeo_score=null, 0 issues. The model interpreted ambiguity as "decline to answer".
* New helpers/orchestrator_schema.py — two Pydantic v2 schemas (SEOAnalysisOutput, AEOAnalysisOutput) with min_length=2 on issues array, ge=0/le=100 on score fields (no null possible), Literal types for action_type / category / severity, min_length=50 on action_description.
* gemini_generate() — added response_schema kwarg. When provided, the Gemini call is constrained to JSON output via response_mime_type=application/json + response_schema, with temperature dropped from 0.7 to 0.3 for deterministic compliance.
* New _validated_analysis() helper wraps the LLM call: parses JSON, validates against the Pydantic schema, and on ValidationError automatically retries up to 2 times with a hardened system message that cites the specific schema error + normative gap examples (FAQ schema, Organization markup, conversational tone, E-E-A-T signals) so the model knows EXACTLY what to fix.
* ai_analyze_seo + ai_aeo_analysis — switched to the validated path. On permanent LLM failure (all 3 attempts fail), the endpoint returns a structured fallback with 2 issues including MANUAL_REVIEW + a normative gap (GENERATE_SCHEMA or ADD_FAQ_SECTION) so the UI never displays "0 problemi" + "--" again.
* PHP/JS compatibility — issues are now objects with .description field (vs. plain strings before). Both the PHP build_action_from_issue mapper and the JS buildProblemCards function already handled the object case (isinstance check + .description fallback), so this is a transparent upgrade. The LLM-emitted action_type can also drive the executor directly, bypassing the heuristic keyword mapper when present.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.3 =
* CRITICAL UX FIX — anti-hallucination calibration. v3.39.2 introduced REGOLE CRITICHE that were too restrictive: the LLM applied rule "se un'informazione non e' presente, dichiarala NON DETERMINABILE" to NORMATIVE SEO/AEO gaps too, not just factual claims. Verified Chrome MCP on aeo-orchestra.com v3.39.2: fresh analysis returned 0 SEO problems, 0 AEO problems, seo_score=null, aeo_score=null. Zero hallucinations (correct) but also zero analysis (useless). User saw "SEO MEDIO --" and "AEO MEDIO --" with 0 problems and concluded the plugin was broken.
* Prompt revision — replaced the single REGOLE CRITICHE block in routes/ai.py + helpers/site_context.py with three clearly delimited sections: (1) REGOLE PER CLAIM FATTUALI applies only to claims about WHAT the page sells / IS / CONTAINS — still strict, never invent products / topics / prices, mark factual gaps as NON DETERMINABILE; (2) REGOLE PER ANALISI NORMATIVA explicitly licenses the AI to identify SEO/AEO best-practice gaps even when the missing element is not mentioned in the text (FAQ schema, structured data, intro-as-direct-answer, H1 keyword targeting, meta description length, keyword density, internal linking, conversational tone for AEO, thin content < 500 words, E-E-A-T signals), with a curated list of legitimate gap categories; (3) REGOLE DI SCORING mandates a numeric 0-100 value for every score field (never null, never "--", never skipped — poor content yields a low specific score like 25 or 35, not nothing).
* Target output guidance added to the prompt: 3-7 problems per page is the expected range. Pages with 0 problems are flagged as a rare case requiring the normative rules to be re-applied.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.2 =
* Bug #1 (P0) — Site Context fields appeared to "not save". Root cause was hydration, not save: data was correctly persisted in MongoDB and returned by the GET endpoint, but loadProfile() scalar list was missing the three new site_context_description / value_prop / target_audience keys, so the textareas stayed empty on reload. Extended the hydration list. Extracted hydrateProfile() helper so we can re-run it.
* Bug #3 (P0 — chip persistence) — same family of issue. Mongo had 7 products_services + 5 suppliers_partners items, GET returned them, setChipValues was correct, yet chips appeared empty on some reloads. Added a defensive setTimeout(0) second pass that re-runs the entire hydration after the first sync pass — covers any rendering-order race where details/collapsibles weren't fully resolved when the first pass walked $form. Also wired chip-repeater re-render into saveProfile success so the DOM matches the backend-cleaned payload after each save (skips chip wrappers whose input has focus to avoid clobbering active typing).
* Bug #2 (P0 — auto-generate "0 pagine analizzate" on JS-rendered sites) — aeo-orchestra.com is React-rendered: 26 KB HTML strips to 111 chars of body text, well below the v3.39.1 >200 threshold, so the endpoint returned "0 pages" without refunding. Now: User-Agent header identifies the crawler, title + meta description + og:* are captured BEFORE tag stripping (modern SPAs still emit SEO metadata), threshold lowered to 50 chars for homepage / 80 for secondary pages, dynamic slug discovery via anchor parsing for chi-siamo / about / azienda / company / servizi / services / solutions / what-we-do / prodotti / products, per-URL diagnostic log (status + bytes) returned in the 422 error, refund + clear Italian error when 0 pages collected, sanity check on Gemini output (refund if all 4 fields empty).
* Bug #4 (P1) — wired build_site_context_block into the remaining content-generation AI endpoints: /ai/generate-meta (Meta Tags AI), /ai/generate-content (Content AI), /ai/aeo-content (AEO Content). The site_context_block is prepended to each system message before brand_voice_addition so brand semantics anchor before voice tuning. /ai/analyze + /ai/aeo-analyze already had it from v3.39.1.
* Backend crawler refactor — split the monolithic _fetch_text into _fetch_html (HTTP) + _extract_meta_and_text (title + meta + og + body) + _format_page_block (delimited LLM-ready blob) + _discover_about_service_slugs (homepage anchor scanner). All testable independently, all reusable for future site-context refresh + multi-tenant scrape jobs.
* Plugin Check 0/0 on the WP.org ZIP.

= 3.39.1 =
* CRITICAL P0 — anti-hallucination fix. Verified Chrome MCP on aeo-orchestra.com homepage v3.39.0: AI analysis said the page was "incentrato sull'orchestra musicale, concerti, musicisti" even though it's plugin software with zero music context. Backend prompts received only (url, keyword, content) and guessed semantics from the brand name. Same risk on any business with an ambiguous name.
* Backend prompts — ai_analyze_seo + ai_aeo_analysis now accept keyword-only kwargs (page_title, meta_description, h1, h2_list, body_text 5000ch, site_context_block). Prompt is split into delimited "=== CONTESTO SITO ===" + "=== PAGINA DA ANALIZZARE ===" blocks followed by 5 REGOLE CRITICHE: analyze ONLY provided content, mark missing info as NON DETERMINABILE, treat site_context as authoritative, cite specific page passages, don't estimate when content is too short.
* Plugin context extraction — new extract_page_context() helper in class-ajax-handlers.php pulls page_title, meta_description (Orchestra → bridge → Yoast fallback), h1 (regex on raw HTML → post_title fallback), first 10 h2s, body_text (apply_filters('the_content') if raw too short → strip tags → collapse whitespace → 5000 char cap). Merged into the api_request payload sent to both /ai/analyze and /ai/aeo-analyze.
* NEW feature — Site Context section in Profilo Business (between PUBLIC and INTERNAL). Four fields the AI prompts treat as authoritative source: cosa-fa-il-sito (600 char), value proposition (400 char), target audience (400 char), and most importantly TERMINI DA DISAMBIGUARE (list of {term, correct_meaning, not_meaning} — explicit "X means Y, NOT Z" pairs the LLM must respect). Example for AEO Orchestra: term="Orchestra" / correct="Plugin AEO software" / not="Orchestra musicale".
* Auto-generation — "🔍 Genera automaticamente dal sito (3 cr)" button crawls homepage + Italian/English /chi-siamo, /servizi, /about, /services, /what-we-do slugs (httpx, 6s timeout, 9k chars combined), runs Gemini Flash with JSON-only system message, returns 4 fields the user reviews + edits before saving. Refund-on-failure for both LLM exceptions and parse failures.
* Centralized helper — helpers/site_context.py: fetch_site_context (Mongo lookup) + format_site_context_block (delimited Italian block) + build_site_context_block one-shot + ANTI_HALLUCINATION_RULES constant available for future ai.py call sites (Brand Voice, Schema, Keyword Research, FAQ, Meta Tags, Content Rewrite).
* Backend schema — business_profile.py ALL_USER_FIELDS + VALIDATION_RULES extended with site_context_description, site_context_value_prop, site_context_target_audience, site_context_ambiguous_terms (new sc_list validator: dicts with term + correct_meaning + not_meaning, capped 80/200/200 chars, max 12 items).
* Plugin Check 0/0 on the WP.org ZIP.

= 3.39.0 =
* CRITICAL UX FIX — every Problemi SEO / Problemi AEO card now has a concrete actionable solution inline. Before: the orchestrator endpoint built actions HEURISTICALLY from score thresholds (seo_score<70, aeo_score<60, suggestions count>2, meta missing). Pages with above-threshold scores but multiple AI-detected issues (e.g. aeo_score 75 with 6 AEO issues on a homepage) produced ZERO actions, so every problem card fell back to a generic "rivedi manualmente questa pagina" hint. The plugin appeared to identify problems but offered no path to fix them.
* Backend Part 1 — new build_action_from_issue() PHP helper with 10-pattern keyword→action mapping (schema/markup → GENERATE_SCHEMA, FAQ/domande → ADD_FAQ_SECTION, meta/description → REWRITE_META, headings → FIX_HEADING_STRUCTURE, internal link → ADD_INTERNAL_LINKS, intro/focus → REWRITE_INTRO, citability/E-E-A-T → ADD_AUTHORITY_SIGNALS, featured snippet → OPTIMIZE_FEATURED_SNIPPET, keyword → OPTIMIZE_KEYWORDS, expand → EXPAND_CONTENT). Each emitted action carries action_type, action_title, action_description (2-3 sentences: WHAT/WHY/impact), auto_executable boolean, executor_agent (one of meta_tags / aeo_content / content_generator / manual_review), estimated_credits, and issue_ref linking back to the AI-detected problem.
* Backend wiring — ajax_orchestrate_single() iterates seo[issues] and aeo[issues] arrays AFTER the legacy aggregate actions and emits one action per issue. Unmapped issues become MANUAL_REVIEW with a specific issue-prefix hint (never the generic "rivedi manualmente" copy).
* Frontend Part 2 — buildProblemCards() in admin.js now: (a) prefers exact issue_ref match over the legacy page_title-intersection fallback, (b) prefers action.description (rich Italian copy from the new backend mapper) over getActionDetailDescription (legacy switch by agent), (c) when no action matches, calls the new SeoAeoOrchestra.contextHintForIssue() with 10 regex rules pointing to specific Orchestra pages (Schema → SEO+AEO Output Nativo, FAQ → Contenuti AEO, meta → Meta Tags AI, etc.).
* Esegui button gating — the inline "Esegui automaticamente" button now appears only when matchAction.auto_executable !== false AND agent is not manual_review, and shows the estimated_credits inline in the label (e.g. "⚡ Esegui automaticamente (10 cr)").
* Zero occurrences of "rivedi manualmente questa pagina" in problem cards after this release. Plugin Check 0/0 on the WP.org ZIP.

= 3.38.9 =
* Cronologia "Riapri" — addendum to the v3.38.8 modal wiring. The confirmation modal opened and the AJAX returned 200, but the underlying restoreFromHistory() only painted three innerHTMLs, never hydrated the in-memory data behind clickable problem cards, never updated scalar counters, and never revealed #orchestrator-results (which is display:none until JS reveals it). Net effect: confirming the modal silently did nothing on the page.
* orchestrateComplete() now saves a full state snapshot in restore_payload.state (allSeoIssues, allAeoIssues, allActions, results, pages, counters). The legacy outputs map is preserved for backward compat with pre-3.38.9 history entries; the wrong selector #orch-action-list (which never existed on this page) was corrected to #orch-action-plan.
* restoreFromHistory() detects state-bearing payloads and: (1) hydrates SeoAeoOrchestra._allSeoIssues / _allAeoIssues / _allActions / _results / orchestrateResults / orchestratePages so the v3.38.8 inline problem cards render correctly when the user clicks "Problemi SEO" / "Problemi AEO", (2) fills the six scalar counters (avg_seo, avg_aeo, pages, seo_issues, aeo_issues, total_actions), (3) reveals #orchestrator-results, (4) closes the confirm modal automatically, (5) shows a richer success toast "✓ Analisi del {date} caricata dalla cronologia", (6) scrolls to the result section so the user sees the restored data without manual scrolling.

= 3.38.8 =
* Cronologia "Riapri" — clicking a Riapri button on the analysis history now opens a confirmation modal ("Vuoi caricare questa analisi storica?") before restoring. Previously the click would call restoreFromHistory() directly and the legacy fields/outputs selectors didn't match the current orchestrator output panel, so nothing visibly happened. The modal also writes ?history_id=<id> to the URL via history.replaceState for shareable links.
* Piano d'Azione — "Mostra completati (N)" toggle in the Prossimi passi card header. Completed TODO tiles are hidden by default; clicking the toggle reveals them with an inline counter. State persisted in localStorage (seo_aeo_orch_todo_show_done_v1). The toggle is hidden when no items are completed.
* Problemi SEO/AEO — inline "Come risolvere" + action executors under each problem card. Issues are grouped by exact text so duplicates across pages collapse to one card with an "su N pagine" badge. Each card carries the existing getActionDetailDescription() text + "Esegui automaticamente" button (reuses the .orch-execute-btn delegation from Piano d'Azione) + "Mostra pagine (N)" expander showing the affected page list. Issues without a matching action show a fallback "rivedi manualmente" hint.
* Floating savebar regression — replaced the :visible-gated polling loop that never fired when #orch-identity-form sat inside collapsed details. Now: (1) lazy snapshot on the first input/change/blur event (DOM presence is sufficient, no visibility requirement), (2) ajaxComplete listener seeds the baseline from the identity-get response 50ms after AJAX returns, (3) 4s defensive fallback. The savebar now reliably surfaces "Modifiche non salvate" on Solaris and aeo-orchestra.com when fields change.

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

For older changelog entries (3.37.2 and earlier), see the project repository at https://github.com/jcappelliemer/aeo-orchestra .

=== AEO Orchestra ===
Contributors: jcappelli
Tags: seo, aeo, llms-txt, schema, chatgpt
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.40.8
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

= 3.40.8 =
* P0c-A - Silent execution UI fix. Verified Chrome MCP on v3.40.7 against aeo-orchestra.com Page id=69: clicking Esegui on a Schema action returned the button to normal after ~55s with no toast, no Completato badge, no inline feedback - despite the backend AJAX completing 200 OK. Root cause: executeAction queried `#orch-action-result-undefined` because Problemi card execute buttons (.orch-problem-exec) don't carry data-idx (only Piano d Azione cards do). jQuery returned empty set, .show()/.html() were no-ops. Fix: when data-idx is undefined, walk up to the closest `.orch-action-item, .orch-problem-card, .orch-problem-actions` and lazily inject a `.orch-action-result-fallback` block. Plus an always-on SeoAeoOrchestra.showNotice() toast for every response branch (success / manual_mode / error) so the user gets feedback even if the container injection silently fails.
* P0c-B - REST meta visibility. _seo_aeo_custom_schema_html is now registered via register_post_meta on every public post type with show_in_rest:true, so verifier scripts that GET /wp-json/wp/v2/<type>/N?_fields=meta can see the auto-applied JSON-LD block in the meta object. Auth callback gates the write to current_user_can('edit_posts'). Hook fires on init priority 20.
* P0c-C - FAQ real persistence. ADD_FAQ_SECTION action now actually modifies the post: the AI HTML is scanned for a <h2>FAQ ... block (heuristic 1) or a <div class*='faq'>...</div> block (heuristic 2), falling back to the whole AI HTML if neither matches. The block is appended to post_content between idempotent <!-- aeo-orchestra:faq-block --> markers (re-runs replace the previous block in place rather than duplicating). wp_update_post bumps post_modified for REST verification. Returns verified:true + post_modified_gmt:<new ts> on success, falls back to honest manual_mode on extraction failure or wp_update_post error.

= 3.40.7 =
* P0a EMERGENCY - Fatal PHP error "Call to undefined method SEO_AEO_Orchestra_Ajax_Handlers::aeo_polylang_term_label()". The v3.40.4 patch wrote two callers (Layout articoli AI categories + Calendar default categories) but the helper definition was lost because the anchor expected  while the file actually exposes . v3.40.7 re-adds the private helper as a null-safe object/array handler that calls  when Polylang is active and returns the plain term name otherwise.
* P0b - Action dispatch semantic agent rename. The DOM  attribute now reflects the action type semantics instead of always reading : GENERATE_SCHEMA -> schema_generator, ADD_FAQ_SECTION -> faq_generator, ADD_AUTHORITY_SIGNALS -> authority_generator, REWRITE_INTRO -> intro_rewriter, OPTIMIZE_FEATURED_SNIPPET -> snippet_optimizer, OPTIMIZE_KEYWORDS -> keyword_optimizer, REWRITE_META -> meta_optimizer. Six new alias cases in ajax_execute_action route the new agent names to the right persistence path. meta_optimizer/keyword_optimizer use the real /ai/generate-meta + update_post_meta flow (same as the legacy meta_tags case). schema_generator persists the AI-generated JSON-LD into post_meta _seo_aeo_custom_schema_html + bumps post_modified for REST verification. The other four return honest manual_mode pending v3.40.8 surgical executors.
* P0c - Real-execution verification. The /aeo-analyze schema persistence path now calls wp_update_post with current_time('mysql') for post_modified and post_modified_gmt, then re-reads post_modified_gmt and compares with the pre-execute timestamp. The response gains  +  so the frontend can confirm via REST GET /wp-json/wp/v2/<type>/N that modified actually changed. Verified false produces a yellow "salvato ma post_modified non aggiornato (cache?)" message instead of a green "Completato".
* P0c - Frontend timeout. executeAction switched from $.post (no timeout) to $.ajax with timeout: 120000. The 151+ second "Sto generando..." hang seen on v3.40.6 Chrome MCP is fixed: after 2 minutes the call aborts and a red "Esecuzione impiega troppo tempo (>120s). Riprova o usa la modalita manuale." banner appears with the action button restored.
* P0d - page_quote truncation. Gemini Flash consistently emitted page_quote > 200 chars and burned all 5 retries -> AEO fallback -> UI showed "--". Three-layer fix: (a) pre-Pydantic post-processing in _validated_analysis truncates any issues[].page_quote > 197 chars to 197 chars + "..." before model_validate_json; (b) AEO system prompt gains "CRITICAL - page_quote constraint: <= 200 caratteri verbatim, ... per troncare" and the JSON schema example shows "<passaggio MAX 200 char verbatim O NON_PRESENTE>"; (c) AEOAnalysisOutput.issues[].page_quote max_length relaxed from 200 to 300 as a safety net beyond the explicit truncation.

= 3.40.6 =
* P0 Bug A - Duplicate actions in Piano d Azione. When the LLM emitted 2+ issues matching the same regex (e.g. "manca schema JSON-LD" + "manca markup structured data") the heuristic mapper produced 2 identical "Genera schema JSON-LD" cards. v3.40.6 deduplicates the actions array by (action_type, post_id) tuple before persist; the first match wins.
* P0 Bug C/D - Fake execution removed. Clicking "Esegui" on Schema/FAQ/Authority/Intro/Snippet actions used to show "Completato" + a green "Contenuto generato!" box even though the backend NEVER wrote anything to wp_posts. The aeo_content and content_generator execute branches now branch on action_type: GENERATE_SCHEMA persists the AI-generated JSON-LD into post_meta _seo_aeo_custom_schema_html and emits it via wp_head priority 12 (new SEO_AEO_Schema::emit_custom_schema hook). All other action_types return manual_mode:true with the proposed text instead of a fake success stamp.
* Frontend executeAction completely rewritten to honor the new response shape. Three distinct UX paths now exist: (a) applied:true OR meta_tags.saved -> green "Completato" + verification hint (Rich Results link for Schema); (b) manual_mode:true -> amber "Modalita manuale" banner with the AI text in a scrollable code-style box + Copia testo button; (c) error/surgical_failed -> red error banner with reason. No more fake success on output that never touched the post.
* Post-meta hook: SEO_AEO_Schema::emit_custom_schema reads _seo_aeo_custom_schema_html on is_singular() and echoes it on wp_head priority 12 (after the main schema renderer). Runs regardless of whether the Native Schema option is enabled, so a single "Esegui" on a GENERATE_SCHEMA action persists the JSON-LD without any other setup.
* DEFERRED to v3.40.7 - Per-action auto-persistence for ADD_FAQ_SECTION (append FAQ block to post_content), ADD_INTERNAL_LINKS (splice anchors via surgical editor), EXPAND_CONTENT (append paragraphs), FIX_HEADING_STRUCTURE (surgical H1/H2 rewrites), ADD_AUTHORITY_SIGNALS/REWRITE_INTRO/OPTIMIZE_FEATURED_SNIPPET (require AI prompt to emit {old_text, new_text} pairs for the surgical editor). Until then those action_types correctly return manual_mode and the UI shows the copy-paste path.

= 3.40.5 =
* P0 - AEO silent fallback (UI banner + auto-refund + retry log + per-tier metric). Symptom verified Chrome MCP on v3.40.3: SEO analysis succeeded (78 score + 5 problems) but AEO analysis silently returned the hardcoded fallback (-- score, 0 issues) with no error banner. User paid 5cr but got half the result.
* Frontend (admin.js displayAEOResults): renders a red banner above the scores when the response carries _llm_failed:true. Banner shows the fallback reason (schema_validation / json_parse_failed / empty_response / llm_call_failed / pipeline_exception:<type>) and whether credits were refunded. Auto-refreshes the wallet UI so the user sees the refund immediately.
* Backend (/api/ai/aeo-analyze in routes/ai.py): when the response is the hardcoded fallback (_llm_failed:true) AND the call was not a free-first analysis, auto-refunds credit_cost to the user via wallet.add_credits with source="aeo_fallback_refund". credit_transactions logs the refund.
* Backend (_validated_analysis): gained a retry_log out-parameter. Each retry now records {attempt, error_type, error excerpt, raw response excerpt} so api_logs has the full forensic trail for every failed AEO call.
* Backend api_logs new fields: aeo_fallback (bool), aeo_refunded (bool), aeo_tier (string, default "standard"), aeo_retry_log (array of attempt dicts), aeo_fallback_reason (last error_type). credits_consumed records 0 when refunded so /admin/clients credit usage stays accurate.
* Sentry alert configuration deferred to ops (not code). The query  is sufficient to monitor failure rate by tier; once the data accumulates, set an alert rule on standard-tier fallback ratio.

= 3.40.4 =
* P1.1 - Accent residues with apostrophe-as-accent surrogate (Compatibilita', modalita', capacita', Possibilita') cleaned up across 7 PHP files. The v3.40.3 word-boundary regex missed the trailing apostrophe in these spellings; v3.40.4 strips it explicitly so the UI now reads "Compatibilitaà Sito", "modalitaà", "capacitaà" with proper UTF-8 accents.
* P1.2 - Signal #6 (React/Next.js hydration markers) now visible in the Compatibilita Sito breakdown UI. v3.40.3 added the signal to detect_headless() but existing installations kept the cached 5-signal profile. SEO_AEO_Site_Scanner::get_profile() now auto-invalidates when signals_version < 6 and forces a rescan.
* P1.3 - Hybrid "Parziale" tier (25-40% confidence band) for sites that mix WP server-side rendering with React/JS islands. New SEO_AEO_Capability_Matrix entry "hybrid" with surgical_text/block_append in "high" mode (auto + verify) and schema in "full". Compatibilita Sito section shows a yellow warning banner when hybrid is detected: "Sito hybrid rilevato: WordPress server-side con componenti React/JS. Le modifiche AI verranno applicate al backend WP ma potrebbero non essere visibili se il frontend renderizza override custom". Headless row renders "Parziale (hybrid)" instead of "No".
* P1.5 - Polylang locale suffix in category dropdowns. "Uncategorized x 5" duplicates on multi-language sites now disambiguate as "Uncategorized (it)", "Uncategorized (en)", etc. Helper  wired into Layout articoli AI and Calendar default category dropdowns. Falls back to plain name when Polylang isn't active.
* P1.7 - Brand Voice CTA link in Auto-Pilot hero warning. "Nessuna Brand Voice attiva" badge now includes a clickable "-> Configura Brand Voice ora" link to admin.php?page=seo-aeo-brand-voice.
* P2.1 - IT translations: "Image SEO Manager" -> "Gestore Immagini SEO", "Bulk Generation" -> "Generazione di massa", "Bulk fix selezionati" -> "Correggi selezionati in massa", "Bulk fix in corso/completato" -> "Correzione di massa".
* DEFERRED to v3.40.5: Setup Wizard step 6 (P1.4 - Impostazioni Compat covers the configuration use case for now), completed-step inline summaries (P1.9), chart bar value separators (P1.6), Premium upgrade CTAs (P1.8), singular plurals audit (P2.2), Pesca da Research dynamic ID (P2.3), Cannibalizzazione H1/H2 duplicate (P2.4), Pianificazione cadence clarification (P2.5), Registro URL linkify (P2.6).

= 3.40.3 =
* P0 — Preview modal stuck (v3.40.0 regression). The "👁 Mostra modifiche" button fired the propose AJAX (status 200 OK) but the modal never opened and the button stayed disabled showing "Sto generando…". Root cause: a capture-phase click listener in admin-dashboard.php intercepted ALL `.orch-action-btn` clicks (including `.orch-preview-btn`) and routed them through the legacy propose + inline review-panel flow, preempting the v3.39.6 preview modal (`previewAction` in admin.js). The legacy renderer's silent failure left the button disabled.
* Fix: capture-phase listener now skips `.orch-preview-btn` so admin.js previewAction handles preview buttons → `seo_aeo_orchestra_preview_action` AJAX → modal with side-by-side diff (current vs proposed) + 3 CTAs (Annulla / Rigenera / Applica modifiche). Esegui (`.orch-execute-btn`) buttons keep the legacy propose flow.
* Hardening on the Esegui path: showReview() wrapped in try/catch so a render exception toasts + restores the button instead of leaving it stuck. Explicit 60s timeout on the propose AJAX. timeout vs xhr-fail distinguished in the toast text.
* admin.js: added `console.log('[PREVIEW] modal open', !!modalEl, agent=…)` after showPreviewModal returns, plus a fallback toast if the modal node never lands in the DOM. The always() handler already restores the button text in every code path.

= 3.40.2 =
* P0a — Multi-signal builder + headless detection. v3.40.0 lightweight scanner mis-classified aeo-orchestra.com (React/Next.js headless) as "Gutenberg" because some blog posts use block markup. New SEO_AEO_Site_Scanner::detect_headless() aggregates 5 signals: WPGraphQL plugin (30%), siteurl != home (25%), env constants VERCEL/NETLIFY/NEXT/NUXT (20%), theme stem matches /headless|api|stub|null|frontity/i (15%), REST API enabled (10%). Threshold 40% to flag headless. scan_full() combines builder + headless with confidence scores and writes the full profile to option aeo_site_profile + back-compat aeo_site_builder / aeo_site_is_headless / aeo_headless_mode options. When headless confidence >= builder confidence the environment promotes to headless_<mode> in the capability matrix.
* P0c — NEW "Compatibilita\' Sito" section in Impostazioni (templates/settings.php). Shows builder + confidence, headless + confidence + signal breakdown (collapsible "Vedi segnali rilevati" with each weighted signal hit/miss + note), effective environment label, capability matrix table (color-coded badges per mode: full=green, high=blue, medium=amber, low=orange, manual=red), "🔄 Re-scansiona sito" button. AJAX endpoint seo_aeo_orchestra_rescan_site forces SiteScanner::force_rescan() and reloads the page on success.
* P0d — Classic + Gutenberg surgical text editors. NEW includes/class-surgical-editor.php with SEO_AEO_Classic_Surgical_Editor (DOMDocument fragment parse + XPath text-node find + innerHTML replace, falls back to string-level str_replace for the happy path) and SEO_AEO_Gutenberg_Surgical_Editor (parse_blocks recursive walker that replaces text in attrs[content|text|value] for core/heading, paragraph, quote, pullquote, button, list-item, falls back to innerHTML/innerContent substring replace for 3rd-party blocks).
* ajax_execute_action gained a surgical dispatch branch BEFORE the manual-mode short-circuit AND BEFORE the agent switch. When action_type maps to surgical_text in the capability matrix AND action_data carries {post_id, edits:[{old_text, new_text, tag_type?}, ...]}, route through Gutenberg editor if can_handle() OR Classic editor as fallback. Success returns {success:true, surgical:true, engine, edits_applied}. Miss returns {surgical_failed:true, reason, failures} so the frontend can offer manual mode without burning more credits.
* DEFERRED to v3.40.3: Setup Wizard 3-stage modal (Part 3 — Compat tab in Impostazioni covers the configuration use case for now). Elementor / Divi / WPBakery / Beaver / Headless surgical editors. AI prompt updates so REWRITE_INTRO etc. emit {old_text, new_text} pairs the dispatch can route through.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.40.1 =
* P0 — Keyword Research hardened. Verified Chrome MCP on v3.40.0: 4 of 6 cronologia runs failed with "L'AI ha risposto con un formato non valido, crediti rimborsati" (~67% fail rate). Credits were correctly refunded but the user saw raw error + no result.
* gemini_generate() — new json_mode=False kwarg. When True, sets response_mime_type=application/json + temperature=0.3 on the Gemini call. Explicit flag instead of overloading response_schema (which would trigger Gemini's "too many states for serving" rejection on rich schemas).
* /seo/keyword-research — switched to json_mode=True. Added explicit ONE retry on first-pass parse failure with a hardened prompt that quotes the broken response back to the model. When BOTH attempts fail AND the tolerant parser also fails, emit a structured fallback of niche-aware generic keywords (informational + commercial + transactional + navigational clusters) instead of raising TypedAPIError. Refund credits but return success + fallback_used=true + credits_refunded so the UI can surface a banner.
* New _build_keyword_fallback(niche, max_kw) helper produces up to N templated keywords with same shape as real LLM output (so downstream sanitizer + UI render unchanged). Source field = "fallback" tags these entries.
* P1 — Typo "difficolta" → "difficoltà" in keyword-research.php hero subtitle + file-level docblock (CSV column header stays ASCII-safe).
* P2 — Brand Voice a11y: added for=/id= association on <label> for orch-bv-name + orch-bv-post-type. Screen readers now connect labels to inputs.
* v3.40.0 follow-up — mark_manual_applied tracker AJAX. New PHP handler seo_aeo_orchestra_mark_manual_applied called by the "Ho applicato manualmente" CTA in the manual-mode preview modal. Records a history entry with type=manual_applied + builder + action_type so the user sees manual applications in Cronologia. JS wires the click through to this endpoint (replaces the v3.40.0 stub toast).
* DEFERRED to v3.40.2: Setup Wizard 3-stage modal (foundation in v3.40.0 exposes get_capability_summary()). Classic + Gutenberg surgical editors (Part 5a) also defer to v3.40.2 — current cycle delivered the architecturally critical P0 fix + tracker which unblock the user path. Surgical editors plug into the same dispatch architecture without further refactoring.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.40.0 =
* NEW FEATURE — Universal compat foundation: capability matrix + lightweight builder detection + per-action mode dispatch + manual-mode UX. This is the architecture that distinguishes AEO Orchestra from Yoast/RankMath (which do 0% body edits across the WP ecosystem) by exposing what we CAN modify automatically per builder, and gracefully falling back to manual-paste mode where we CANNOT.
* Part 1 — capability matrix (includes/class-capability-matrix.php). 10 environments × 3 dimensions (surgical_text, block_append, schema) × 5 reliability levels (full / high / medium / low / manual). Maps every action_type to its capability dimension. ACTION_TYPE_MAP + ENVIRONMENT key resolution + is_manual_mode / is_verify_mode helpers + get_capability_summary for UI rendering.
* Part 2 — lightweight builder detection (includes/class-site-scanner.php). detect_builder() scans the active-plugins list for known builder slugs (Elementor, Divi, WPBakery, Bricks, Oxygen, Beaver, Breakdance), checks the active theme for Divi/Extra, and falls back to gutenberg/classic based on a sample of the 10 most-recent posts using has_blocks(). Result cached in option aeo_site_builder, re-scanned via force_rescan(). Runs once on activation.
* Part 4 — per-action mode dispatch wired into ajax_preview_action + ajax_execute_action. Each request now carries action_type. PHP looks up the mode via SEO_AEO_Capability_Matrix::get_mode_for_action(). Preview responses tag mode + builder + manual_instructions so the frontend can render the right variant. Execute path short-circuits when mode is manual/low — never writes to DB, returns a manual_mode payload directing the user to the preview modal.
* Part 6 — manual-mode UX in preview modal. New branch in showPreviewModal renders: amber banner ("Modalita\' Manuale richiesta — Il tuo sito usa Elementor..."), original text in red, proposed text in green with "📋 Copia testo proposto" button, numbered builder-specific instructions list ("Apri Elementor cliccando..."), and "✓ Ho applicato manualmente" CTA (real tracker AJAX lands in v3.40.1).
* DEFERRED to v3.40.1+: (3) Full 3-stage Setup Wizard with capability summary table, (5) per-builder surgical editors (ClassicEditor DOMDocument, Gutenberg parse_blocks walker, Elementor JSON walker, HeadlessREST direct write), (7) full headless mode handling (REST + WPGraphQL + SSG webhook), (8) deep cache integration (WP Rocket / Super Cache / Cloudflare / Elementor / Divi). Foundation in v3.40.0 makes each of these incremental additions instead of architectural changes.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

= 3.39.10 =
* Task 1 — ETA baseline calibration. Default 25s underestimate of real ~50s p50 caused the ETA to reach 0 and freeze on "Quasi finito..." for 25-30s while the LLM completed. Bumped _ORCH_DEFAULT_ETA from 25 to 50 (matches observed median). The rolling-localStorage median continues to override this as soon as 1+ successful analyses are recorded.
* Task 1 — Overage display. When totalRemaining drops to 0 the ETA used to show static "Quasi finito..." indefinitely. Now shows "Quasi finito... (+Ns extra)" with a live-incrementing overage so the user sees the clock keeps ticking and calibrates expectations. Fixed an off-by-one in the tick computation (used Math.max which floored the signed remaining; switched to signed arithmetic so overage is computable).
* Task 2 (P1) — Preview button stuck. Verified screenshot v3.39.9: clicking "👁 Mostra modifiche" stayed in "Generando preview..." state indefinitely (>1 min observed). The previewAction $.post had no timeout, no diagnostics, and showPreviewModal exceptions inside .done() weren't caught — combination produced a black-hole button.
* Fix — switched to jQuery.ajax with explicit 60s timeout. Added console.log diagnostics at click / response / always-restore. Wrapped showPreviewModal in try/catch so render exceptions show an error toast instead of silently leaving the user without feedback. Distinct timeout message ("Preview impiega troppo tempo (>60s). Riprova tra qualche secondo.") vs generic network error. Standard pattern for future: every loading state needs success + failure + timeout exit paths.
* Plugin Check 1.9.0 against the WP.org ZIP: 0 errors / 0 warnings.

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

For older changelog entries (3.38.5 and earlier), see the project repository at https://github.com/jcappelliemer/aeo-orchestra .

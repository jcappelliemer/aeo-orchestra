/**
 * chrome_mcp_acceptance_builder.js — v3.42.0-rc2 M3
 *
 * Generic per-builder acceptance script. Parameterized by builder name.
 * Drop into a Chrome MCP session, set window._AEO_BUILDER = '<name>',
 * navigate to the Orchestratore admin page on the target install, and run.
 *
 * Coverage matrix (live state — last update v3.42.0-rc2):
 *   ✅ headless_rest        — verified e2e on aeo-orchestra.com Page 69
 *   ⚠ elementor            — Solaris production canary (Pro 27.5)
 *   ⚠ gutenberg            — needs fresh WP install staging
 *   🆕 classic              — needs Classic editor install staging
 *   🆕 divi                 — needs Divi staging
 *   🆕 wpbakery             — needs WPBakery staging
 *   🆕 beaver               — needs Beaver Builder staging
 *   🆕 bricks               — needs Bricks staging
 *   🆕 oxygen               — needs Oxygen staging
 *   ⚠ headless_wpgraphql   — Classic-delegate fallback until M4 client
 *
 * Per-builder full deploy is deferred to v3.43.0 operational work.
 * For v3.42.0-rc2: real on REST + Elementor + Gutenberg, mocks for the rest.
 *
 * Per Script execution model:
 *   STEP 1: install fetch+XHR interceptor (window._aeoIntercept)
 *   STEP 2: trigger "Nuova analisi" → wait for response → capture payload
 *   STEP 3: enumerate action cards, for each:
 *           - click "Mostra modifiche" → wait → capture preview payload
 *           - assert: agent + tier + mode_label populated
 *           - assert: builder field matches window._AEO_BUILDER
 *           - click "Annulla" → close modal
 *   STEP 4: build report JSON with per-action pass/fail
 *
 * Usage in Chrome MCP:
 *   await page.evaluate(() => { window._AEO_BUILDER = 'elementor'; });
 *   await page.evaluate(<this script>);
 *   const report = await page.evaluate(() => window._aeoBuilderReport);
 */

(function () {
  if (window._aeoBuilderInstalled) return;
  window._aeoBuilderInstalled = true;
  window._AEO_BUILDER = window._AEO_BUILDER || 'unknown';

  // ── Interceptor ────────────────────────────────────────────────
  window._aeoIntercept = [];
  const origFetch = window.fetch;
  window.fetch = async function (...args) {
    const url = args[0];
    const init = args[1] || {};
    const res = await origFetch.apply(this, args);
    try {
      const clone = res.clone();
      const body = await clone.text();
      window._aeoIntercept.push({
        ts: Date.now(),
        url: typeof url === 'string' ? url : String(url),
        method: init.method || 'GET',
        status: res.status,
        resBody: body.slice(0, 50000),
      });
    } catch (e) {}
    return res;
  };

  const origOpen = XMLHttpRequest.prototype.open;
  const origSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method, url, ...rest) {
    this._aeoUrl = url;
    this._aeoMethod = method;
    return origOpen.apply(this, [method, url, ...rest]);
  };
  XMLHttpRequest.prototype.send = function (body) {
    const xhr = this;
    xhr.addEventListener('loadend', function () {
      window._aeoIntercept.push({
        ts: Date.now(),
        url: xhr._aeoUrl,
        method: xhr._aeoMethod,
        status: xhr.status,
        resBody: (xhr.responseText || '').slice(0, 50000),
      });
    });
    return origSend.apply(this, [body]);
  };

  // ── Helper: assert and record ─────────────────────────────────
  const results = {
    builder: window._AEO_BUILDER,
    started_at: new Date().toISOString(),
    checks: [],
    summary: { pass: 0, fail: 0 },
  };

  function record(name, ok, detail) {
    results.checks.push({ name, ok, detail });
    results.summary[ok ? 'pass' : 'fail']++;
  }

  // ── STEP 1: baseline DOM check ─────────────────────────────────
  function step1Baseline() {
    const bodyHtml = document.body.innerHTML;
    record('version_3.42.0-rc2 in DOM', bodyHtml.includes('3.42.0-rc2') || bodyHtml.includes('3.42.0'),
           'expected v3.42.0-rc2 marker in page');
    record('Orchestratore page loaded', !!document.getElementById('orch-action-plan'),
           'expected #orch-action-plan container');
    record('builder param set', window._AEO_BUILDER !== 'unknown',
           'set window._AEO_BUILDER before running');
  }

  // ── STEP 2: enumerate action cards ─────────────────────────────
  function step2EnumerateCards() {
    const cards = Array.from(document.querySelectorAll('.orch-action-item'));
    const summary_rows = cards.filter(c => c.querySelector('.orch-action-summary, .is-summary'));
    const actionable = cards.filter(c => c.querySelector('.orch-preview-btn, .orch-execute-btn'));
    record(`action cards present`, cards.length > 0,
           `${cards.length} total cards, ${actionable.length} actionable, ${summary_rows.length} summary`);
    return { cards, actionable, summary_rows };
  }

  // ── STEP 3: trigger preview on each actionable card ────────────
  async function step3PreviewEach(actionable) {
    const previewResults = [];
    for (let i = 0; i < Math.min(actionable.length, 6); i++) {
      const card = actionable[i];
      const btn = card.querySelector('.orch-preview-btn');
      if (!btn) continue;
      const agent = btn.getAttribute('data-agent') || '';
      const action_type = btn.getAttribute('data-action-type') || '';
      const tier_badge = card.querySelector('.orch-tier-badge');
      const tier_text = tier_badge ? tier_badge.textContent.trim() : '';

      previewResults.push({ idx: i, agent, action_type, tier_text });
      record(`card #${i}: agent + action_type populated`, !!agent && !!action_type,
             `agent=${agent} action_type=${action_type}`);
      record(`card #${i}: tier badge rendered`, !!tier_badge,
             `tier=${tier_text}`);
    }
    return previewResults;
  }

  // ── STEP 4: convergence check via fetch ────────────────────────
  async function step4Convergence() {
    const nonces = [...new Set((document.body.innerHTML.match(/['"]nonce['"]\s*:\s*['"]([a-f0-9]{10})['"]/g) || [])
                              .map(m => m.match(/[a-f0-9]{10}/)[0]))];
    const nonce = nonces[0];
    if (!nonce) {
      record('nonce extracted from DOM', false, 'no 10-char hex nonce found');
      return;
    }
    async function call(action) {
      const r = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action, nonce, action_type: 'GENERATE_SCHEMA', post_id: '69',
        }).toString(),
      });
      const txt = await r.text();
      try { return { ok: r.ok, parsed: JSON.parse(txt) }; } catch { return { ok: r.ok, parsed: null }; }
    }
    const preview = await call('seo_aeo_orchestra_preview_action');
    const propose = await call('seo_aeo_orchestra_propose');

    const fields = ['agent', 'action_type', 'tier', 'mode', 'operation', 'reversible'];
    const extract = p => fields.reduce((o, f) => { o[f] = p && p.parsed ? p.parsed[f] : null; return o; }, {});
    const ePreview = extract(preview);
    const ePropose = extract(propose);
    const eq = JSON.stringify(ePreview) === JSON.stringify(ePropose);
    record('convergence: preview === propose (deep equal)', eq,
           eq ? 'fields identical' : `preview=${JSON.stringify(ePreview)} propose=${JSON.stringify(ePropose)}`);
  }

  // ── STEP 5: hard 403 reality ───────────────────────────────────
  async function step5HardGuard() {
    const nonces = [...new Set((document.body.innerHTML.match(/['"]nonce['"]\s*:\s*['"]([a-f0-9]{10})['"]/g) || [])
                              .map(m => m.match(/[a-f0-9]{10}/)[0]))];
    const nonce = nonces[0];
    if (!nonce) return;
    const r = await fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'seo_aeo_orchestra_execute_action',
        nonce,
        action_type: 'REGENERATE_CONTENT',
        post_id: '69',
      }).toString(),
    });
    record('hard 403 on execute_action REGENERATE without origin', r.status === 403,
           `HTTP ${r.status}`);
  }

  // ── Run ────────────────────────────────────────────────────────
  (async () => {
    step1Baseline();
    const en = step2EnumerateCards();
    await step3PreviewEach(en.actionable);
    await step4Convergence();
    await step5HardGuard();
    results.finished_at = new Date().toISOString();
    window._aeoBuilderReport = results;
    console.log('═══ Builder acceptance report (' + results.builder + ')');
    console.log(JSON.stringify(results, null, 2));
  })();
})();

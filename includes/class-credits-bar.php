<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Credits Bar (3.30.0) — pillola crediti persistente in cima a ogni pagina
 * admin del plugin con MODAL DI RICARICA INTEGRATO (Option B).
 *
 * Click su "Ricarica" -> modal in-WP con 4 bundle topup (100/500/1000/5000 cr)
 * -> click "Acquista" -> AJAX a backend -> Stripe Checkout in nuova tab
 * -> webhook accredita topup_credits -> modal polla balance e auto-chiude
 * mostrando il nuovo saldo.
 *
 * Differenza vs 3.28.1 (Option A): non si esce piu' da WP-admin.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Credits_Bar {

    public function __construct() {
        add_action('in_admin_header', array($this, 'render_bar'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Mostra la pillola solo nelle pagine del plugin.
     */
    private function is_plugin_page() {
        if (!isset($_GET['page'])) return false;
        $p = sanitize_text_field($_GET['page']);
        return strpos($p, 'seo-aeo-') === 0;
    }

    public function enqueue_assets($hook) {
        if (!$this->is_plugin_page()) return;
        // Inline CSS minimo, no file extra
    }

    public function render_bar() {
        if (!$this->is_plugin_page()) return;
        if (!current_user_can('manage_options')) return;

        $T = function ($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        ?>
        <div id="orch-credits-bar" class="orch-credits-bar" data-license="<?php echo esc_attr($license_key); ?>">
            <span class="orch-cb-icon">&#128142;</span>
            <span class="orch-cb-label"><?php echo esc_html($T('Crediti')); ?></span>
            <strong class="orch-cb-value" id="orch-cb-value">&hellip;</strong>
            <button type="button" class="orch-cb-recharge" id="orch-cb-recharge-btn">
                <?php echo esc_html($T('Ricarica')); ?> &rarr;
            </button>
        </div>

        <!-- Topup modal (Option B, 3.30.0-beta) -->
        <div id="orch-topup-modal-bd" class="orch-topup-modal-bd" style="display:none;" aria-hidden="true">
            <div class="orch-topup-modal" role="dialog" aria-modal="true" aria-labelledby="orch-topup-title">
                <button type="button" class="orch-topup-close" id="orch-topup-close" aria-label="<?php echo esc_attr($T('Chiudi')); ?>">&times;</button>
                <h2 id="orch-topup-title">&#128142; <?php echo esc_html($T('Ricarica crediti')); ?></h2>
                <p class="orch-topup-current">
                    <?php echo esc_html($T('Saldo attuale:')); ?>
                    <strong id="orch-topup-current-bal">&hellip;</strong>
                    <?php echo esc_html($T('crediti')); ?>
                </p>
                <div id="orch-topup-options" class="orch-topup-options">
                    <div class="orch-topup-loading"><?php echo esc_html($T('Caricamento opzioni...')); ?></div>
                </div>
                <p class="orch-topup-footer-note">
                    <?php echo esc_html($T('Pagamento sicuro Stripe. I crediti sono accreditati immediatamente dopo il pagamento.')); ?>
                </p>
                <div id="orch-topup-status" class="orch-topup-status" style="display:none;"></div>
            </div>
        </div>

        <?php ob_start(); ?>
.orch-credits-bar {
            position: fixed;
            top: 32px;
            right: 16px;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 7px 14px;
            background: linear-gradient(135deg, #0A0E27, #1e3a8a, #0055FF);
            color: #fff;
            border-radius: 999px;
            box-shadow: 0 4px 12px rgba(0, 85, 255, 0.25);
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }
        .orch-credits-bar.is-low { background: linear-gradient(135deg, #7c2d12, #dc2626, #f59e0b); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); }
        .orch-cb-icon { font-size: 16px; line-height: 1; }
        .orch-cb-label { opacity: 0.78; font-weight: 500; font-size: 12px; }
        .orch-cb-value { font-weight: 800; font-size: 15px; letter-spacing: 0.3px; min-width: 36px; text-align: right; }
        .orch-cb-value.is-loading { opacity: 0.5; }
        .orch-cb-recharge {
            color: #fff;
            background: rgba(255, 255, 255, 0.18);
            padding: 4px 11px;
            border-radius: 999px;
            border: 0;
            cursor: pointer;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 12px;
            font-family: inherit;
            transition: all 0.15s;
        }
        .orch-cb-recharge:hover {
            background: rgba(255, 255, 255, 0.28);
            color: #fff !important;
            transform: translateX(2px);
        }
        @media (max-width: 782px) {
            .orch-credits-bar { top: 46px; right: 8px; padding: 6px 10px; font-size: 12px; }
            .orch-cb-label { display: none; }
        }
        @media (max-width: 600px) {
            .orch-credits-bar { display: none; }
        }

        /* Topup modal */
        .orch-topup-modal-bd {
            position: fixed; inset: 0;
            background: rgba(10, 14, 39, 0.72);
            backdrop-filter: blur(4px);
            z-index: 100000;
            display: flex; align-items: center; justify-content: center;
            padding: 20px; box-sizing: border-box;
            animation: orch-topup-fade-in 0.18s ease-out;
        }
        @keyframes orch-topup-fade-in { from { opacity: 0 } to { opacity: 1 } }
        .orch-topup-modal {
            position: relative;
            background: #fff;
            color: #0A0E27;
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.35);
            max-width: 920px; width: 100%;
            max-height: 92vh; overflow-y: auto;
            padding: 32px 32px 24px;
            box-sizing: border-box;
            animation: orch-topup-slide-up 0.22s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes orch-topup-slide-up { from { transform: translateY(16px); opacity: 0 } to { transform: translateY(0); opacity: 1 } }
        .orch-topup-close {
            position: absolute; top: 12px; right: 12px;
            width: 36px; height: 36px;
            border: 0; background: #f3f4f6; color: #374151;
            border-radius: 50%; cursor: pointer;
            font-size: 20px; line-height: 1; font-weight: 600;
            transition: all 0.15s;
        }
        .orch-topup-close:hover { background: #e5e7eb; transform: rotate(90deg); }
        .orch-topup-modal h2 {
            margin: 0 0 8px; font-size: 24px; font-weight: 800;
            background: linear-gradient(90deg, #0A0E27, #0055FF, #00E5FF);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        .orch-topup-current { margin: 0 0 24px; color: #6b7280; font-size: 14px; }
        .orch-topup-current strong { color: #0055FF; font-size: 16px; }
        .orch-topup-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin: 0 0 18px;
        }
        .orch-topup-loading {
            grid-column: 1 / -1;
            text-align: center; padding: 32px; color: #9ca3af;
        }
        .orch-topup-card {
            position: relative;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 16px 16px;
            display: flex; flex-direction: column;
            transition: all 0.18s;
            cursor: default;
        }
        .orch-topup-card:hover {
            border-color: #0055FF;
            box-shadow: 0 8px 24px rgba(0, 85, 255, 0.15);
            transform: translateY(-2px);
        }
        .orch-topup-card.is-popular { border-color: #0055FF; }
        .orch-topup-card.is-best { border-color: #10b981; }
        .orch-topup-badge {
            position: absolute; top: -10px; left: 50%;
            transform: translateX(-50%);
            background: #0055FF; color: #fff;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.4px; text-transform: uppercase;
            white-space: nowrap;
        }
        .orch-topup-card.is-best .orch-topup-badge { background: #10b981; }
        .orch-topup-credits {
            font-size: 28px; font-weight: 900; color: #0A0E27;
            line-height: 1.1; margin: 4px 0 2px;
        }
        .orch-topup-credits-label { color: #6b7280; font-size: 12px; font-weight: 500; }
        .orch-topup-price {
            font-size: 22px; font-weight: 800; color: #0055FF;
            margin: 14px 0 2px;
        }
        .orch-topup-rate { color: #9ca3af; font-size: 11px; margin-bottom: 14px; }
        .orch-topup-buy {
            margin-top: auto;
            background: linear-gradient(135deg, #0055FF, #00E5FF);
            color: #fff;
            border: 0;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 700; font-size: 13px;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        .orch-topup-buy:hover { box-shadow: 0 6px 16px rgba(0, 85, 255, 0.35); transform: translateY(-1px); }
        .orch-topup-buy:disabled { opacity: 0.6; cursor: wait; transform: none; }
        .orch-topup-card.is-best .orch-topup-buy { background: linear-gradient(135deg, #10b981, #34d399); }
        .orch-topup-footer-note {
            margin: 0; padding-top: 12px;
            border-top: 1px solid #f3f4f6;
            color: #9ca3af; font-size: 12px; text-align: center;
        }
        .orch-topup-status {
            margin-top: 14px; padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px; font-weight: 500;
            text-align: center;
        }
        .orch-topup-status.is-info { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
        .orch-topup-status.is-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-weight: 700; }
        .orch-topup-status.is-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 600px) {
            .orch-topup-modal { padding: 24px 18px 18px; }
            .orch-topup-options { grid-template-columns: 1fr 1fr; }
        }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

        <?php ob_start(); ?>
(function() {
            var ajaxUrl = (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxUrl) || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            var nonce   = (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce)   || '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>';
            var lowThreshold = 50;

            var T = {
                buy:           <?php echo wp_json_encode($T('Acquista')); ?>,
                popular:       <?php echo wp_json_encode($T('Più popolare')); ?>,
                bestValue:     <?php echo wp_json_encode($T('Miglior valore')); ?>,
                creditsLabel:  <?php echo wp_json_encode($T('crediti')); ?>,
                rateUnit:      <?php echo wp_json_encode($T('cr/€')); ?>,
                openedTab:     <?php echo wp_json_encode($T('Pagamento aperto in nuova tab. Torna qui dopo il pagamento, i crediti saranno accreditati automaticamente.')); ?>,
                successPrefix: <?php echo wp_json_encode($T('✓ Crediti accreditati! Nuovo saldo:')); ?>,
                errorCheckout: <?php echo wp_json_encode($T('Errore checkout. Riprova.')); ?>,
                loading:       <?php echo wp_json_encode($T('Caricamento opzioni...')); ?>
            };

            var lastBalance = null;
            var pollHandle = null;

            function fetchBalance(cb) {
                if (!window.jQuery) { if (cb) cb(null); return; }
                jQuery.post(ajaxUrl, {
                    action: 'seo_aeo_orchestra_credits_bar_balance',
                    nonce: nonce
                }, function(resp) {
                    var $el = jQuery('#orch-cb-value');
                    var $bar = jQuery('#orch-credits-bar');
                    if (resp && resp.success && typeof resp.balance !== 'undefined') {
                        var balance = parseInt(resp.balance, 10) || 0;
                        $el.text(balance.toLocaleString()).removeClass('is-loading');
                        if (balance < lowThreshold) { $bar.addClass('is-low'); } else { $bar.removeClass('is-low'); }
                        if (cb) cb(balance);
                    } else {
                        $el.text('—').removeClass('is-loading');
                        if (cb) cb(null);
                    }
                }).fail(function() {
                    jQuery('#orch-cb-value').text('—').removeClass('is-loading');
                    if (cb) cb(null);
                });
            }

            function fmt(n) { return (typeof n === 'number') ? n.toLocaleString() : n; }
            function escapeHtml(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }

            function renderOptions(payload) {
                var $opts = jQuery('#orch-topup-options');
                if (!payload || !payload.options || !payload.options.length) {
                    $opts.html('<div class="orch-topup-loading">' + escapeHtml(T.errorCheckout) + '</div>');
                    return;
                }
                jQuery('#orch-topup-current-bal').text(fmt(parseInt(payload.current_balance, 10) || 0));
                lastBalance = parseInt(payload.current_balance, 10) || 0;

                var html = '';
                payload.options.forEach(function(o) {
                    var classes = ['orch-topup-card'];
                    if (o.popular) classes.push('is-popular');
                    if (o.best_value) classes.push('is-best');
                    var badge = '';
                    if (o.popular)    badge = '<span class="orch-topup-badge">' + escapeHtml(T.popular) + '</span>';
                    if (o.best_value) badge = '<span class="orch-topup-badge">' + escapeHtml(T.bestValue) + '</span>';
                    var crPerEur = (o.price > 0) ? (o.credits / o.price).toFixed(1) : '0';
                    html += '<div class="' + classes.join(' ') + '" data-pkg="' + escapeHtml(o.package_id) + '">'
                        + badge
                        + '<div class="orch-topup-credits">' + fmt(parseInt(o.credits, 10)) + '</div>'
                        + '<div class="orch-topup-credits-label">' + escapeHtml(T.creditsLabel) + '</div>'
                        + '<div class="orch-topup-price">' + escapeHtml(o.price) + '€</div>'
                        + '<div class="orch-topup-rate">' + crPerEur + ' ' + escapeHtml(T.rateUnit) + '</div>'
                        + '<button type="button" class="orch-topup-buy" data-pkg="' + escapeHtml(o.package_id) + '">'
                        + escapeHtml(T.buy) + ' →</button>'
                        + '</div>';
                });
                $opts.html(html);
            }

            function showStatus(kind, msg) {
                var $s = jQuery('#orch-topup-status');
                $s.removeClass('is-info is-success is-error').addClass('is-' + kind).text(msg).show();
            }
            function hideStatus() { jQuery('#orch-topup-status').hide().text(''); }

            function loadOptions() {
                var $opts = jQuery('#orch-topup-options');
                $opts.html('<div class="orch-topup-loading">' + escapeHtml(T.loading) + '</div>');
                hideStatus();
                jQuery.post(ajaxUrl, {
                    action: 'seo_aeo_orchestra_topup_options',
                    nonce: nonce
                }, function(resp) {
                    if (resp && resp.options) {
                        renderOptions(resp);
                    } else {
                        $opts.html('<div class="orch-topup-loading">' + escapeHtml(T.errorCheckout) + '</div>');
                    }
                }).fail(function() {
                    $opts.html('<div class="orch-topup-loading">' + escapeHtml(T.errorCheckout) + '</div>');
                });
            }

            function startBalancePolling() {
                if (pollHandle) clearInterval(pollHandle);
                pollHandle = setInterval(function() {
                    fetchBalance(function(newBal) {
                        if (newBal !== null && lastBalance !== null && newBal > lastBalance) {
                            showStatus('success', T.successPrefix + ' ' + fmt(newBal));
                            clearInterval(pollHandle); pollHandle = null;
                            setTimeout(closeModal, 3000);
                        }
                    });
                }, 5000);
            }
            function stopBalancePolling() {
                if (pollHandle) { clearInterval(pollHandle); pollHandle = null; }
            }

            function openModal() {
                jQuery('#orch-topup-modal-bd').css('display', 'flex').attr('aria-hidden', 'false');
                jQuery('body').css('overflow', 'hidden');
                loadOptions();
            }
            function closeModal() {
                jQuery('#orch-topup-modal-bd').hide().attr('aria-hidden', 'true');
                jQuery('body').css('overflow', '');
                stopBalancePolling();
                hideStatus();
                fetchBalance();
            }

            jQuery(function($) {
                $('#orch-cb-value').addClass('is-loading');
                fetchBalance();
                setInterval(fetchBalance, 60000);

                $(document).on('click', '#orch-cb-recharge-btn', function(e) {
                    e.preventDefault();
                    openModal();
                });

                $(document).on('click', '#orch-topup-close', function(e) {
                    e.preventDefault(); closeModal();
                });
                $(document).on('click', '#orch-topup-modal-bd', function(e) {
                    if (e.target && e.target.id === 'orch-topup-modal-bd') closeModal();
                });
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && $('#orch-topup-modal-bd').is(':visible')) closeModal();
                });

                $(document).on('click', '.orch-topup-buy', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var pkg = $btn.data('pkg');
                    if (!pkg) return;
                    $('.orch-topup-buy').prop('disabled', true);
                    showStatus('info', '...');
                    $.post(ajaxUrl, {
                        action: 'seo_aeo_orchestra_topup_checkout',
                        nonce: nonce,
                        package_id: pkg
                    }, function(resp) {
                        if (resp && resp.url) {
                            window.open(resp.url, '_blank', 'noopener');
                            showStatus('info', T.openedTab);
                            startBalancePolling();
                        } else {
                            var msg = (resp && (resp.error || resp.detail || resp.message)) || T.errorCheckout;
                            showStatus('error', typeof msg === 'string' ? msg : T.errorCheckout);
                        }
                        $('.orch-topup-buy').prop('disabled', false);
                    }).fail(function() {
                        showStatus('error', T.errorCheckout);
                        $('.orch-topup-buy').prop('disabled', false);
                    });
                });
            });
        })();
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>
        <?php
    }
}

// AJAX handler per ricevere il balance del wallet (proxy a backend)
add_action('wp_ajax_seo_aeo_orchestra_credits_bar_balance', function () {
    check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }

    if (!class_exists('SEO_AEO_API_Client')) {
        wp_send_json(array('success' => true, 'balance' => 0));
        return;
    }
    try {
        $api = new SEO_AEO_API_Client();
        $resp = $api->api_request('/plugin/wallet/balance', array());
        if (is_array($resp) && isset($resp['total'])) {
            wp_send_json(array('success' => true, 'balance' => (int) $resp['total']));
            return;
        }
        if (is_array($resp) && isset($resp['plan_credits'])) {
            $bal = (int) ($resp['plan_credits'] ?? 0) + (int) ($resp['topup_credits'] ?? 0);
            wp_send_json(array('success' => true, 'balance' => $bal));
            return;
        }
        wp_send_json(array('success' => true, 'balance' => 0));
    } catch (Throwable $e) {
        wp_send_json(array('success' => false, 'balance' => 0, 'error' => $e->getMessage()));
    }
});

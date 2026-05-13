<?php
/**
 * SEO_AEO_AI_Helpers — shared utilities per generation AI articles.
 *
 * Centralizza logica duplicata tra Calendar / Autopilot / Ajax handlers:
 *  - attach_featured_image (con metadata SEO completi: alt, title, caption, description, filename slug-based)
 *  - mark_ai_generated     (post meta tracking: 6 fields per analytics + audit trail)
 *  - build_inline_image_block (Gutenberg <!-- wp:image --> con CSS constrain max-width 780, aspect 16/9)
 *
 * Pattern static helpers senza state. Tutti i metodi sono idempotent-safe.
 *
 * @since 3.35.8 (refactor extract duplicates - stabilization sprint giorno 2)
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_AI_Helpers {

    /**
     * Salva immagine base64 come WP attachment + set as featured image.
     * Genera filename SEO-friendly (slug-based), popola alt/title/caption/description.
     *
     * 3.33.4 — Metadata completi: alt_text, title, caption, description, descriptive filename.
     *
     * @param int    $post_id      Parent post ID.
     * @param string $base64       Image data (raw base64, no data URI prefix).
     * @param string $title        Post topic, used for alt fallback / caption / description / filename slug.
     * @param string $alt_text     Optional explicit alt text. Defaults to $title.
     * @param string $source_label Reserved for future use (es. filename prefix). Currently unused: tutti i source generano lo stesso pattern.
     * @return int|false Attachment ID, or false/null on failure.
     */
    public static function attach_featured_image($post_id, $base64, $title, $alt_text = '', $source_label = 'ai') {
        if (empty($base64)) return false;
        $img_data = base64_decode($base64);
        if (!$img_data) return false;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload_dir = wp_upload_dir();
        // Filename SEO: slug del titolo invece di hash random
        $slug = sanitize_title($title);
        if (empty($slug)) $slug = 'ai-image';
        $slug = mb_substr($slug, 0, 60);
        $filename = $slug . '-' . substr(md5($title . microtime(true)), 0, 6) . '.png';
        $filepath = trailingslashit($upload_dir['path']) . $filename;
        $bytes = file_put_contents($filepath, $img_data);
        if ($bytes === false) return false;

        $alt = !empty($alt_text) ? $alt_text : $title;
        $caption = sprintf('Illustrazione generata da AI: %s', $title);
        $description = sprintf('Immagine creata automaticamente da AEO Orchestra per illustrare il contenuto su "%s". Generata via AI image model.', $title);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'] ? $wp_filetype['type'] : 'image/png',
            'post_title'     => sanitize_text_field($title),
            'post_excerpt'   => sanitize_text_field($caption),       // Caption (sotto immagine in alcuni theme)
            'post_content'   => sanitize_textarea_field($description), // Description (pagina attachment)
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
        if (is_wp_error($attach_id) || !$attach_id) return false;
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
        // Alt text (campo separato, non in attachment post)
        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt));
        set_post_thumbnail($post_id, $attach_id);
        return $attach_id;
    }

    /**
     * Marca un post come AI-generated con full meta (6 campi).
     *
     * 3.32.0 — Imposta i meta "AI generated" su un post creato da Orchestra.
     * Idempotente: chiamabile più volte, sovrascrive valori.
     *
     * Campi:
     *   _seo_aeo_ai_generated         '1' (flag)
     *   _seo_aeo_ai_generated_at      timestamp epoch
     *   _seo_aeo_ai_generated_version versione plugin che ha generato
     *   _seo_aeo_brand_voice_used     '1'/'0' (flag attivo al momento generazione)
     *   _seo_aeo_autopilot_job_id     id job autopilot se applicabile (vuoto altrimenti)
     *   _seo_aeo_ai_source            stringa che identifica il flow
     *
     * @param int    $post_id Post ID target.
     * @param string $source  Flow identifier (es. 'autopilot', 'calendar', 'calendar_preview', 'publish_content', 'autopilot_regenerate', 'complete_article').
     * @param int    $job_id  Autopilot job id se applicabile, 0 altrimenti.
     */
    public static function mark_ai_generated($post_id, $source = 'unknown', $job_id = 0) {
        if (!$post_id || $post_id <= 0) return;
        update_post_meta($post_id, '_seo_aeo_ai_generated', '1');
        update_post_meta($post_id, '_seo_aeo_ai_generated_at', time());
        update_post_meta($post_id, '_seo_aeo_ai_generated_version', defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '');
        update_post_meta($post_id, '_seo_aeo_brand_voice_used', get_option('seo_aeo_orchestra_brand_voice_active', '0'));
        update_post_meta($post_id, '_seo_aeo_autopilot_job_id', $job_id ? (int) $job_id : '');
        update_post_meta($post_id, '_seo_aeo_ai_source', (string) $source);
    }

    /**
     * Build Gutenberg `<!-- wp:image -->` block HTML con CSS constrain.
     *
     * 3.33.2 — inline prepend per visibilità frontend su tutti i theme (incluso Elementor che non renderizza featured image).
     *
     * Output: max-width 780, aspect-ratio 16/9, object-fit cover, border-radius 8px, margin 0 auto 24px.
     * className="orch-ai-cover" per styling override custom.
     *
     * @param int    $attach_id Attachment ID (per wp-image-{id} class + JSON id).
     * @param string $img_url   Full URL dell'immagine (sarà passato a esc_url).
     * @param string $alt_text  Alt text (sarà passato a esc_attr).
     * @return string HTML block pronto da prependere a post_content (newline-terminated).
     */
    public static function build_inline_image_block($attach_id, $img_url, $alt_text) {
        $attach_id = (int) $attach_id;
        $alt_attr = esc_attr($alt_text);
        $img_url_esc = esc_url($img_url);
        return "<!-- wp:image {\"id\":{$attach_id},\"sizeSlug\":\"large\",\"linkDestination\":\"none\",\"className\":\"orch-ai-cover\"} -->\n"
            . '<figure class="wp-block-image size-large orch-ai-cover" style="max-width:780px;margin:0 auto 24px;"><img src="' . $img_url_esc . '" alt="' . $alt_attr . '" class="wp-image-' . $attach_id . '" style="width:100%;height:auto;aspect-ratio:16/9;object-fit:cover;border-radius:8px;display:block;"/></figure>' . "\n"
            . "<!-- /wp:image -->\n\n";
    }
}

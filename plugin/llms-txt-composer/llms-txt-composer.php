<?php
/**
 * Plugin Name: LLMs.txt Composer
 * Plugin URI: https://github.com/aminostudio/llms-txt-composer
 * Description: An editorial llms.txt for WordPress, with automatic, manual and hybrid modes.
 * Version: 1.3.0-beta.1
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Amino Studio
 * Author URI: https://aminostudio.fr/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: llms-txt-composer
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Virtual /llms.txt endpoint. No filesystem writes or external services.
 * Legacy site-specific v1.1 lives in a different directory and is not modified.
 */
final class Amino_LLMS {
    private const VERSION = '1.3.0-beta.1';
    private const OPTION = 'llms_txt_composer_settings';
    private const PREVIOUS = 'llms_txt_composer_previous_settings';
    private const CACHE = 'llms_txt_composer_document_cache';
    private const MAX_BYTES = 131072;

    public static function boot(): void {
        add_action('init', [__CLASS__, 'add_route']);
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_filter('redirect_canonical', [__CLASS__, 'skip_canonical'], 10, 2);
        add_action('template_redirect', [__CLASS__, 'serve']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('admin_post_amino_llms_save', [__CLASS__, 'save_settings']);
        add_action('admin_post_amino_llms_restore', [__CLASS__, 'restore_settings']);
        add_action('wp_ajax_amino_llms_find_posts', [__CLASS__, 'ajax_find_posts']);
        add_action('save_post', [__CLASS__, 'invalidate']);
        add_action('deleted_post', [__CLASS__, 'invalidate']);
        add_action('trashed_post', [__CLASS__, 'invalidate']);
        add_action('untrashed_post', [__CLASS__, 'invalidate']);
        add_action('set_object_terms', [__CLASS__, 'invalidate']);
        add_action('updated_post_meta', [__CLASS__, 'invalidate']);
        add_action('added_post_meta', [__CLASS__, 'invalidate']);
        add_action('deleted_post_meta', [__CLASS__, 'invalidate']);
        add_action('update_option_blogname', [__CLASS__, 'invalidate']);
        add_action('update_option_blogdescription', [__CLASS__, 'invalidate']);
        // The generated Home and permalink links depend on the site's public URL.
        add_action('update_option_home', [__CLASS__, 'invalidate']);
    }

    public static function activate(): void {
        // Copy settings once to a separate option namespace. The old plugin's
        // uninstall.php deletes amino_llms_*; this new copy must survive it.
        if (false === get_option(self::OPTION, false)) {
            $legacy = get_option('amino_llms_settings', false);
            if (is_array($legacy)) {
                update_option(self::OPTION, $legacy, false);
            }
        }
        if (false === get_option(self::PREVIOUS, false)) {
            $legacy_previous = get_option('amino_llms_previous_settings', false);
            if (is_array($legacy_previous)) {
                update_option(self::PREVIOUS, $legacy_previous, false);
            }
        }
        self::add_route();
        flush_rewrite_rules(false);
        // Activation deliberately leaves every existing root llms.txt untouched.
    }

    public static function deactivate(): void {
        flush_rewrite_rules(false);
    }

    public static function add_route(): void {
        add_rewrite_rule('^llms\.txt/?$', 'index.php?amino_llms_document=1', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'amino_llms_document';
        return $vars;
    }

    public static function skip_canonical($redirect, $requested) {
        return (string) get_query_var('amino_llms_document') === '1' ? false : $redirect;
    }

    public static function defaults(): array {
        return [
            'mode' => 'hybrid',
            'site_name' => (string) get_bloginfo('name'),
            'summary' => (string) get_bloginfo('description'),
            'introduction' => '',
            'manual_markdown' => '',
            'post_types' => ['page', 'post'],
            'items_per_type' => 8,
            'highlight_ids' => '',
        ];
    }

    public static function settings(): array {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        $s = array_merge(self::defaults(), $saved);
        $s['post_types'] = is_array($s['post_types']) ? $s['post_types'] : [];
        return $s;
    }

    public static function public_types(): array {
        $types = get_post_types(['public' => true], 'objects');
        unset($types['attachment']);
        return $types;
    }

    public static function clean_settings(array $input): array {
        $mode = sanitize_key((string) ($input['mode'] ?? 'hybrid'));
        if (!in_array($mode, ['automatic', 'manual', 'hybrid'], true)) {
            $mode = 'hybrid';
        }
        $available = array_keys(self::public_types());
        $raw_types = isset($input['post_types']) && is_array($input['post_types']) ? $input['post_types'] : [];
        $selected = array_values(array_unique(array_intersect($available, array_map('sanitize_key', $raw_types))));
        $ids = [];
        $raw_ids = $input['highlight_ids'] ?? '';
        $parts = is_array($raw_ids) ? $raw_ids : preg_split('/[\s,]+/', (string) $raw_ids, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts ?: [] as $part) {
            $id = (string) $part;
            if (preg_match('/^[1-9][0-9]*$/D', $id) && !in_array((int) $id, $ids, true)) {
                $ids[] = (int) $id;
            }
        }
        return [
            'mode' => $mode,
            'site_name' => self::limit(sanitize_text_field((string) ($input['site_name'] ?? '')), 160),
            'summary' => self::limit(sanitize_text_field((string) ($input['summary'] ?? '')), 500),
            // Markdown is plain text, not HTML. Preserve percent-encoded links and code fences.
            'introduction' => self::sanitize_markdown((string) ($input['introduction'] ?? ''), 12000),
            'manual_markdown' => self::sanitize_markdown((string) ($input['manual_markdown'] ?? ''), self::MAX_BYTES),
            'post_types' => $selected,
            'items_per_type' => max(1, min(25, absint($input['items_per_type'] ?? 8))),
            'highlight_ids' => implode(',', array_slice($ids, 0, 10)),
        ];
    }

    private static function sanitize_markdown(string $value, int $max_bytes): string {
        // Safe as text/plain; it is escaped as text when shown in WordPress admin.
        $value = wp_check_invalid_utf8($value, true);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return self::limit($value, $max_bytes);
    }

    private static function limit(string $value, int $bytes): string {
        if (strlen($value) <= $bytes) {
            return $value;
        }
        $short = substr($value, 0, $bytes);
        while ($short !== '' && preg_match('//u', $short) !== 1) {
            $short = substr($short, 0, -1);
        }
        return $short;
    }

    private static function md_label(string $value): string {
        $value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value));
        return str_replace(['\\', '[', ']'], ['\\\\', '\[', '\]'], $value);
    }

    private static function allowed_post(WP_Post $post): bool {
        if ($post->post_status !== 'publish' || $post->post_password !== '') {
            return false;
        }
        $type = get_post_type_object($post->post_type);
        if (!$type || !$type->public || $post->post_type === 'attachment') {
            return false;
        }
        if ((string) get_post_meta($post->ID, '_yoast_wpseo_meta-robots-noindex', true) === '1') {
            return false;
        }
        $rank_math = get_post_meta($post->ID, 'rank_math_robots', true);
        if ($rank_math === 'noindex' || (is_array($rank_math) && in_array('noindex', $rank_math, true))) {
            return false;
        }
        return (bool) apply_filters('amino_llms_include_post', true, $post);
    }

    private static function markdown_url(string $url): string {
        // Markdown destination parentheses must be encoded, not interpreted
        // as closing punctuation. Keep already encoded path characters intact.
        return str_replace(['(', ')'], ['%28', '%29'], esc_url_raw($url));
    }

    private static function link(WP_Post $post): string {
        $url = get_permalink($post);
        if (!is_string($url) || !in_array(strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return '';
        }
        if (strtolower((string) wp_parse_url($url, PHP_URL_HOST)) !== strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST))) {
            return '';
        }
        $title = self::md_label((string) get_the_title($post));
        return $title === '' ? '' : '- [' . $title . '](' . self::markdown_url($url) . ')';
    }

    public static function render(?array $settings = null): string {
        $s = $settings ?? self::settings();
        if ($s['mode'] === 'manual') {
            return trim((string) $s['manual_markdown']) . "\n";
        }
        $title = self::md_label((string) $s['site_name']);
        if ($title === '') {
            $title = self::md_label((string) get_bloginfo('name'));
        }
        $summary = self::md_label((string) $s['summary']);
        $lines = ['# ' . $title, ''];
        if ($summary !== '') {
            $lines[] = '> ' . $summary;
            $lines[] = '';
        }
        if ($s['mode'] === 'hybrid' && trim((string) $s['introduction']) !== '') {
            $lines[] = trim((string) $s['introduction']);
            $lines[] = '';
        }
        $lines[] = '## Main Pages';
        $lines[] = '';
        $lines[] = '- [Home](' . esc_url_raw(home_url('/')) . ')';
        $lines[] = '';

        $seen = [];
        $home_id = absint(get_option('page_on_front', 0));
        if ($home_id) {
            $seen[$home_id] = true;
        }
        $featured = [];
        foreach (explode(',', (string) $s['highlight_ids']) as $id) {
            if (!ctype_digit($id) || !(int) $id) {
                continue;
            }
            $post = get_post((int) $id);
            if (!($post instanceof WP_Post) || !self::allowed_post($post) || isset($seen[$post->ID])) {
                continue;
            }
            $row = self::link($post);
            if ($row !== '') {
                $featured[] = $row;
                $seen[$post->ID] = true;
            }
        }
        if ($featured) {
            $lines[] = '## Highlighted';
            $lines[] = '';
            array_push($lines, ...$featured);
            $lines[] = '';
        }

        $types = self::public_types();
        foreach ($s['post_types'] as $post_type) {
            if (!isset($types[$post_type])) {
                continue;
            }
            $items = [];
            // Work through a bounded window when recent posts are protected,
            // noindexed or already in Highlights. One query is enough in most cases.
            for ($batch = 0; $batch < 5; $batch++) {
                $posts = get_posts([
                    'post_type' => $post_type,
                    'post_status' => 'publish',
                    'posts_per_page' => 100,
                    'offset' => $batch * 100,
                    'orderby' => $post_type === 'page' ? ['menu_order' => 'ASC', 'title' => 'ASC'] : 'date',
                    'order' => 'DESC',
                    'ignore_sticky_posts' => true,
                    'suppress_filters' => false,
                ]);
                foreach ($posts as $post) {
                    if (!($post instanceof WP_Post) || isset($seen[$post->ID]) || !self::allowed_post($post)) {
                        continue;
                    }
                    $row = self::link($post);
                    if ($row === '') {
                        continue;
                    }
                    $items[] = $row;
                    $seen[$post->ID] = true;
                    if (count($items) >= (int) $s['items_per_type']) {
                        break;
                    }
                }
                if (count($items) >= (int) $s['items_per_type'] || count($posts) < 100) {
                    break;
                }
            }
            if ($items) {
                $heading = $post_type === 'post' ? 'Latest Articles' : ($post_type === 'page' ? 'Pages' : $types[$post_type]->labels->name);
                $lines[] = '## ' . self::md_label((string) $heading);
                $lines[] = '';
                array_push($lines, ...$items);
                $lines[] = '';
            }
        }
        return (string) apply_filters('amino_llms_rendered_document', trim(implode("\n", $lines)) . "\n", $s);
    }

    public static function valid_document(string $document): bool {
        return $document !== '' && strlen($document) <= self::MAX_BYTES &&
            strpos($document, "\0") === false && preg_match('//u', $document) === 1 &&
            preg_match('/\A#[ \t]+\S/u', $document) === 1;
    }

    public static function invalidate(): void {
        delete_transient(self::CACHE);
    }

    public static function current_document(): string {
        $cached = get_transient(self::CACHE);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        $document = self::render();
        if (self::valid_document($document)) {
            set_transient(self::CACHE, $document, 5 * MINUTE_IN_SECONDS);
        }
        return $document;
    }

    public static function serve(): void {
        if ((string) get_query_var('amino_llms_document') !== '1') {
            return;
        }
        $document = self::current_document();
        if (!self::valid_document($document)) {
            status_header(503);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'llms.txt is not configured yet.';
            exit;
        }
        status_header(200);
        header('Content-Type: text/plain; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This endpoint is text/plain Markdown, not HTML; escaping changes link syntax.
        echo $document;
        exit;
    }

    public static function admin_menu(): void {
        add_options_page('LLMs.txt Composer', 'LLMs.txt Composer', 'manage_options', 'llms-txt-composer', [__CLASS__, 'admin_page']);
    }

    public static function admin_assets(string $hook): void {
        if ($hook === 'settings_page_llms-txt-composer') {
            wp_enqueue_style('llms-txt-composer-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], self::VERSION);
            wp_enqueue_script('llms-txt-composer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', [], self::VERSION, true);
            wp_localize_script('llms-txt-composer-admin', 'llmsComposerSearch', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('amino_llms_featured_search'),
                'searching' => __('Searching published content…', 'llms-txt-composer'),
                'none' => __('No eligible public content found.', 'llms-txt-composer'),
                'choose' => __('Select a result to feature it.', 'llms-txt-composer'),
                'added' => __('Added. Save settings to publish your changes.', 'llms-txt-composer'),
                'limit' => __('You can feature up to 10 items.', 'llms-txt-composer'),
                'failed' => __('Search could not be completed. Try again.', 'llms-txt-composer'),
                'remove' => __('Remove', 'llms-txt-composer'),
                'min' => __('Enter at least two characters to search.', 'llms-txt-composer'),
            ]);
        }
    }

    /**
     * Search only eligible publicly published content. Results are capped and
     * revalidated server-side rather than trusting client-selected IDs.
     */
    public static function find_featured_posts(string $query): array {
        $query = trim(self::limit(sanitize_text_field($query), 80));
        if (strlen($query) < 2) {
            return [];
        }
        $types = self::public_types();
        if (!$types) {
            return [];
        }
        $posts = get_posts([
            'post_type' => array_keys($types),
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 60,
            'orderby' => 'relevance',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        ]);
        $results = [];
        $home_id = absint(get_option('page_on_front', 0));
        foreach ($posts as $post) {
            if (!($post instanceof WP_Post) || $post->ID === $home_id || !self::allowed_post($post)) {
                continue;
            }
            if (self::link($post) === '') {
                continue;
            }
            $results[] = [
                'id' => (int) $post->ID,
                'title' => self::limit(wp_strip_all_tags((string) get_the_title($post)), 160),
                'type' => (string) $types[$post->post_type]->labels->name,
            ];
            if (count($results) === 10) {
                break;
            }
        }
        return $results;
    }

    /** Admin-only AJAX lookup; never disclose drafts, protected or noindex posts. */
    public static function ajax_find_posts(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
            return;
        }
        if (false === check_ajax_referer('amino_llms_featured_search', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            return;
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized and length-limited in find_featured_posts().
        $term = isset($_GET['q']) && is_string($_GET['q']) ? wp_unslash($_GET['q']) : '';
        wp_send_json_success(self::find_featured_posts($term));
    }

    private static function authorize(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You cannot manage these settings.', 'llms-txt-composer'));
        }
    }

    private static function redirect(string $notice): void {
        wp_safe_redirect(add_query_arg('amino_llms_notice', $notice, admin_url('options-general.php?page=llms-txt-composer')));
        exit;
    }

    public static function save_settings(): void {
        self::authorize();
        check_admin_referer('amino_llms_save');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is sanitized in clean_settings().
        $input = isset($_POST['amino_llms']) && is_array($_POST['amino_llms']) ? wp_unslash($_POST['amino_llms']) : [];
        $clean = self::clean_settings($input);
        if (!self::valid_document(self::render($clean))) {
            self::redirect('invalid');
        }
        update_option(self::PREVIOUS, self::settings(), false);
        update_option(self::OPTION, $clean, false);
        self::invalidate();
        self::redirect('saved');
    }

    public static function restore_settings(): void {
        self::authorize();
        check_admin_referer('amino_llms_restore');
        $previous = get_option(self::PREVIOUS, null);
        if (!is_array($previous)) {
            self::redirect('nothing');
        }
        $current = self::settings();
        update_option(self::OPTION, $previous, false);
        update_option(self::PREVIOUS, $current, false);
        self::invalidate();
        self::redirect('restored');
    }

    public static function admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = self::settings();
        $preview = self::render($s);
        $root_file = trailingslashit(ABSPATH) . 'llms.txt';
        $static_file = is_file($root_file);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status notice.
        $notice = isset($_GET['amino_llms_notice']) ? sanitize_key(wp_unslash($_GET['amino_llms_notice'])) : '';
        echo '<div class="wrap llms-txt-composer-admin"><div class="llms-txt-composer-hero"><span class="llms-txt-composer-kicker">LLMS.TXT / COMPOSER</span><h1>' . esc_html__('Your website, clearly understood.', 'llms-txt-composer') . '</h1><p>' . esc_html__('Write an introduction that matters. Keep public content links up to date. No AI API, tracking or root file permissions.', 'llms-txt-composer') . '</p></div>';
        if ($notice) {
            $okay = in_array($notice, ['saved', 'restored'], true);
            echo '<div class="notice notice-' . esc_attr($okay ? 'success' : 'error') . '"><p>' . esc_html($notice === 'invalid' ? 'A valid document needs a # heading and must be smaller than 128 KiB.' : ucfirst($notice)) . '</p></div>';
        }
        if ($static_file) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('An existing physical llms.txt was detected. Your web server may serve that file instead of the virtual document. Import its contents manually before renaming or removing it. This plugin never deletes or overwrites that file.', 'llms-txt-composer') . '</p></div>';
        }
        if ((string) get_option('permalink_structure', '') === '') {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Pretty permalinks appear to be disabled. A virtual /llms.txt depends on WordPress rewrite rules and may not resolve on this server. Enable permalinks and test the public URL.', 'llms-txt-composer') . '</p></div>';
        }
        echo '<div class="llms-txt-composer-meta"><span>' . esc_html__('Delivery:', 'llms-txt-composer') . ' <strong>' . esc_html__('Virtual /llms.txt', 'llms-txt-composer') . '</strong></span><span>' . esc_html__('Mode:', 'llms-txt-composer') . ' <strong>' . esc_html(ucfirst((string) $s['mode'])) . '</strong></span><span>v' . esc_html(self::VERSION) . '</span></div>';
        echo '<div class="llms-txt-composer-grid"><div class="llms-txt-composer-panel"><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="amino_llms_save">';
        wp_nonce_field('amino_llms_save');
        echo '<h2>' . esc_html__('Editorial settings', 'llms-txt-composer') . '</h2><p><label for="al-mode"><strong>' . esc_html__('Document mode', 'llms-txt-composer') . '</strong></label><select class="widefat" id="al-mode" name="amino_llms[mode]">';
        foreach (['hybrid' => 'Hybrid: authored identity + current links', 'automatic' => 'Automatic: public WordPress content', 'manual' => 'Manual: your own Markdown'] as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($s['mode'], $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></p><div data-mode-show="automatic hybrid"><p><label for="al-name"><strong>' . esc_html__('Site name', 'llms-txt-composer') . '</strong></label><input id="al-name" class="widefat" name="amino_llms[site_name]" maxlength="160" value="' . esc_attr((string) $s['site_name']) . '"></p>';
        echo '<p><label for="al-summary"><strong>' . esc_html__('Short description', 'llms-txt-composer') . '</strong></label><textarea class="widefat" rows="3" id="al-summary" name="amino_llms[summary]">' . esc_textarea((string) $s['summary']) . '</textarea></p></div>';
        echo '<div data-mode-show="hybrid"><p><label for="al-intro"><strong>' . esc_html__('Editorial introduction (Hybrid)', 'llms-txt-composer') . '</strong></label><textarea class="widefat" rows="5" id="al-intro" name="amino_llms[introduction]">' . esc_textarea((string) $s['introduction']) . '</textarea></p></div>';
        echo '<div data-mode-show="manual"><p><label for="al-manual"><strong>' . esc_html__('Complete Markdown (Manual)', 'llms-txt-composer') . '</strong></label><textarea class="widefat llms-txt-composer-mono" rows="6" id="al-manual" name="amino_llms[manual_markdown]">' . esc_textarea((string) $s['manual_markdown']) . '</textarea></p></div>';
        echo '<div data-mode-show="automatic hybrid"><fieldset><legend><strong>' . esc_html__('Include public content types', 'llms-txt-composer') . '</strong></legend>';
        foreach (self::public_types() as $type) {
            echo '<label class="llms-txt-composer-choice"><input type="checkbox" name="amino_llms[post_types][]" value="' . esc_attr($type->name) . '" ' . checked(in_array($type->name, $s['post_types'], true), true, false) . '> ' . esc_html($type->labels->name) . '</label>';
        }
        echo '</fieldset><p><label for="al-count"><strong>' . esc_html__('Links per content type', 'llms-txt-composer') . '</strong></label><input type="number" class="small-text" min="1" max="25" id="al-count" name="amino_llms[items_per_type]" value="' . esc_attr((string) $s['items_per_type']) . '"></p>';
        echo '<div id="llms-composer-featured" class="llms-txt-composer-featured">';
        echo '<p><label for="al-featured-search"><strong>' . esc_html__('Search content to highlight', 'llms-txt-composer') . '</strong></label>';
        echo '<input class="widefat" type="search" id="al-featured-search" autocomplete="off" aria-controls="al-featured-results" aria-describedby="al-featured-status" placeholder="' . esc_attr__('Search posts, pages or public content types…', 'llms-txt-composer') . '"></p>';
        echo '<p id="al-featured-status" role="status" aria-live="polite" class="description"></p>';
        echo '<ul id="al-featured-results" class="llms-txt-composer-results"></ul>';
        echo '<p><strong>' . esc_html__('Selected highlights', 'llms-txt-composer') . '</strong> <span class="description">' . esc_html__('(up to 10)', 'llms-txt-composer') . '</span></p>';
        echo '<ul id="al-featured-selected" class="llms-txt-composer-selected">';
        foreach (explode(',', (string) $s['highlight_ids']) as $value) {
            if (!ctype_digit($value) || !(int) $value) {
                continue;
            }
            $featured_post = get_post((int) $value);
            if (!($featured_post instanceof WP_Post) || !self::allowed_post($featured_post)) {
                continue;
            }
            $label = (string) get_the_title($featured_post);
            // translators: %s: title of the published post or page removed from featured items.
            echo '<li data-featured-id="' . esc_attr((string) $featured_post->ID) . '"><span>' . esc_html($label) . '</span> <button type="button" data-featured-remove class="button-link" aria-label="' . esc_attr(sprintf(__('Remove %s', 'llms-txt-composer'), $label)) . '">' . esc_html__('Remove', 'llms-txt-composer') . '</button></li>';
        }
        echo '</ul><details class="llms-txt-composer-advanced"><summary>' . esc_html__('Advanced: edit post IDs manually', 'llms-txt-composer') . '</summary>';
        echo '<p><label for="al-high">' . esc_html__('Featured post IDs (comma-separated)', 'llms-txt-composer') . '</label><input class="widefat" id="al-high" name="amino_llms[highlight_ids]" value="' . esc_attr((string) $s['highlight_ids']) . '"></p></details></div></div>';
        submit_button(__('Save and update llms.txt', 'llms-txt-composer'));
        echo '</form>';
        if (is_array(get_option(self::PREVIOUS, null))) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="amino_llms_restore">';
            wp_nonce_field('amino_llms_restore');
            submit_button(__('Restore previous settings', 'llms-txt-composer'), 'secondary', 'submit', false);
            echo '</form>';
        }
        echo '</div><div class="llms-txt-composer-panel"><h2>' . esc_html__('Markdown preview', 'llms-txt-composer') . '</h2><p>' . esc_html__('Preview of your last saved settings. Save changes to update the public endpoint.', 'llms-txt-composer') . '</p>';
        echo '<textarea class="widefat llms-txt-composer-mono" rows="24" readonly aria-label="' . esc_attr__('Markdown preview', 'llms-txt-composer') . '">' . esc_textarea($preview) . '</textarea>';
        echo '<p class="description">' . esc_html(number_format_i18n(strlen($preview))) . ' bytes · <a href="' . esc_url(home_url('/llms.txt')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View public llms.txt', 'llms-txt-composer') . '</a></p>';
        echo '<p class="description">' . esc_html__('llms.txt is a discovery format, not a guarantee of AI indexing or citations.', 'llms-txt-composer') . '</p></div></div></div>';
    }
}

Amino_LLMS::boot();
register_activation_hook(__FILE__, ['Amino_LLMS', 'activate']);
register_deactivation_hook(__FILE__, ['Amino_LLMS', 'deactivate']);

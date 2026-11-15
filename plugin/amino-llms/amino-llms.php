<?php
/**
 * Plugin Name: Amino LLMS
 * Description: Create an editorial llms.txt with automatic, manual or hybrid WordPress content discovery.
 * Version: 1.2.0-beta.1
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Amino Studio
 * License: GPL-2.0-or-later
 * Text Domain: amino-llms
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Portable development engine.
 *
 * This separate plugin does not replace the site-specific v1.1.0 in production.
 * No file is created or adopted during installation.
 */
final class Amino_LLMS {
    private const OPT = 'amino_llms_v12_settings';
    private const HASH = 'amino_llms_v12_file_hash';
    private const BACKUP = 'amino_llms_v12_last_backup';
    private const ERROR = 'amino_llms_v12_last_error';
    private const CRON = 'amino_llms_v12_refresh';
    private const MAX_BYTES = 131072;

    public static function boot(): void {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('admin_post_amino_llms_save', [__CLASS__, 'save_settings']);
        add_action('admin_post_amino_llms_publish', [__CLASS__, 'publish']);
        add_action('admin_post_amino_llms_restore', [__CLASS__, 'restore']);
        add_action('save_post', [__CLASS__, 'on_content_change'], 30, 2);
        add_action('transition_post_status', [__CLASS__, 'on_status_change'], 30, 3);
        add_action('trashed_post', [__CLASS__, 'automatic_refresh']);
        add_action('deleted_post', [__CLASS__, 'automatic_refresh']);
        add_action(self::CRON, [__CLASS__, 'automatic_refresh']);
    }

    public static function activate(): void {
        if (!wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON);
        }
        // Never write to, delete, or take ownership of an existing llms.txt here.
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook(self::CRON);
    }

    public static function defaults(): array {
        return [
            'mode' => 'hybrid',
            'site_name' => (string) get_bloginfo('name'),
            'summary' => (string) get_bloginfo('description'),
            'introduction' => '',
            'manual_markdown' => '',
            'post_types' => ['page', 'post'],
            'items_per_type' => 6,
            'highlight_ids' => '',
            'auto_refresh' => true,
        ];
    }

    public static function settings(): array {
        $saved = get_option(self::OPT, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        $settings = array_merge(self::defaults(), $saved);
        $settings['post_types'] = is_array($settings['post_types']) ? $settings['post_types'] : [];
        return $settings;
    }

    private static function public_types(): array {
        $types = get_post_types(['public' => true], 'objects');
        unset($types['attachment']);
        return $types;
    }

    public static function clean_settings(array $input): array {
        $defaults = self::defaults();
        $modes = ['automatic', 'manual', 'hybrid'];
        $mode = sanitize_key((string) ($input['mode'] ?? 'hybrid'));
        $types = array_keys(self::public_types());
        $selected = array_values(array_intersect($types, array_map('sanitize_key', (array) ($input['post_types'] ?? []))));
        $ids = [];
        foreach (preg_split('/[\s,]+/', (string) ($input['highlight_ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $value) {
            if (preg_match('/^[0-9]+$/D', $value) && (int) $value > 0 && !in_array((int) $value, $ids, true)) {
                $ids[] = (int) $value;
            }
        }
        return [
            'mode' => in_array($mode, $modes, true) ? $mode : 'hybrid',
            'site_name' => substr(sanitize_text_field((string) ($input['site_name'] ?? $defaults['site_name'])), 0, 160),
            'summary' => substr(sanitize_text_field((string) ($input['summary'] ?? '')), 0, 500),
            'introduction' => substr(sanitize_textarea_field((string) ($input['introduction'] ?? '')), 0, 12000),
            'manual_markdown' => substr(sanitize_textarea_field((string) ($input['manual_markdown'] ?? '')), 0, self::MAX_BYTES),
            'post_types' => $selected,
            'items_per_type' => max(1, min(25, absint($input['items_per_type'] ?? 6))),
            'highlight_ids' => implode(',', array_slice($ids, 0, 10)),
            'auto_refresh' => !empty($input['auto_refresh']),
        ];
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
        if (!$type || !$type->public) {
            return false;
        }
        // WordPress, Yoast and Rank Math signals; extensible for other SEO plugins.
        if ((string) get_post_meta($post->ID, '_yoast_wpseo_meta-robots-noindex', true) === '1') {
            return false;
        }
        $rank = get_post_meta($post->ID, 'rank_math_robots', true);
        if ($rank === 'noindex' || (is_array($rank) && in_array('noindex', $rank, true))) {
            return false;
        }
        return (bool) apply_filters('amino_llms_include_post', true, $post);
    }

    private static function item(WP_Post $post): string {
        $url = get_permalink($post);
        if (!is_string($url) || !in_array(strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return '';
        }
        // Local URLs are not fetched: compare their hosts without an SSRF-style check.
        // wp_http_validate_url() would incorrectly reject legitimate localhost installations.
        if (strtolower((string) wp_parse_url($url, PHP_URL_HOST)) !== strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST))) {
            return '';
        }
        $label = self::md_label((string) get_the_title($post));
        return $label === '' ? '' : '- [' . $label . '](' . esc_url_raw($url) . ')';
    }

    public static function render_document(?array $settings = null): string {
        $s = $settings ?? self::settings();
        if (($s['mode'] ?? '') === 'manual') {
            return trim((string) ($s['manual_markdown'] ?? '')) . "\n";
        }
        $name = self::md_label((string) ($s['site_name'] ?? ''));
        $summary = self::md_label((string) ($s['summary'] ?? ''));
        $lines = ['# ' . $name, ''];
        if ($summary !== '') {
            $lines[] = '> ' . $summary;
            $lines[] = '';
        }
        if (($s['mode'] ?? '') === 'hybrid' && trim((string) ($s['introduction'] ?? '')) !== '') {
            $lines[] = trim((string) $s['introduction']);
            $lines[] = '';
        }

        $lines[] = '## Main Pages';
        $lines[] = '';
        $lines[] = '- [Home](' . esc_url_raw(home_url('/')) . ')';
        $lines[] = '';
        $seen = [];
        // Avoid a second link to the front page when WordPress uses a static page.
        $front_id = absint(get_option('page_on_front', 0));
        if ($front_id) {
            $seen[$front_id] = true;
        }
        $ids = preg_split('/,/', (string) ($s['highlight_ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $featured = [];
        foreach (array_slice($ids, 0, 10) as $id) {
            $p = get_post((int) $id);
            if (!($p instanceof WP_Post) || !self::allowed_post($p)) {
                continue;
            }
            $line = self::item($p);
            if ($line !== '' && !isset($seen[$p->ID])) {
                $featured[] = $line;
                $seen[$p->ID] = true;
            }
        }
        if ($featured) {
            $lines[] = '## Highlighted';
            $lines[] = '';
            array_push($lines, ...$featured);
            $lines[] = '';
        }
        $available = self::public_types();
        foreach ((array) ($s['post_types'] ?? []) as $type) {
            if (!isset($available[$type])) {
                continue;
            }
            $heading = $type === 'post' ? 'Latest Articles' : ($type === 'page' ? 'Pages' : $available[$type]->labels->name);
            $results = get_posts([
                'post_type' => $type,
                'post_status' => 'publish',
                'posts_per_page' => 100,
                'orderby' => $type === 'page' ? 'menu_order title' : 'date',
                'order' => 'DESC',
                'has_password' => false,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
            ]);
            $items = [];
            foreach ($results as $p) {
                if (!($p instanceof WP_Post) || isset($seen[$p->ID]) || !self::allowed_post($p)) {
                    continue;
                }
                $line = self::item($p);
                if ($line === '') {
                    continue;
                }
                $items[] = $line;
                $seen[$p->ID] = true;
                if (count($items) >= (int) ($s['items_per_type'] ?? 6)) {
                    break;
                }
            }
            if ($items) {
                $lines[] = '## ' . self::md_label($heading);
                $lines[] = '';
                array_push($lines, ...$items);
                $lines[] = '';
            }
        }
        return (string) apply_filters('amino_llms_rendered_document', trim(implode("\n", $lines)) . "\n", $s);
    }

    private static function path(): string|WP_Error {
        // Distinct public and WordPress install paths cannot reliably be mapped to one file.
        if (rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/') !== rtrim((string) wp_parse_url(site_url('/'), PHP_URL_PATH), '/')) {
            return new WP_Error('different_roots', 'WordPress and public site paths differ; file location must be configured before writing.');
        }
        return trailingslashit(ABSPATH) . 'llms.txt';
    }

    private static function filesystem(): object|WP_Error {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if (!WP_Filesystem() || !is_object($wp_filesystem) || $wp_filesystem->method !== 'direct') {
            return new WP_Error('unsupported_filesystem', 'Direct WordPress filesystem access is required for a safe local-file replacement.');
        }
        return $wp_filesystem;
    }

    private static function error(string $message): bool {
        update_option(self::ERROR, $message, false);
        return false;
    }

    private static function legacy_conflict(): bool {
        $legacy = 'amino-llms-dynamic/amino-llms-dynamic.php';
        $network = function_exists('get_site_option') ? (array) get_site_option('active_sitewide_plugins', []) : [];
        return in_array($legacy, (array) get_option('active_plugins', []), true) || array_key_exists($legacy, $network);
    }

    private static function validate_document(string $content): bool {
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            return self::error('The document is empty or exceeds 128 KiB.');
        }
        if (!preg_match('/^#[ \t]+\S/mu', $content)) {
            return self::error('An llms.txt document needs a top-level # heading.');
        }
        return true;
    }

    /**
     * Fail closed on unknown files or external modifications.
     * $adopt=true requires a checked admin form submission, never a cron job.
     */
    public static function write_document(string $content, bool $adopt = false): bool {
        if (self::legacy_conflict()) {
            return self::error('The legacy Amino Studio llms.txt plugin is active. Deactivate it before using this engine.');
        }
        if (!self::validate_document($content)) {
            return false;
        }
        $path = self::path();
        if (is_wp_error($path)) {
            return self::error($path->get_error_message());
        }
        $fs = self::filesystem();
        if (is_wp_error($fs)) {
            return self::error($fs->get_error_message());
        }
        if (is_link($path)) {
            return self::error('Refusing to overwrite an llms.txt symbolic link.');
        }
        $exists = $fs->exists($path);
        if ($exists && (!$fs->is_file($path) || !$fs->is_readable($path))) {
            return self::error('The existing llms.txt is not a readable regular file.');
        }
        $previous = $exists ? $fs->get_contents($path) : null;
        if ($exists && !is_string($previous)) {
            return self::error('The existing llms.txt could not be read.');
        }
        $known = (string) get_option(self::HASH, '');
        if ($exists && !$adopt && ($known === '' || !hash_equals($known, hash('sha256', $previous)))) {
            return self::error('An existing or externally modified llms.txt needs explicit adoption before replacement.');
        }
        if (!$exists && $known !== '' && !$adopt) {
            return self::error('The previously managed file was removed. Publish explicitly before recreating it.');
        }
        if (!$fs->is_writable(dirname($path)) || ($exists && !$fs->is_writable($path))) {
            return self::error('The llms.txt directory or file is not writable by PHP.');
        }
        if ($exists && $previous === $content) {
            update_option(self::HASH, hash('sha256', $content), false);
            delete_option(self::ERROR);
            return true;
        }
        if (is_string($previous) && strlen($previous) > self::MAX_BYTES) {
            return self::error('The previous llms.txt is too large for the built-in backup; refusing to replace it.');
        }
        $temp = dirname($path) . '/.amino-llms-' . wp_generate_uuid4() . '.tmp';
        $mode = $exists ? octdec((string) $fs->getchmod($path)) : 0644;
        if (!$mode) {
            $mode = 0644;
        }
        if (!$fs->put_contents($temp, $content, $mode)) {
            $fs->delete($temp);
            return self::error('Could not safely stage the new llms.txt in its destination directory.');
        }
        // WordPress' filesystem move(overwrite=true) DELETES the target before
        // renaming. For direct/local filesystem installations, POSIX rename()
        // performs an atomic replacement instead, keeping the old target intact
        // on failure. The temporary file is always created in the same directory.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Required to avoid deleting the published file before replacement is committed.
        if (!@rename($temp, $path)) {
            $fs->delete($temp);
            return self::error('Atomic replacement failed; the existing llms.txt was left intact.');
        }
        clearstatcache(true, $path);
        if ($fs->get_contents($path) !== $content) {
            return self::error('The written llms.txt failed readback verification.');
        }
        if ($exists) {
            update_option(self::BACKUP, ['content' => $previous, 'date' => current_time('mysql')], false);
        }
        update_option(self::HASH, hash('sha256', $content), false);
        delete_option(self::ERROR);
        return true;
    }

    public static function automatic_refresh(): void {
        $s = self::settings();
        if (empty($s['auto_refresh']) || get_option(self::HASH, '') === '') {
            return; // Nothing is owned by this plugin until explicit publication.
        }
        self::write_document(self::render_document($s), false);
    }

    public static function on_content_change(int $post_id, WP_Post $post): void {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        if (!in_array($post->post_type, self::settings()['post_types'], true)) {
            return;
        }
        self::automatic_refresh();
    }

    public static function on_status_change(string $new, string $old, WP_Post $post): void {
        if ($new !== $old && in_array($post->post_type, self::settings()['post_types'], true)) {
            self::automatic_refresh();
        }
    }

    public static function admin_menu(): void {
        add_options_page('Amino LLMS', 'Amino LLMS', 'manage_options', 'amino-llms', [__CLASS__, 'admin_page']);
    }

    public static function admin_assets(string $hook): void {
        if ($hook === 'settings_page_amino-llms') {
            wp_enqueue_style('amino-llms-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], '1.2.0');
        }
    }

    private static function authorize(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage this document.', 'amino-llms'));
        }
    }

    private static function redirect(string $state): void {
        wp_safe_redirect(add_query_arg('amino_llms_notice', $state, admin_url('options-general.php?page=amino-llms')));
        exit;
    }

    public static function save_settings(): void {
        self::authorize();
        check_admin_referer('amino_llms_save');
        // The received array is sanitized field-by-field by clean_settings().
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Centralized type-specific sanitization in clean_settings().
        $values = isset($_POST['amino_llms']) && is_array($_POST['amino_llms']) ? wp_unslash($_POST['amino_llms']) : [];
        update_option(self::OPT, self::clean_settings($values), false);
        self::redirect('saved');
    }

    public static function publish(): void {
        self::authorize();
        check_admin_referer('amino_llms_publish');
        $adopt = isset($_POST['amino_llms_confirm_replace']) && $_POST['amino_llms_confirm_replace'] === '1';
        self::redirect(self::write_document(self::render_document(), $adopt) ? 'published' : 'error');
    }

    public static function restore(): void {
        self::authorize();
        check_admin_referer('amino_llms_restore');
        $backup = get_option(self::BACKUP, null);
        if (!is_array($backup) || !is_string($backup['content'] ?? null)) {
            self::redirect('error');
        }
        // Restoring is an intentional publication of a saved previous version.
        $ok = self::write_document($backup['content'], true);
        if ($ok) {
            // A restored pre-plugin file is no longer managed by automatic updates.
            delete_option(self::HASH);
        }
        self::redirect($ok ? 'restored' : 'error');
    }

    public static function admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = self::settings();
        $preview = self::render_document($s);
        $path = self::path();
        $exists = !is_wp_error($path) && is_file($path);
        $tracked = get_option(self::HASH, '') !== '';
        $conflict = self::legacy_conflict();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice, no state change.
        $notice = isset($_GET['amino_llms_notice']) ? sanitize_key(wp_unslash($_GET['amino_llms_notice'])) : '';
        echo '<div class="wrap amino-llms-admin"><div class="amino-llms-hero"><span class="amino-llms-kicker">AMINO / LLMS</span><h1>A clear guide to your website.</h1><p>Build a concise, curated Markdown introduction for AI agents. Your website, your words, your control.</p></div>';
        if ($notice) {
            echo '<div class="notice notice-' . esc_attr($notice === 'error' ? 'error' : 'success') . '"><p>' . esc_html(ucfirst($notice)) . '</p></div>';
        }
        $err = get_option(self::ERROR, '');
        if ($err) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) $err) . '</p></div>';
        }
        if ($conflict) {
            echo '<div class="notice notice-warning"><p>The legacy Amino llms.txt plugin is active. This development engine will not write the file until the conflict is resolved.</p></div>';
        }
        if (is_wp_error($path)) {
            echo '<div class="notice notice-warning"><p>' . esc_html($path->get_error_message()) . '</p></div>';
        }
        echo '<div class="amino-llms-meta"><span>File: <strong>' . esc_html($exists ? 'Exists' : 'Not created') . '</strong></span><span>Ownership: <strong>' . esc_html($tracked ? 'Managed' : 'Not yet adopted') . '</strong></span><span>Version: <strong>1.2.0 beta</strong></span></div>';
        echo '<div class="amino-llms-grid"><div class="amino-llms-panel"><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="amino_llms_save">';
        wp_nonce_field('amino_llms_save');
        echo '<h2>Content &amp; editorial settings</h2>';
        echo '<p><label for="al-mode"><strong>Document mode</strong></label><select id="al-mode" name="amino_llms[mode]" class="widefat">';
        foreach (['hybrid'=>'Hybrid · written identity + live links', 'automatic'=>'Automatic · published content', 'manual'=>'Manual · full Markdown control'] as $value=>$label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($s['mode'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></p><p><label for="al-name"><strong>Website name</strong></label><input class="widefat" id="al-name" maxlength="160" name="amino_llms[site_name]" value="' . esc_attr($s['site_name']) . '"></p>';
        echo '<p><label for="al-summary"><strong>Short summary</strong></label><textarea class="widefat" id="al-summary" rows="3" name="amino_llms[summary]">' . esc_textarea($s['summary']) . '</textarea></p>';
        echo '<p><label for="al-intro"><strong>Editorial introduction (Hybrid)</strong></label><textarea class="widefat" id="al-intro" rows="5" name="amino_llms[introduction]">' . esc_textarea($s['introduction']) . '</textarea></p>';
        echo '<p><label for="al-manual"><strong>Full Markdown document (Manual)</strong></label><textarea class="widefat amino-llms-mono" id="al-manual" rows="7" name="amino_llms[manual_markdown]">' . esc_textarea($s['manual_markdown']) . '</textarea></p>';
        echo '<fieldset><legend><strong>Included public post types</strong></legend>';
        foreach (self::public_types() as $type) {
            echo '<label class="amino-llms-choice"><input type="checkbox" name="amino_llms[post_types][]" value="' . esc_attr($type->name) . '" ' . checked(in_array($type->name, $s['post_types'], true), true, false) . '> ' . esc_html($type->labels->name) . '</label>';
        }
        echo '</fieldset><p><label for="al-count"><strong>Maximum links per type</strong></label><input class="small-text" id="al-count" type="number" min="1" max="25" name="amino_llms[items_per_type]" value="' . esc_attr((string) $s['items_per_type']) . '"></p>';
        echo '<p><label for="al-featured"><strong>Highlighted post IDs</strong> (comma-separated, in editorial order)</label><input class="widefat" id="al-featured" name="amino_llms[highlight_ids]" value="' . esc_attr($s['highlight_ids']) . '"></p>';
        echo '<p><label><input type="checkbox" name="amino_llms[auto_refresh]" value="1" ' . checked(!empty($s['auto_refresh']), true, false) . '> Update an already managed file after content changes and hourly checks</label></p>';
        submit_button('Save settings');
        echo '</form></div><div class="amino-llms-panel"><h2>Live preview</h2><p>This preview reflects your last saved settings. Publishing is a separate action.</p>';
        echo '<textarea class="widefat amino-llms-mono" rows="20" readonly aria-label="Generated Markdown preview">' . esc_textarea($preview) . '</textarea>';
        echo '<p class="description">' . esc_html(number_format_i18n(strlen($preview))) . ' bytes · <a href="' . esc_url(home_url('/llms.txt')) . '" target="_blank" rel="noopener noreferrer">View public llms.txt</a></p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="amino_llms_publish">';
        wp_nonce_field('amino_llms_publish');
        if ($exists && !$tracked) {
            echo '<p><label><input type="checkbox" name="amino_llms_confirm_replace" value="1" required> I understand an existing llms.txt will be replaced, with its previous contents retained as a restore point.</label></p>';
        } elseif ($tracked) {
            echo '<p class="description">If the file was changed outside this plugin, publication requires a deliberate re-adoption.</p>';
            echo '<p><label><input type="checkbox" name="amino_llms_confirm_replace" value="1"> Re-adopt externally modified llms.txt (only if intentional)</label></p>';
        }
        submit_button('Publish llms.txt', 'primary', 'submit', false);
        echo '</form>';
        $backup = get_option(self::BACKUP, null);
        if (is_array($backup) && is_string($backup['content'] ?? null)) {
            echo '<hr><h3>Previous document</h3><p>Saved before the last replacement: ' . esc_html((string) ($backup['date'] ?? '')) . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="amino_llms_restore">';
            wp_nonce_field('amino_llms_restore');
            submit_button('Restore previous document', 'secondary', 'submit', false);
            echo '</form>';
        }
        echo '</div></div></div>';
    }
}

Amino_LLMS::boot();
register_activation_hook(__FILE__, ['Amino_LLMS', 'activate']);
register_deactivation_hook(__FILE__, ['Amino_LLMS', 'deactivate']);

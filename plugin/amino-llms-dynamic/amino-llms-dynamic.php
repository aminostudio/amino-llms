<?php
/**
 * Plugin Name: Amino Studio — Dynamic llms.txt
 * Description: Maintains an editorial llms.txt with ACTA highlights, recent articles and portfolio projects, independently of Yoast SEO.
 * Plugin URI: https://github.com/aminostudio/llms-txt-composer/tree/main/plugin/amino-llms-dynamic
 * Version: 1.1.1
 * Requires PHP: 8.0
 * Author: Amino Studio
 * Author URI: https://profiles.wordpress.org/aminogfx/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: amino-llms-dynamic
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Amino_Dynamic_LLMS {
    private const VERSION = '1.1.1';
    private const CRON = 'amino_llms_refresh';
    private const START = '<!-- AMINO-DYNAMIC:START -->';
    private const END = '<!-- AMINO-DYNAMIC:END -->';
    private const OPTION = 'amino_llms_highlighted_ids';
    private const MODE_OPTION = 'amino_llms_highlight_mode';
    private const ERROR = 'amino_llms_last_error';
    private const INSTALLED_VERSION = 'amino_llms_plugin_version';
    private const LAST_REFRESH = 'amino_llms_last_refresh';

    public static function boot(): void {
        add_action('init', [__CLASS__, 'maybe_upgrade'], 5);
        add_action(self::CRON, [__CLASS__, 'refresh']);
        add_action('save_post_post', [__CLASS__, 'on_post_save'], 30, 2);
        add_action('save_post_amino_project', [__CLASS__, 'on_post_save'], 30, 2);
        add_action('trashed_post', [__CLASS__, 'refresh']);
        add_action('untrashed_post', [__CLASS__, 'refresh']);
        add_action('deleted_post', [__CLASS__, 'refresh']);
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_notices', [__CLASS__, 'notice']);
    }

    private static function schedule(): void {
        if (!wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON);
        }
    }

    public static function activate(): void {
        self::schedule();
        if (get_option(self::OPTION, null) === null) {
            add_option(self::OPTION, '', '', false);
        }
        if (get_option(self::MODE_OPTION, null) === null) {
            add_option(self::MODE_OPTION, 'auto', '', false);
        }
        self::refresh();
        update_option(self::INSTALLED_VERSION, self::VERSION, false);
    }

    /** Also handles ordinary ZIP upgrades, which do not execute activation hooks. */
    public static function maybe_upgrade(): void {
        if (get_option(self::INSTALLED_VERSION, '') === self::VERSION) {
            return;
        }
        self::schedule();
        if (get_option(self::MODE_OPTION, null) === null) {
            // Existing 1.0.0 IDs are an editorial selection; never silently replace them.
            $mode = (string) get_option(self::OPTION, '') !== '' ? 'manual' : 'auto';
            add_option(self::MODE_OPTION, $mode, '', false);
        }
        self::refresh();
        update_option(self::INSTALLED_VERSION, self::VERSION, false);
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook(self::CRON);
    }

    public static function on_post_save(int $post_id, WP_Post $post): void {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        if (!in_array($post->post_type, ['post', 'amino_project'], true)) {
            return;
        }
        // Also refreshes when a previously published item becomes a draft.
        self::refresh();
    }

    private static function markdown_label(string $label): string {
        $label = html_entity_decode(wp_strip_all_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = (string) preg_replace('/\s+/u', ' ', trim($label));
        return str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $label);
    }

    private static function item(WP_Post $post, bool $with_date = false): string {
        $url = get_permalink($post);
        if (!is_string($url) || !str_starts_with($url, home_url('/'))) {
            return '';
        }
        $title = self::markdown_label((string) get_the_title($post));
        if ($title === '') {
            return '';
        }
        $date = $with_date ? ' (' . get_the_date('Y-m-d', $post) . ')' : '';
        return '- [' . $title . '](' . esc_url_raw($url) . ')' . $date;
    }

    private static function highlighted_ids(): array {
        $raw = (string) get_option(self::OPTION, '');
        $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            $id = absint($part);
            if ($id && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return array_slice($ids, 0, 8);
    }

    private static function dynamic_block(): string {
        $out = [
            self::START,
            '',
            '## ACTA — Independent Editorial Journal',
            '',
            'ACTA is Amino Studio’s independent French-language journal devoted to digital infrastructures, artificial intelligence, Big Tech, geopolitical power, cybersecurity, technological sovereignty and civil liberties. Its investigations and analyses are distinct from client portfolio work.',
            '',
            '- [Explore the ACTA journal](' . home_url('/acta/') . ')',
            '',
            '### Highlighted ACTA Articles',
            '',
        ];

        $seen = [];
        $ids = self::highlighted_ids();
        if ((string) get_option(self::MODE_OPTION, 'auto') !== 'manual' || !$ids) {
            // Automatic mode: the three most recent published ACTA articles.
            $automatic = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 3,
                'orderby' => 'date',
                'order' => 'DESC',
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
            ]);
            $ids = array_map(static function (WP_Post $post): int { return $post->ID; }, $automatic);
        }
        foreach ($ids as $id) {
            $post = get_post($id);
            if (!($post instanceof WP_Post) || $post->post_type !== 'post' || $post->post_status !== 'publish') {
                continue;
            }
            $line = self::item($post, true);
            if ($line !== '') {
                $out[] = $line;
                $seen[] = $id;
            }
        }
        if (!$seen) {
            $out[] = 'Explore the journal archive for current investigations and analysis.';
        }

        $out[] = '';
        $out[] = '### Latest ACTA Articles';
        $out[] = '';
        $out[] = 'Recent published articles not already listed in the highlights.';
        $out[] = '';
        $recent = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'post__not_in' => $seen,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        ]);
        $recent_count = 0;
        foreach ($recent as $post) {
            $line = self::item($post, true);
            if ($line !== '') {
                $out[] = $line;
                $recent_count++;
            }
        }
        if (!$recent_count) {
            $out[] = 'Browse the ACTA archive for all published articles.';
        }

        $out[] = '';
        $out[] = '## Recently Updated Portfolio Projects';
        $out[] = '';
        $out[] = 'The following published portfolio entries were most recently updated. Refer to individual project pages for the studio’s specific roles and collaborations.';
        $out[] = '';
        $projects = get_posts([
            'post_type' => 'amino_project',
            'post_status' => 'publish',
            'posts_per_page' => 8,
            'orderby' => 'modified',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);
        $project_count = 0;
        foreach ($projects as $project) {
            $line = self::item($project);
            if ($line !== '') {
                $out[] = $line;
                $project_count++;
            }
        }
        if (!$project_count) {
            $out[] = 'Browse the Work page for the published portfolio.';
        }
        $out[] = '';
        $out[] = self::END;
        return implode("\n", $out);
    }

    /** Return one H2 section, without touching any other editorial copy. */
    private static function section(string $document, string $heading): ?array {
        $pattern = '/^' . preg_quote($heading, '/') . '[ \t]*\R.*?(?=^## [^\r\n]+|\z)/ms';
        if (!preg_match($pattern, $document, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        return [$matches[0][0], $matches[0][1]];
    }

    private static function replace_section(string $document, array $match, string $new_section): string {
        return substr($document, 0, $match[1]) . $new_section . substr($document, $match[1] + strlen($match[0]));
    }

    /** One-time idempotent migration of the static llms.txt overview. */
    private static function ensure_editorial_sections(string $document): ?string {
        $main = self::section($document, '## Main Pages');
        $categories = self::section($document, '## Project Categories');
        if ($main === null || $categories === null) {
            self::error('Required editorial sections (Main Pages / Project Categories) were not found. No changes made.');
            return null;
        }

        $main_text = $main[0];
        if (!str_contains($main_text, home_url('/acta/'))) {
            $line = '- [ACTA — Editorial Journal](' . home_url('/acta/') . '): Independent French-language journal covering digital power, AI, cybersecurity, infrastructure and civil liberties.';
            $main_text = self::insert_after_link($main_text, home_url('/lab/'), $line);
        }
        if (!str_contains($main_text, home_url('/contact/'))) {
            $line = '- [Contact](' . home_url('/contact/') . '): Contact details and enquiries for the studio.';
            $main_text = self::insert_after_link($main_text, home_url('/acta/'), $line);
        }
        $document = self::replace_section($document, $main, $main_text);

        // Find again: Main Pages may have grown, changing later offsets.
        $categories = self::section($document, '## Project Categories');
        if ($categories === null) {
            self::error('Project Categories disappeared during migration. No changes made.');
            return null;
        }
        $cat_text = $categories[0];
        if (!str_contains($cat_text, home_url('/project-category/spaces/'))) {
            $line = '- [Spaces Projects](' . home_url('/project-category/spaces/') . ')';
            $cat_text = self::insert_after_link($cat_text, home_url('/project-category/photo/'), $line);
        }
        $document = self::replace_section($document, $categories, $cat_text);

        // The two published Spaces entries are part of the curated portfolio, not ACTA.
        if (self::section($document, '## Selected Spaces Projects') === null) {
            $categories = self::section($document, '## Project Categories');
            if ($categories === null) {
                self::error('Could not locate the project categories insertion point. No changes made.');
                return null;
            }
            $spaces = implode("\n", [
                '## Selected Spaces Projects',
                '',
                '- [Ziryab 1.0 — Al-Ándalus CS](' . home_url('/project/ziryab-1-0/') . '): Original spatial design project for Al-Ándalus CS in Castelló, Spain.',
                '- [Ziryab 2.0 — Al-Ándalus CS](' . home_url('/project/ziryab-2-0/') . '): Later extension of the Al-Ándalus CS project, following Ziryab 1.0.',
                '',
            ]);
            $document = substr($document, 0, $categories[1]) . $spaces . "\n" . substr($document, $categories[1]);
        }
        return $document;
    }

    /** Appends safely when the predecessor is missing; never changes existing list items. */
    private static function insert_after_link(string $section, string $after_url, string $line): string {
        $pattern = '/^(- \[[^\r\n]*\]\(' . preg_quote($after_url, '/') . '\)[^\r\n]*)(\R)/m';
        $updated = preg_replace_callback($pattern, static function (array $matches) use ($line): string {
            return $matches[1] . $matches[2] . $line . $matches[2];
        }, $section, 1, $count);
        if ($count === 1 && is_string($updated)) {
            return $updated;
        }
        return rtrim($section) . "\n" . $line . "\n\n";
    }

    private static function error(string $message): void {
        update_option(self::ERROR, $message, false);
    }

    private static function save_file(string $path, string $content, string $previous): bool {
        $length = strlen($content);
        $dir = dirname($path);
        if (is_writable($dir)) {
            // Atomic replacement prevents a crawler seeing a half-written document.
            $temp = @tempnam($dir, '.amino-llms-');
            if (is_string($temp) && dirname($temp) === $dir) {
                $mode = @fileperms($path);
                if ($mode !== false) {
                    @chmod($temp, $mode & 0777);
                }
                $written = @file_put_contents($temp, $content, LOCK_EX);
                if ($written === $length && @rename($temp, $path)) {
                    clearstatcache(true, $path);
                    if (@file_get_contents($path) === $content) {
                        return true;
                    }
                    @file_put_contents($path, $previous, LOCK_EX);
                    return false;
                }
                @unlink($temp);
            }
        }

        // Some hosts allow editing llms.txt but not creating sibling files.
        // Keep the previous bytes in memory and restore them on write failure.
        $written = @file_put_contents($path, $content, LOCK_EX);
        if ($written !== $length || @file_get_contents($path) !== $content) {
            @file_put_contents($path, $previous, LOCK_EX);
            return false;
        }
        return true;
    }

    public static function refresh(): bool {
        $path = trailingslashit(ABSPATH) . 'llms.txt';
        if (is_link($path) || !is_file($path) || !is_readable($path) || !is_writable($path)) {
            self::error('Root llms.txt is missing, is a symlink, or is not readable and writable by PHP. The existing file was not changed.');
            return false;
        }
        $original = @file_get_contents($path);
        if (!is_string($original)) {
            self::error('Could not read root llms.txt. No changes made.');
            return false;
        }
        if (substr_count($original, self::START) > 1 || substr_count($original, self::END) > 1) {
            self::error('Duplicate dynamic markers in llms.txt. No changes made.');
            return false;
        }
        $start = strpos($original, self::START);
        $end = strpos($original, self::END);
        if (($start === false) !== ($end === false) || ($start !== false && $end < $start)) {
            self::error('Invalid or incomplete dynamic markers. No changes made.');
            return false;
        }

        $editorial = self::ensure_editorial_sections($original);
        if ($editorial === null) {
            return false;
        }
        $block = self::dynamic_block();
        $start = strpos($editorial, self::START);
        $end = strpos($editorial, self::END);
        if ($start !== false && $end !== false) {
            $updated = substr($editorial, 0, $start) . $block . substr($editorial, $end + strlen(self::END));
        } else {
            $place = strpos($editorial, '## Marseille, France and International Work');
            if ($place === false) {
                self::error('The geography insertion point is missing. No changes made.');
                return false;
            }
            $updated = substr($editorial, 0, $place) . $block . "\n\n" . substr($editorial, $place);
        }

        if ($updated !== $original && !self::save_file($path, $updated, $original)) {
            self::error('Unable to write and verify llms.txt; the previous version was restored where possible.');
            return false;
        }
        delete_option(self::ERROR);
        update_option(self::LAST_REFRESH, current_time('mysql'), false);
        return true;
    }

    public static function menu(): void {
        add_options_page('Amino llms.txt', 'Amino llms.txt', 'manage_options', 'amino-llms', [__CLASS__, 'settings']);
    }

    public static function settings(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $result = null;
        if (isset($_POST['amino_llms_save'])) {
            check_admin_referer('amino_llms_save');
            $raw = isset($_POST['amino_llms_ids']) ? sanitize_text_field(wp_unslash($_POST['amino_llms_ids'])) : '';
            $ids = [];
            foreach (preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                $id = absint($part);
                if ($id && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
            update_option(self::OPTION, implode(',', array_slice($ids, 0, 8)), false);
            $mode = isset($_POST['amino_llms_mode']) && wp_unslash($_POST['amino_llms_mode']) === 'manual' ? 'manual' : 'auto';
            update_option(self::MODE_OPTION, $mode, false);
            $result = self::refresh();
        } elseif (isset($_POST['amino_llms_force'])) {
            check_admin_referer('amino_llms_force');
            $result = self::refresh();
        }
        echo '<div class="wrap"><h1>Amino Studio — Dynamic llms.txt <small>v' . esc_html(self::VERSION) . '</small></h1>';
        echo '<p>Preserves the editorial text. Keeps ACTA and Contact in Main Pages, adds the two Spaces projects, and refreshes ACTA highlights, recent articles and updated portfolio projects.</p>';
        if ($result !== null) {
            echo $result
                ? '<div class="notice notice-success"><p>llms.txt updated and verified.</p></div>'
                : '<div class="notice notice-error"><p>llms.txt was NOT updated. Review the error below.</p></div>';
        }
        echo '<p><strong>Last successful refresh:</strong> ' . esc_html((string) get_option(self::LAST_REFRESH, 'Never')) . '</p>';
        $error = get_option(self::ERROR, '');
        if ($error) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) $error) . '</p></div>';
        }
        echo '<form method="post">';
        wp_nonce_field('amino_llms_save');
        $mode = (string) get_option(self::MODE_OPTION, 'auto');
        echo '<p><label for="amino_llms_mode"><strong>Highlighted ACTA mode:</strong></label></p>';
        echo '<p><select id="amino_llms_mode" name="amino_llms_mode">';
        echo '<option value="auto"' . selected($mode, 'auto', false) . '>Automatic: three latest published articles</option>';
        echo '<option value="manual"' . selected($mode, 'manual', false) . '>Editorial: selected post IDs in fixed order</option>';
        echo '</select></p>';
        echo '<p><label for="amino_llms_ids"><strong>Editorially highlighted ACTA post IDs</strong> (up to 8, comma-separated, in editorial order; used in editorial mode):</label></p>';
        echo '<p><input class="regular-text" id="amino_llms_ids" name="amino_llms_ids" value="' . esc_attr((string) get_option(self::OPTION, '')) . '"></p>';
        echo '<p><button class="button button-primary" name="amino_llms_save" value="1">Save highlights and refresh</button></p></form>';
        echo '<p><strong>Published ACTA post IDs:</strong></p><ul>';
        $available = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 20, 'orderby' => 'date', 'order' => 'DESC']);
        foreach ($available as $post) {
            echo '<li><code>' . esc_html((string) $post->ID) . '</code> — ' . esc_html(get_the_title($post)) . '</li>';
        }
        echo '</ul>';
        echo '<form method="post">';
        wp_nonce_field('amino_llms_force');
        echo '<p><button class="button" name="amino_llms_force" value="1">Refresh now</button></p></form>';
        echo '<p><a href="' . esc_url(home_url('/llms.txt')) . '" target="_blank" rel="noopener noreferrer">View public llms.txt</a></p></div>';
    }

    public static function notice(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $error = get_option(self::ERROR, '');
        if ($error) {
            echo '<div class="notice notice-warning"><p><strong>Amino llms.txt:</strong> ' . esc_html((string) $error) . ' <a href="' . esc_url(admin_url('options-general.php?page=amino-llms')) . '">Settings</a></p></div>';
        }
    }
}

Amino_Dynamic_LLMS::boot();
register_activation_hook(__FILE__, ['Amino_Dynamic_LLMS', 'activate']);
register_deactivation_hook(__FILE__, ['Amino_Dynamic_LLMS', 'deactivate']);

<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
$root = sys_get_temp_dir() . '/amino-llms-virtual-' . getmypid() . '/';
if (!mkdir($root, 0700, true) && !is_dir($root)) {
    throw new RuntimeException('Fixture root failed');
}
define('ABSPATH', $root);
class WP_Post {
    public function __construct(
        public int $ID,
        public string $post_type,
        public string $post_status,
        public string $post_title,
        public string $post_name,
        public string $post_date,
        public string $post_password = ''
    ) {}
}
$GLOBALS['options'] = ['blogname' => 'Example Studio', 'blogdescription' => 'Digital craft and documentary practice', 'page_on_front' => 10];
$GLOBALS['transients'] = [];
$GLOBALS['routes'] = [];
$GLOBALS['flushed'] = 0;
$GLOBALS['post_meta'] = [
    4 => ['_yoast_wpseo_meta-robots-noindex' => '1'],
    6 => ['rank_math_robots' => ['noindex']],
];
$GLOBALS['posts'] = [
    new WP_Post(1, 'post', 'publish', 'A cultural investigation', 'investigation', '2026-11-15'),
    new WP_Post(2, 'post', 'publish', 'A second article', 'second', '2026-11-14'),
    new WP_Post(3, 'post', 'publish', 'Password story', 'secret', '2026-11-13', 'password'),
    new WP_Post(4, 'post', 'publish', 'Yoast hidden', 'yoast-hidden', '2026-11-12'),
    new WP_Post(5, 'post', 'private', 'Private story', 'private', '2026-11-11'),
    new WP_Post(6, 'post', 'publish', 'Rank Math hidden', 'rank-hidden', '2026-11-10'),
    new WP_Post(7, 'book', 'publish', 'Book on technology', 'books/tech', '2026-11-15'),
    new WP_Post(8, 'book', 'publish', 'Book about culture', 'books/culture', '2026-11-11'),
    new WP_Post(10, 'page', 'publish', 'Home page', 'home', '2026-10-01'),
    new WP_Post(11, 'page', 'publish', 'About the studio', 'about', '2026-10-02'),
];
$GLOBALS['types'] = [
    'post' => (object) ['name' => 'post', 'public' => true, 'labels' => (object) ['name' => 'Posts']],
    'page' => (object) ['name' => 'page', 'public' => true, 'labels' => (object) ['name' => 'Pages']],
    'book' => (object) ['name' => 'book', 'public' => true, 'labels' => (object) ['name' => 'Books']],
    'attachment' => (object) ['name' => 'attachment', 'public' => true, 'labels' => (object) ['name' => 'Media']],
    'private_cpt' => (object) ['name' => 'private_cpt', 'public' => false, 'labels' => (object) ['name' => 'Private']],
];
function add_action(...$args): void {}
function add_filter(...$args): void {}
function register_activation_hook(...$args): void {}
function register_deactivation_hook(...$args): void {}
function get_query_var($key): string {return (string) ($GLOBALS['query'][$key] ?? '');}
function add_rewrite_rule($rule, $query, $position): void {$GLOBALS['routes'][] = compact('rule', 'query', 'position');}
function flush_rewrite_rules($hard = true): void {$GLOBALS['flushed']++;}
function get_bloginfo($key): string {return (string) ($GLOBALS['options'][$key === 'name' ? 'blogname' : 'blogdescription'] ?? '');}
function get_option($key, $default = false): mixed {return $GLOBALS['options'][$key] ?? $default;}
function update_option($key, $value, $autoload = false): bool {$GLOBALS['options'][$key] = $value;return true;}
function delete_option($key): bool {unset($GLOBALS['options'][$key]);return true;}
function get_transient($key): mixed {return $GLOBALS['transients'][$key] ?? false;}
function set_transient($key, $value, $seconds): bool {$GLOBALS['transients'][$key] = $value;return true;}
function delete_transient($key): bool {unset($GLOBALS['transients'][$key]);return true;}
function get_post_types($args = [], $mode = 'names'): array {
    return array_filter($GLOBALS['types'], static fn($type) => $type->public === ($args['public'] ?? true));
}
function get_post_type_object($name): ?object {return $GLOBALS['types'][$name] ?? null;}
function get_post($id): ?WP_Post {foreach ($GLOBALS['posts'] as $post) {if ($post->ID === $id) return $post;}return null;}
function get_post_meta($id, $key, $single = true): mixed {return $GLOBALS['post_meta'][$id][$key] ?? '';}
function get_posts($args): array {
    $items = array_filter($GLOBALS['posts'], fn($post) => $post->post_type === $args['post_type'] && $post->post_status === 'publish');
    usort($items, fn($a, $b) => strcmp($b->post_date, $a->post_date));
    return array_slice($items, 0, $args['posts_per_page'] ?? 6);
}
function get_permalink(WP_Post $post): string {return 'https://example.test/' . $post->post_name . '/';}
function get_the_title(WP_Post $post): string {return $post->post_title;}
function home_url($path = '/'): string {return 'https://example.test' . $path;}
function wp_parse_url($url, $component = null): mixed {return parse_url($url, $component);}
function esc_url_raw($url): string {return $url;}
function wp_strip_all_tags($text): string {return strip_tags($text);}
function apply_filters($hook, $value, ...$args): mixed {return $value;}
function sanitize_key($value): string {return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));}
function sanitize_text_field($value): string {return trim(strip_tags((string) $value));}
function sanitize_textarea_field($value): string {return trim(strip_tags((string) $value));}
function wp_check_invalid_utf8($text, $strip = false): string {return (string) $text;}
function absint($v): int {return abs((int) $v);}
function trailingslashit($value): string {return rtrim($value, '/') . '/';}
require dirname(__DIR__) . '/plugin/amino-llms/amino-llms.php';

$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS ' . (++$checks) . ': ' . $message . PHP_EOL;
}
Amino_LLMS::activate();
$GLOBALS['query']['amino_llms_document']='1';
check(Amino_LLMS::skip_canonical('https://example.test/llms.txt/','https://example.test/llms.txt') === false, 'Canonical redirects disabled for virtual document');
unset($GLOBALS['query']['amino_llms_document']);
check($GLOBALS['flushed'] === 1, 'Activation flushes rewrite rules');
check($GLOBALS['routes'][0]['rule'] === '^llms\.txt/?$', 'Registered virtual llms.txt route');
check(!file_exists($root . 'llms.txt'), 'Activation creates no root file');
check(in_array('amino_llms_document', Amino_LLMS::query_vars([]), true), 'Public endpoint query var available');

$defaults = Amino_LLMS::defaults();
check($defaults['mode'] === 'hybrid', 'Hybrid is the default');
check($defaults['post_types'] === ['page', 'post'], 'Pages and posts are included by default');

$clean = Amino_LLMS::clean_settings([
    'mode' => 'manual',
    'site_name' => '<b>My Studio</b>',
    'summary' => 'A <strong>small</strong> website',
    'post_types' => ['book', 'page', 'private_cpt', 'attachment', 'book'],
    'items_per_type' => 500,
    'highlight_ids' => '1,2,1,invalid,-9',
    'manual_markdown' => '# My manually created llms.txt' . "\n\n" . '## Projects' . "\n" . '- [Encoded](https://example.test/my%20file.pdf)',
]);
check($clean['mode'] === 'manual', 'Manual mode recognized');
check($clean['post_types'] === ['page', 'book'], 'Nonpublic, media and repeated types excluded');
check($clean['items_per_type'] === 25, 'Per-type limit enforced');
check($clean['highlight_ids'] === '1,2', 'Highlights sanitized and deduplicated');
check($clean['summary'] === 'A small website', 'HTML stripped from short summary');
check(Amino_LLMS::valid_document(Amino_LLMS::render($clean)), 'Manual Markdown is accepted with a top heading');
check(str_contains(Amino_LLMS::render($clean), 'my%20file.pdf'), 'Manual Markdown preserves percent-encoded URLs');

$hybrid = Amino_LLMS::clean_settings([
    'mode' => 'hybrid',
    'site_name' => 'Example Studio',
    'summary' => 'Independent digital projects',
    'introduction' => 'Our editorial note.',
    'post_types' => ['page', 'post', 'book'],
    'items_per_type' => 6,
    'highlight_ids' => '2,4,3,5,7,1',
]);
$doc = Amino_LLMS::render($hybrid);
check(str_starts_with($doc, '# Example Studio'), 'Generated title');
check(str_contains($doc, '> Independent digital projects'), 'Generated summary');
check(str_contains($doc, 'Our editorial note.'), 'Written hybrid introduction');
check(str_contains($doc, '## Highlighted'), 'Editorial highlights section');
check(substr_count($doc, 'A second article') === 1, 'Featured item not repeated');
check(str_contains($doc, '## Books') && str_contains($doc, 'Book on technology'), 'Selected public CPT appears');
check(str_contains($doc, 'About the studio'), 'Published pages appear');
check(!str_contains($doc, 'Home page'), 'Front page not repeated');
check(!str_contains($doc, 'Yoast hidden'), 'Yoast noindex content excluded');
check(!str_contains($doc, 'Rank Math hidden'), 'Rank Math noindex content excluded');
check(!str_contains($doc, 'Password story'), 'Password-protected content excluded');
check(!str_contains($doc, 'Private story'), 'Private content excluded');
check(Amino_LLMS::valid_document($doc), 'Generated Markdown valid');
check(!Amino_LLMS::valid_document('This is not Markdown with a title'), 'Document without top heading rejected');
check(!Amino_LLMS::valid_document('# Title' . str_repeat('x', 140000)), 'Oversized document rejected');

update_option('amino_llms_settings', $hybrid);
$first = Amino_LLMS::current_document();
check($first === $doc, 'Public document generated from settings');
check($GLOBALS['transients']['amino_llms_document_cache'] === $doc, 'Document cached');
Amino_LLMS::invalidate();
check(!isset($GLOBALS['transients']['amino_llms_document_cache']), 'Cache invalidated on edits');
$automatic = $hybrid;
$automatic['mode'] = 'automatic';
check(!str_contains(Amino_LLMS::render($automatic), 'Our editorial note.'), 'Automatic mode omits hybrid introduction');
Amino_LLMS::deactivate();
check($GLOBALS['flushed'] === 2, 'Deactivation flushes rewrite rules');
check(!file_exists($root . 'llms.txt'), 'No filesystem edits after all operations');
rmdir($root);
echo "ALL $checks PORTABLE TESTS PASSED\n";

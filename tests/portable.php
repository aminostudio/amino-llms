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
$GLOBALS['actions'] = [];
function add_action(...$args): void {$GLOBALS['actions'][$args[0]][] = $args[1] ?? null;}
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
    $items = array_filter($GLOBALS['posts'], fn($post) =>
        in_array($post->post_type, (array) $args['post_type'], true) &&
        $post->post_status === 'publish' &&
        (empty($args['s']) || stripos($post->post_title, (string) $args['s']) !== false)
    );
    usort($items, fn($a, $b) => strcmp($b->post_date, $a->post_date));
    return array_slice($items, $args['offset'] ?? 0, $args['posts_per_page'] ?? 6);
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
function current_user_can($cap): bool {return $GLOBALS['ajax_admin'] ?? false;}
function check_ajax_referer($action, $query_arg, $die = true) {return ($GLOBALS['ajax_nonce_valid'] ?? false) ? 1 : false;}
function wp_unslash($value) {return $value;}
function wp_send_json_success($data): void {$GLOBALS['ajax_result'] = ['success' => true, 'data' => $data];}
function wp_send_json_error($data, $code = 400): void {$GLOBALS['ajax_result'] = ['success' => false, 'data' => $data, 'code' => $code];}

require dirname(__DIR__) . '/plugin/llms-txt-composer/llms-txt-composer.php';

$checks = 0;
class WP_Error {
    public function __construct(public string $code = 'probe_failure') {}
}
function is_wp_error($response): bool {return $response instanceof WP_Error;}
function wp_remote_retrieve_response_code($response): int {return (int) ($response['response']['code'] ?? 0);}
function wp_remote_retrieve_header($response, $name): string {return (string) ($response['headers'][strtolower($name)] ?? '');}
function wp_remote_retrieve_body($response): string {return (string) ($response['body'] ?? '');}
function wp_safe_remote_get($url, $args = []): array {
    $GLOBALS['probe_args'] = ['url' => $url, 'options' => $args];
    return $GLOBALS['probe_response'] ?? ['response' => ['code' => 200], 'headers' => [], 'body' => ''];
}
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS ' . (++$checks) . ': ' . $message . PHP_EOL;
}
$GLOBALS['options']['amino_llms_settings'] = ['mode' => 'manual', 'manual_markdown' => '# Old configuration'];
$GLOBALS['options']['amino_llms_previous_settings'] = ['mode' => 'hybrid', 'introduction' => 'Previously saved'];
Amino_LLMS::activate();
check(get_option('llms_txt_composer_settings')['mode'] === 'manual', 'Legacy settings migrated to isolated composer option');
check(get_option('llms_txt_composer_previous_settings')['introduction'] === 'Previously saved', 'Previous settings snapshot migrated');
check(get_option('amino_llms_settings')['mode'] === 'manual', 'Legacy configuration preserved for old plugin');
$GLOBALS['query']['amino_llms_document']='1';
check(Amino_LLMS::skip_canonical('https://example.test/llms.txt/','https://example.test/llms.txt') === false, 'Canonical redirects disabled for virtual document');
unset($GLOBALS['query']['amino_llms_document']);
check($GLOBALS['flushed'] === 1, 'Activation flushes rewrite rules');
check($GLOBALS['routes'][0]['rule'] === '^llms\.txt/?$', 'Registered virtual llms.txt route');
check(!file_exists($root . 'llms.txt'), 'Activation creates no root file');
$admin_css = file_get_contents(dirname(__DIR__) . '/plugin/llms-txt-composer/assets/admin.css');
check(is_string($admin_css) && str_contains($admin_css, '.llms-txt-composer-admin'), 'Renamed admin stylesheet matches the plugin HTML');
check(!str_contains($admin_css, '.amino-llms-'), 'Old admin CSS selectors do not survive public rename');
check(in_array('amino_llms_document', Amino_LLMS::query_vars([]), true), 'Public endpoint query var available');

$defaults = Amino_LLMS::defaults();
check($defaults['mode'] === 'hybrid', 'Hybrid is the default');
check($defaults['post_types'] === ['page', 'post'], 'Pages and posts are included by default');

$public_ok = [
    'response' => ['code' => 200],
    'headers' => ['content-type' => 'text/plain; charset=UTF-8', 'x-llms-txt-composer' => '2.0.0-alpha.1'],
    'body' => "# LAB Preview\n\n## Main Pages\n",
];
check(Amino_LLMS::assess_public_response($public_ok)['state'] === 'verified', 'Live probe confirms its own signed Markdown HTTP response');
$blocked = [
    'response' => ['code' => 503],
    'headers' => ['content-type' => 'text/html'],
    'body' => '<html>Under Construction</html>',
];
check(Amino_LLMS::assess_public_response($blocked)['state'] === 'intercepted', 'Live probe detects HTML from Under Construction instead of Composer');
$bad = $public_ok;
$bad['response']['code'] = 503;
check(Amino_LLMS::assess_public_response($bad)['state'] === 'invalid', 'Signed HTTP 503 from Composer is classified as invalid output');
check(Amino_LLMS::assess_public_response(new WP_Error())['state'] === 'unreachable', 'Blocked site loopback produces safe unreachable status');
$GLOBALS['probe_response'] = $public_ok;
Amino_LLMS::probe_public_endpoint();
check(
    $GLOBALS['probe_args']['url'] === 'https://example.test/llms.txt' &&
    $GLOBALS['probe_args']['options']['redirection'] === 0 &&
    $GLOBALS['probe_args']['options']['limit_response_size'] === 4096,
    'Live probe targets only the local public endpoint without redirects'
);
$GLOBALS['ajax_admin'] = false;
$GLOBALS['ajax_nonce_valid'] = true;
Amino_LLMS::ajax_probe();
check(!$GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['code'] === 403, 'Public-route probe denies non-admin users');
$GLOBALS['ajax_admin'] = true;
$GLOBALS['ajax_nonce_valid'] = false;
Amino_LLMS::ajax_probe();
check(!$GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['code'] === 403, 'Public-route probe denies invalid nonce');
$GLOBALS['ajax_nonce_valid'] = true;
Amino_LLMS::ajax_probe();
check($GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['data']['state'] === 'verified', 'Authorized public route probe returns classified HTTP findings');

check(
    Amino_LLMS::delivery_diagnostics("# Ready\n", false, true) === ['markdown' => true, 'permalinks' => true, 'root_file' => true],
    'Delivery checks accept valid Markdown, pretty permalinks and no physical-file conflict'
);
check(
    Amino_LLMS::delivery_diagnostics('Invalid without heading', true, false) === ['markdown' => false, 'permalinks' => false, 'root_file' => false],
    'Delivery checks flag invalid, plain-permalink and physical-file conflict states'
);

$matches = Amino_LLMS::find_featured_posts('technology');
check(count($matches) === 1 && $matches[0]['id'] === 7 && $matches[0]['type'] === 'Books', 'Editor search finds public CPT by title');
check([] === Amino_LLMS::find_featured_posts('Yoast hidden'), 'Editor search hides Yoast noindex entries');
check([] === Amino_LLMS::find_featured_posts('Rank Math hidden'), 'Editor search hides Rank Math noindex entries');
check([] === Amino_LLMS::find_featured_posts('Password story'), 'Editor search hides password-protected entries');
check([] === Amino_LLMS::find_featured_posts('Private story'), 'Editor search hides private entries');
check([] === Amino_LLMS::find_featured_posts('Home page'), 'Editor search skips front page duplicated by generator');
check([] === Amino_LLMS::find_featured_posts('a'), 'Editor search refuses single-character terms');

foreach (range(2000, 2079) as $id) {
    $GLOBALS['posts'][] = new WP_Post($id, 'post', 'publish', 'Hidden topic ' . $id, 'hidden-' . $id, '2026-12-01');
    $GLOBALS['post_meta'][$id] = ['_yoast_wpseo_meta-robots-noindex' => '1'];
}
$GLOBALS['posts'][] = new WP_Post(2080, 'post', 'publish', 'Visible topic document', 'visible-topic', '2026-10-01');
$found_deep = Amino_LLMS::find_featured_posts('topic');
check(count($found_deep) === 1 && 2080 === $found_deep[0]['id'], 'Featured search scans beyond 60 excluded hits without leaking noindex records');
$GLOBALS['posts'] = array_values(array_filter($GLOBALS['posts'], static fn($post) => $post->ID < 2000));
foreach (range(2000, 2079) as $id) {
    unset($GLOBALS['post_meta'][$id]);
}

$GLOBALS['ajax_admin'] = false;
$GLOBALS['ajax_nonce_valid'] = true;
$_GET['q'] = 'technology';
Amino_LLMS::ajax_find_posts();
check(!$GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['code'] === 403, 'AJAX rejects unauthorized users');
$GLOBALS['ajax_admin'] = true;
$GLOBALS['ajax_nonce_valid'] = false;
Amino_LLMS::ajax_find_posts();
check(!$GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['code'] === 403, 'AJAX rejects invalid nonce');
$GLOBALS['ajax_nonce_valid'] = true;
Amino_LLMS::ajax_find_posts();
check($GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['data'][0]['id'] === 7, 'Authorized AJAX returns eligible published content');
$_GET['q'] = ['unexpected' => 'array'];
Amino_LLMS::ajax_find_posts();
check($GLOBALS['ajax_result']['success'] && $GLOBALS['ajax_result']['data'] === [], 'AJAX safely handles malformed search input');
unset($_GET['q']);


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
$described = Amino_LLMS::clean_settings([
    'mode' => 'hybrid',
    'site_name' => 'Example Studio',
    'post_types' => ['post', 'book'],
    'highlight_ids' => '7,2,4,3,7',
    'featured_descriptions' => [
        '7' => 'Editorially selected [research] and <b>context</b>',
        '2' => 'Second story - background',
        '4' => 'Should not show noindex',
        '3' => ['nested attack'],
        '88' => 'Unselected item',
    ],
]);
check($described['highlight_ids'] === '7,2,4,3', 'v2 retains curated user order and deduplicates legacy IDs');
check($described['featured_descriptions'][7] === 'Editorially selected [research] and context', 'v2 descriptions strip HTML but preserve safe editorial characters');
check(!isset($described['featured_descriptions'][88]) && !isset($described['featured_descriptions'][3]), 'v2 ignores unselected and malformed nested descriptions');
$featured_doc = Amino_LLMS::render($described);
check(str_contains($featured_doc, '[Book on technology](https://example.test/books/tech/): Editorially selected \\[research\\] and context'), 'v2 emits escaped Markdown descriptions only for public selected content');
check(!str_contains($featured_doc, 'Should not show noindex'), 'v2 does not leak descriptions attached to noindex entries');
check(strpos($featured_doc, 'Book on technology') < strpos($featured_doc, 'A second article'), 'v2 preserves user-defined featured link order in Markdown');

check($clean['summary'] === 'A small website', 'HTML stripped from short summary');

$previous_handler = set_error_handler(
    static function (int $severity, string $message): bool {
        throw new RuntimeException('Malformed editor payload triggered a PHP warning: ' . $message);
    }
);
try {
    $bad = Amino_LLMS::clean_settings([
        'mode' => ['manual'],
        'site_name' => ['nested' => '<b>Injected</b>'],
        'summary' => ['unexpected'],
        'introduction' => ['unexpected'],
        'manual_markdown' => ['unexpected'],
        'post_types' => [['post'], 'post', 'page', ['private_cpt']],
        'items_per_type' => ['500'],
        'highlight_ids' => [['2'], '1', 2, ['3']],
        'featured_descriptions' => ['1' => ['malformed'], '2' => '<strong>Good</strong>', '99' => 'Unselected'],
    ]);
} finally {
    restore_error_handler();
}
check(
    $bad['mode'] === 'hybrid' && $bad['site_name'] === '' &&
    $bad['summary'] === '' && $bad['introduction'] === '' &&
    $bad['manual_markdown'] === '' && $bad['items_per_type'] === 8,
    'Malformed nested editor values fall back safely without PHP warnings'
);
check(
    $bad['post_types'] === ['post', 'page'] && $bad['highlight_ids'] === '1,2' &&
    $bad['featured_descriptions'] === [2 => 'Good'],
    'Malformed post types and highlight IDs are ignored without losing valid selections'
);

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
$odd = new WP_Post(90, 'post', 'publish', 'Story with (parentheses)', 'a(test)', '2026-11-10');
$GLOBALS['posts'][] = $odd;
check(str_contains(Amino_LLMS::render($hybrid), '/a%28test%29/'), 'Generated Markdown safely encodes URL parentheses');
array_pop($GLOBALS['posts']);

// The 100 newest results can all be excluded; the generator must keep looking.
foreach (range(1000, 1104) as $id) {
    $GLOBALS['posts'][] = new WP_Post($id, 'post', 'publish', 'Noindex ' . $id, 'excluded-' . $id, '2026-12-01');
    $GLOBALS['post_meta'][$id] = ['_yoast_wpseo_meta-robots-noindex' => '1'];
}
$scanning = $hybrid;
$scanning['highlight_ids'] = '';
$scanning['post_types'] = ['post'];
$scanned = Amino_LLMS::render($scanning);
check(str_contains($scanned, 'A cultural investigation'), 'Paged discovery finds public content after 100+ excluded posts');
check(!str_contains($scanned, 'Noindex 1000'), 'Paged discovery never includes noindexed posts');
$GLOBALS['posts'] = array_values(array_filter($GLOBALS['posts'], static fn($p) => $p->ID < 1000));

check(Amino_LLMS::valid_document($doc), 'Generated Markdown valid');
check(!Amino_LLMS::valid_document('This is not Markdown with a title'), 'Document without top heading rejected');
check(!Amino_LLMS::valid_document("Unexpected preface\n# Later heading"), 'Heading later in document cannot satisfy top heading requirement');
check(Amino_LLMS::valid_document("# Valid heading\n\n## Section\nContent"), 'First-line heading and later sections are accepted');
check(!Amino_LLMS::valid_document("# Title\n" . chr(0) . "hidden"), 'NUL byte rejected in public document');
check(!Amino_LLMS::valid_document("# Title\n" . chr(255)), 'Invalid UTF-8 rejected in public document');
check(!Amino_LLMS::valid_document('# Title' . str_repeat('x', 140000)), 'Oversized document rejected');

update_option('llms_txt_composer_settings', $hybrid);
$first = Amino_LLMS::current_document();
check($first === $doc, 'Public document generated from settings');
check($GLOBALS['transients']['llms_txt_composer_document_cache'] === $doc, 'Document cached');
check(isset($GLOBALS['actions']['update_option_home']), 'Public home URL changes register a cache invalidation hook');
foreach ($GLOBALS['actions']['update_option_home'] as $callback) {
    $callback();
}
check(!isset($GLOBALS['transients']['llms_txt_composer_document_cache']), 'Changing the WordPress home URL clears stale llms.txt links');
Amino_LLMS::current_document();
Amino_LLMS::invalidate();
check(!isset($GLOBALS['transients']['llms_txt_composer_document_cache']), 'Cache invalidated on edits');
$automatic = $hybrid;
$automatic['mode'] = 'automatic';
check(!str_contains(Amino_LLMS::render($automatic), 'Our editorial note.'), 'Automatic mode omits hybrid introduction');
Amino_LLMS::deactivate();
check($GLOBALS['flushed'] === 2, 'Deactivation flushes rewrite rules');
check(!file_exists($root . 'llms.txt'), 'No filesystem edits after all operations');
rmdir($root);
echo "ALL $checks PORTABLE TESTS PASSED\n";

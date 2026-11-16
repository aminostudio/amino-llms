<?php
/**
 * WordPress.org release metadata regression checks.
 *
 * Run with: php tests/release-metadata.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$plugin_path = $root . '/plugin/llms-txt-composer/llms-txt-composer.php';
$readme_path = $root . '/plugin/llms-txt-composer/readme.txt';

$plugin = file_get_contents($plugin_path);
$readme = file_get_contents($readme_path);
if (false === $plugin || false === $readme) {
    fwrite(STDERR, "Release metadata files are missing or unreadable.\n");
    exit(1);
}

/** Extract a field from the opening WordPress plugin docblock. */
function plugin_header(string $source, string $field): string
{
    $pattern = '/^[ \t]*\*[ \t]*' . preg_quote($field, '/') . ':[ \t]*(.*?)[ \t]*$/m';
    return preg_match($pattern, $source, $matches) ? trim($matches[1]) : '';
}

/** Extract a WordPress.org readme header field. */
function directory_header(string $source, string $field): string
{
    $pattern = '/^' . preg_quote($field, '/') . ':[ \t]*(.*?)[ \t]*$/mi';
    return preg_match($pattern, $source, $matches) ? trim($matches[1]) : '';
}

$plugin_name = plugin_header($plugin, 'Plugin Name');
$plugin_version = plugin_header($plugin, 'Version');
$readme_version = directory_header($readme, 'Stable tag');
$plugin_uri = plugin_header($plugin, 'Plugin URI');
$author_uri = plugin_header($plugin, 'Author URI');
$text_domain = plugin_header($plugin, 'Text Domain');
$contributor_list = directory_header($readme, 'Contributors');

$failures = [];
if ('LLMs.txt Composer' !== $plugin_name) {
    $failures[] = 'Unexpected WordPress plugin display name.';
}
if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-.][a-zA-Z0-9.-]+)?$/', $plugin_version)) {
    $failures[] = 'Missing or malformed plugin Version header.';
}
if ($readme_version !== $plugin_version) {
    $failures[] = 'readme.txt Stable tag does not match the PHP plugin Version.';
}
if ('llms-txt-composer' !== $text_domain) {
    $failures[] = 'Text Domain must match the distribution slug.';
}
if ('aminostudiofr' !== $contributor_list) {
    $failures[] = 'WordPress.org Contributors must name the verified aminostudiofr account.';
}
if ('' === $author_uri || !filter_var($author_uri, FILTER_VALIDATE_URL)) {
    $failures[] = 'Author URI must be a valid URL.';
}
if ('' !== $plugin_uri) {
    if (!filter_var($plugin_uri, FILTER_VALIDATE_URL)) {
        $failures[] = 'Plugin URI must be a valid URL.';
    } elseif (rtrim($plugin_uri, '/') === rtrim($author_uri, '/')) {
        $failures[] = 'WordPress.org rejects identical Plugin URI and Author URI.';
    }
}
if ($plugin_uri !== 'https://github.com/aminostudio/llms-txt-composer') {
    $failures[] = 'Plugin URI must reference the current renamed repository.';
}

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "WordPress.org metadata consistency passed: {$plugin_name} {$plugin_version} (aminostudiofr).\n";

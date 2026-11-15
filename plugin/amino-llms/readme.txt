=== Amino LLMS ===
Contributors: aminostudio
Tags: llms-txt, markdown, ai, content
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A clear, human-guided llms.txt for WordPress. Automatic, Manual and Hybrid modes. No external AI service.

== Description ==

**Amino LLMS** creates a simple Markdown guide at **/llms.txt** so compatible AI agents and researchers can navigate the most relevant parts of your site.

* **Automatic:** generate a short site introduction and updated public links from WordPress.
* **Manual:** write and maintain your own complete Markdown document.
* **Hybrid:** combine your own editorial introduction with automatically updated links.
* Choose which public content types appear (posts, pages, custom post types).
* Highlight a small number of selected posts and set a per-type link limit.
* Preview the resulting Markdown in WordPress administration.
* Avoid private, draft, password-protected and commonly marked noindex content.
* Save and restore the previous settings.
* No file writes, external API keys, remote calls or telemetry.

**Lightweight delivery:** The document is served through the WordPress rewrite system. No writable server-root directory or scheduled jobs are necessary.

**Important:** When a physical llms.txt already exists on the server, the web server may serve it before WordPress. The plugin warns about this but never deletes or changes the existing file. Other llms.txt-generating plugins may also conflict; use only one llms.txt provider at a time.

The llms.txt format is a discovery convention. It does not guarantee model ingestion, indexation, ranking or citation.

== Installation ==

1. Upload the **amino-llms** folder to /wp-content/plugins/ (or install the ZIP in Plugins > Add New > Upload Plugin).
2. Activate **Amino LLMS**.
3. Open **Settings > Amino LLMS**.
4. Choose Automatic, Manual, or Hybrid. Save your settings.
5. Visit your site's /llms.txt and check the Markdown output.

If an older physical llms.txt exists, first copy its contents to a safe location. Only then remove or rename it to allow WordPress to handle the URL. Always disable a conflicting generator before proceeding.

== Frequently Asked Questions ==

= Does it send my content to an AI service? =

No. Generation uses only WordPress content and configuration on your server.

= Does llms.txt guarantee better AI search visibility? =

No. The convention lets compatible agents discover selected site resources; engines decide independently how to use it.

= Will it delete an existing llms.txt? =

Never. This plugin serves a virtual endpoint. Existing files are left untouched.

= Does it work with custom post types? =

Yes, publicly registered post types appear as options. The administrator controls which types are included.

= Do I need Yoast SEO? =

No. Amino LLMS is independent. Disable other llms.txt generators to avoid URL conflicts.

== Changelog ==

= 1.2.1 =
* Pagination past the first 100 WordPress results when filtering out noindex and protected content.
* Markdown-safe URLs with parentheses encoded.
* Permalink diagnostic in the WordPress settings screen.

= 1.2.0 =
* New generic virtual /llms.txt endpoint: no root-file writes or cron.
* Automatic, Manual and Hybrid document modes.
* Configurable public content types, highlighted links and link limits.
* Markdown preview, saved-setting rollback, sanitization and caching.
* Protection from accidental disclosure of draft, private, password-protected and common noindex content.
* Admin interface and WordPress rewrite activation/deactivation.

== Upgrade Notice ==

= 1.2.1 =
New generic implementation. The older site-specific Amino Studio Dynamic llms.txt v1.1 is a separate plugin, not an automatic update path.

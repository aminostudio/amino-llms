=== Amino LLMS ===
Contributors: aminostudio
Tags: llms, llms-txt, ai, markdown, editorial
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.0-beta.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create an editorial llms.txt with automatic, manual and hybrid modes.

== Description ==

Amino LLMS is a development preview for site owners who want an editorially useful llms.txt document.

* Automatic: concise introduction and updated links to published content.
* Manual: edit the complete Markdown document yourself.
* Hybrid: write your own introduction and automatically maintain public links.
* Configure public post types, selected highlights and per-type limits.
* Preview before publishing.
* Preserve existing files until an administrator explicitly approves adoption.
* Stop automatic writes if an external editor changes the file.
* Keep a single WordPress-option restore point for the previous document.

The current beta supports direct root-file writes only when public and WordPress paths match, and refuses to run alongside the legacy Amino Studio llms plugin. Backup and staging verification are strongly recommended.

The llms.txt format does not guarantee AI indexing, citations or rankings.

== Installation ==

1. For development/staging only, upload the plugin/amino-llms directory.
2. Activate the plugin and visit Settings > Amino LLMS.
3. Choose a mode and save your settings.
4. Review the generated Markdown and explicitly publish it.
5. If your site already has an llms.txt, tick the adoption checkbox only after reviewing the backup implications.

Do not activate alongside Amino Studio — Dynamic llms.txt.

== Changelog ==

= 1.2.0-beta.1 =
* New portable engine, no hardcoded ACTA, Ziryab or studio URLs.
* Automatic, manual and hybrid document composition.
* Explicit file ownership and external-change protection.
* Basic settings and Markdown preview.

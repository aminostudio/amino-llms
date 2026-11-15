# Amino LLMS

![Amino LLMS](assets/brand-mark.svg)

**Your website, clearly understood.**

**Amino LLMS** is an open-source WordPress project for creating and maintaining a useful, curated `llms.txt`: a Markdown guide that helps compatible AI agents navigate the most important parts of your website.

**A sitemap lists everything. An editorial guide explains what matters.**

> **Development status:** the existing v1.1.0 engine is specific to Amino Studio and remains in `plugin/amino-llms-dynamic/`. A **portable 1.2.0-beta.1** prototype is now available in `plugin/amino-llms/` on the feature branch. Neither is a WordPress.org release. Do not install both on the same site.

## Why build this?

- **Editorial first.** Your organization describes itself in its own words.
- **Fresh resources.** WordPress keeps published links up to date.
- **Curated highlights.** Important work need not disappear behind chronology.
- **Plain Markdown.** No external AI service, API key, tracking or opaque scoring.
- **SEO-independent.** Complements an XML sitemap and can coexist with Yoast when its own llms.txt generator is disabled.
- **Transparent code.** GPL-licensed, local, inspectable and open to contributions.

## What v1.1.0 actually does

The existing site-specific engine:

- Updates a delimited dynamic section of a pre-existing root `llms.txt`, preserving surrounding editorial copy.
- Lists ACTA highlights and six newer articles, avoiding repeated links across these lists.
- Lists recently updated entries of the `amino_project` custom post type.
- Provides **Automatic / Editorial** modes for **featured articles**, *not* for the entire document.
- Reacts to relevant WordPress post changes, with an hourly WP-Cron fallback.
- Detects invalid markers, checks file permissions and verifies writes.
- Migrates the existing 1.0.0 selections without silently changing their editorial order.

**Limitations:** Existing writable root file required. Site-specific headings and URLs are hardcoded. WordPress cron depends on traffic or a server cron. There is no general-purpose manual Markdown editor, no reusable post-type picker and no WordPress.org-ready installer yet.

## Portable core · v1.2.0-beta.1

The new, **separate** development plugin lives at `plugin/amino-llms/`. It does not have hardcoded ACTA, Ziryab, or Amino Studio URLs.

| Document mode | Current beta behavior |
| --- | --- |
| **Automatic** | Generates a Markdown guide from site details and public WordPress content |
| **Manual** | Publishes an administrator-authored Markdown document |
| **Hybrid** | Combines a written introduction with automatically updated public links |

Current features:
- Configure the site title, summary, introduction, source post types, content count and highlighted IDs.
- Preview Markdown in a WordPress settings screen before publication.
- Keep `llms.txt` intact on plugin activation and reject unapproved overwrites.
- Detect external modifications via a stored SHA-256 checksum.
- Keep the previous text as a restore point in a non-autoloaded WordPress option.
- Refuse symlinks, oversized or structurally invalid documents, and simultaneous use of the legacy plugin.
- Exclude drafts, private/password-protected content and commonly used noindex metadata (Yoast and Rank Math).
- Update a **previously managed** document after relevant content changes or via hourly WP-Cron, when enabled.

**Not yet production certified:** generic real-WordPress integration tests, multisite, installation-directory mismatch, comprehensive SEO-plugin visibility rules, concurrency review, visual picker, translated UI and formal WordPress Plugin Check. Review the [release checklist](docs/release-checklist.md).

### Test the portable beta (staging only)

1. Ensure the legacy `Amino Studio — Dynamic llms.txt` plugin is **not active**.
2. Copy or ZIP the directory **`plugin/amino-llms/`**, with that directory as the WordPress plugin folder.
3. Activate it on a **staging** WordPress install, then open **Settings → Amino LLMS**.
4. Choose a document mode, save settings, inspect the Markdown preview and **publish explicitly**.
5. If a root `llms.txt` already exists, deliberately confirm its replacement only after reviewing its content. A restore point is stored inside WordPress.

Neither activation nor saving settings silently publishes to the root of the site.

## Product direction

The hybrid mode is the recommended starting point: a human introduction plus machine-maintained links. The 1.3 roadmap focuses on a polished searchable content picker, broader configuration, stronger rollback, URL diagnostics and accessibility. **No claim of guaranteed AI indexing, citation or ranking.**

See the [roadmap](ROADMAP.md), [brand copy](docs/brand-copy.md) and [release checklist](docs/release-checklist.md).

## Repository layout

```text
plugin/amino-llms-dynamic/    Legacy 1.1.0 implementation (site-specific)
plugin/amino-llms/            Portable 1.2 beta implementation + admin CSS
tests/run.php                 Legacy WordPress-function mock tests
tests/portable.php            Portable core WordPress-function mock tests
.github/workflows/            PHP lint and test matrix
assets/brand-mark.svg         Brand identity concept
docs/                         Product direction and release checklist
```

## Development and verification

Requires PHP 8.0+ for both implementations. Run:

```bash
php -l plugin/amino-llms-dynamic/amino-llms-dynamic.php
php -l plugin/amino-llms/amino-llms.php
php tests/run.php
php tests/portable.php
```

Both test runners mock WordPress functions, including publication, file safety, noindex filtering and content selection. They run on PHP 8.0/8.2/8.4 in GitHub Actions. **Mocks do not establish real WordPress compatibility or make the beta ready for WordPress.org.**

## Safety and privacy

Amino LLMS does not need to send website content to a third-party AI platform. In the general-purpose version, pre-existing `llms.txt` files must never be silently overwritten. This is a core release criterion, not a claim about the current 1.1.0.

For vulnerabilities see [SECURITY.md](SECURITY.md). For contributions see [CONTRIBUTING.md](CONTRIBUTING.md).

## About `llms.txt`

The format is a [community proposal](https://llmstxt.org/), not a guarantee that any AI model will ingest, quote or rank your website. A good `llms.txt` adds structured guidance; it does not replace your website, `robots.txt`, structured data or XML sitemap.

## Licence

[GPL-2.0-or-later](LICENSE) · © Amino Studio.

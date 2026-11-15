# Amino LLMS

![Amino LLMS](assets/brand-mark.svg)

**Your website, clearly understood.**

**Amino LLMS** is an open-source WordPress project for creating and maintaining a useful, curated `llms.txt`: a Markdown guide that helps compatible AI agents navigate the most important parts of your website.

**A sitemap lists everything. An editorial guide explains what matters.**

> **Development preview, not a general-purpose plugin release.** The bundled v1.1.0 engine is a production-tested, *Amino Studio-specific* implementation. It hardcodes ACTA, Ziryab and Amino Studio conventions. **Do not install it unchanged on unrelated WordPress sites.** Portability is the first milestone.

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

## Product vision

| Mode (planned) | Result |
| --- | --- |
| **Automatic** | A site-aware document maintained from publicly published content |
| **Manual** | Full Markdown editing, review and deliberate publication |
| **Hybrid** | A written identity and curated references combined with automatically updated links |

Additional goals: visual content selection, preview before publishing, validation, noindex/private-content filtering, safe import and rollback, multilingual admin, and portable WordPress configuration.

The future interface must remain accessible, lightweight and self-hosted. See [the roadmap](ROADMAP.md), [brand copy](docs/brand-copy.md) and [release checklist](docs/release-checklist.md).

## Repository layout

```text
plugin/amino-llms-dynamic/    Existing 1.1.0 implementation (site-specific)
tests/run.php                 Local WordPress-function mock tests
.github/workflows/            PHP lint and test matrix
assets/brand-mark.svg         Brand identity concept
docs/                         Product direction and release checklist
```

## Development and verification

Requires PHP 8.0+ to run the existing implementation. Run:

```bash
php -l plugin/amino-llms-dynamic/amino-llms-dynamic.php
php tests/run.php
```

The test runner mocks WordPress functions. It exercises migration, idempotence, marker failures, highlights, scheduling and symlink handling. **Mock tests are not a substitute for integration tests on real WordPress installations.** Do not package this repository as a generic production ZIP yet.

## Safety and privacy

Amino LLMS does not need to send website content to a third-party AI platform. In the general-purpose version, pre-existing `llms.txt` files must never be silently overwritten. This is a core release criterion, not a claim about the current 1.1.0.

For vulnerabilities see [SECURITY.md](SECURITY.md). For contributions see [CONTRIBUTING.md](CONTRIBUTING.md).

## About `llms.txt`

The format is a [community proposal](https://llmstxt.org/), not a guarantee that any AI model will ingest, quote or rank your website. A good `llms.txt` adds structured guidance; it does not replace your website, `robots.txt`, structured data or XML sitemap.

## Licence

[GPL-2.0-or-later](LICENSE) · © Amino Studio.

# Amino LLMS

![Amino LLMS](assets/brand-mark.svg)

**Your website, clearly understood.**

Amino LLMS is an independent, lightweight WordPress plugin for publishing a human-guided `llms.txt` in Markdown. **Automatic, Manual, and Hybrid** modes let site owners explain what matters rather than dumping a complete XML sitemap.

**No external AI service. No telemetry. No server-root file writes.**

## What ships in 1.2.0

- Automatic mode: site identity and links to recently published public content.
- Manual mode: complete Markdown authoring in the WordPress admin.
- Hybrid mode: editorial introduction plus automatically generated links.
- Select public WordPress post types, a link limit and featured post IDs.
- Markdown preview and a one-step restore of prior settings.
- Filter private, draft, password-protected and common noindex content.
- Serve **/llms.txt** through WordPress rewrite, with short-lived caching and cache invalidation after content updates.
- Show a warning when an existing physical `llms.txt` may take precedence.

**Why virtual delivery?** It eliminates filesystem permissions and destructive updates for most installations. The plugin never removes existing static files. Server and competing-plugin routing still need attention.

### Developer build

Production-installable ZIPs are created by [GitHub Actions](.github/workflows/package.yml), not from the entire GitHub repository.

```bash
php -l plugin/amino-llms/amino-llms.php
php tests/portable.php
```

CI also runs WordPress Plugin Check and an HTTP smoke test against a disposable WordPress instance. See [ROADMAP.md](ROADMAP.md) for remaining work and [SECURITY.md](SECURITY.md) for reporting.

### Migration from Amino Studio 1.1

The site-specific legacy implementation lives at `plugin/amino-llms-dynamic/`. It is **not** the same plugin or an automatic update. Preserve your existing `llms.txt`, deactivate the old generator, import your editorial content into the new Manual or Hybrid mode, and *only then* rename the physical `llms.txt` so WordPress can serve the virtual endpoint. Verify the public URL before switching any production site.

### Honest limitations

This is the first minimal stable GitHub build, not yet an approved WordPress.org listing. Test it on staging before submitting or deploying to production. The editor currently uses a comma-separated field for highlighted IDs. More sophisticated content search and multilingual settings are future improvements, not release blockers.

An `llms.txt` is a [community-proposed discovery format](https://llmstxt.org/). It does not guarantee citations, AI search placement or model ingestion.

## Repository

- `plugin/amino-llms/`: **distributable WordPress plugin**.
- `plugin/amino-llms-dynamic/`: archived Amino Studio 1.1 site-specific reference.
- `tests/`: mock-based regression suites.
- `.github/workflows/`: PHP matrix, Plugin Check, runtime and installable ZIP build.
- `ROADMAP.md`: remaining work.

GPL-2.0-or-later · © Amino Studio.

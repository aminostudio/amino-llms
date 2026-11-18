# LLMs.txt Composer

![LLMs.txt Composer](assets/brand-mark.svg)

**Your website, clearly understood.**

**v2.0.0-alpha.1** introduces ordered editorial highlights with optional per-link descriptions. Existing v1.3 settings are retained. This is an experimental LAB candidate, not a public stable release.

LLMs.txt Composer is an independent, lightweight WordPress plugin for publishing a human-guided `llms.txt` in Markdown. **Automatic, Manual, and Hybrid** modes let site owners explain what matters rather than dumping a complete XML sitemap.

**No external AI service. No telemetry. No server-root file writes.**

## Features in the 1.3.0 beta candidate

- Automatic mode: site identity and links to recently published public content.
- Manual mode: complete Markdown authoring in the WordPress admin.
- Hybrid mode: editorial introduction plus automatically generated links.
- Select public WordPress post types and a link limit; search published content by title to add featured links (up to 10), reorder with keyboard-friendly buttons and write a short description for each. The manual ID field remains available as an advanced fallback.
- Markdown preview and a one-step restore of prior settings.
- Filter private, draft, password-protected and common noindex content.
- Serve **/llms.txt** through WordPress rewrite, with short-lived caching and cache invalidation after content updates.
- Show local delivery diagnostics for Markdown validity, pretty permalinks and physical `llms.txt` file conflicts. A separate administrator-only HTTP probe can detect an actual HTML maintenance page or another route provider.
- Show mode-specific fields for Automatic, Manual and Hybrid, without discarding hidden content.

**Why virtual delivery?** It eliminates filesystem permissions and destructive updates for most installations. The plugin never removes existing static files. Server and competing-plugin routing still need attention.

### Developer build

CI builds a versioned WordPress ZIP from the plugin header/readme (plus a verified SHA-256 manifest). GitHub Actions runs on PRs and `main` only, avoiding duplicate feature-branch jobs. Automatic GitHub release publication remains disabled; publishing and WordPress.org submission require an explicit release decision.


Installable ZIPs are built by [GitHub Actions](.github/workflows/package.yml) and retained as workflow artifacts. Approved public releases can be published separately on [GitHub Releases](https://github.com/aminostudio/llms-txt-composer/releases); the 1.3.0 beta is **not** auto-published.

```bash
php -l plugin/llms-txt-composer/llms-txt-composer.php
php tests/portable.php
```

CI also runs WordPress Plugin Check and an HTTP smoke test against a disposable WordPress instance. See [the v1.3 LAB acceptance checklist](docs/LAB-QA-v1.3.0-beta.1.md) before staging or distributing the beta. See [ROADMAP.md](ROADMAP.md) for remaining work and [SECURITY.md](SECURITY.md) for reporting.

### v2.0 alpha upgrade behavior

Version 2.0 keeps `llms_txt_composer_settings`, the ordered `highlight_ids` field and all existing editor modes. The new optional `featured_descriptions` map only adds text next to valid published featured links. Existing v1.3 sites require no destructive migration or database rename. Do not enable the alpha outside LAB before testing the save, preview and restore cycle.

### Rename and safe migration from Amino LLMS 1.2.1

The WordPress.org candidate has a new plugin slug: `llms-txt-composer`. It is a **separate plugin** from `amino-llms`, not an in-place WordPress update. Deactivate the old portable plugin, keep its files installed temporarily, install and activate the new candidate, then verify Settings and `/llms.txt`. The new plugin **copies** legacy options on first activation into `llms_txt_composer_*` and does not erase them. Only after checking that settings survived, remove the old plugin through WordPress. Removing the old plugin first runs its uninstall cleanup, destroying the settings before they can be migrated.

### Migration from Amino Studio 1.1

The site-specific legacy implementation lives at `plugin/amino-llms-dynamic/`. It is **not** the same plugin or an automatic update. Preserve your existing `llms.txt`, deactivate the old generator, import your editorial content into the new Manual or Hybrid mode, and *only then* rename the physical `llms.txt` so WordPress can serve the virtual endpoint. Verify the public URL before switching any production site.

### Honest limitations

This is the first minimal stable GitHub build, not yet an approved WordPress.org listing. Test it on staging before submitting or deploying to production. The editor currently uses a comma-separated field for highlighted IDs. More sophisticated content search and multilingual settings are future improvements, not release blockers.

An `llms.txt` is a [community-proposed discovery format](https://llmstxt.org/). It does not guarantee citations, AI search placement or model ingestion.

## Repository

- `plugin/llms-txt-composer/`: **distributable WordPress plugin**.
- `plugin/amino-llms-dynamic/`: archived Amino Studio 1.1 site-specific reference.
- `tests/`: mock-based regression suites.
- `.github/workflows/`: PHP matrix, Plugin Check, runtime and installable ZIP build.
- `ROADMAP.md`: remaining work.

GPL-2.0-or-later · © Amino Studio.

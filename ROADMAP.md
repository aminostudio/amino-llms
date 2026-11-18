# Roadmap · LLMs.txt Composer

## RC priority: distribute the essentials

- [x] Keep the former Amino Studio-specific 1.1 code in the repository for reference.
- [x] Build a generic WordPress plugin with Auto, Manual and Hybrid modes.
- [x] Choose public post types, highlighted content and item limits.
- [x] Serve a virtual /llms.txt without touching server-root files.
- [x] Hide private, draft, password-protected and common noindex content.
- [x] Cache output briefly, clear on content edits, and support settings rollback.
- [x] Build an installable ZIP through GitHub Actions.
- [x] Validate real WordPress HTTP 200/503 behavior and Plugin Check in disposable GitHub CI.
- [x] Exercise real WordPress admin-post save/restore handlers in CI across Manual/Hybrid modes.
- [ ] Validate keyboard, responsive UX and page-cache/CDN behavior on LAB in an actual browser.
- [ ] Approve a stable Composer release after the 1.3.0 beta passes LAB and WordPress.org preflight.

## After first public distribution

- [x] Searchable text-based public-content picker with keyboard controls, plus advanced manual ID fallback (v1.3 beta).
- [ ] Richer visual selection and ordering without exposing private/noindex content.
- [ ] Stronger SEO visibility adapters and exclusions.
- [ ] Multilingual WordPress admin and full document localization.
- [ ] Richer structural validation and optional resource descriptions.
- [ ] Compatibility with WordPress subdirectories, multisite, and alternate web servers.
- [ ] Installer/migration assistant for existing physical llms.txt files.
- [ ] Screenshots and richer WordPress.org listing assets.

## Principles

1. **One file, one owner:** never silently overwrite, delete or shadow existing physical files.
2. **Human first:** concise editorial choices are more helpful than indiscriminate sitemap copying.
3. **No magic claims:** llms.txt does not guarantee search ranking or AI citation.
4. **Test the promised feature:** mocks, real WordPress, Plugin Check and installable ZIP are separate gates.

# LLMs.txt Composer 2.0.0-alpha.1 | LAB candidate

This major-version **alpha** introduces human-curated descriptions and ordering for featured WordPress links, while preserving the 1.3.0-beta.1 settings format and automatic/hybrid output when no notes are supplied.

- Select published content by title, move featured links up/down using accessible controls, and optionally enter a short editorial description (180 characters).
- Notes are stored only for selected IDs and normalized/escaped before output; private/noindex content remains excluded.
- Previous 1.3 settings, manual Markdown and rollback remain supported.
- No API keys, telemetry, site-root writes, remote services or production deployment.

**QA required:** GitHub PHP matrix, WordPress Plugin Check/runtime, ZIP integrity; after CI succeeds install on LAB and test keyboard reorder, descriptions, Markdown, rollback and caching. Do not publish to WordPress.org until a separate approved stable release.

- Adds an optional live public-URL check. An administrator triggers a bounded anonymous server-side request to /llms.txt; a Composer-specific response header distinguishes the plugin from Under Construction HTML, static files and caches.
- Detects interception without changing other plugins or bypassing LAB maintenance mode. The probe does not send administrator cookies.

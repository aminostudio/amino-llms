# Roadmap

The **1.1.0 source is deliberately preserved** as a site-specific production baseline. Planned functionality is not claimed as shipped.

## Phase 1 · Portable core (1.2 beta implemented, real-site verification pending)

- [x] Create a separate portable 1.2 beta without ACTA, Ziryab, Amino Studio or domain-specific assumptions.
- [x] Discover public WordPress post types and allow users to include/exclude types.
- [ ] Improve import experience (safe refusal and explicit replacement implemented; guided import pending).
- [x] Require explicit administrator publication, never write on activation.
- [x] Prototype **Automatic**, **Manual** and **Hybrid** document modes (staging tests pending).
- [ ] Keep featured-article selection independent of document-generation mode.
- [ ] Add advanced section ordering and link selection (post type and count controls already in beta).
- [ ] Fully exclude private/noindex content across supported SEO plugins (basic Yoast and Rank Math filtering implemented).
- [ ] Handle WordPress in subdirectories and detect generators/route conflicts.

## Phase 2 · Exceptional WordPress administration

- [ ] Visual summary dashboard, independent of the site's active theme.
- [ ] Editor for site introduction, content categories and selected pages.
- [ ] Searchable article/project selector instead of raw WordPress IDs.
- [ ] Markdown preview, link validation and document size diagnostics.
- [ ] Separate actions for **Generate preview** and **Publish**.
- [ ] Revision snapshots, safe rollback and actionable error messages.
- [ ] Keyboard accessible, i18n-ready, performant interface using WordPress APIs.
- [ ] No external AI dependency, telemetry or CDN-based runtime assets.

## Phase 3 · Harden and release

- [ ] WordPress integration tests covering activation, updates, cron, REST, failures and multisite.
- [ ] Test concurrent writes, atomic rollback, permissions and symlinks.
- [ ] PHP compatibility matrix and WordPress Plugin Check / coding standards.
- [ ] Review conflicts with Yoast, other llms generators, server caching and CDN behavior.
- [ ] Installable GitHub release ZIP with changelog, provenance and checksum.
- [ ] Accessible admin screenshots and WordPress.org assets.
- [ ] WordPress.org submission **only after** generic functionality is verified.

## Principles

1. Existing site content must be preserved unless a user explicitly elects to replace it.
2. A small editorially curated guide is more useful than indiscriminately copying a sitemap.
3. We do not promise AI ranking, citation or ingestion.
4. Every claimed feature must have a corresponding test or a documented limitation.

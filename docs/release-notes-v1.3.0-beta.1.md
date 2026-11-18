# LLMs.txt Composer v1.3.0-beta.1

**LAB-only beta candidate**, not a WordPress.org submission or general-availability stable release.

- Search WordPress published posts, pages and public post types by title to choose up to 10 featured links.
- Reject private, protected, common SEO noindex and duplicate homepage content from search results.
- Require an administrator session and validated nonce for the new AJAX endpoint.
- Keep the underlying `highlight_ids` option, direct ID entry as fallback, current document behavior and rollback.
- No external JS libraries, trackers or external AI services.

QA before distribution: test keyboard search/add/remove and saved output on LAB, validate physical-file and Under Construction route conflicts, Plugin Check, PHPUnit-style mock tests and WordPress runtime checks. Updating a live site should wait for acceptance.

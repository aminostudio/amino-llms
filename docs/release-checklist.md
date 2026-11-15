# Release checklist

## Functionality
- [ ] Fresh installation without `llms.txt` does not corrupt or erase anything.
- [ ] Existing file is never adopted or overwritten without explicit confirmation.
- [ ] Automatic/manual/hybrid mode switching preserves user content.
- [ ] Preview cannot inadvertently publish; publishing requires permission and nonce.
- [ ] Post publish, unpublish, trash, slug change and highlight reorder work.
- [ ] Published content is sorted by the intended date and priority.
- [ ] No duplicates, broken URLs or hidden/private/noindex/password-protected content.

## Safety and reliability
- [ ] Unwritable files/directories, symlinks, bad markers and stale cache.
- [ ] Atomic replacement/fallback, rollback, concurrency and lock handling.
- [ ] WordPress subdirectory installations, multisite and different rewrite setups.
- [ ] Admin authorization, sanitization, escaping and input limits.
- [ ] Backward compatibility migration verified against v1.1.0.

## Publishing
- [ ] PHPCS/WPCS, Plugin Check and real WordPress integration tests.
- [ ] PHP test matrix and committed source quality checks.
- [ ] Documentation, accessibility, screenshots and accurate changelog.
- [ ] GitHub release ZIP, reproducible build and SHA-256.
- [ ] WordPress.org submission only after generic beta validation.

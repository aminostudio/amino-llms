# Distribution release checklist

## Essential functionality
- [x] Generic site identification, Automatic, Manual and Hybrid modes.
- [x] WordPress post, page and public CPT discovery.
- [x] Markdown preview and saved settings rollback.
- [x] Draft, private, password-protected and common noindex filtering.
- [x] No filesystem writes: virtual /llms.txt endpoint.
- [x] Detect presence of an existing physical root file and warn.
- [ ] Verify URL rewriting on Apache/Nginx and with different permalink structures.
- [ ] Verify manual mode saving and public rendering in an actual WordPress admin.
- [ ] Test interactions with Yoast, caching, CDN, and competing llms generators.
- [ ] Confirm compatibility with WordPress in a subdirectory and multisite.

## Security / compliance
- [x] Capability checks, CSRF nonces, input sanitization, output escaping.
- [x] No external runtime dependencies, telemetry or AI API.
- [x] No destructive changes to existing physical files.
- [ ] Security review, WPCS and accessibility check.
- [ ] Verify editor rejects large/invalid text and preserves previous settings.
- [ ] Review all notes from WordPress Plugin Check.

## Packaging
- [x] Correct plugin folder, main PHP header, readme and GPL license.
- [x] Minimal reproducible ZIP workflow and checksums.
- [ ] Stable version number, final changelog and screenshots.
- [ ] Real WordPress staging smoke tests and manual sign-off.
- [ ] WordPress.org submission only once complete, no placeholders.

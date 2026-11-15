# Security policy

**Status:** Public development repository. The included v1.1.0 remains site-specific; it is not a WordPress.org release.

Please use GitHub's **Report a vulnerability** / private security advisory functionality to report a security concern rather than filing a public exploit report.

Areas needing careful review include:

- Root file writes, symlink traversal, permissions and concurrent refreshes.
- File integrity, failure recovery and server/CDN cache consistency.
- Administrative permissions, request nonces and cross-site request forgery protection.
- Markdown generated from user-supplied titles, descriptions and URLs.
- Accidental exposure of draft, private, password-protected or noindex material.
- Interactions with third-party SEO or llms.txt generation plugins.

Please do not test vulnerabilities against unrelated production websites.

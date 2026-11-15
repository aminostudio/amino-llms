# Amino LLMS 1.2.1

**Reliable discovery and correct Markdown links.**

Maintenance release following the first public GitHub prerelease.

### What changed
- Find eligible public content even when the 100 newest posts are protected, excluded or already featured. Scan additional batches, with a bounded maximum of 500 candidate posts per content type.
- Correctly encode parentheses in generated Markdown link destinations, preserving other URL encoding.
- Warn WordPress administrators when plain permalinks might prevent the virtual `/llms.txt` route from resolving.
- Add regression tests for excluded-post pagination and punctuation in permalinks.

### What stays
- Automatic, Manual and Hybrid modes.
- WordPress-native admin, Markdown preview and settings rollback.
- Virtual `/llms.txt`, without filesystem writes or third-party services.
- No draft, private, password-protected or explicitly noindexed content from the supported metadata checks.
- Existing physical `llms.txt` remains untouched.

### Installation
Use **amino-llms-1.2.1.zip** from this release, not the GitHub repository source ZIP.

This is still marked a GitHub prerelease for staging review. WordPress.org submission remains a separate decision after the audit.

**Important:** `amino-llms` is distinct from the older site-specific `amino-llms-dynamic` plugin. Deactivate competing providers and verify the public URL before switching.

GPL-2.0-or-later · Amino Studio.

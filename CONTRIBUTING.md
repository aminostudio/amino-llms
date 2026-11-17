# Contributing to LLMs.txt Composer

LLMs.txt Composer is being developed into a general-purpose WordPress plugin. The current source is a site-specific proof of concept: please read [ROADMAP.md](ROADMAP.md) before implementing broad features.

For each change, describe the motivation, affected WordPress versions and compatibility implications. Add focused tests for document preservation, activation, generation, permissions, post visibility, malformed Markdown markers and upgrade paths.

Check locally:

```sh
php -l plugin/amino-llms-dynamic/amino-llms-dynamic.php
php tests/run.php
```

Use WordPress core APIs and established capability checks, nonces, sanitized inputs and escaped outputs. Treat an existing root `llms.txt` as user content, not a disposable cache. Do not add external telemetry, CDN dependencies or automated AI network calls.

For interface changes, include screenshots or an offline preview, and preserve keyboard access and readable contrast.

Report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

By contributing, you agree your contributions are compatible with GPL-2.0-or-later.

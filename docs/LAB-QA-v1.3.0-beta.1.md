# LAB acceptance plan: LLMs.txt Composer 1.3.0-beta.1

**Status:** checklist prepared, LAB acceptance **not yet performed**. Do not confuse passing CI with a human browser test. No production or WordPress.org rollout is authorized by this document.

## 0. Safety gates
- Confirm a working LAB backup and export/copy any manually authored `llms.txt` before any replacement.
- Record installed plugin version, WordPress/PHP versions, active cache/under-construction plugins, permalink format and whether the web root contains a physical `llms.txt`.
- Confirm the ZIP folder is `llms-txt-composer/` with `llms-txt-composer.php`, `readme.txt`, `uninstall.php`, local CSS/JS and LICENSE. Verify GitHub Actions SHA-256.
- The legacy `amino-llms` plugin is a **separate slug**; if migrating from it, deactivate first, retain files/options until Composer successfully copies them. Never uninstall the legacy plugin before confirming the new copy.

## 1. Editor smoke test
- In **Settings → LLMs.txt Composer**, switch Automatic, Manual and Hybrid. Each mode should reveal only relevant fields. Unsaved text should not disappear when switching back.
- Keyboard-only: Tab through mode picker, featured search, results, Add/Remove buttons, selected list, advanced ID fallback and Save. Focus should remain visible.
- Try on a narrow mobile viewport and with JavaScript disabled; all core form fields must remain accessible without JS.
- Search a published post, page and public CPT by title. Verify that draft, private, password-protected, common noindex and homepage duplicates never appear in the selector.
- Add a result, prevent duplicates, remove it, and verify no more than 10 featured links can be chosen. Test 1-character queries and no matches.
- Save each mode and inspect the saved read-only preview, its byte count and the actual public document. Restore the prior settings and confirm the previous contents return.
- Test a malformed Manual Markdown body without a starting `#` heading. It must not silently replace the last valid configuration.

## 2. Public route and cache
- Open `/llms.txt` without an authenticated administrator session, including when Under Construction or caching plugins are active.
- Confirm successful responses are HTTP 200, `Content-Type: text/plain`, UTF-8, `X-Content-Type-Options: nosniff`, with Markdown starting with a heading.
- After an approved content/settings change, verify cache invalidation on the **actual site**, not only in CI. Examine CDN or proxy caching separately.
- In a disposable test environment, simulate an invalid stored Manual document: the endpoint must return HTTP 503 with `Cache-Control: no-store` and a plain-text error. Restore original settings immediately.
- Where a physical `llms.txt` exists, verify the plugin's warning and that it never overwrites or removes the file. A server may prioritize the physical file.
- Verify pretty permalinks, home-URL change invalidation, alternate web server rewrites and WordPress subdirectory setup when applicable.

## 3. Release evidence and decision
- Attach or note GitHub Actions run URLs for PHP 8.0/8.2/8.4, Plugin Check/runtime, ZIP and SHA-256.
- Record manual browser outcomes, issues, WordPress and PHP versions, conflicts and rollback results.
- Only after a clean QA decision: tag a version, publish its verified installable ZIP and prepare a **separately approved** WordPress.org submission. GitHub release auto-publishing stays disabled.
- Do not claim `llms.txt` guarantees AI indexing, citations or rankings.

Reviewer: _pending_

Decision: **not yet approved**

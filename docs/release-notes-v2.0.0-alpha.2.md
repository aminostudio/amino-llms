# LLMs.txt Composer v2.0.0-alpha.2 | LAB candidate

**Test-only alpha** building on v2.0.0-alpha.1. Do not publish to WordPress.org or production before manual LAB acceptance.

- Added a **Preview unsaved changes** button for Automatic, Manual and Hybrid documents.
- It sanitizes and renders current form values with administrator permission and a dedicated WordPress nonce, but **does not save options, update the public `/llms.txt` URL or invalidate its cache**.
- Shows Markdown text, validation result and UTF-8 byte count. Invalid manual drafts are rejected as publishable settings while still visible for editing.
- Keeps editorial notes, keyboard ordering, privacy/noindex filtering, real HTTP diagnostics and legacy option compatibility.
- Adds portable and disposable real-WordPress tests proving draft previews leave stored settings and transients unchanged.
- No external AI service, telemetry, root-file writes or third-party dependencies.

LAB acceptance: inspect the three modes, change a draft and preview it, confirm the public endpoint/settings are unchanged until Save, then test actual Save and Restore. Verify maintenance-mode interception separately.

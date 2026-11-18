# LAB QA | LLMs.txt Composer 2.0.0-alpha.2

**Status: manual acceptance pending.** The GitHub CI test site is separate from `labo.aminostudio.fr`.

## Baseline on LAB (18 November 2026)
- 2.0.0-alpha.1 was verified installed and active before this sprint; the alpha.2 ZIP must be installed separately after green CI.
- No saved `llms_txt_composer_settings` or previous-settings option was found when checked through WPVibe. This is normal before the first editor save.
- Public anonymous `/llms.txt` returned the **Under Construction** HTML page rather than Markdown. Keep Under Construction active; the expected v2 route probe result is **intercepted**.

## Safe acceptance steps
1. Make sure LAB backup exists, then install the CI-built `llms-txt-composer-2.0.0-alpha.2.zip` only on LAB.
2. Open **Settings → LLMs.txt Composer**. Confirm the version displays 2.0.0-alpha.2.
3. Select Manual and enter `# Draft preview test` with a second section. Click **Preview unsaved changes**. Verify Markdown, valid status and byte count appear without clicking Save.
4. Refresh the page. The draft text must not have been stored. If the test was the first edit, the `llms_txt_composer_settings` option should still be absent.
5. Repeat preview in Hybrid with an editorial introduction, one public selected post, a description, and a reordered list. Only public eligible posts should appear in the output.
6. Remove the leading `#` from a Manual draft. **Preview unsaved changes** must report an invalid draft without replacing the public document.
7. Click **Verify actual public /llms.txt**. Under Construction is expected to be detected as an interception. A loopback-only firewall error may instead report unreachable. Do not disable maintenance to make the test green.
8. Test Save and Restore **only with deliberately approved LAB test content**. Confirm saved Markdown/notes survive a reload, then Restore correctly returns the prior settings.
9. Keyboard-check Add, Up, Down, Remove, Preview and Save, including a narrow mobile viewport.
10. Record PHP/WP versions, HTML status, failed tests and final approval. Do not deploy to production or WordPress.org without a separate release decision.

## Automated gates
- PHP matrix: 8.0, 8.2 and 8.4
- WordPress Plugin Check and real HTTP 200/503 route contract
- Admin capability and nonce requirements on both preview and live probe
- Draft previews leave WordPress options and public-cache transient unchanged
- Reproducible versioned ZIP + SHA-256

**Reviewer:** pending

**Decision:** Not yet approved for production / WordPress.org

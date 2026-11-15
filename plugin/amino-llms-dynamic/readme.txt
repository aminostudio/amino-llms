=== Amino Studio — Dynamic llms.txt ===
Version: 1.1.0
Requires PHP: 8.0
License: GPL-2.0-or-later

SITE-SPECIFIC DEVELOPMENT BASELINE

This plugin maintains the existing editorial /llms.txt on Amino Studio's own WordPress site. It is not a generic plugin release and should not be installed unchanged elsewhere.

- Maintains ACTA highlights, current articles and project entries.
- Preserves existing editorial text and validates delimiter markers.
- Keeps ACTA and Contact in Main Pages; adds Spaces and Ziryab entries.
- Provides automatic or curated *highlight* selection, not manual document editing.
- Regenerates after relevant post saves and by hourly WP-Cron.
- Has file-write checks and returns actual refresh status.

Development roadmap: see repository ROADMAP.md.
Not affiliated with Yoast; intended to coexist when Yoast's llms.txt generation is disabled.

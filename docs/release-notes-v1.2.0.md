# Amino LLMS 1.2.0

**Your website, clearly understood.**

A minimal, self-hosted WordPress plugin that publishes a clean Markdown guide at `/llms.txt`.

### Included
- Automatic, Manual and Hybrid document modes
- Published WordPress pages, posts and public custom post types
- Curated article highlights and configurable link limits
- Live Markdown preview in WordPress Settings
- One-step restoration of previous settings
- Protection from private, password-protected, draft and commonly noindexed content
- Virtual HTTP delivery in `text/plain` without writing in the server root
- No external AI service, telemetry, API token or scheduled cron

### Installation
Download **amino-llms-1.2.0.zip** from the assets of this release and install via WordPress → Plugins → Add New → Upload Plugin.

Then open **Settings → Amino LLMS**, choose a document mode and save. Confirm your public `/llms.txt`.

**Existing physical llms.txt:** The web server may deliver it before the virtual endpoint. The plugin never deletes or rewrites that file. Keep a backup, disable other llms.txt generators, and only then change your current configuration.

### Release status
This GitHub publication is marked as a **prerelease for staging validation**. It passed PHP 8.0, 8.2 and 8.4 checks and WordPress Plugin Check. It is **not yet published on WordPress.org**. Please report issues before using on business-critical sites.

### Reminder
An llms.txt document does not guarantee AI model indexing, citation or search visibility.

GPL-2.0-or-later · Amino Studio.

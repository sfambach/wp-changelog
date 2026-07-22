# Gutenberg Changelog & Version History

A lightweight WordPress plugin for logging content changes inside the Gutenberg editor and rendering a formatted changelog table on the frontend.

**Author:** Stefan Fambach  
**Website:** [www.fambach.net](https://www.fambach.net) — further information  
**Version:** 1.6.0  
**Requires:** WordPress 6.0+, PHP 7.4+

## Description

Managing content updates across multi-author blogs or corporate websites can be tricky. This plugin provides a clear workflow:

- **Input blocks** (editor-only) — capture change notes while you edit
- **Change Log** (public output) — compiles all notes into a sortable table for readers

You can insert a Change Log block manually on individual pages, or enable **global integration** to append the table automatically to selected post types.

## Blocks

| Block | Visibility | Purpose |
|-------|------------|---------|
| **Single Change Note** | Editor only | One change entry with auto-filled date and author |
| **Multi Change Note** | Editor only | Multiple change rows in a compact table |
| **Revision Multiline Note** | Editor only | Syncs rows from WordPress post revisions; editable comments per revision date |
| **Change Log** | Frontend (configurable) | Renders the compiled changelog table |

Legacy block slugs (`wpc/change-item`, `wpc/multi-note`, `wpc/change-table`, `wpc/version-multiline-note`, `wpc/generated-multiline-note`) remain registered for backward compatibility but are hidden from the block inserter.

## Features

### Change Log table

* **Post created row** — uses the earliest stored revision date when available
* **Sort order** — newest or oldest date row on top
* **Visible on page** — hide the table on the public site while keeping the editor preview
* **Author column** — show or hide
* **Table style** — Default or Stripes
* **Fixed-width cells** — optional fixed table layout
* **Alignment** — left, center, right, wide, full width
* **Date consolidation** — merge entries with the same date and author into one row
* **Change column options** — bullet list rendering; sort merged changes by time or alphabetically (oldest/newest on top within a cell)

### Revision Multiline Note

* Automatically creates one row per revision date when the post is saved
* Preserves your comments when revision dates sync
* Sortable by date, change text, or author

### Global integration

Configure under **Settings → Change Log**:

* Enable automatic append on selected post types (default: pages)
* Mirror all Change Log display options globally
* Manual Change Log blocks on a page take precedence over the global table
* Live preview at the bottom of the block editor when global mode applies

### Translations

German (`de_DE`) translations are included for the admin settings page, block editor UI, and frontend table output.

## Project Structure

```
wp-changelog/
├── wp-changelog.php
├── includes/
│   ├── helpers.php                  # Constants, attribute schemas, shared utilities
│   ├── collectors.php               # Collect, sort, and consolidate changelog entries
│   ├── render-change-log.php        # Change Log table rendering
│   ├── render-revision-multiline-note.php
│   ├── blocks.php                   # Block registration, editor assets, REST routes
│   ├── settings.php                 # Settings → Change Log admin page
│   └── global-change-log.php        # the_content integration + editor preview REST
├── assets/js/
│   ├── shared.js                    # Shared note block UI helpers
│   ├── single-change-note.js
│   ├── multi-change-note.js
│   ├── revision-multiline-note.js
│   ├── change-log.js
│   └── global-change-log-editor.js  # Global Change Log preview in the editor
└── languages/                       # .pot, .po, .l10n.php, block editor JSON
```

## Installation

### From a release

1. Download `wp-changelog.zip` from the [latest release](https://github.com/sfambach/wp-changelog/releases).
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**, upload the zip, and activate.
3. Or unzip into `/wp-content/plugins/wp-changelog/` and activate under **Plugins**.

### From source

1. Clone this repository into `/wp-content/plugins/wp-changelog/`.
2. Activate the plugin through the **Plugins** menu in WordPress.

## How to Use

### Per-page workflow

1. Create or edit a post or page.
2. Add **Single Change Note**, **Multi Change Note**, and/or **Revision Multiline Note** blocks while editing. These are not shown to visitors.
3. Insert a **Change Log** block where the table should appear (typically at the bottom).
4. Save or update the post to refresh the live preview.
5. In the block sidebar under **General Settings**, adjust sort order, visibility, author column, table style (Default / Stripes), and consolidation options.

### Global workflow

1. Go to **Settings → Change Log**.
2. Enable **Append the Change Log table automatically** and select the post types.
3. Configure table display options (same as the block sidebar).
4. On matching pages without a manual Change Log block, the table is appended automatically. A preview appears at the bottom of the block editor.

## Architecture Notes

* Change notes are stored in `post_content` as Gutenberg block attributes (dynamic blocks with `save: null`).
* No custom database table is used.
* The Revision Multiline Note block is an input surface only; the Change Log is the public output.
* Site-wide audit logging beyond page changelogs is out of scope for this plugin.

## Changelog

### 1.6.0 (2026-07-22)

Stable release.

* Single Change Note, Multi Change Note, Revision Multiline Note, and Change Log blocks
* Global Change Log integration via **Settings → Change Log**
* Editor-only revision and note blocks; configurable Change Log visibility on the frontend
* Date consolidation, merged change sorting, Default/Stripes table styles
* Post-created date derived from oldest revision when available
* Full German translations (`de_DE`)
* Modular PHP (`includes/`) and per-block JavaScript (`assets/js/`)

## License

This project is licensed under the GPLv2 or later.

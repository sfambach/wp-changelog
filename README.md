# Gutenberg Changelog & Version History

A lightweight WordPress plugin for logging content changes inside the Gutenberg editor and rendering a formatted changelog table on the frontend.

**Author:** Stefan Fambach  
**Website:** [www.fambach.net](https://www.fambach.net) — further information  
**Version:** 2.0.1  
**Requires:** WordPress 6.0+, PHP 7.4+  
**Plugin website:** [github.com/sfambach/wp-changelog](https://github.com/sfambach/wp-changelog)

## AI disclosure

This plugin was built with AI assistance: the code was written largely by Claude (Anthropic) via Claude Code. Stefan Fambach specified, reviewed and tested it.

## Description

Managing content updates across multi-author blogs or corporate websites can be tricky. This plugin provides a clear workflow:

- **Input blocks** (editor-only) — capture change notes while you edit
- **Change Log** (public output) — compiles all notes into a sortable table for readers

You can insert a Change Log block manually on individual pages, or enable **global integration** to append the table automatically to selected post types.

## Blocks

| Block | Visibility | Purpose |
|-------|------------|---------|
| **Einzel-Log-Eintrag** | Editor only | One change entry with auto-filled date and author |
| **Log-Liste** | Editor only | Multiple change rows in a compact table; sync missing dates from page |
| **Logausgabe** | Frontend (configurable) | Renders the compiled changelog table with caption |

Legacy block slugs (`wpc/change-item`, `wpc/multi-note`, `wpc/change-table`) remain registered for backward compatibility but are hidden from the block inserter.

## Features

### Change Log table

* **Post created row** — uses the earliest date from the page history (revisions, `post_date`); an earlier change note date takes precedence
* **Table caption** — default **Logbuch** (DE) / **Changelog** (EN), editable like the core table caption
* **Sort order** — newest or oldest date row on top
* **Visible on page** — hide the table on the public site while keeping the editor preview
* **Author column** — show or hide
* **Table style** — Default or Stripes (theme-aware colors via CSS variables / `color-mix`)
* **Fixed-width cells** — optional fixed table layout
* **Alignment** — left, center, right, wide, full width
* **Date consolidation** — merge entries with the same date and author into one row
* **Change column options** — bullet list rendering; sort merged changes by time or alphabetically (oldest/newest on top within a cell)

### Editor UX

* Compact idle view for Einzel-Log entries (`#log: date — change — author`)
* **Enter** in the Change field inserts a paragraph after the note
* Optional typing shortcut `#log` (configurable under Settings → Change Log)
* Log-Liste: sort by date/change/author; sync missing dates from the page

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
├── wp-changelog.php                 # Bootstrap + PSR-4 autoloader
├── src/
│   ├── Plugin.php                   # Singleton bootstrap
│   ├── Helpers.php                  # Constants, attribute schemas, shared utilities
│   ├── Collectors.php               # Collect, sort, and consolidate changelog entries
│   ├── Renderer.php                 # Change Log table rendering
│   ├── BlockRegistry.php            # Block registration, editor assets
│   ├── Settings.php                 # Settings → Change Log admin page
│   └── GlobalChangeLog.php          # the_content integration + editor preview REST
├── templates/
│   ├── change-log-table.php
│   └── settings-page.php
├── assets/js/
│   ├── shared.js                    # Shared note block UI helpers
│   ├── single-change-note.js
│   ├── multi-change-note.js
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
2. Add **Einzel-Log-Eintrag** and/or **Log-Liste** while editing (editor-only; not shown to visitors). Tip: type `#log ` in an empty paragraph to create a note quickly.
3. Insert a **Logausgabe** block where the table should appear (typically at the bottom), or rely on global integration.
4. Save or update the post to refresh the live preview.
5. In the block sidebar under **General Settings**, adjust sort order, visibility, author column, table style (Default / Stripes), and consolidation options.

### Global workflow

1. Go to **Settings → Change Log**.
2. Enable **Append the Change Log table automatically** and select the post types.
3. Configure table display options (same as the block sidebar) and optional editor shortcut.
4. On matching pages without a manual Change Log block, the table is appended automatically. A preview appears at the bottom of the block editor.

More details: see [FAQ.md](FAQ.md).

## Architecture Notes

* Change notes are stored in `post_content` as Gutenberg block attributes (dynamic blocks with `save: null`).
* No custom database table is used.
* PHP uses OOP under the `WPChangelog` namespace (`src/`) with templates for HTML output.
* Site-wide audit logging beyond page changelogs is out of scope for this plugin.

## Changelog

### 2.0.1 (2026-10-03)

* Post created row: date now taken from the page history (revisions, `post_date`) instead of the earliest change note; earlier note dates still win.

### 2.0.0 (2026-07-30)

Major release: OOP architecture and editor UX.

* Migrated procedural `includes/` to namespaced classes under `src/` (`WPChangelog\*`) with `templates/` for views.
* PSR-4 autoloader and `Plugin` singleton bootstrap.
* Einzel-Log: compact idle summary, Enter inserts a paragraph after the note, optional `#log` typing shortcut (Settings).
* Theme-aware table background and Stripes via CSS variables / `color-mix`.
* Clearer empty paragraphs between compact log notes in the editor.
* FAQ expanded (shortcut, Enter, themes/stripes, spacing).

### 1.6.1 (2026-07-26)

* Restored modular v1.6.0 codebase as the working base (GitHub `sfambach/wp-changelog`).
* Fixed plugin asset URLs for Windows junction/symlink installs.
* German editor titles: Einzel-Log-Eintrag, Log-Liste, Logausgabe.
* Logausgabe caption (Logbuch/Changelog) with toolbar toggle and inline editing.
* Column proportions for note tables (date/author narrow, change flexible).
* Log-Liste: sync missing dates from page changelog entries.
* Added `FAQ.md` and `docs/plan.md`.

### 1.6.0 (2026-07-22)

Stable release.

* Single Change Note, Multi Change Note, and Change Log blocks
* Global Change Log integration via **Settings → Change Log**
* Editor-only note blocks; configurable Change Log visibility on the frontend
* Date consolidation, merged change sorting, Default/Stripes table styles
* Post-created date derived from oldest revision when available
* Full German translations (`de_DE`)
* OOP PHP (`src/` namespace `WPChangelog`) and per-block JavaScript (`assets/js/`)

## License

This project is licensed under the GPLv2 or later.

# Gutenberg Changelog & Version History

A lightweight, powerful WordPress plugin that allows editors to log content updates directly inside the Gutenberg editor and automatically render a beautifully formatted changelog table in the frontend.

## Description

Managing content updates across multi-author blogs or corporate websites can be tricky. This plugin introduces a streamlined workflow to track document edits directly within the block editor.

It consists of three blocks:

1. **Single Change Note:** An internal block used by editors to leave a single log entry. When inserted, it automatically captures the current date and the logged-in user's name. It provides a text field for a comment. This block remains strictly invisible to your website visitors.
2. **Multi Change Note:** An internal block for entering multiple change entries in a compact table UI. Rows can be added and removed; entries are sorted by date. Also hidden on the frontend.
3. **Change Log:** A dynamic block to be placed anywhere in your post (ideally at the bottom). It automatically fetches the post creation date as the initial entry and dynamically compiles all change notes from the post. Entries are automatically sorted in **descending order (newest first)** by default.

Legacy block slugs (`wpc/change-item`, `wpc/multi-note`, `wpc/change-table`) remain registered for backward compatibility but are hidden from the block inserter.

## Features

* **Backend-Only Metadata:** Keep internal editing notes safely hidden from the public.
* **Smart Initialization:** Automatically populates the current date and the author's display name upon insertion.
* **Multi-row editing:** Add several changes at once with the Multi Change Note block.
* **Chronological Sorting:** Displays the log in descending order so readers or admins always see the latest changes first.
* **Native WordPress Look & Feel:** The Change Log supports default WordPress table block features (Standard/Stripes styles, fixed-width cells, and alignment controls like Wide/Full width).
* **Toggleable Author Column:** Easily show or hide the author column via the block inspector sidebar controls in Gutenberg.
* **Date consolidation:** Merge entries that share the same date and author into one row.
* **Flexible change column:** Optionally render changes as a bullet list, with configurable sort order for merged rows.

## Project Structure

```
wp-changelog/
├── wp-changelog.php
├── includes/
│   ├── helpers.php
│   ├── collectors.php
│   ├── render-change-log.php
│   └── blocks.php
├── assets/js/
│   ├── shared.js
│   ├── single-change-note.js
│   ├── multi-change-note.js
│   └── change-log.js
└── languages/
```

## Installation

### Manual Installation
1. Download or clone this repository.
2. Upload the `wp-changelog` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.

## How to Use

1. Create or edit a post/page.
2. Whenever you make a significant change, click the `+` icon and insert a **Single Change Note** (or use **Multi Change Note** for several entries at once). Type what you modified.
3. At the end of your document, insert the **Change Log** block.
4. Save your post as a draft or update it to populate the live preview.
5. (Optional) Click on the Change Log block and use the right sidebar to toggle the **"Show Author"** option, consolidate dates, or switch the style to **Stripes**.

## Changelog

### 1.0.0 (2026-07-22)
* First stable release: Single Change Note, Multi Change Note, Revision Multiline Note, and Change Log blocks.
* Global Change Log integration via **Settings → Change Log** with automatic table append per post type.
* Editor-only revision block; Change Log output on the frontend with configurable visibility.
* Date consolidation, merged change sorting, table style (Default/Stripes), and full German translations.
* Modular PHP architecture (`includes/`) and per-block JavaScript (`assets/js/`).

### 1.5.0 (2026-07-22)
* Refactored plugin into modular PHP includes and one JS file per block.
* Renamed blocks: Single Change Note, Multi Change Note, and Change Log.
* Legacy block slugs remain supported but hidden from the inserter.
* Updated German translations for new block names.

### 1.3.1 (2026-07-22)
* Fixed undefined `$is_template_preview` bug that broke table rendering.
* Refactored PHP into dedicated data collection and rendering functions.
* Fixed translation path (`languages/`) and updated German translations.
* Editor preview now refreshes on save instead of every keystroke.
* Nested change-item blocks are now found correctly.

### 1.3.0
* Added date consolidation and sort order options.

### 1.1.0 (2026-07-20)
* Added automatic author detection for logged-in users.
* Added a Gutenberg sidebar toggle control to hide/show the author column.
* Integrated native WordPress table block styling attributes (`hasFixedLayout`, `is-style-stripes`, and alignments).

### 1.0.0
* Initial release with basic backend logging and dynamic frontend rendering.

## License

This project is licensed under the GPLv2 or later.

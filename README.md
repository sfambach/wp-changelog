# Gutenberg Changelog & Version History

A lightweight, powerful WordPress plugin that allows editors to log content updates directly inside the Gutenberg editor and automatically render a beautifully formatted changelog table in the frontend.

## Description

Managing content updates across multi-author blogs or corporate websites can be tricky. This plugin introduces a streamlined workflow to track document edits directly within the block editor. 

It consists of two specific blocks:
1. **Change Note (Backend Only):** An internal block used by editors to leave a log entry. When inserted, it automatically captures the current date and the logged-in user's name. It provides a text field for a comment. This block remains strictly invisible to your website visitors.
2. **Changelog Table:** A dynamic block to be placed anywhere in your post (ideally at the bottom). It automatically fetches the post creation date as the initial entry and dynamically compiles all "Change Notes" from the post. Entries are automatically sorted in **descending order (newest first)**.

## Features

* **Backend-Only Metadata:** Keep internal editing notes safely hidden from the public.
* **Smart Initialization:** Automatically populates the current date and the author's display name upon insertion.
* **Chronological Sorting:** Displays the log in descending order so readers or admins always see the latest changes first.
* **Native WordPress Look & Feel:** The Changelog Table supports default WordPress table block features (Standard/Stripes styles, fixed-width cells, and alignment controls like Wide/Full width).
* **Toggleable Author Column:** Easily show or hide the author column via the block inspector sidebar controls in Gutenberg.

## Installation

### Manual Installation
1. Download or clone this repository.
2. Upload the `wp-gutenberg-changelog` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.

## How to Use

1. Create or edit a post/page.
2. Whenever you make a significant change, click the `+` icon and insert the **Change Note (Backend Only)** block. Type what you modified.
3. At the end of your document, insert the **Changelog Table** block.
4. Save your post as a draft or update it to populate the live preview.
5. (Optional) Click on the Changelog Table block and use the right sidebar to toggle the **"Show Author"** option or switch the style to **Stripes**.

## Screenshots

*(Note: Add your own screenshots here if hosting on GitHub or WordPress.org)*
1. `screenshot-1.png`: The backend input block with automatic date and author generation.
2. `screenshot-2.png`: The Gutenberg sidebar settings and live-preview of the table.

## Changelog

### 1.1.0 (2026-07-20)
* Added automatic author detection for logged-in users.
* Added a Gutenberg sidebar toggle control to hide/show the author column.
* Integrated native WordPress table block styling attributes (`hasFixedLayout`, `is-style-stripes`, and alignments).

### 1.0.0
* Initial release with basic backend logging and dynamic frontend rendering.

## License

This project is licensed under the GPLv2 or later.

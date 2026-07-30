# Plan / Feature-Stand (wp-changelog)

Quelle der Wahrheit: GitHub [sfambach/wp-changelog](https://github.com/sfambach/wp-changelog) (v2.0.0).

## Blöcke

| Anzeigename | Slug | Sichtbarkeit |
|-------------|------|--------------|
| Einzel-Log-Eintrag | `wpc/single-change-note` (+ legacy `wpc/change-item`) | nur Editor |
| Log-Liste | `wpc/multi-change-note` (+ legacy `wpc/multi-note`) | nur Editor |
| Logausgabe | `wpc/change-log` (+ legacy `wpc/change-table`) | Frontend (konfigurierbar) |

## Logausgabe-Einstellungen

- Sort Order (asc/desc)
- Visible on page
- Show Author / Fixed width / Table style
- Consolidate dates + List for changes + merged change sort
- Caption (Logbuch/Changelog), Toolbar-Toggle

## Session-Notizen (2026-07-26)

Wiederhergestellt von v1.6.0-Basis; danach UI-Fixes: Junction-URL, DE-Namen, Caption, Spaltenbreiten, Sync-Button.

## Architektur (2026-07-29 / v2.0.0)

PHP ist OOP unter Namespace `WPChangelog` (`src/`), Views in `templates/`. Keine globalen `wpc_*`-Funktionen mehr.

## Session-Notizen (2026-07-30)

- Theme-Tokens für Tabellenhintergrund/Stripes (`color-mix`)
- Einzel-Log: kompakte Idle-Ansicht, Enter → Absatz, `#log`-Shortcut
- Absätze zwischen Log-Zeilen im Editor besser sichtbar

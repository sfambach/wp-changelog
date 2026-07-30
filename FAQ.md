# FAQ — Gutenberg Changelog

## Warum sehe ich die Blöcke im Editor nicht?

Bei Windows-Junctions/Symlinks muss die Plugin-URL über den Plugin-Slug aufgelöst werden. Ab v1.6.1 nutzt `Helpers::plugin_url()` den Pfad `wp-changelog/wp-changelog.php`. Danach Editor hart neu laden (**Strg+F5**).

## Wo stelle ich die Sortierung ein?

- **Logausgabe** (Change Log): Block-Sidebar → *General Settings* → **Sort Order** (Newest / Oldest on top).
- Global: **Einstellungen → Change Log**.

## Standardbeschriftung der Logausgabe

Die Beschriftung ist standardmäßig **Logbuch** (DE) bzw. **Changelog** (EN). Im Editor unter der Tabelle bearbeiten oder über den Toolbar-Button „Caption“ ein-/ausblenden.

## Tabellenstil „Stripes“

Unter Block-Sidebar → *General Settings* → **Table style** → **Stripes**. Nach Plugin-Updates Editor mit **Strg+F5** neu laden, damit die Preview-Skripte aktualisiert werden.

Hintergrund und Stripes nutzen Theme-Tokens (`--wp--preset--color--base` / `--wp--preset--color--contrast` via `color-mix`). Block-Themes mit `theme.json` übernehmen so Farben hell und dunkel; klassische Themes fallen auf `currentColor` / transparent zurück. Themes können `.wp-block-table.is-style-stripes` weiterhin überschreiben.

## Enter im Einzel-Log

Im Change-Feld legt **Enter** einen neuen Absatz *nach* dem Eintrag an (bzw. springt in den nächsten leeren Absatz). So entsteht eine normale Schreibzeile zwischen Log-Einträgen.

## Absatz zwischen Log-Einträgen

Über das **+** zwischen zwei Einzel-Logs kannst du einen Absatz einfügen. Leere Absätze zwischen den kompakten Log-Zeilen sind im Editor bewusst etwas höher, damit sie nicht „verschwinden“. Editor nach Plugin-Update mit **Strg+F5** neu laden.

## Sync fehlender Daten in der Log-Liste

Button **Sync missing dates from page** ergänzt Zeilen für Datumsangaben aus Einzel-Log-Einträgen und dem Beitragsdatum, die in der Log-Liste noch fehlen.

## Sortierung der Log-Liste

Sidebar → **Table Settings**:
- **Order by** — Date / Change / Author
- **Sort direction** — Oldest / Newest on top

Die Liste wird neu sortiert, sobald du die Einstellungen änderst, und erneut **nach dem Speichern** (falls sich Daten geändert haben). Beim Tippen in Zeilen springt die Reihenfolge nicht.

## Sortierung der Logausgabe

Sidebar → **General Settings** → **Sort Order**, plus unter **Changefield Options** die Optionen für zusammengeführte Zellen. Die Live-Vorschau aktualisiert sich sofort bei Einstellungsänderungen (Editor ggf. mit **Strg+F5** neu laden).

## „Post created“-Datum

Die Zeile **Post created** / **Beitrag erstellt** nimmt das **früheste Datum aus den Change-Notes** (Einzel-Log-Einträge und Log-Liste). Gibt es noch keine Einträge, wird das Beitragsdatum (`post_date`) als Fallback verwendet.

## Globale Integration

Unter **Einstellungen → Change Log** kann die Tabelle automatisch an ausgewählte Beitragstypen angehängt werden. Ein manueller Logausgabe-Block auf der Seite hat Vorrang.

## Shortcut `#log` im Editor

Unter **Einstellungen → Change Log → Editor shortcut** kannst du die Prefix-Umwandlung ein-/ausschalten und das Prefix ändern (Standard: `#log`).

In einem leeren Absatz tippe z.B. `#log Fixed typo` — nach dem Leerzeichen hinter dem Prefix wird daraus ein **Einzel-Log-Eintrag**; der Text danach landet in der Change-Spalte. Danach Editor ggf. mit **Strg+F5** neu laden.

## Alte Block-Namen

Legacy-Slugs (`wpc/change-item`, `wpc/multi-note`, `wpc/change-table`) bleiben für bestehende Inhalte registriert, sind aber im Inserter ausgeblendet.

# FAQ — Gutenberg Changelog

## Warum sehe ich die Blöcke im Editor nicht?

Bei Windows-Junctions/Symlinks muss die Plugin-URL über den Plugin-Slug aufgelöst werden. Ab v1.6.1 nutzt `wpc_plugin_url()` den Pfad `wp-changelog/wp-changelog.php`. Danach Editor hart neu laden (**Strg+F5**).

## Wo stelle ich die Sortierung ein?

- **Logausgabe** (Change Log): Block-Sidebar → *General Settings* → **Sort Order** (Newest / Oldest on top).
- Global: **Einstellungen → Change Log**.

## Standardbeschriftung der Logausgabe

Die Beschriftung ist standardmäßig **Logbuch** (DE) bzw. **Changelog** (EN). Im Editor unter der Tabelle bearbeiten oder über den Toolbar-Button „Caption“ ein-/ausblenden.

## Tabellenstil „Stripes“

Unter Block-Sidebar → *General Settings* → **Table style** → **Stripes**. Nach Plugin-Updates Editor mit **Strg+F5** neu laden, damit die Preview-Skripte aktualisiert werden.

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

## Alte Block-Namen

Legacy-Slugs (`wpc/change-item`, `wpc/multi-note`, `wpc/change-table`) bleiben für bestehende Inhalte registriert, sind aber im Inserter ausgeblendet.

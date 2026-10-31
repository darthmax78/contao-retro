# contao-retro

Eigene Inhaltselemente, Templates und CSS für **retro-gaming.at**
(Contao 6 mit RockSolid Oneo, CRT-Arcade-Design).

Das Theme selbst bleibt unverändert. Was hier liegt, ist alles, was über die
`custom.css` hinausgeht: PHP, DCA, Sprachdateien, Twig-Templates und das CSS
der eigenen Bausteine – jeweils als eigenes, abgeschlossenes Modul.

## Aufbau

| Pfad | Inhalt |
|---|---|
| `src/` | Bundle-Klasse, Manager-Plugin, Controller (`Darthmax\RetroBundle\`) |
| `config/services.yaml` | Autowiring für alles unter `src/` |
| `contao/dca/`, `contao/languages/de/` | Paletten, Felder, Bezeichnungen |
| `contao/templates/` | Twig-Templates (`content_element/<typ>.html.twig` …) |
| `contao/templates/.twig-root` | **Pflicht** – ohne die Datei sucht Contao `ce_<typ>` statt `content_element/<typ>` |
| `public/` | CSS, Icons, JS → nach der Installation unter `bundles/darthmaxretro/` |

Gleichnamige Templates im `templates/`-Ordner der Website gewinnen gegen das Bundle.

## CSS der Bausteine

Je Baustein eine Datei unter `public/css/` (`retro-table`, `last-edited`, `stub`, `banner-lcd`,
`anecdote`). Der `RetroCssListener` hängt sie als eigene `<link>` **nach** allen Stylesheets
des Layouts an (Oneo `main.css` → `custom.css` → Bundle). Farben kommen ausschließlich aus
den `--rg-*`-Variablen, die die `custom.css` der Website im `:root` festlegt.

Den SVG-Filter `#lcd-gruen` für den Banner-Filter gibt der `LcdFilterListener` auf jeder
Frontend-Seite vor `</body>` aus – nur, wenn die Seite noch keinen hat (keine doppelte ID).

## Installation

Das Repository ist öffentlich, ein Token ist nicht nötig.

```bash
composer config repositories.contao-retro vcs https://github.com/darthmax78/contao-retro
composer require darthmax78/contao-retro:^0.3
vendor/bin/contao-console cache:clear
```

Neue Datenbankfelder brauchen danach `contao:migrate`.

## Änderungen

Code nur hier im Repository ändern, nie in `vendor/`. Auf der Website danach
`composer update darthmax78/contao-retro` und `cache:clear`.
Jedes Arbeitspaket bekommt eine Nummer `rg-NN` im Commit-Text.

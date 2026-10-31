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

## Installation

Das Repository ist öffentlich, ein Token ist nicht nötig.

```bash
composer config repositories.contao-retro vcs https://github.com/darthmax78/contao-retro
composer require darthmax78/contao-retro:^0.1
vendor/bin/contao-console cache:clear
```

Neue Datenbankfelder brauchen danach `contao:migrate`.

## Änderungen

Code nur hier im Repository ändern, nie in `vendor/`. Auf der Website danach
`composer update darthmax78/contao-retro` und `cache:clear`.
Jedes Arbeitspaket bekommt eine Nummer `rg-NN` im Commit-Text.

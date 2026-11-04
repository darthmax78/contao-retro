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
| `contao/config/config.php` | Backend-Bereich |
| `contao/dca/`, `contao/languages/de/` | Paletten, Felder, Bezeichnungen |
| `contao/templates/` | Twig-Templates (`content_element/<typ>.html.twig` …) |
| `contao/templates/.twig-root` | **Pflicht** – ohne die Datei sucht Contao `ce_<typ>` statt `content_element/<typ>` |
| `public/` | CSS, Icons, JS → nach der Installation unter `bundles/darthmaxretro/` |

Gleichnamige Templates im `templates/`-Ordner der Website gewinnen gegen das Bundle.

## CSS der Bausteine

Je Baustein eine Datei unter `public/css/` (`retro-table`, `last-edited`, `stub`, `banner-lcd`,
`anecdote`, `abschnitte`). Der `RetroCssListener` hängt sie als eigene `<link>` **nach** allen Stylesheets
des Layouts an (Oneo `main.css` → `custom.css` → Bundle). Farben kommen ausschließlich aus
den `--rg-*`-Variablen, die die `custom.css` der Website im `:root` festlegt.

Den SVG-Filter `#lcd-gruen` für den Banner-Filter gibt der `LcdFilterListener` auf jeder
Frontend-Seite direkt nach `<body>` aus – nur, wenn die Seite noch keinen hat (keine doppelte ID).
Er muss vor seinen Verwendern stehen, sonst bleibt der Banner in Safari bei Seiten aus dem Cache bunt.

## Abschnittstitel und Sprungleiste

Zwei Inhaltselemente (Herkunft `darthmax78/contao-inventar` v1.4.2, Typen und Felder `sa_*` unverändert):

- **Abschnittstitel** (`sa_abschnitt`): Überzeile (`sa_kicker`) als Terminal-Prompt `> TEXT ───`,
  Überschrift aus dem Standardfeld, optionale Einleitung (`sa_lead`). Klasse `centered` zentriert.
- **Sprungleiste** (`sa_sprungleiste`): Links auf alle Abschnittstitel der Seite (in News/Terminen nur
  des eigenen Beitrags), Beschriftung = Überzeile, sonst Überschrift. Erscheint ab zwei Zielen, klebt beim
  Scrollen unter dem Kopf, markiert den aktuellen Abschnitt (▶-Cursor, LCD-Button-Look).

CSS `public/css/abschnitte.css`, Skript `public/js/sprungleiste.js` (erkennt den fixierten Kopf selbst).
Normale Überschriften im Text zählen nicht als Sprungziel.

## Geräte-Datenbank

Backend-Bereich **Retro-Datenbank** (seit rg-09): Konsolen, Handhelds, Homecomputer und PDAs mit festen
Spalten statt frei definierbarer Merkmale. Die Palette richtet sich nach dem Typ.

| Tabelle | Inhalt |
|---|---|
| `tl_retro_geraet` | Gerät mit allen Spezifikationen, Prüfstatus, Artikel-Link |
| `tl_retro_geraet_firma` | Firmen je Gerät mit Rolle (Hersteller, Entwicklung, Vertrieb) |
| `tl_retro_geraet_komponente` | Chips je Gerät mit Rolle und Takt |
| `tl_retro_release` | Marktstarts und Preisänderungen je Region, Preis in Originalwährung |
| `tl_retro_quelle` | Quellen je Gerät, optional je Steckbrief-Gruppe |
| `tl_retro_firma`, `tl_retro_komponente` | Kataloge |

Revisionen sind eigene Geräte und verweisen über `basis` auf das Basismodell (eine Ebene).
Leere Felder erben von dort, `0` ist ein echter Wert. Zahl und Anzeigetext sind getrennt: Die Zahl dient
Vergleichen, der Text überschreibt die Anzeige. Speicher steht in KB (1 KB = 1024 Byte), Datumsangaben als
Jahr, Monat und Tag, damit unbekannte Teile leer bleiben können.

Das Inhaltselement **Steckbrief** (`retro_steckbrief`, seit rg-10) gibt ein Gerät in der Optik von
`retro-table steckbrief` aus. `SteckbriefBauer` ist reine Logik ohne Datenbank, `GeraetLader` holt die Daten.
Marktstarts erbt ein Modell nie, Firmen und Komponenten nur, wenn es selbst keine hat.
Änderungen an Gerät, Basismodell, Firma oder Komponente machen die Cache-Tags der Seite ungültig.

## Installation

Das Repository ist öffentlich, ein Token ist nicht nötig.

```bash
composer config repositories.contao-retro vcs https://github.com/darthmax78/contao-retro
composer require darthmax78/contao-retro:^0.6
vendor/bin/contao-console cache:clear
```

Neue Datenbankfelder brauchen danach `contao:migrate`.

## Änderungen

Code nur hier im Repository ändern, nie in `vendor/`. Auf der Website danach
`composer update darthmax78/contao-retro` und `cache:clear`.
Jedes Arbeitspaket bekommt eine Nummer `rg-NN` im Commit-Text.

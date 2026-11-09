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

Je Baustein eine Datei unter `public/css/` (`retro-table`, `steckbrief`, `last-edited`, `stub`, `banner-lcd`,
`anecdote`, `abschnitte`, `zitat`, `kacheln`, `bild`, `quellen`, `spiele`, `timeline`). Der `RetroCssListener` hängt sie als eigene `<link>` **nach** allen Stylesheets
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
  des eigenen Beitrags), Beschriftung = eigener Leistentext (`sa_sprungtext`, seit rg-21), sonst Überzeile, sonst Überschrift. Erscheint ab zwei Zielen, klebt beim
  Scrollen unter dem Kopf, markiert den aktuellen Abschnitt (▶-Cursor, LCD-Button-Look).

CSS `public/css/abschnitte.css`, Skript `public/js/sprungleiste.js` (erkennt den fixierten Kopf selbst).
Normale Überschriften im Text zählen nicht als Sprungziel.

Dieselbe Optik wie die Überzeile bekommen Elementüberschriften von Galerien automatisch und alle anderen Elemente
mit der Klasse `titel-prompt` (seit rg-21, ebenfalls in `abschnitte.css`). Sie sind kein Sprungziel.

## Zitat

Inhaltselement **Zitat** (`retro_zitat`, seit rg-17): Pixel-Anführungszeichen, Original (`zitat_text`, Sprache
in `zitat_sprache` als `lang`-Attribut), optionale Übersetzung (`zitat_uebersetzung`, kursiv), darunter Name,
Zusatz und ein Quellenlink (`zitat_quelle_text`, `zitat_quelle_url`: Anker im Quellenblock, Seite oder
Webadresse). Jede Zeile der Textfelder wird ein Absatz. CSS `public/css/zitat.css` setzt die
`blockquote`-Vorgaben von Oneo zurück. Optionales Porträt (`zitat_bild` + Standardfeld `size`, seit rg-18).

## Produktfoto

Klasse `bild-produkt` für das Contao-Bild-Element (seit rg-21): Rahmen im Kachel-Look, Bild zentriert und höchstens
440 px hoch, Bildunterschrift im Terminal-Stil. Mit `bild-lcd` zusätzlich in LCD-Grün (Filter `#lcd-gruen`),
Originalfarben beim Überfahren. CSS `public/css/bild.css`.

## Quellen

Inhaltselement **Quellen** (`retro_quellen`, seit rg-24): Zeilenliste (`quellen_liste`: Herausgeber, Titel, Link, Wofür,
Abgerufen), ausgegeben als nummerierte Liste `[1] Herausgeber: Titel – Wofür (abgerufen am …)`. Jeder Eintrag hat den
Anker `quelle-N` (mit `quellen_kennung` „lynx“: `quelle-lynx-N`) – Ziel für den Quellenlink im Zitat; ein angesprungener
Eintrag wird hervorgehoben. Das Element ist selbst Ziel der Sprungleiste (Leistentext `sa_sprungtext`, sonst Überschrift,
sonst „Quellen“). Links nur mit `https://`, `/pfad`, `#anker` oder Insert-Tag. CSS `public/css/quellen.css`.

## Kacheln

Rahmen **Kacheln** (`retro_kacheln`, seit rg-20) mit Überschrift aus dem Standardfeld; darin nur Kindelemente
**Kachel** (`retro_kachel`): Marke (`kachel_marke`, LCD-Pille), Bild (`kachel_bild` + `size`), Linktext
(`kachel_linktext`, Pfeil automatisch). Die ganze Kachel ist der Link: Abschnittstitel der Seite
(`kachel_abschnitt` → `#abschnitt-<id>`, Auswahl über `KachelListener`) oder `kachel_url`. Raster mit
so vielen Spalten, wie mit mindestens 260 px passen. CSS `public/css/kacheln.css`.

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
| `tl_retro_firma`, `tl_retro_komponente`, `tl_retro_zubehoer` | Kataloge |

Revisionen sind eigene Geräte und verweisen über `basis` auf das Basismodell (eine Ebene).
Leere Felder erben von dort, `0` ist ein echter Wert. Zahl und Anzeigetext sind getrennt: Die Zahl dient
Vergleichen, der Text überschreibt die Anzeige. Speicher steht in KB (1 KB = 1024 Byte), Datumsangaben als
Jahr, Monat und Tag, damit unbekannte Teile leer bleiben können.

Original-Zubehör steht im Katalog `tl_retro_zubehoer` (seit rg-12) und wird direkt am Teil mehreren Geräten zugeordnet,
es wird nicht vererbt. Das Inhaltselement **Zubehör** (`retro_zubehoer`) gibt die Tabelle zu einem oder mehreren Geräten aus
(`retro_geraete`, seit rg-23): Jedes Teil steht einmal da, bei mehreren Geräten mit Spalte „Passt zu“ („beide“/„alle“
oder die Namen). `ZubehoerGeraeteMigration` überträgt das bisherige Einzelgerät (`retro_geraet`) in die Liste.

Das Inhaltselement **Steckbrief** (`retro_steckbrief`, seit rg-10) gibt ein Gerät in der Optik von
`retro-table steckbrief` aus. `SteckbriefBauer` ist reine Logik ohne Datenbank, `GeraetLader` holt die Daten.
Über der Tabelle steht eine Titelzeile aus Typ, Jahr, Hersteller, Name und Modellnummern. Die Gruppen verteilen sich
auf zwei Spalten, sobald das Element breit genug ist (Container-Abfrage in `public/css/steckbrief.css`).
Marktstarts erbt ein Modell nie, Firmen und Komponenten nur, wenn es selbst keine hat.
Änderungen an Gerät, Basismodell, Firma oder Komponente machen die Cache-Tags der Seite ungültig.

Das Inhaltselement **Unterschiede** (`retro_unterschiede`, seit rg-22) baut die Steckbriefe eines Geräts und seines
Basismodells und zeigt nur die Zeilen mit abweichendem Wert (`SteckbriefBauer::unterschiede()`), neuer Wert in Amber.
Modellliste und „Neu gegenüber …“ werden nicht verglichen; der Freitext erscheint als letzte Zeile „Außerdem neu“.
Ohne Basismodell oder ohne Unterschiede bleibt das Element im Frontend leer.

## Spiele-Datenbank

Backend **Retro-Datenbank › Spiele** (`tl_retro_spiel`, seit rg-25): ein Datensatz je Spiel und Plattform, Entwickler und
Publisher aus dem Firmen-Katalog, optional bekannte Programmierer, Grafiker und Musiker (rg-26/rg-28) und ein Teaser, Genre aus fester Liste (`Spiele\Genres`), bis zu 6 Screenshots (sortierbar, das erste ist
das Galeriebild), „In der Galerie zeigen“ + Sortierzahl.

**CSV-Import** über die Schaltfläche in der Spieleliste (`SpieleImportModul`, `Spiele\SpieleCsv`, `Spiele\SpieleImport`):
Kopfzeile Pflicht, Trennzeichen `;` `,` oder Tab, UTF-8 oder Windows-1252. Spalten `name`, `geraet` (Pflicht), `entwickler`,
`publisher`, `programmierer`, `grafiker`, `musiker`, `teaser`, `jahr`/`monat`/`tag` oder `datum`, `region`, `genre`, `spieler`, `modellnummer`, `modul_kb`, `beschreibung`,
`screenshots` (Dateien durch `|`, ohne `files/` relativ zum Bildordner), `galerie`, `sortierung`, `quelle`, `veroeffentlicht`, `status` (seit rg-30: `offiziell`, `prototyp`, `homebrew`, auch englisch).
Name + Gerät erkennen vorhandene Spiele, leere Zellen ändern nichts, fehlende Firmen werden angelegt. Immer zuerst Probelauf
mit Bericht, geschrieben wird erst mit „Jetzt importieren“ (in einer Transaktion).

Revisionen bekommen keine eigenen Spiele: Ein Spiel hängt am Basismodell und gilt für alle Revisionen (rg-28); der Import
weist darauf hin, wenn eine Zeile eine Revision nennt.

Inhaltselement **Spielegalerie** (`retro_spielegalerie`): Spiele der gewählten Geräte (`retro_geraete`, samt Basismodell) mit Häkchen, Raster mit
3/2/1 Spalten, Screenshot pixelgenau skaliert (ohne Bildgröße in Originalgröße), Klick öffnet alle Screenshots des Spiels
in der Großansicht. Mit „Bildschirm“ (`spielegalerie_display`, seit rg-29) zeigt sie Game-Boy-Screenshots im Look von
DMG, Pocket oder Light: `Spiele\DisplayFaerber` färbt per GD um (Helligkeit auf den Bildbereich gespreizt, Verlauf dunkel→hell
des Displays), Ergebnis als WebP unter `assets/retro-display/` (600 px Galerie, 1000 px Großansicht), erzeugt beim ersten Abruf
über die Route `/_retro/display/{look}/{breite}/{uuid}.webp` (nur Screenshots von Spielen). Darunter Name und Angaben (`spielegalerie_angaben`: Jahr, Genre, Publisher, Entwickler). CSS `public/css/spiele.css`.

**Status** (`status`, seit rg-30, `Spiele\Status`): offiziell erschienen (Vorgabe, auch für bestehende Spiele),
unveröffentlicht/Prototyp, Homebrew/Neuerscheinung. Bestimmt, was die Spielestatistik zählt.

Inhaltselement **Spielestatistik** (`retro_spielestatistik`, seit rg-30, `Spiele\SpieleStatistik`): rechnet live aus
`tl_retro_spiel` für die gewählten Geräte samt Basismodell, nur veröffentlichte Spiele mit dem gewählten Status
(`statistik_status`, Vorgabe offiziell); die Galerie-Markierung zählt nicht. Blöcke (`statistik_bloecke`): Kennzahlen
(Anzahl, Zeitraum, Publisher, Ø Modulgröße, größtes Modul in Amber, darunter was gezählt wurde und wo Angaben fehlen),
Spiele pro Jahr (Segmentsäulen, Jahre lückenlos), Genres (die häufigsten `statistik_genres_max`, Vorgabe 6, Rest als
„Weitere“ ohne Balken), häufigste Publisher. CSS `public/css/statistik.css`.

Inhaltselement **Spieleliste** (`retro_spieleliste`, seit rg-31): alle Spiele der gewählten Geräte samt Basismodell als
Tabelle im `retro-table`-Look, gefiltert nach Status wie die Spielestatistik (`statistik_status`). Spalten wählbar
(`spieleliste_spalten`: Jahr, Genre, Entwickler, Publisher, Modul, Spieler; Name immer), Suchfeld optional
(`spieleliste_suche`). `public/js/spieleliste.js` sortiert per Klick auf den Spaltenkopf (Zahlen numerisch, Spieler nach
Höchstzahl, leere Werte ans Ende) und filtert beim Tippen; ohne Skript bleibt die Liste nach Name sortiert. Am Handy
Karten mit Beschriftung, Sortierköpfe als Knopfleiste. CSS `public/css/spieleliste.css`.

## Installation

Das Repository ist öffentlich, ein Token ist nicht nötig.

```bash
composer config repositories.contao-retro vcs https://github.com/darthmax78/contao-retro
composer require darthmax78/contao-retro:^0.22
vendor/bin/contao-console cache:clear
```

Neue Datenbankfelder brauchen danach `contao:migrate`.

## Änderungen

Code nur hier im Repository ändern, nie in `vendor/`. Auf der Website danach
`composer update darthmax78/contao-retro` und `cache:clear`.
Jedes Arbeitspaket bekommt eine Nummer `rg-NN` im Commit-Text.

## Arbeitspakete im Backend

Das Modul **Retro-Datenbank › Arbeitspakete** (nur Administratoren mit Zwei-Faktor-Anmeldung) nimmt
`rg-NN.tar.gz` samt `rg-NN.tar.gz.minisig` an. Die Website führt dabei nichts selbst aus: `Einspieler\Spool`
legt die Dateien und einen Auftrag unter `downloads/_einspieler/` ab, ein getrennter Dienst auf dem Server
prüft die Signatur und führt das Paket aus einer privaten Kopie aus. Der Dienst ist nicht Teil dieses
Bundles; ohne ihn zeigt das Modul nur einen Hinweis.

Ein Paket enthält unter `rg-NN/`:

| Datei | Inhalt |
|---|---|
| `paket.ini` | `paket=rg-NN`, `titel=…`, `beschreibung=…` |
| `paket.sh` | wird mit `probelauf`, `einspielen` oder `zurueck` aufgerufen und läuft ohne Rückfragen |
| `erlaubt.txt` | Datenbankänderungen, die das Paket machen darf: je Zeile `sql <Regex>` oder `migration <Regex>` |

## Timeline

Inhaltselement **Timeline** (`retro_timeline`, seit rg-27): Stationen der Seite als Zeitleiste im CRT-Look –
Leuchtlinie, Einträge als Pixel, Meilensteine in Amber mit Label, Jahreszahlen und Pausen („Insert coin to continue“).

Quellen der Einträge:

- **News** mit „In der Timeline zeigen“ (Bereich Timeline in der News, Felder `timeline_*` in `tl_news`): Art
  (Eintrag/Meilenstein), optional Kurztitel, Kurztext und Tags. Ohne Kurzfassung gelten Überschrift und Teaser,
  der Link führt automatisch zur News. Bewusst eigene Felder statt Contaos „Hervorheben“.
- **Retro-Datenbank › Timeline** (`tl_retro_timeline`): Stationen ohne eigene News und Pausen mit eigenem Text;
  optional eigene Datumsanzeige („08/2025 – 09/2026“) und Link.
- **Automatisch**: Jahreszahlen; Pausen, wenn zwischen zwei Einträgen mindestens `timeline_pause_monate` Monate liegen
  (eine eingetragene Pause ersetzt die automatische an ihrer Stelle).

Einstellungen im Element: Nachrichtenarchive (leer = alle), Reihenfolge, Anzahl (Pausen zählen nicht), Pausen-Schwelle,
kompakt (ohne Text, Tags, Legende) und ein Link zur ganzen Geschichte statt „Fortsetzung folgt“.
Logik in `src/Timeline/TimelineBauer.php` (ohne Contao-Abhängigkeiten), Laden in `TimelineLader`. CSS `public/css/timeline.css`,
schmale Darstellung per Container-Abfrage (Element unter 560 px).

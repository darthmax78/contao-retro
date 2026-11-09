<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Färbt Game-Boy-Screenshots auf ein anderes Display um (rg-29): DMG, Pocket oder Light.
 *
 * Alle drei zeigen dieselben vier Graustufen, nur in anderen Farben. Der Färber liest die
 * Helligkeit jedes Bildpunkts, spreizt sie auf den Bereich des Bildes (1.–99. Perzentil, damit
 * die Herkunft – DMG, Pocket, Light – egal ist) und legt einen Verlauf von der dunkelsten zur
 * hellsten Displayfarbe darüber. Die LCD-Rasterung der Aufnahme bleibt dabei erhalten.
 *
 * Ergebnis: WebP unter assets/retro-display/<look>/<breite>/ (600 für die Galerie, 1000 für die
 * Großansicht), einmal erzeugt und danach aus dem Cache (Schlüssel: Pfad, Änderungszeit, Look,
 * Breite, Version). Erzeugt wird erst beim Abruf über DisplayBildController, nicht beim Seitenaufbau.
 * Farben gemessen an Magnus' Aufnahmen (je 6 Spiele pro Display, 08.11.2026).
 */
final class DisplayFaerber
{
    public const LOOKS = [
        'dmg' => [[0x18, 0x48, 0x02], [0x89, 0x97, 0x04]],
        'pocket' => [[0x12, 0x16, 0x15], [0x8C, 0x91, 0x70]],
        'light' => [[0x01, 0x66, 0x28], [0x03, 0xE7, 0x73]],
    ];

    /** Erlaubte Breiten: Galerie (2× Kachel) und Großansicht */
    public const BREITEN = [600, 1000];

    private const VERSION = 1;
    private const ORDNER = 'assets/retro-display';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projektDir,
    ) {
    }

    /**
     * Öffentlicher Pfad (ohne führenden Schrägstrich) des fertigen Bildes, null wenn es noch nicht existiert.
     */
    public function vorhanden(string $quellePfad, string $look, int $breite): ?string
    {
        $ziel = $this->ziel($quellePfad, $look, $breite);

        return null !== $ziel && is_file($this->projektDir.'/'.$ziel) ? $ziel : null;
    }

    /**
     * Breite und Höhe des Ergebnisses, aus den Maßen der Quelle berechnet (ohne es zu erzeugen).
     *
     * @return array{int, int}|null
     */
    public function masse(string $quellePfad, int $breite): ?array
    {
        $masse = @getimagesize($this->projektDir.'/'.ltrim($quellePfad, '/'));

        if (false === $masse || $masse[0] < 1) {
            return null;
        }

        $b = min($breite, $masse[0]);

        return [$b, (int) round($masse[1] * $b / $masse[0])];
    }

    /**
     * Erzeugt das umgefärbte Bild (falls nötig) und gibt seinen absoluten Dateipfad zurück,
     * null wenn Look oder Breite unbekannt sind, die Datei fehlt oder GD sie nicht lesen kann.
     */
    public function faerben(string $quellePfad, string $look, int $maxBreite): ?string
    {
        $quelle = $this->projektDir.'/'.ltrim($quellePfad, '/');
        $relativ = $this->ziel($quellePfad, $look, $maxBreite);

        if (null === $relativ || !\in_array($maxBreite, self::BREITEN, true) || !\function_exists('imagecreatefromstring')) {
            return null;
        }

        $ziel = $this->projektDir.'/'.$relativ;

        if (is_file($ziel)) {
            return $ziel;
        }

        $bild = @imagecreatefromstring((string) file_get_contents($quelle));

        if (false === $bild) {
            return null;
        }

        if (!imageistruecolor($bild)) {
            imagepalettetotruecolor($bild);
        }

        if (imagesx($bild) > $maxBreite) {
            $klein = imagescale($bild, $maxBreite, -1, IMG_BICUBIC);
            imagedestroy($bild);

            if (false === $klein) {
                return null;
            }

            $bild = $klein;
        }

        $breite = imagesx($bild);
        $hoehe = imagesy($bild);
        $helligkeit = [];

        for ($y = 0; $y < $hoehe; ++$y) {
            for ($x = 0; $x < $breite; ++$x) {
                $c = imagecolorat($bild, $x, $y);
                $helligkeit[] = (int) ((($c >> 16 & 0xFF) * 299 + ($c >> 8 & 0xFF) * 587 + ($c & 0xFF) * 114) / 1000);
            }
        }

        [$unten, $oben] = $this->bereich($helligkeit);
        $tabelle = $this->verlauf(self::LOOKS[$look], $unten, $oben);
        $i = 0;

        for ($y = 0; $y < $hoehe; ++$y) {
            for ($x = 0; $x < $breite; ++$x) {
                imagesetpixel($bild, $x, $y, $tabelle[$helligkeit[$i++]]);
            }
        }

        (new Filesystem())->mkdir(\dirname($ziel));
        $temp = $ziel.'.'.bin2hex(random_bytes(4)).'.tmp';
        $ok = imagewebp($bild, $temp, 90);
        imagedestroy($bild);

        if (!$ok) {
            @unlink($temp);

            return null;
        }

        rename($temp, $ziel);

        return $ziel;
    }

    private function ziel(string $quellePfad, string $look, int $breite): ?string
    {
        $quelle = $this->projektDir.'/'.ltrim($quellePfad, '/');

        if (!isset(self::LOOKS[$look]) || !is_file($quelle)) {
            return null;
        }

        $schluessel = md5($quellePfad.'|'.filemtime($quelle).'|'.$look.'|'.$breite.'|'.self::VERSION);

        return self::ORDNER.'/'.$look.'/'.$breite.'/'.substr($schluessel, 0, 2).'/'.$schluessel.'.webp';
    }

    /**
     * 1. und 99. Perzentil der Helligkeit (Histogramm statt Sortieren).
     *
     * @param list<int> $helligkeit
     *
     * @return array{int, int}
     */
    public function bereich(array $helligkeit): array
    {
        $histogramm = array_fill(0, 256, 0);

        foreach ($helligkeit as $h) {
            ++$histogramm[$h];
        }

        $anzahl = \count($helligkeit);
        $summe = 0;
        $unten = 0;
        $oben = 255;

        foreach ($histogramm as $wert => $n) {
            $summe += $n;

            if ($summe <= $anzahl * 0.01) {
                $unten = $wert;
            }

            if ($summe >= $anzahl * 0.99) {
                $oben = $wert;
                break;
            }
        }

        return [$unten, max($oben, $unten + 1)];
    }

    /**
     * Farbe (GD-Truecolor) je Helligkeit 0–255.
     *
     * @param array{array{int, int, int}, array{int, int, int}} $farben dunkel, hell
     *
     * @return list<int>
     */
    public function verlauf(array $farben, int $unten, int $oben): array
    {
        [$dunkel, $hell] = $farben;
        $tabelle = [];

        for ($h = 0; $h < 256; ++$h) {
            $t = max(0.0, min(1.0, ($h - $unten) / ($oben - $unten)));
            $rgb = [];

            for ($k = 0; $k < 3; ++$k) {
                $rgb[$k] = (int) round($dunkel[$k] + ($hell[$k] - $dunkel[$k]) * $t);
            }

            $tabelle[] = ($rgb[0] << 16) | ($rgb[1] << 8) | $rgb[2];
        }

        return $tabelle;
    }
}

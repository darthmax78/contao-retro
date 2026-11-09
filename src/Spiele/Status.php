<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

/**
 * Status eines Spiels (rg-30): entscheidet, was die Spielestatistik mitzählt.
 * „Offiziell“ = zur Lebenszeit der Plattform regulär erschienen. Vorgabe für neue und bestehende Spiele.
 * Der CSV-Import akzeptiert Schlüssel, Anzeigetext und englische Begriffe.
 */
final class Status
{
    public const LISTE = [
        'offiziell' => 'Offiziell erschienen',
        'prototyp' => 'Unveröffentlicht / Prototyp',
        'homebrew' => 'Homebrew / Neuerscheinung',
    ];

    /** Kurzform für den Hinweis unter der Statistik („Gezählt: offizielle Spiele“) */
    public const KURZ = [
        'offiziell' => 'offizielle Spiele',
        'prototyp' => 'Prototypen',
        'homebrew' => 'Homebrew',
    ];

    private const ANDERE = [
        'official' => 'offiziell', 'licensed' => 'offiziell', 'lizenziert' => 'offiziell', 'released' => 'offiziell',
        'erschienen' => 'offiziell', 'retail' => 'offiziell',
        'unveroeffentlicht' => 'prototyp', 'unreleased' => 'prototyp', 'prototype' => 'prototyp', 'beta' => 'prototyp',
        'cancelled' => 'prototyp', 'canceled' => 'prototyp', 'eingestellt' => 'prototyp',
        'indie' => 'homebrew', 'unlicensed' => 'homebrew', 'unlizenziert' => 'homebrew', 'neuerscheinung' => 'homebrew',
    ];

    public static function erkennen(string $eingabe): ?string
    {
        $norm = self::normal($eingabe);

        if ('' === $norm) {
            return null;
        }

        foreach (self::LISTE as $schluessel => $text) {
            if ($norm === self::normal($schluessel) || $norm === self::normal($text)) {
                return $schluessel;
            }
        }

        return self::ANDERE[$norm] ?? null;
    }

    private static function normal(string $s): string
    {
        $s = mb_strtolower(strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']));

        return (string) preg_replace('/[^a-z0-9]/', '', $s);
    }
}

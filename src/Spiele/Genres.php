<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

/**
 * Genres der Spiele-Datenbank (rg-25). Schlüssel = gespeicherter Wert, Text = Anzeige.
 * Der CSV-Import akzeptiert Schlüssel, Anzeigetext und die englischen Gegenstücke.
 */
final class Genres
{
    public const LISTE = [
        'action' => 'Action',
        'jump_and_run' => 'Jump ’n’ Run',
        'shoot_em_up' => 'Shoot ’em up',
        'beat_em_up' => 'Beat ’em up',
        'kampfspiel' => 'Kampfspiel',
        'rennspiel' => 'Rennspiel',
        'sport' => 'Sport',
        'puzzle' => 'Puzzle',
        'geschicklichkeit' => 'Geschicklichkeit',
        'rollenspiel' => 'Rollenspiel',
        'adventure' => 'Adventure',
        'strategie' => 'Strategie',
        'simulation' => 'Simulation',
        'flipper' => 'Flipper',
        'denkspiel' => 'Brett- und Kartenspiel',
        'sonstiges' => 'Sonstiges',
    ];

    private const ENGLISCH = [
        'platformer' => 'jump_and_run', 'platform' => 'jump_and_run', 'jumpnrun' => 'jump_and_run',
        'shooter' => 'shoot_em_up', 'shmup' => 'shoot_em_up', 'shootemup' => 'shoot_em_up',
        'beatemup' => 'beat_em_up', 'brawler' => 'beat_em_up', 'fighting' => 'kampfspiel', 'fighter' => 'kampfspiel',
        'racing' => 'rennspiel', 'rennen' => 'rennspiel', 'sports' => 'sport',
        'skill' => 'geschicklichkeit', 'arcade' => 'action', 'rpg' => 'rollenspiel', 'roleplaying' => 'rollenspiel',
        'strategy' => 'strategie', 'pinball' => 'flipper', 'board' => 'denkspiel', 'boardgame' => 'denkspiel',
        'card' => 'denkspiel', 'cardgame' => 'denkspiel', 'other' => 'sonstiges', 'misc' => 'sonstiges',
    ];

    /** Schlüssel zu einer Eingabe aus der CSV, null wenn unbekannt. */
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

        return self::ENGLISCH[$norm] ?? null;
    }

    private static function normal(string $s): string
    {
        $s = mb_strtolower(strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']));

        return (string) preg_replace('/[^a-z0-9]/', '', $s);
    }
}

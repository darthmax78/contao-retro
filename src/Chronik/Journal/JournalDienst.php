<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Journal;

use Doctrine\DBAL\Connection;

/**
 * Speichert Tagesprotokolle in tl_chronik_journal (Chronik-Paket).
 *
 * Ein Eintrag je geprüftem Tag; Tage ohne Änderungen werden als „leer“ vermerkt,
 * damit der Cronjob sie nicht jedes Mal neu prüft.
 */
final class JournalDienst
{
    /** So weit holt der Cronjob verpasste Tage nach (Versionen verfallen ohnehin). */
    public const NACHHOLEN_TAGE = 60;

    public function __construct(
        private readonly Connection $db,
        private readonly JournalSammler $sammler,
    ) {
    }

    /** @return array{tag: string, leer: bool, zusammenfassung: string, neu: bool} */
    public function erfassen(\DateTimeImmutable $tag, bool $ueberschreiben = false): array
    {
        $tagText = $tag->format('Y-m-d');
        $vorhanden = $this->db->fetchOne('SELECT id FROM tl_chronik_journal WHERE tag = ?', [$tagText]);

        if (false !== $vorhanden && !$ueberschreiben) {
            $z = $this->db->fetchAssociative('SELECT leer, zusammenfassung FROM tl_chronik_journal WHERE id = ?', [$vorhanden]);

            return ['tag' => $tagText, 'leer' => (bool) $z['leer'], 'zusammenfassung' => (string) $z['zusammenfassung'], 'neu' => false];
        }

        $ergebnis = $this->sammler->tag($tag);
        $werte = [
            'tstamp' => time(),
            'tag' => $tagText,
            'leer' => $ergebnis['leer'] ? 1 : 0,
            'zusammenfassung' => JournalSammler::zusammenfassung($ergebnis['zahlen']),
            'zahlen' => json_encode($ergebnis['zahlen'], JSON_THROW_ON_ERROR),
            'inhalt' => $ergebnis['leer'] ? '' : $ergebnis['markdown'],
        ];

        if (false === $vorhanden) {
            $this->db->insert('tl_chronik_journal', $werte);
        } else {
            $this->db->update('tl_chronik_journal', $werte, ['id' => $vorhanden]);
        }

        return ['tag' => $tagText, 'leer' => $ergebnis['leer'], 'zusammenfassung' => $werte['zusammenfassung'], 'neu' => true];
    }

    /**
     * Holt alle noch nicht erfassten Tage bis gestern nach (Cronjob).
     *
     * @return list<array{tag: string, leer: bool, zusammenfassung: string, neu: bool}>
     */
    public function nachholen(?\DateTimeImmutable $heute = null): array
    {
        $gestern = ($heute ?? new \DateTimeImmutable('today'))->modify('-1 day');
        $letzter = $this->db->fetchOne('SELECT MAX(tag) FROM tl_chronik_journal');
        $grenze = $gestern->modify('-'.(self::NACHHOLEN_TAGE - 1).' days');
        $start = $letzter ? max(new \DateTimeImmutable($letzter.' +1 day'), $grenze) : $gestern;

        $ergebnisse = [];

        for ($tag = $start; $tag <= $gestern; $tag = $tag->modify('+1 day')) {
            $ergebnisse[] = $this->erfassen($tag);
        }

        return $ergebnisse;
    }

    /** @return list<array{tag: string, inhalt: string}> nur Tage mit Änderungen */
    public function lesen(string $von, string $bis): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT tag, inhalt FROM tl_chronik_journal WHERE leer = 0 AND tag BETWEEN ? AND ? ORDER BY tag',
            [$von, $bis],
        );
    }
}

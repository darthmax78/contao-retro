<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Timeline;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsModel;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Lädt die Einträge der Timeline (rg-27):
 *
 * - News mit „In der Timeline zeigen“ (Felder timeline_* in tl_news), optional nur aus
 *   bestimmten Archiven. Titel und Text: Kurzfassung, sonst Überschrift und Teaser.
 *   Der Link führt automatisch zur News.
 * - Einträge aus tl_retro_timeline (Stationen ohne eigene News und Pausen mit eigenem Text).
 *
 * Die Folge (Jahre, automatische Pausen, Reihenfolge, Anzahl) baut der TimelineBauer.
 */
final class TimelineLader
{
    public function __construct(
        private readonly Connection $db,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @param list<int> $archive leer = alle Archive
     *
     * @return list<array<string, mixed>>
     */
    public function eintraege(array $archive, bool $auchUnveroeffentlicht = false): array
    {
        return [...$this->news($archive, $auchUnveroeffentlicht), ...$this->manuell($auchUnveroeffentlicht)];
    }

    /**
     * @param list<int> $archive
     *
     * @return list<array<string, mixed>>
     */
    private function news(array $archive, bool $auchUnveroeffentlicht): array
    {
        $jetzt = time();
        $sql = 'SELECT id, date, headline, teaser, timeline_typ, timeline_titel, timeline_text, timeline_tags
                FROM tl_news WHERE timeline_zeigen = 1';
        $werte = [];
        $typen = [];

        if (!$auchUnveroeffentlicht) {
            // start/stop: leer bzw. 0 = ohne Grenze
            $sql .= " AND published = 1
                      AND (start IS NULL OR start = '' OR start <= ?)
                      AND (stop IS NULL OR stop = '' OR stop > ?)";
            $werte[] = $jetzt;
            $werte[] = $jetzt;
            $typen[] = ParameterType::INTEGER;
            $typen[] = ParameterType::INTEGER;
        }

        if ($archive) {
            $sql .= ' AND pid IN (?)';
            $werte[] = $archive;
            $typen[] = ArrayParameterType::INTEGER;
        }

        $eintraege = [];

        foreach ($this->db->fetchAllAssociative($sql.' ORDER BY date', $werte, $typen) as $zeile) {
            $titel = self::text($zeile['timeline_titel']) ?: self::text($zeile['headline']);
            $text = self::text($zeile['timeline_text']) ?: self::text(strip_tags((string) $zeile['teaser']));

            $eintraege[] = [
                'typ' => 'meilenstein' === $zeile['timeline_typ'] ? 'meilenstein' : 'eintrag',
                'zeit' => (int) $zeile['date'],
                'datum' => date('d.m.Y', (int) $zeile['date']),
                'datum_iso' => date('Y-m-d', (int) $zeile['date']),
                'titel' => $titel,
                'text' => $text,
                'tags' => self::tags((string) $zeile['timeline_tags']),
                'url' => $this->newsUrl((int) $zeile['id']),
                'quelle' => 'news',
            ];
        }

        return $eintraege;
    }

    /** @return list<array<string, mixed>> */
    private function manuell(bool $auchUnveroeffentlicht): array
    {
        $eintraege = [];
        $zeilen = $this->db->fetchAllAssociative(
            'SELECT * FROM tl_retro_timeline WHERE datum IS NOT NULL AND datum > 0'
            .($auchUnveroeffentlicht ? '' : ' AND published = 1').' ORDER BY datum',
        );

        foreach ($zeilen as $zeile) {
            $zeit = (int) $zeile['datum'];

            $eintraege[] = [
                'typ' => \in_array($zeile['typ'], TimelineBauer::TYPEN, true) ? $zeile['typ'] : 'eintrag',
                'zeit' => $zeit,
                'datum' => self::text($zeile['datum_text']) ?: date('d.m.Y', $zeit),
                'datum_iso' => date('Y-m-d', $zeit),
                'titel' => self::text($zeile['titel']),
                'text' => self::text($zeile['text']),
                'tags' => self::tags((string) $zeile['tags']),
                'url' => self::url((string) $zeile['url']),
                'quelle' => 'manuell',
            ];
        }

        return $eintraege;
    }

    private function newsUrl(int $id): string
    {
        try {
            $news = NewsModel::findById($id);

            return null === $news ? '' : $this->urlGenerator->generate($news);
        } catch (\Throwable) {
            // z. B. Archiv ohne Weiterleitungsseite: Eintrag ohne Link
            return '';
        }
    }

    private static function text(mixed $wert): string
    {
        $wert = StringUtil::decodeEntities((string) $wert);

        return trim((string) preg_replace('/\s+/u', ' ', $wert));
    }

    /** @return list<string> */
    private static function tags(string $wert): array
    {
        return array_values(array_filter(array_map(self::text(...), explode(',', StringUtil::decodeEntities($wert)))));
    }

    /** Erlaubt Anker, Seitenpfade, http(s) und Insert-Tags aus dem Link-Picker. */
    private static function url(string $wert): string
    {
        $wert = self::text($wert);

        if (str_starts_with($wert, '#') || str_starts_with($wert, '/') || str_starts_with($wert, '{{')
            || preg_match('~^https?://~i', $wert)) {
            return $wert;
        }

        return '';
    }
}

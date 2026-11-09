<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Spiele\Genres;
use Darthmax\RetroBundle\Spiele\SpieleStatistik;
use Darthmax\RetroBundle\Spiele\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spielestatistik (rg-30): Zahlen zu den Spielen der gewählten Geräte, live aus tl_retro_spiel.
 *
 * Blöcke (einzeln abschaltbar): Kennzahlen (Anzahl, Zeitraum, Publisher, Ø und größtes Modul),
 * Spiele pro Jahr (Segmentsäulen), Genres (Segmentbalken), häufigste Publisher (Textzeile).
 * Gezählt werden nur veröffentlichte Spiele mit dem gewählten Status, Vorgabe „offiziell“.
 */
#[AsContentElement('retro_spielestatistik', category: 'media')]
class SpielestatistikController extends AbstractContentElementController
{
    private const BLOECKE = ['kennzahlen', 'jahre', 'genres', 'firmen'];

    public function __construct(private readonly SpieleStatistik $statistik)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $backend = $this->isBackendScope($request);
        $geraete = array_values(array_unique(array_filter(array_map('intval', StringUtil::deserialize($model->retro_geraete, true)))));
        $bloecke = array_values(array_intersect(self::BLOECKE, StringUtil::deserialize($model->statistik_bloecke, true))) ?: self::BLOECKE;
        $status = array_values(array_intersect(array_keys(Status::LISTE), StringUtil::deserialize($model->statistik_status, true))) ?: ['offiziell'];

        $this->tagResponse(['contao.db.tl_retro_spiel']);

        $daten = $this->statistik->berechnen($geraete, $status, (int) $model->statistik_genres_max ?: 6);

        if (0 === $daten['anzahl'] && !$backend) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);

        $template->set('st_titel', trim((string) ($headline['value'] ?? '')));
        $template->set('st_bloecke', array_fill_keys($bloecke, true));
        $template->set('st_hinweis', $daten['anzahl'] ? '' : ($geraete ? 'Für diese Geräte gibt es noch keine veröffentlichten Spiele mit dem gewählten Status.' : 'Bitte mindestens ein Gerät wählen.'));
        $template->set('st_kennzahlen', $this->kennzahlen($daten));
        $template->set('st_fussnote', $this->fussnote($daten, $status));
        $template->set('st_jahre', $this->jahre($daten['jahre']));
        $template->set('st_genres', $this->genres($daten['genres']));
        $template->set('st_firmen', $daten['firmen']);

        return $template->getResponse();
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return list<array{label: string, wert: string, akzent: bool}>
     */
    private function kennzahlen(array $d): array
    {
        if (0 === $d['anzahl']) {
            return [];
        }

        $zahlen = [['label' => 'Spiele', 'wert' => (string) $d['anzahl'], 'akzent' => false]];

        if (null !== $d['jahr_von']) {
            $zahlen[] = [
                'label' => 'Zeitraum',
                'wert' => $d['jahr_von'] === $d['jahr_bis'] ? (string) $d['jahr_von'] : $d['jahr_von'].'–'.$d['jahr_bis'],
                'akzent' => false,
            ];
        }

        if ($d['publisher'] > 0) {
            $zahlen[] = ['label' => 'Publisher', 'wert' => (string) $d['publisher'], 'akzent' => false];
        }

        if (null !== $d['modul_schnitt']) {
            $zahlen[] = ['label' => 'Ø Modul', 'wert' => SpieleStatistik::groesse($d['modul_schnitt']), 'akzent' => false];
            $zahlen[] = ['label' => 'Größtes Modul', 'wert' => SpieleStatistik::groesse($d['modul_max']), 'akzent' => true];
        }

        return $zahlen;
    }

    /**
     * Was gezählt wurde und wo Angaben fehlen, z. B. „Gezählt: offizielle Spiele · Modulgröße bei 52 von 71 erfasst“.
     *
     * @param array<string, mixed> $d
     * @param list<string>         $status
     */
    private function fussnote(array $d, array $status): string
    {
        if (0 === $d['anzahl']) {
            return '';
        }

        $teile = [];

        if (\count($status) < \count(Status::LISTE)) {
            $kurz = array_map(static fn (string $s): string => Status::KURZ[$s], $status);
            $letzte = array_pop($kurz);
            $teile[] = 'Gezählt: '.($kurz ? implode(', ', $kurz).' und '.$letzte : $letzte);
        }

        if ($d['modul_erfasst'] > 0 && $d['modul_erfasst'] < $d['anzahl']) {
            $teile[] = sprintf('Modulgröße bei %d von %d erfasst', $d['modul_erfasst'], $d['anzahl']);
        }

        if ($d['ohne_jahr'] > 0) {
            $teile[] = 1 === $d['ohne_jahr'] ? '1 Spiel ohne Jahr' : $d['ohne_jahr'].' Spiele ohne Jahr';
        }

        return implode(' · ', $teile);
    }

    /**
     * @param list<array{jahr: int, anzahl: int}> $jahre
     *
     * @return list<array{jahr: int, kurz: string, anzahl: int, hoehe: int}>
     */
    private function jahre(array $jahre): array
    {
        $max = max([1, ...array_column($jahre, 'anzahl')]);

        return array_map(static fn (array $j): array => [
            'jahr' => $j['jahr'],
            'kurz' => substr((string) $j['jahr'], -2),
            'anzahl' => $j['anzahl'],
            // Mindesthöhe, damit ein einzelnes Spiel sichtbar bleibt; leere Jahre bleiben leer
            'hoehe' => 0 === $j['anzahl'] ? 0 : max(4, (int) round(100 * $j['anzahl'] / $max)),
        ], $jahre);
    }

    /**
     * @param list<array{genre: string, anzahl: int}> $genres
     *
     * @return list<array{name: string, rest: bool, anzahl: int, breite: int}>
     */
    private function genres(array $genres): array
    {
        $max = max([1, ...array_column($genres, 'anzahl')]);

        return array_map(static fn (array $g): array => [
            'name' => SpieleStatistik::REST === $g['genre']
                ? sprintf('Weitere (%d %s)', $g['arten'], 1 === $g['arten'] ? 'Genre' : 'Genres')
                : (Genres::LISTE[$g['genre']] ?? $g['genre']),
            'rest' => SpieleStatistik::REST === $g['genre'],
            'anzahl' => $g['anzahl'],
            'breite' => max(3, (int) round(100 * $g['anzahl'] / $max)),
        ], $genres);
    }
}

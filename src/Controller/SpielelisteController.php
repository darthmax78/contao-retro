<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Spiele\Genres;
use Darthmax\RetroBundle\Spiele\SpieleLader;
use Darthmax\RetroBundle\Spiele\SpieleStatistik;
use Darthmax\RetroBundle\Spiele\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spieleliste (rg-31): alle Spiele der gewählten Geräte als Tabelle, live aus tl_retro_spiel.
 *
 * Spalten wählbar (Name immer), per Klick auf den Spaltenkopf sortierbar, optional mit Suchfeld
 * (public/js/spieleliste.js). Gezählt werden veröffentlichte Spiele mit dem gewählten Status
 * (Feld statistik_status, Vorgabe „offiziell“) – dieselbe Zählweise wie die Spielestatistik.
 */
#[AsContentElement('retro_spieleliste', category: 'media')]
class SpielelisteController extends AbstractContentElementController
{
    /** Spalte => Überschrift; Reihenfolge = Reihenfolge in der Tabelle */
    public const SPALTEN = [
        'jahr' => 'Jahr',
        'genre' => 'Genre',
        'entwickler' => 'Entwickler',
        'publisher' => 'Publisher',
        'modul' => 'Modul',
        'spieler' => 'Spieler',
    ];

    public function __construct(
        private readonly SpieleLader $lader,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $backend = $this->isBackendScope($request);
        $geraete = array_values(array_unique(array_filter(array_map('intval', StringUtil::deserialize($model->retro_geraete, true)))));
        $status = array_values(array_intersect(array_keys(Status::LISTE), StringUtil::deserialize($model->statistik_status, true))) ?: ['offiziell'];
        $spalten = array_values(array_intersect(array_keys(self::SPALTEN), StringUtil::deserialize($model->spieleliste_spalten, true)))
            ?: array_keys(self::SPALTEN);

        $this->tagResponse(['contao.db.tl_retro_spiel']);

        $spiele = $this->lader->liste($geraete, $status, $backend || $this->tokenChecker->isPreviewMode());

        if (!$spiele && !$backend) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);
        $skript = \dirname(__DIR__, 2).'/public/js/spieleliste.js';

        $template->set('sl_titel', trim((string) ($headline['value'] ?? '')));
        $template->set('sl_spalten', array_intersect_key(self::SPALTEN, array_flip($spalten)));
        $template->set('sl_zeilen', array_map(fn (array $s): array => $this->zeile($s), $spiele));
        $template->set('sl_suche', (bool) $model->spieleliste_suche);
        $template->set('sl_anzahl', \count($spiele));
        $template->set('sl_gezaehlt', $this->gezaehlt($status));
        $template->set('sl_hinweis', $spiele ? '' : ($geraete ? 'Für diese Geräte gibt es noch keine Spiele mit dem gewählten Status.' : 'Bitte mindestens ein Gerät wählen.'));
        $template->set('sl_skript', sprintf('/bundles/darthmaxretro/js/spieleliste.js?v=%d', is_file($skript) ? filemtime($skript) : 0));

        return $template->getResponse();
    }

    /**
     * Je Spalte Anzeige und Sortierwert (Zahlen numerisch, leere Werte sortiert das Skript ans Ende).
     *
     * @param array<string, mixed> $s
     *
     * @return array{name: string, entwurf: bool, zellen: array<string, array{text: string, wert: string}>}
     */
    private function zeile(array $s): array
    {
        $jahr = (int) $s['jahr'];
        $kb = (int) $s['modul_kb'];
        // Spieler nach Höchstzahl sortieren: „1–8“ → 8, „1–4 (ComLynx)“ → 4
        preg_match_all('/\d+/', (string) $s['spieler'], $zahlen);
        $spieler = $zahlen[0] ? max(array_map('intval', $zahlen[0])) : 0;
        $text = static fn (mixed $v): string => trim(html_entity_decode((string) $v, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));

        return [
            'name' => $text($s['name']),
            'entwurf' => !$s['published'],
            'zellen' => [
                'jahr' => ['text' => $jahr > 0 ? (string) $jahr : '', 'wert' => $jahr > 0 ? (string) $jahr : ''],
                'genre' => ['text' => Genres::LISTE[$s['genre']] ?? '', 'wert' => ''],
                'entwickler' => ['text' => $text($s['entwickler_name'] ?? ''), 'wert' => ''],
                'publisher' => ['text' => $text($s['publisher_name'] ?? ''), 'wert' => ''],
                'modul' => ['text' => $kb > 0 ? SpieleStatistik::groesse($kb) : '', 'wert' => $kb > 0 ? (string) $kb : ''],
                'spieler' => ['text' => $text($s['spieler'] ?? ''), 'wert' => $spieler > 0 ? (string) $spieler : ''],
            ],
        ];
    }

    /** „offizielle Spiele“, „offizielle Spiele und Homebrew“ – leer, wenn alle Status gezählt werden */
    private function gezaehlt(array $status): string
    {
        if (\count($status) >= \count(Status::LISTE)) {
            return '';
        }

        $kurz = array_map(static fn (string $s): string => Status::KURZ[$s], $status);
        $letzte = array_pop($kurz);

        return $kurz ? implode(', ', $kurz).' und '.$letzte : $letzte;
    }
}

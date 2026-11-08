<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprungleiste: sammelt alle sichtbaren Abschnittstitel der Seite direkt aus
 * der Datenbank, auch verschachtelte (ptable tl_content), Reihenfolge wie im
 * Backend. Beschriftung: Überzeile, sonst Überschrift.
 *
 * Liegt die Leiste in einem News-Beitrag oder Termin, wird nur dieser Beitrag
 * durchsucht: Dessen Elemente hängen an tl_news bzw. tl_calendar_events,
 * nicht an den Artikeln der Leserseite.
 *
 * Herkunft: darthmax78/contao-inventar v1.4.2 (Typen und Felder sa_* unverändert), rg-08.
 */
#[AsContentElement('sa_sprungleiste', category: 'links')]
class SprungleisteController extends AbstractContentElementController
{
    private const TYPEN = ['sa_abschnitt', 'retro_quellen'];
    private const MAX_TIEFE = 6;

    public function __construct(private readonly Connection $db)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $seite = $this->getPageModel() ?? $request->attributes->get('pageModel');
        $seitenId = $seite instanceof PageModel ? (int) $seite->id : (int) ($GLOBALS['objPage']->id ?? 0);
        $ziele = [];
        $jetzt = time();

        [$wurzelTabelle, $wurzelId] = $this->wurzel($model);

        if ('tl_article' !== $wurzelTabelle) {
            $this->sammeln($wurzelId, $wurzelTabelle, $ziele, 0, $jetzt);
        } elseif ($seitenId) {
            foreach ($this->db->fetchAllAssociative(
                'SELECT id, published, start, stop FROM tl_article WHERE pid = ? ORDER BY sorting',
                [$seitenId],
            ) as $a) {
                if ((bool) $a['published'] && $this->imZeitraum($a, $jetzt)) {
                    $this->sammeln((int) $a['id'], 'tl_article', $ziele, 0, $jetzt);
                }
            }
        }

        // Wegen <base href> brauchen Sprunglinks den Pfad der Seite vor dem #.
        $template->set('basis', $request->getBaseUrl().$request->getPathInfo());
        $template->set('ziele', $ziele);

        // Skript aus dem Bundle (public/js → /bundles/darthmaxretro/js), Version = Dateizeit
        $skript = \dirname(__DIR__, 2).'/public/js/sprungleiste.js';
        $template->set('skript', sprintf(
            '/bundles/darthmaxretro/js/sprungleiste.js?v=%d',
            is_file($skript) ? filemtime($skript) : 0,
        ));

        return $template->getResponse();
    }

    /**
     * Geht von der Leiste durch verschachtelte Elemente bis zum Besitzer hoch
     * (tl_article, tl_news, tl_calendar_events …).
     *
     * @return array{0: string, 1: int}
     */
    private function wurzel(ContentModel $model): array
    {
        $ptable = (string) $model->ptable;
        $pid = (int) $model->pid;

        for ($i = 0; 'tl_content' === $ptable && $i <= self::MAX_TIEFE; ++$i) {
            $eltern = $this->db->fetchAssociative('SELECT pid, ptable FROM tl_content WHERE id = ?', [$pid]);

            if (false === $eltern) {
                break;
            }

            $ptable = (string) $eltern['ptable'];
            $pid = (int) $eltern['pid'];
        }

        return [$ptable, $pid];
    }

    /**
     * @param list<array{anker: string, label: string}> $ziele
     */
    private function sammeln(int $pid, string $ptable, array &$ziele, int $tiefe, int $jetzt): void
    {
        if ($tiefe > self::MAX_TIEFE) {
            return;
        }

        foreach ($this->db->fetchAllAssociative(
            'SELECT * FROM tl_content WHERE pid = ? AND ptable = ? ORDER BY sorting',
            [$pid, $ptable],
        ) as $e) {
            if ((bool) $e['invisible'] || !$this->imZeitraum($e, $jetzt)) {
                continue;
            }

            if (\in_array($e['type'], self::TYPEN, true)) {
                // Reihenfolge: eigener Leistentext (rg-21) → Überzeile → Überschrift
                $label = trim(StringUtil::decodeEntities((string) ($e['sa_sprungtext'] ?? '')));

                if ('' === $label) {
                    $label = trim(StringUtil::decodeEntities((string) ($e['sa_kicker'] ?? '')));
                }

                if ('' === $label) {
                    $h = StringUtil::deserialize($e['headline'] ?? '', true);
                    $label = trim(StringUtil::decodeEntities((string) ($h['value'] ?? ($h[0] ?? ''))));
                }

                // Quellenblock (rg-24) ohne Leistentext und Überschrift: „Quellen“
                if ('' === $label && 'retro_quellen' === $e['type']) {
                    $label = 'Quellen';
                }

                if ('' !== $label) {
                    $ziele[] = ['anker' => 'abschnitt-'.$e['id'], 'label' => $label];
                }
            }

            $this->sammeln((int) $e['id'], 'tl_content', $ziele, $tiefe + 1, $jetzt);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function imZeitraum(array $row, int $jetzt): bool
    {
        $start = (string) ($row['start'] ?? '');
        $stop = (string) ($row['stop'] ?? '');

        return ('' === $start || '0' === $start || (int) $start <= $jetzt)
            && ('' === $stop || '0' === $stop || (int) $stop > $jetzt);
    }
}

<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;

/**
 * Bindet das CSS der Bausteine auf jeder Frontend-Seite ein
 * (public/css/<modul>.css → /bundles/darthmaxretro/css/<modul>.css).
 *
 * Bewusst als eigene <link> über $GLOBALS['TL_HEAD']: Contao gibt TL_HEAD nach allen
 * Stylesheets des Layouts aus. Über TL_CSS/TL_USER_CSS fasst der Combiner die Dateien
 * dagegen VOR die main.css des Themes. Reihenfolge damit: Oneo main.css → custom.css → Bundle.
 *
 * Die Module verwenden nur die --rg-* Farbvariablen aus der custom.css.
 */
#[AsHook('generatePage')]
class RetroCssListener
{
    /** Reihenfolge = Reihenfolge der Regeln in der früheren custom.css */
    private const MODULE = ['retro-table', 'steckbrief', 'last-edited', 'stub', 'banner-lcd', 'anecdote', 'abschnitte', 'zitat', 'kacheln'];

    public function __invoke(PageModel $page, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $pfad = \dirname(__DIR__, 2).'/public/css/';

        foreach (self::MODULE as $modul) {
            $datei = $pfad.$modul.'.css';

            if (!is_file($datei)) {
                continue;
            }

            $GLOBALS['TL_HEAD']['darthmax_retro_'.$modul] = sprintf(
                '<link rel="stylesheet" href="/bundles/darthmaxretro/css/%s.css?v=%d">',
                $modul,
                filemtime($datei),
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Link\SeitenAnker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zitat mit optionaler Übersetzung und Nachweis (rg-17), Porträtfoto (rg-18).
 *
 * Originaltext in zitat_text (Sprache in zitat_sprache → lang-Attribut), Übersetzung
 * in zitat_uebersetzung, darunter Porträt (zitat_bild + Standardfeld size), Name, Zusatz
 * (Lebensdaten, Rolle) und ein Quellenlink.
 * Absätze = Zeilen im Textfeld. Kein |nl2br im Template (maskiert Entities doppelt).
 */
#[AsContentElement('retro_zitat', category: 'texts')]
class ZitatController extends AbstractContentElementController
{
    /** Ohne gewählte Bildgröße: quadratisch zugeschnitten, doppelte Anzeigegröße für scharfe Bildschirme */
    private const PORTRAET_GROESSE = [144, 144, 'crop'];

    public function __construct(
        private readonly Studio $studio,
        private readonly SeitenAnker $anker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('portraet', $this->portraet($model));
        $template->set('absaetze', self::absaetze((string) $model->zitat_text));
        $template->set('uebersetzung', self::absaetze((string) $model->zitat_uebersetzung));
        $template->set('sprache', self::sprache((string) $model->zitat_sprache));
        $template->set('name', self::text((string) $model->zitat_name));
        $template->set('zusatz', self::text((string) $model->zitat_zusatz));
        $template->set('quelle_url', $this->anker->aufSeite(self::url((string) $model->zitat_quelle_url)));
        $template->set('quelle_text', self::text((string) $model->zitat_quelle_text) ?: 'Quelle');

        return $template->getResponse();
    }

    private function portraet(ContentModel $model): ?Figure
    {
        if (!$model->zitat_bild) {
            return null;
        }

        $groesse = StringUtil::deserialize($model->size, true);
        $gewaehlt = '' !== implode('', array_map('strval', $groesse));

        return $this->studio
            ->createFigureBuilder()
            ->fromUuid((string) $model->zitat_bild)
            ->setSize($gewaehlt ? $model->size : self::PORTRAET_GROESSE)
            ->buildIfResourceExists()
        ;
    }

    private static function text(string $wert): string
    {
        return trim(StringUtil::decodeEntities($wert));
    }

    /** @return list<string> */
    private static function absaetze(string $wert): array
    {
        $zeilen = preg_split('/\R/u', self::text($wert)) ?: [];

        return array_values(array_filter(array_map('trim', $zeilen), static fn (string $z): bool => '' !== $z));
    }

    /** „en“, „de_AT“ oder „de-AT“ → BCP-47 mit Bindestrich, sonst leer. */
    private static function sprache(string $wert): string
    {
        $wert = str_replace('_', '-', self::text($wert));

        return preg_match('/^[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,8})*$/', $wert) ? $wert : '';
    }

    /** Erlaubt Anker, relative Pfade, http(s) und Insert-Tags aus dem Link-Picker. */
    private static function url(string $wert): string
    {
        $wert = self::text($wert);

        if ('' === $wert) {
            return '';
        }

        if (str_starts_with($wert, '#') || str_starts_with($wert, '/') || str_starts_with($wert, '{{')
            || preg_match('~^https?://~i', $wert)) {
            return $wert;
        }

        return '';
    }
}

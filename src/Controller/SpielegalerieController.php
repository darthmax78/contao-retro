<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Spiele\Genres;
use Darthmax\RetroBundle\Spiele\SpieleLader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spielegalerie (rg-25): die für die Galerie markierten Spiele der gewählten Geräte.
 *
 * Je Spiel das erste Bild der Screenshot-Sortierung, darunter Name und bis zu zwei Angaben
 * (Jahr, Genre, Publisher, Entwickler). Ein Klick öffnet alle Screenshots des Spiels in der
 * Großansicht (gemeinsame Lightbox-Gruppe je Spiel). Ohne Bildgröße bleibt der Screenshot
 * in Originalgröße und wird per CSS pixelgenau hochskaliert.
 */
#[AsContentElement('retro_spielegalerie', category: 'media')]
class SpielegalerieController extends AbstractContentElementController
{
    private const ANGABEN = ['jahr', 'genre', 'publisher', 'entwickler', 'programmierer', 'grafiker', 'musiker'];

    /** Bis zu dieser Breite gilt ein Screenshot als native Auflösung und wird pixelgenau vergrößert. */
    private const NATIV_MAX_BREITE = 480;

    /** Größere Screenshots (Aufnahmen mit CRT-/LCD-Filter) weich auf diese Breite verkleinern (2× Anzeige). */
    private const GROSS_BREITE = [800, 0, 'proportional'];

    public function __construct(
        private readonly SpieleLader $lader,
        private readonly Studio $studio,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $backend = $this->isBackendScope($request);
        $geraete = array_values(array_unique(array_filter(array_map('intval', StringUtil::deserialize($model->retro_geraete, true)))));
        $max = (int) $model->spielegalerie_max ?: 12;

        $this->tagResponse(['contao.db.tl_retro_spiel']);

        $spiele = $this->lader->galerie($geraete, $max, $backend || $this->tokenChecker->isPreviewMode());

        if (!$spiele && !$backend) {
            return new Response();
        }

        $angaben = array_values(array_intersect(self::ANGABEN, StringUtil::deserialize($model->spielegalerie_angaben, true)));
        $groesse = StringUtil::deserialize($model->size, true);
        $mitGroesse = '' !== implode('', array_map('strval', $groesse));
        $eintraege = [];

        foreach ($spiele as $spiel) {
            $gruppe = 'spiel-'.$spiel['id'];
            $bilder = array_values(array_filter(StringUtil::deserialize($spiel['screenshots'], true)));
            $figur = null;
            $nativ = false;
            $weitere = [];

            foreach ($bilder as $i => $uuid) {
                $builder = $this->studio->createFigureBuilder()
                    ->fromUuid((string) $uuid)
                    ->enableLightbox()
                    ->setLightboxGroupIdentifier($gruppe)
                ;

                if (0 === $i) {
                    $builder->setMetadata(new Metadata([
                        Metadata::VALUE_ALT => (string) $spiel['name'],
                        Metadata::VALUE_TITLE => $this->titel($spiel),
                    ]));
                    $figur = $builder->setSize($mitGroesse ? $model->size : null)->buildIfResourceExists();
                    $breite = $figur?->getImage()->getOriginalDimensions()->getSize()->getWidth() ?? 0;
                    $nativ = $breite > 0 && $breite <= self::NATIV_MAX_BREITE;

                    // Große Aufnahmen: Nearest-Neighbour beim Verkleinern erzeugt RGB-Moiré, daher weich verkleinern
                    if ($figur && !$nativ && !$mitGroesse) {
                        $figur = $builder->setSize(self::GROSS_BREITE)->buildIfResourceExists();
                    }

                    continue;
                }

                $href = $builder->buildIfResourceExists()?->getLightbox()->getLinkHref();

                if ($href) {
                    $weitere[] = $href;
                }
            }

            $eintraege[] = [
                'name' => (string) $spiel['name'],
                'titel' => $this->titel($spiel),
                'meta' => $this->meta($spiel, $angaben),
                'figur' => $figur,
                'lightbox' => $figur?->hasLightbox() ? $figur->getLightbox()->getLinkHref() : '',
                'gruppe' => $gruppe,
                'weitere' => $weitere,
                'anzahl' => \count($weitere) + ($figur ? 1 : 0),
                'entwurf' => !$spiel['published'],
                'nativ' => $nativ ?? false,
            ];
        }

        $headline = StringUtil::deserialize($model->headline, true);

        $template->set('sg_titel', trim((string) ($headline['value'] ?? '')));
        $template->set('sg_spiele', $eintraege);
        $template->set('sg_hinweis', $eintraege ? '' : ($geraete ? 'Für diese Geräte ist noch kein Spiel „In der Galerie zeigen“ markiert.' : 'Bitte mindestens ein Gerät wählen.'));

        return $template->getResponse();
    }

    /**
     * Bildtitel (auch Beschriftung in der Großansicht): Name, mit Teaser „Name – Teaser“.
     *
     * @param array<string, mixed> $spiel
     */
    private function titel(array $spiel): string
    {
        $teaser = trim(preg_replace('/\s+/u', ' ', (string) ($spiel['teaser'] ?? '')) ?? '');

        return '' === $teaser ? (string) $spiel['name'] : $spiel['name'].' – '.$teaser;
    }

    /**
     * @param array<string, mixed> $spiel
     * @param list<string>         $angaben
     */
    private function meta(array $spiel, array $angaben): string
    {
        $teile = [];

        foreach ($angaben as $angabe) {
            $teile[] = match ($angabe) {
                'jahr' => (int) $spiel['jahr'] > 0 ? (string) $spiel['jahr'] : '',
                'genre' => Genres::LISTE[$spiel['genre']] ?? '',
                'publisher' => (string) ($spiel['publisher_name'] ?? ''),
                'entwickler' => (string) ($spiel['entwickler_name'] ?? ''),
                'programmierer', 'grafiker', 'musiker' => trim((string) ($spiel[$angabe] ?? '')),
                default => '',
            };
        }

        return implode(' · ', array_filter($teile, static fn (string $t): bool => '' !== $t));
    }
}

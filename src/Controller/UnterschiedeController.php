<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Steckbrief\GeraetLader;
use Darthmax\RetroBundle\Steckbrief\SteckbriefBauer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unterschiede (rg-22): Was eine Revision gegenüber ihrem Basismodell ändert.
 *
 * Baut beide Steckbriefe mit dem SteckbriefBauer und zeigt nur die Zeilen mit
 * unterschiedlichem Wert – dieselben Texte wie in den Steckbriefen. Ohne Basismodell
 * oder ohne Unterschiede bleibt das Element leer (im Backend mit Hinweis).
 * Der Freitext „Neu gegenüber Basismodell“ erscheint als letzte Zeile „Außerdem neu“.
 */
#[AsContentElement('retro_unterschiede', category: 'texts')]
class UnterschiedeController extends AbstractContentElementController
{
    public function __construct(
        private readonly GeraetLader $lader,
        private readonly SteckbriefBauer $bauer,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $backend = $this->isBackendScope($request);
        $neu = $this->lader->lade((int) $model->retro_geraet);

        if (null === $neu || (!$neu['geraet']['published'] && !$backend && !$this->tokenChecker->isPreviewMode())) {
            return new Response();
        }

        $basisId = (int) ($neu['basis']['id'] ?? 0);
        $alt = $basisId > 0 ? $this->lader->lade($basisId) : null;

        $tags = ['contao.db.tl_retro_geraet.'.$neu['geraet']['id']];

        if ($alt) {
            $tags[] = 'contao.db.tl_retro_geraet.'.$alt['geraet']['id'];
        }

        $this->tagResponse($tags);

        $zeilen = $alt ? $this->bauer->unterschiede($this->bauer->baue($neu), $this->bauer->baue($alt)) : [];

        $notiz = trim(StringUtil::decodeEntities((string) ($neu['geraet']['neu_gegenueber_basis'] ?? '')));

        if (!$zeilen && '' === $notiz && !$backend) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);
        $eigenerTitel = trim((string) ($headline['value'] ?? ''));
        $altName = (string) ($alt['geraet']['name'] ?? '');

        $template->set('unterschiede_titel', '' !== $eigenerTitel ? $eigenerTitel : ('' !== $altName ? 'Neu gegenüber '.$altName : 'Unterschiede'));
        $template->set('unterschiede_alt', $altName);
        $template->set('unterschiede_neu', (string) $neu['geraet']['name']);
        $template->set('unterschiede_zeilen', $zeilen);
        $template->set('unterschiede_notiz', $alt ? $notiz : '');
        $template->set('unterschiede_hinweis', match (true) {
            null === $alt => 'Dieses Gerät hat kein Basismodell – es gibt nichts zu vergleichen.',
            !$zeilen && '' === $notiz => 'Keine Unterschiede zum Basismodell eingetragen.',
            default => '',
        });

        return $template->getResponse();
    }
}

<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Stub"-Hinweisbox: markiert einen Artikel als im Aufbau befindlich.
 * Nutzt die Standardfelder "headline" und "text" von tl_content,
 * mit sinnvollem Vorgabetext, wenn beide leer bleiben.
 * Typ "stub" (fest im Attribut, unabhängig vom Klassennamen)
 *
 * Wichtig: Die Template-Variable "headline" gehört dem Basis-Template
 * (_base.html.twig erwartet dort ein Objekt mit .text/.tag_name) und wird
 * deshalb NICHT überschrieben. Die Box nutzt eigene Variablen "stub_*".
 */
#[AsContentElement('stub', category: 'texts')]
class StubController extends AbstractContentElementController
{
    private const DEFAULT_HEADLINE = 'Artikel im Aufbau';
    private const DEFAULT_TEXT = '<p>Dieser Artikel ist ein Stub. Inhalte werden laufend ergänzt.</p>';
    private const DEFAULT_TAG = 'h3';
    private const ALLOWED_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Das Feld "headline" speichert serialisiert Text + HTML-Ebene (h1–h6),
        // z. B. a:2:{s:4:"unit";s:2:"h3";s:5:"value";s:4:"test";} – erst deserialisieren.
        $headlineData = StringUtil::deserialize($model->headline, true);
        $headline = trim((string) ($headlineData['value'] ?? ''));
        $tag = (string) ($headlineData['unit'] ?? '');
        $text = trim((string) $model->text);

        $template->set('stub_headline', $headline !== '' ? $headline : self::DEFAULT_HEADLINE);
        $template->set('stub_tag', \in_array($tag, self::ALLOWED_TAGS, true) ? $tag : self::DEFAULT_TAG);
        $template->set(
            'stub_text',
            $text !== '' ? $this->insertTagParser->replace($text) : self::DEFAULT_TEXT
        );

        return $template->getResponse();
    }
}

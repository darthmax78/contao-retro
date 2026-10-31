<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Gibt den SVG-Filter #lcd-gruen einmal pro Frontend-Seite vor </body> aus
 * (gehört zum Baustein „Banner-Filter“, CSS: public/css/banner-lcd.css).
 *
 * Chrome wendet SVG-Filter aus CSS nur an, wenn das <svg> im selben Dokument steht –
 * eine externe .svg-Datei reicht nicht. Steht schon ein Element mit id="lcd-gruen" in
 * der Seite (z. B. noch aus dem Seitenlayout), wird nichts eingefügt: keine doppelte ID.
 *
 * Palette und Rechenweg: Projektdokument design/banner-lcd-gruen-filter.md
 * (Graustufen als flacher Kanal-Durchschnitt, 4 diskrete Stufen
 * #0B1012 / #173322 / #1F4A31 / #2E6B49).
 */
#[AsEventListener]
class LcdFilterListener
{
    private const SVG = <<<'SVG'
        <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false" data-quelle="contao-retro">
          <filter id="lcd-gruen" color-interpolation-filters="sRGB">
            <feColorMatrix type="matrix" values="
              0.3333 0.3333 0.3333 0 0
              0.3333 0.3333 0.3333 0 0
              0.3333 0.3333 0.3333 0 0
              0      0      0      1 0"/>
            <feComponentTransfer>
              <feFuncR type="discrete" tableValues="0.043 0.090 0.122 0.180"/>
              <feFuncG type="discrete" tableValues="0.063 0.200 0.290 0.420"/>
              <feFuncB type="discrete" tableValues="0.071 0.133 0.192 0.286"/>
            </feComponentTransfer>
          </filter>
        </svg>
        SVG;

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || 'frontend' !== $event->getRequest()->attributes->get('_scope')) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        if (!str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return;
        }

        $content = $response->getContent();

        if (!\is_string($content) || preg_match('/\bid\s*=\s*["\']lcd-gruen["\']/', $content)) {
            return;
        }

        $pos = strripos($content, '</body>');

        if (false === $pos) {
            return;
        }

        $response->setContent(substr_replace($content, self::SVG."\n", $pos, 0));
        $response->headers->remove('Content-Length');
    }
}

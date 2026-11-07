<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Link;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Macht aus einem reinen Anker („#abschnitt-12“) einen Link auf die aktuelle Seite
 * („/konsolenwiki/lynx.html#abschnitt-12“).
 *
 * Grund: Contao setzt im Seitenkopf <base href="https://retro-gaming.at/">. Ein reiner Anker
 * wird dadurch relativ zur Startseite aufgelöst und springt dorthin statt in den Abschnitt.
 * Der Pfad kommt aus der Hauptanfrage, nicht aus der Fragment-Anfrage des Elements.
 */
class SeitenAnker
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function aufSeite(string $url): string
    {
        if (!str_starts_with($url, '#')) {
            return $url;
        }

        $anfrage = $this->requestStack->getMainRequest();

        if (null === $anfrage) {
            return $url;
        }

        return $anfrage->getBaseUrl().$anfrage->getPathInfo().$url;
    }
}

<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\StringUtil;

/**
 * Spiele-Datenbank (rg-25): höchstens 6 Screenshots je Spiel.
 */
class SpielListener
{
    public const MAX_SCREENSHOTS = 6;

    #[AsCallback('tl_retro_spiel', 'fields.screenshots.save')]
    public function screenshotsPruefen(mixed $wert): mixed
    {
        if (\count(StringUtil::deserialize($wert, true)) > self::MAX_SCREENSHOTS) {
            throw new \RuntimeException(sprintf('Höchstens %d Screenshots je Spiel.', self::MAX_SCREENSHOTS));
        }

        return $wert;
    }
}

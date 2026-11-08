<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Darthmax\RetroBundle\DarthmaxRetroBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(DarthmaxRetroBundle::class)
                // News-Bundle zuerst: contao/dca/tl_news.php ergänzt dessen Paletten (rg-27)
                ->setLoadAfter([ContaoCoreBundle::class, 'Contao\\NewsBundle\\ContaoNewsBundle']),
        ];
    }
}

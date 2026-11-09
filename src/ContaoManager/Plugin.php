<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Darthmax\RetroBundle\DarthmaxRetroBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(DarthmaxRetroBundle::class)
                // News-Bundle zuerst: contao/dca/tl_news.php ergänzt dessen Paletten (rg-27)
                ->setLoadAfter([ContaoCoreBundle::class, 'Contao\\NewsBundle\\ContaoNewsBundle']),
        ];
    }

    // rg-29: eigene Routen (Screenshots im Display-Look)
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $datei = __DIR__.'/../../config/routes.yaml';

        $loader = $resolver->resolve($datei);

        return $loader ? $loader->load($datei) : null;
    }
}

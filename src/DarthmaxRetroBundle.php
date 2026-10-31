<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Erweiterungen für retro-gaming.at.
 *
 * Bundle-Wurzel ist das Repository (nicht src/), damit Contao die Ordner
 * contao/ (DCA, Sprachen, Templates) und public/ (Assets) findet.
 * Öffentliche Assets landen nach assets:install unter public/bundles/darthmaxretro/.
 */
class DarthmaxRetroBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }
}

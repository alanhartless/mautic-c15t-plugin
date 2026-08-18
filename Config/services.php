<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('MauticPlugin\\C15tBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->alias('mautic.integration.c15t', MauticPlugin\C15tBundle\Integration\C15tIntegration::class);
    $services->alias('mautic.integration.c15t.config', MauticPlugin\C15tBundle\Integration\Support\ConfigSupport::class);
    $services->alias('mautic.c15t.integration_registry', MauticPlugin\C15tBundle\Service\IntegrationRegistry::class);
};

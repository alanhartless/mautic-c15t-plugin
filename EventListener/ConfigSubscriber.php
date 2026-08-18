<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\MauticC15tBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hooks the plugin's site-profiles config into Mautic's main Configuration
 * screen (Configuration -> Consent Manager (c15t)) instead of the
 * Plugins/Integrations config form -- founder direction, matching exactly
 * how MessengerBundle's own RabbitMQ DSN settings work (its
 * EventListener/ConfigSubscriber.php + Form/Type/ConfigType.php, both
 * fetched directly from mautic/mautic on GitHub and mirrored here, not
 * guessed). Confirmed real, not from docs: MessengerBundle's own
 * Config/config.php registers NO explicit service for its
 * ConfigSubscriber at all, yet it demonstrably works (the founder used
 * exactly that screen earlier for RabbitMQ) -- Mautic auto-discovers
 * EventListener/*Subscriber.php classes. This plugin relies on that same
 * auto-discovery applying equally to plugin bundles, not just core ones
 * -- UNVERIFIED against a live install, see this repo's README "Local
 * validation caveat".
 */
class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'MauticC15tBundle',
            'formAlias'  => 'c15tconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticC15t/FormTheme/Config/_config_c15tconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticC15tBundle'),
        ]);
    }
}

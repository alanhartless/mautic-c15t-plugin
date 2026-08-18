<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\C15tBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            'bundle'     => 'C15tBundle',
            'formAlias'  => 'c15tconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticC15t/FormTheme/Config/_config_c15tconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('C15tBundle'),
        ]);
    }
}

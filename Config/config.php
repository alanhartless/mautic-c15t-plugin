<?php

// Route/service shape confirmed against a real, currently-shipped Mautic
// plugin (plugins/MauticTagManagerBundle/Config/config.php, fetched
// directly from mautic/mautic on GitHub, not the outdated plugin-dev
// docs page) -- the 'services.integrations' argument list below is
// copied verbatim from that same file; AbstractIntegration's constructor
// signature is inherited, not something this plugin's own
// ConsentIntegration class changes.

return [
    'name'        => 'c15t Consent Manager',
    'description' => 'Self-hosted consent management (c15t) -- serves one embeddable loader script per site, gates Mautic tracking on consent, manages per-site script-loader integrations.',
    'version'     => '1.0',
    'author'      => 'Wryters Desk',

    'routes' => [
        // Public (unauthenticated) -- any embedding site's visitor browser
        // requests this directly, same as Mautic's own /mtc.js (CoreBundle's
        // JsController, confirmed via app/bundles/CoreBundle/Config/config.php).
        'public' => [
            'mautic_c15t_loader' => [
                'path'       => '/consent.js',
                'controller' => 'MauticPlugin\MauticC15tBundle\Controller\PublicController::loaderAction',
            ],
        ],
    ],

    'services' => [
        'integrations' => [
            'mautic.integration.c15t' => [
                'class'     => MauticPlugin\MauticC15tBundle\Integration\ConsentIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],
        'other' => [
            'mautic.c15t.integration_registry' => [
                'class' => MauticPlugin\MauticC15tBundle\Service\IntegrationRegistry::class,
            ],
        ],
    ],
];

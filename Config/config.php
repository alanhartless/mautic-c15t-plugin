<?php

// Route/service shape confirmed against a real, currently-shipped Mautic
// plugin (plugins/MauticTagManagerBundle/Config/config.php, fetched
// directly from mautic/mautic on GitHub, not the outdated plugin-dev
// docs page) -- the 'services.integrations' argument list below is
// copied verbatim from that same file; AbstractIntegration's constructor
// signature is inherited, not something this plugin's own
// ConsentIntegration class changes.

// IntegrationRegistry has no constructor dependencies, so it's safe to
// instantiate directly here (no DI container available yet at this
// point) -- used only to generate default parameter keys for every
// packaged integration's own enabled/param fields, so this list can
// never drift out of sync with Form/Type/ConfigType.php's own loop over
// the same registry.
$c15tIntegrationDefaults = [];
foreach ((new MauticPlugin\MauticC15tBundle\Service\IntegrationRegistry())->getPackaged() as $c15tKey => $c15tIntegration) {
    $c15tPrefix = str_replace('-', '_', $c15tKey);
    $c15tIntegrationDefaults[$c15tPrefix.'_enabled'] = false;
    foreach (array_keys($c15tIntegration['params']) as $c15tParamKey) {
        $c15tIntegrationDefaults[$c15tPrefix.'_'.$c15tParamKey] = '';
    }
}

return [
    'name'        => 'c15t Consent Manager',
    'description' => 'Self-hosted consent management (c15t) -- serves one embeddable loader script per site, gates Mautic tracking on consent, manages per-site script-loader integrations.',
    'version'     => '1.0',
    'author'      => 'alanhartless',

    // Defaults for the Configuration -> Consent Manager (c15t) screen's
    // fields (EventListener/ConfigSubscriber.php, Form/Type/ConfigType.php)
    // -- read back at runtime via CoreParametersHelper, same pattern as
    // MessengerBundle's own messenger_dsn_* parameters. Per-integration
    // keys come from $c15tIntegrationDefaults above, generated from
    // IntegrationRegistry::getPackaged() so this list can't drift from
    // the form/loader that actually consume it.
    'parameters' => array_merge([
        'domains'               => '',
        'backend_url'           => '',
        'categories'            => ['necessary'],
        'disable_default_css'   => false,
        'advanced_scripts_json' => '[]',
    ], $c15tIntegrationDefaults),

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
            // Form/Type/ConfigType.php now takes real constructor
            // dependencies (IntegrationRegistry + translator), unlike its
            // earlier no-args version -- explicit registration here so
            // Symfony's form factory resolves the container-managed
            // instance (carrying those dependencies) rather than trying a
            // bare `new ConfigType()`, which would hard-fail with "too
            // few arguments". UNVERIFIED against a live install that
            // Mautic's plugin service loader applies the tagging a
            // constructor-injected Form Type needs when registered this
            // way (vs. core bundles' services.yaml, which gets Symfony's
            // full autoconfigure pass) -- see this repo's README "Local
            // validation caveat".
            'mautic.c15t.form.config' => [
                'class'     => MauticPlugin\MauticC15tBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'mautic.c15t.integration_registry',
                    'translator',
                ],
            ],
        ],
    ],
];

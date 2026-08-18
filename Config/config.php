<?php

$c15tIntegrationDefaults = [];
foreach ((new MauticPlugin\C15tBundle\Service\IntegrationRegistry())->getPackaged() as $c15tKey => $c15tIntegration) {
    $c15tPrefix = str_replace('-', '_', $c15tKey);
    $c15tIntegrationDefaults[$c15tPrefix.'_enabled'] = false;
    foreach (array_keys($c15tIntegration['params']) as $c15tParamKey) {
        $c15tIntegrationDefaults[$c15tPrefix.'_'.$c15tParamKey] = '';
    }
}

return [
    'name'        => 'c15t Consent Manager',
    'description' => 'c15t consent manager integration. Requires a c15t self-hosted backend. Configure the plugin thorugh Mautic\'s Configuration.',
    'version'     => '1.0.2',
    'author'      => 'Alan Hartless',

    'parameters' => array_merge([
        'domains'               => '',
        'test_domains'          => '',
        'backend_url'           => '',
        'categories'            => ['necessary'],
        'disable_default_css'   => false,
        'reload_on_restrict'    => false,
        'initial_ui'            => 'banner',
        'enable_focus_trap'     => true,
        'banner_text'           => '',
        'modal_text'            => '',
        'consent_mode'          => 'opt-in',
        'policy_packs'          => [],
        'advanced_scripts_json' => '[]',
    ], $c15tIntegrationDefaults),

    'routes' => [
        'public' => [
            'mautic_c15t_loader' => [
                'path'       => '/consent.js',
                'controller' => 'MauticPlugin\C15tBundle\Controller\PublicController::loaderAction',
            ],
        ],
    ],
];

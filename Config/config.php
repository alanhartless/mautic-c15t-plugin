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
    'description' => 'c15t consent manager integration. Requires a self-hosted backend.',
    'version'     => '1.0.2',
    'author'      => 'Alan Hartless',

    'parameters' => array_merge([
        'domains'               => '',
        'backend_url'           => '',
        'categories'            => ['necessary'],
        'disable_default_css'   => false,
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

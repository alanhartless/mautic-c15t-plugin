<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Enable/disable toggle and settings screen are native to Mautic's own
 * Integration system (Plugins section) -- extending AbstractIntegration
 * and implementing getName()/getDisplayName()/getAuthenticationType() is
 * sufficient for that, confirmed against a real, minimal, currently-
 * shipped example (plugins/MauticTagManagerBundle/Integration/
 * TagManagerIntegration.php on GitHub), not assumed from docs.
 *
 * Site-profile configuration -- domain(s) -> consent categories +
 * script-loader integrations, the per-site extensibility this plugin
 * needs -- is stored as one JSON blob in this integration's own
 * feature_settings, edited via a single textarea field rather than a
 * fully dynamic, per-integration-type set of form fields. This is a
 * deliberate v1 scoping call, not an oversight: building a Symfony
 * CollectionType with conditional sub-fields per script-loader
 * integration type is real additional work, and this plugin's actual
 * hard requirement -- Controller/PublicController.php being able to read
 * structured per-site config -- doesn't need it. Revisit if the JSON
 * textarea proves too error-prone in practice once real admins (possibly
 * outside this team, if published) are using it day to day.
 */
class ConsentIntegration extends AbstractIntegration
{
    public function getName(): string
    {
        return 'C15t';
    }

    public function getDisplayName(): string
    {
        return 'Consent Manager (c15t)';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' !== $formArea) {
            return;
        }

        $builder->add(
            'sites_json',
            TextareaType::class,
            [
                'label'    => 'Site profiles (JSON)',
                'data'     => $data['sites_json'] ?? $this->getDefaultSitesJson(),
                'required' => false,
                'attr'     => [
                    'rows'    => 20,
                    'tooltip' => 'One entry per embedding domain: allowed origin(s), consent categories, and script-loader integrations. See docs/consent.md for the exact shape and Service/IntegrationRegistry.php for the supported integration keys.',
                ],
            ]
        );
    }

    /**
     * Seeds the field with a documented example shape rather than an
     * empty textarea. Malformed JSON is NOT validated at this form layer
     * (a v1 simplification, see this class's own header comment) --
     * Controller/PublicController.php handles a broken/missing profile
     * defensively (404, not a fatal error) instead.
     */
    private function getDefaultSitesJson(): string
    {
        return json_encode(
            [
                [
                    'domain'     => 'wrytersdesk.com',
                    'categories' => ['necessary', 'measurement', 'marketing'],
                    'scripts'    => [
                        ['integration' => 'mautic-tracking', 'params' => []],
                    ],
                ],
            ],
            JSON_PRETTY_PRINT
        );
    }
}

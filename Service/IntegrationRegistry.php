<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Service;

/**
 * Known script-loader integrations this plugin formally pre-bundles
 * (Option A from the design discussion) -- each key here MUST have a
 * matching entry in the pre-bundled JS bundle's build
 * (Assets/src/scripts.js's own header comment), exposed at runtime as
 * `window.__c15tScripts[key]`. Adding a new one is a deliberate, bounded
 * change touching both files -- not meant to grow without limit; use
 * RAW_SRC/RAW_INLINE (Option C) for anything not worth that investment.
 *
 * Every param name below was confirmed against @c15t/scripts's own
 * installed source (each helper's actual destructured function
 * signature), not assumed from their docs -- the docs page's own example
 * got google-tag's export name wrong (`googleTag` doesn't exist, the
 * real export is `gtag`) and would also have gotten this param wrong
 * (`id`, not `measurementId`) had it not been checked directly.
 *
 * 'mautic-tracking' takes one param (mauticUrl) -- the tracking domain,
 * unlike the snippet's own structure, DOES vary per installation of this
 * plugin, so it's supplied the same way every other packaged
 * integration's vendor-specific ID is (Assets/src/mautic-tracking.js).
 *
 * Every 'label' below (both the integration's own and each param's) is a
 * TRANSLATION KEY (Translations/en_US/messages.ini,
 * mautic.c15t.integration.<key>[.<param>]), not literal display text --
 * Form/Type/ConfigType.php is the thing that actually calls
 * $translator->trans() on these before building the Configuration
 * screen's field labels/tooltips. This registry stays the single source
 * of truth for WHICH integrations/params exist; the translation file is
 * the single source of truth for their display text.
 */
class IntegrationRegistry
{
    public const RAW_SRC = 'raw-src';
    public const RAW_INLINE = 'raw-inline';

    /**
     * @return array<string, array{label: string, category: string, params: array<string, array{type: string, required: bool, label: string}>}>
     */
    public function getPackaged(): array
    {
        return [
            'mautic-tracking' => [
                'label'    => 'mautic.c15t.integration.mautic_tracking',
                'category' => 'measurement',
                'params'   => [
                    'mauticUrl' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.mautic_tracking.mautic_url'],
                ],
            ],
            'google-tag' => [
                'label'    => 'mautic.c15t.integration.google_tag',
                'category' => 'measurement',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.google_tag.id'],
                ],
            ],
            'google-tag-manager' => [
                'label'    => 'mautic.c15t.integration.google_tag_manager',
                'category' => 'measurement',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.google_tag_manager.id'],
                ],
            ],
            'posthog' => [
                'label'    => 'mautic.c15t.integration.posthog',
                'category' => 'measurement',
                'params'   => [
                    'id'      => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.posthog.id'],
                    'apiHost' => ['type' => 'text', 'required' => false, 'label' => 'mautic.c15t.integration.posthog.api_host'],
                ],
            ],
            'meta-pixel' => [
                'label'    => 'mautic.c15t.integration.meta_pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.meta_pixel.pixel_id'],
                ],
            ],
            'reddit-pixel' => [
                'label'    => 'mautic.c15t.integration.reddit_pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.reddit_pixel.pixel_id'],
                ],
            ],
            'tiktok-pixel' => [
                'label'    => 'mautic.c15t.integration.tiktok_pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.tiktok_pixel.pixel_id'],
                ],
            ],
            'linkedin-insights' => [
                'label'    => 'mautic.c15t.integration.linkedin_insights',
                'category' => 'marketing',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.linkedin_insights.id'],
                ],
            ],
            'x-pixel' => [
                'label'    => 'mautic.c15t.integration.x_pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'mautic.c15t.integration.x_pixel.pixel_id'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string> integration type => translation key
     */
    public function getRawTypes(): array
    {
        return [
            self::RAW_SRC    => 'mautic.c15t.integration.raw_src',
            self::RAW_INLINE => 'mautic.c15t.integration.raw_inline',
        ];
    }

    public function isPackaged(string $integrationKey): bool
    {
        return isset($this->getPackaged()[$integrationKey]);
    }

    public function isRaw(string $integrationKey): bool
    {
        return in_array($integrationKey, [self::RAW_SRC, self::RAW_INLINE], true);
    }
}

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
                'label'    => 'Mautic Tracking (mtc.js)',
                'category' => 'measurement',
                'params'   => [
                    'mauticUrl' => ['type' => 'text', 'required' => true, 'label' => 'Mautic base URL (e.g. https://mautic.example.com)'],
                ],
            ],
            'google-tag' => [
                'label'    => 'Google Analytics (GA4)',
                'category' => 'measurement',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'Measurement ID (e.g. G-XXXXXXX)'],
                ],
            ],
            'google-tag-manager' => [
                'label'    => 'Google Tag Manager',
                'category' => 'measurement',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'Container ID (e.g. GTM-XXXXXXX)'],
                ],
            ],
            'posthog' => [
                'label'    => 'PostHog',
                'category' => 'measurement',
                'params'   => [
                    'id'      => ['type' => 'text', 'required' => true, 'label' => 'Project API key'],
                    'apiHost' => ['type' => 'text', 'required' => false, 'label' => 'API host (optional -- defaults to PostHog Cloud)'],
                ],
            ],
            'meta-pixel' => [
                'label'    => 'Meta Pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'Pixel ID'],
                ],
            ],
            'reddit-pixel' => [
                'label'    => 'Reddit Pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'Pixel ID'],
                ],
            ],
            'tiktok-pixel' => [
                'label'    => 'TikTok Pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'Pixel ID'],
                ],
            ],
            'linkedin-insights' => [
                'label'    => 'LinkedIn Insight Tag',
                'category' => 'marketing',
                'params'   => [
                    'id' => ['type' => 'text', 'required' => true, 'label' => 'Partner ID'],
                ],
            ],
            'x-pixel' => [
                'label'    => 'X (Twitter) Pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'Pixel ID'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string> integration type => human label
     */
    public function getRawTypes(): array
    {
        return [
            self::RAW_SRC    => 'Raw Script (URL)',
            self::RAW_INLINE => 'Raw Script (Inline)',
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

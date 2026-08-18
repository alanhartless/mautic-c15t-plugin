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
 * 'mautic-tracking' has no params -- its actual embed content (Mautic's
 * mtc.js bootstrap snippet) is baked into the pre-bundled JS bundle
 * directly, not templated per-site, since it never varies by site (it's
 * this Mautic instance's own tracking, not a third-party vendor ID).
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
                'params'   => [],
            ],
            'meta-pixel' => [
                'label'    => 'Meta Pixel',
                'category' => 'marketing',
                'params'   => [
                    'pixelId' => ['type' => 'text', 'required' => true, 'label' => 'Pixel ID'],
                ],
            ],
            'google-tag' => [
                'label'    => 'Google Analytics (GA4)',
                'category' => 'measurement',
                'params'   => [
                    'measurementId' => ['type' => 'text', 'required' => true, 'label' => 'Measurement ID'],
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

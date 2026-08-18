<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\Service;

class IntegrationRegistry
{
    public const RAW_SRC = 'raw-src';
    public const RAW_INLINE = 'raw-inline';

    /**
     * @return array<string, array{label: string, category: string, params: array<string, array{type: string, required?: bool, label?: string, source?: string}>}>
     */
    public function getPackaged(): array
    {
        return [
            'mautic-tracking' => [
                'label'    => 'mautic.c15t.integration.mautic_tracking',
                'category' => 'measurement',
                'params'   => [
                    'mauticUrl' => ['type' => 'text', 'source' => 'site_url'],
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

<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class ConsentIntegration extends BasicIntegration implements BasicInterface
{
    public const NAME         = 'c15t';
    public const DISPLAY_NAME = 'Consent Manager (c15t)';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/MauticC15tBundle/Assets/img/c15t.png';
    }
}

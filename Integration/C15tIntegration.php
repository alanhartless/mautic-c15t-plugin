<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class C15tIntegration extends BasicIntegration implements BasicInterface
{
    public const NAME         = 'C15t';
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
        return 'plugins/C15tBundle/Assets/img/c15t.png';
    }
}

<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class ConsentIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

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

    public function getIcon(): string
    {
        return 'plugins/MauticC15tBundle/Assets/img/c15t.png';
    }
}

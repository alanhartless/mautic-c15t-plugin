<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticC15tBundle\Integration\ConsentIntegration;

final class ConfigSupport extends ConsentIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
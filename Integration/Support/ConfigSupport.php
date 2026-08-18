<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\C15tBundle\Integration\C15tIntegration;

final class ConfigSupport extends C15tIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
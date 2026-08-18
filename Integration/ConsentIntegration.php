<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Deliberately minimal -- this Integration exists only for what
 * AbstractIntegration gives for free: a real, iconed entry in Mautic's
 * Plugins list with a native enable/disable toggle (fail-closed when
 * disabled -- see Controller/PublicController.php's own check). Confirmed
 * against a real, minimal, currently-shipped example (plugins/
 * MauticTagManagerBundle/Integration/TagManagerIntegration.php on
 * GitHub), not assumed from docs.
 *
 * The actual site-profile settings live in Mautic's Configuration screen
 * instead (Configuration -> Consent Manager (c15t)) -- see EventListener/
 * ConfigSubscriber.php and Form/Type/ConfigType.php. An earlier version
 * of this class tried to add that same settings field here via
 * appendToForm(), which turned out not to render at all (the "Features"
 * tab's own template only shows specific named fields, not arbitrary
 * custom ones) -- moved deliberately, not just relocated for its own
 * sake.
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
}

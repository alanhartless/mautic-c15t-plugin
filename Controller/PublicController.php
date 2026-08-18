<?php

declare(strict_types=1);

namespace MauticPlugin\MauticC15tBundle\Controller;

use MauticPlugin\MauticC15tBundle\Service\IntegrationRegistry;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves /consent.js -- the single embeddable loader script for whichever
 * site is asking (domain resolved from the Origin/Referer header, matched
 * against the c15t integration's configured site profiles -- see
 * Integration/ConsentIntegration.php's own comment on that JSON shape).
 * Response is ONE combined script: the pre-bundled c15t runtime + banner UI
 * + pre-bundled integration helpers (Assets/build/consent-bundle.js --
 * SEE THAT FILE'S OWN HEADER: not yet built in this pass, a placeholder),
 * followed by a small dynamic bootstrap that initializes it with this
 * site's specific categories/backendURL/scripts. One URL, one request --
 * deliberately not a two-file (static bundle + separate config fetch)
 * split, so embedding stays a single <script src="..."> tag.
 *
 * Public route (no auth), same pattern as Mautic core's own /mtc.js
 * (CoreBundle\Controller\JsController, confirmed via
 * app/bundles/CoreBundle/Config/config.php) -- this plugin's Config/
 * config.php registers it under 'routes.public', not 'routes.main'.
 *
 * UNVERIFIED against a live Mautic instance (no install has run yet):
 * the exact IntegrationHelper method name for fetching a single
 * integration's settings by name. Symfony's own form-builder area name
 * passed to appendToForm() ('features') was likewise not independently
 * confirmed beyond the general AbstractIntegration convention -- check
 * both against a real Configuration -> Plugins -> Consent Manager (c15t)
 * screen after first install, per this repo's own README "Local
 * validation caveat" note.
 */
class PublicController extends CommonController
{
    public function loaderAction(
        Request $request,
        IntegrationHelper $integrationHelper,
        IntegrationRegistry $registry,
    ): Response {
        // Don't count a script-tag fetch as a trackable visitor hit --
        // same convention CoreBundle\Controller\JsController::indexAction
        // uses for /mtc.js itself.
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $requestOrigin = $this->resolveRequestOrigin($request);
        if (null === $requestOrigin) {
            return new Response('// c15t: could not resolve requesting origin', 400, ['Content-Type' => 'application/javascript']);
        }

        $integrationObject = $integrationHelper->getIntegrationObject('C15t');
        if (!$integrationObject || !$integrationObject->getIntegrationSettings()->getIsPublished()) {
            // Master enable/disable toggle (native to Mautic's Integration
            // system) -- disabled means fail closed: no loader script at
            // all, so nothing (including Mautic's own tracking) loads on
            // any embedding site until this is re-enabled.
            return new Response('// c15t: integration disabled', 404, ['Content-Type' => 'application/javascript']);
        }

        $settings = $integrationObject->getIntegrationSettings()->getFeatureSettings();
        $sites    = json_decode($settings['sites_json'] ?? '[]', true, 512, JSON_THROW_ON_ERROR) ?? [];

        $site = $this->findSiteProfile($sites, $requestOrigin);
        if (null === $site) {
            return new Response('// c15t: no site profile configured for this origin', 404, ['Content-Type' => 'application/javascript']);
        }

        $bundleJs = $this->readPrebuiltBundle();
        $bootstrapJs = $this->buildBootstrapJs($site, $registry);

        return new Response(
            $bundleJs."\n".$bootstrapJs,
            200,
            [
                'Content-Type'  => 'application/javascript',
                // Real access control is consent-backend's own trustedOrigins
                // check -- this is just cache-friendliness, not the security
                // boundary. Short TTL since site profiles can change via the
                // Mautic admin at any time.
                'Cache-Control' => 'public, max-age=300',
            ]
        );
    }

    /**
     * Origin header first (real cross-origin fetches always set it);
     * Referer's host as a fallback for plain <script src> requests, which
     * don't always send Origin. Neither is spoof-proof -- see this
     * plugin's own README section on why the REAL boundary is
     * consent-backend's trustedOrigins check, not this lookup.
     */
    private function resolveRequestOrigin(Request $request): ?string
    {
        $origin = $request->headers->get('Origin');
        if ($origin) {
            return parse_url($origin, PHP_URL_HOST) ?: null;
        }

        $referer = $request->headers->get('Referer');
        if ($referer) {
            return parse_url($referer, PHP_URL_HOST) ?: null;
        }

        return null;
    }

    private function findSiteProfile(array $sites, string $requestHost): ?array
    {
        foreach ($sites as $site) {
            $domains = (array) ($site['domain'] ?? []);
            // A single 'domain' string entry is also valid -- (array) cast
            // above normalizes both shapes.
            if (in_array($requestHost, $domains, true)) {
                return $site;
            }
        }

        return null;
    }

    /**
     * Placeholder until Assets/build/consent-bundle.js is actually built
     * (the vanilla banner UI + pre-bundled integration helpers, see that
     * file's own header). Returns a comment only so the combined response
     * is at least valid, inert JS rather than a hard failure while that
     * piece is still outstanding.
     */
    private function readPrebuiltBundle(): string
    {
        $path = __DIR__.'/../Assets/build/consent-bundle.js';
        if (is_file($path)) {
            return (string) file_get_contents($path);
        }

        return '// c15t: Assets/build/consent-bundle.js not yet built -- see that file\'s own placeholder for what belongs here.';
    }

    private function buildBootstrapJs(array $site, IntegrationRegistry $registry): string
    {
        $categories = $site['categories'] ?? ['necessary'];
        $scripts    = [];

        foreach (($site['scripts'] ?? []) as $entry) {
            $integration = $entry['integration'] ?? null;
            $params      = $entry['params'] ?? [];
            if (null === $integration) {
                continue;
            }

            if ($registry->isPackaged($integration)) {
                // Resolved at runtime by the pre-bundled bundle's own
                // window.__c15tScripts[...] lookup -- see Assets/build/
                // consent-bundle.js's own header for that contract.
                $scripts[] = [
                    'packaged' => $integration,
                    'params'   => $params,
                ];
            } elseif (IntegrationRegistry::RAW_SRC === $integration) {
                $scripts[] = [
                    'id'       => $entry['id'] ?? $integration,
                    'src'      => $entry['src'] ?? '',
                    'category' => $entry['category'] ?? 'necessary',
                ];
            } elseif (IntegrationRegistry::RAW_INLINE === $integration) {
                $scripts[] = [
                    'id'          => $entry['id'] ?? $integration,
                    'textContent' => $entry['textContent'] ?? '',
                    'category'    => $entry['category'] ?? 'necessary',
                ];
            }
        }

        $config = [
            'mode'              => 'hosted',
            'backendURL'        => 'https://consent.wrytersdesk.com/api/c15t',
            'consentCategories' => $categories,
            'scripts'           => $scripts,
        ];

        return sprintf(
            'window.__C15T_SITE_CONFIG__ = %s;',
            json_encode($config, JSON_THROW_ON_ERROR)
        );
    }
}

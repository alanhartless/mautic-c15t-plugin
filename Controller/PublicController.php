<?php

declare(strict_types=1);

namespace MauticPlugin\C15tBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\C15tBundle\Integration\C15tIntegration;
use MauticPlugin\C15tBundle\Service\IntegrationRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves /consent.js -- the single embeddable loader script for whichever
 * site is asking (domain resolved from the Origin/Referer header, checked
 * against the flat 'domains' allowlist configured on Mautic's
 * Configuration -> Consent Manager (c15t) screen -- see Form/Type/
 * ConfigType.php). Response is ONE combined script: the pre-bundled c15t
 * runtime + banner UI + pre-bundled integration helpers (Assets/build/
 * consent-bundle.js -- SEE THAT FILE'S OWN HEADER: not yet built in this
 * pass, a placeholder), followed by a small dynamic bootstrap built from
 * this instance's own configured backend URL / categories / enabled
 * integrations. One URL, one request -- deliberately not a two-file
 * (static bundle + separate config fetch) split, so embedding stays a
 * single <script src="..."> tag.
 *
 * Public route (no auth), same pattern as Mautic core's own /mtc.js
 * (CoreBundle\Controller\JsController, confirmed via
 * app/bundles/CoreBundle/Config/config.php) -- this plugin's Config/
 * config.php registers it under 'routes.public', not 'routes.main'.
 *
 * There is exactly ONE set of settings per Mautic instance (not one
 * per site) -- 'domains' is the multi-value allowlist of which sites may
 * embed this one instance's loader, all sharing the same backend URL /
 * categories / enabled integrations. A Mautic instance fronting sites
 * that need genuinely different consent configs needs more than one
 * install of this plugin's backend, which is out of scope here.
 */
class PublicController extends CommonController
{
    public function loaderAction(
        Request $request,
        IntegrationHelper $integrationHelper,
        CoreParametersHelper $coreParametersHelper,
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

        $integrationObject = $integrationHelper->getIntegrationObject(C15tIntegration::NAME);
        if (!$integrationObject || !$integrationObject->getIntegrationSettings()->getIsPublished()) {
            // Master enable/disable toggle (native to Mautic's Integration
            // system, Plugins -> Consent Manager (c15t)) -- disabled means
            // fail closed: no loader script at all, so nothing (including
            // Mautic's own tracking) loads on any embedding site until
            // this is re-enabled.
            return new Response('// c15t: integration disabled', 404, ['Content-Type' => 'application/javascript']);
        }

        $domains = $this->parseDomains((string) $coreParametersHelper->get('domains', ''));
        if (!in_array($requestOrigin, $domains, true)) {
            return new Response('// c15t: this domain is not in the configured allowlist', 404, ['Content-Type' => 'application/javascript']);
        }

        $backendUrl = (string) $coreParametersHelper->get('backend_url', '');
        if ('' === $backendUrl) {
            return new Response('// c15t: no backend_url configured (Configuration -> Consent Manager (c15t))', 500, ['Content-Type' => 'application/javascript']);
        }

        // Independent of the 'domains' allowlist just checked above -- a
        // site can be a test domain without also needing to be listed
        // there, and vice versa (see Translations/en_US/messages.ini's own
        // tooltip text). Only changes which client mode the requesting
        // site's browser initializes with; every other config (categories,
        // consent mode, integrations) still applies the same either way.
        $testDomains = $this->parseDomains((string) $coreParametersHelper->get('test_domains', ''));
        $mode        = in_array($requestOrigin, $testDomains, true) ? 'offline' : 'hosted';

        $bundleJs    = $this->readPrebuiltBundle();
        $bootstrapJs = $this->buildBootstrapJs($coreParametersHelper, $registry, $backendUrl, $mode);

        // Config MUST come first -- the bundle reads window.__C15T_SITE_CONFIG__
        // at load time to initialize itself (Assets/src/index.js's own top-level
        // code, not a deferred/event-driven init). Concatenating the other way
        // around would run the bundle before the config it depends on exists.
        return new Response(
            $bootstrapJs."\n".$bundleJs,
            200,
            [
                'Content-Type'  => 'application/javascript',
                // Real access control is consent-backend's own trustedOrigins
                // check -- this is just cache-friendliness, not the security
                // boundary. Short TTL since the allowlist/config can change
                // via the Mautic admin at any time.
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

    /**
     * 'domains' is a plain textarea (Form/Type/ConfigType.php) -- one
     * domain per line. Also accepts comma-separated entries on a single
     * line, since that's an easy mistake to make when filling the field
     * in.
     */
    private function parseDomains(string $raw): array
    {
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $d): bool => '' !== $d));
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

    /**
     * Builds the scripts[] array from two sources: (1) packaged
     * integrations -- looped from IntegrationRegistry::getPackaged(), one
     * entry per integration whose own '{prefix}_enabled' config field is
     * true, carrying whatever '{prefix}_{param}' values were filled in;
     * (2) 'advanced_scripts_json' -- the raw-src/raw-inline escape hatch
     * for anything not in the packaged list, unchanged in shape from the
     * original design (see this plugin's own README for that JSON shape).
     */
    private function buildBootstrapJs(CoreParametersHelper $coreParametersHelper, IntegrationRegistry $registry, string $backendUrl, string $mode): string
    {
        $categories = (array) $coreParametersHelper->get('categories', ['necessary']);
        if (!in_array('necessary', $categories, true)) {
            $categories[] = 'necessary';
        }

        $scripts = [];

        foreach ($registry->getPackaged() as $key => $integration) {
            $prefix = str_replace('-', '_', $key);
            if (!$coreParametersHelper->get($prefix.'_enabled', false)) {
                continue;
            }

            $params = [];
            foreach ($integration['params'] as $paramKey => $paramSpec) {
                $params[$paramKey] = isset($paramSpec['source'])
                    // Auto-filled from Mautic's own core config (e.g.
                    // 'site_url') rather than a per-instance config field
                    // -- see Service/IntegrationRegistry.php's docblock.
                    ? (string) $coreParametersHelper->get($paramSpec['source'], '')
                    : (string) $coreParametersHelper->get($prefix.'_'.$paramKey, '');
            }

            // Resolved at runtime by the pre-bundled bundle's own
            // window.__c15tScripts[...] lookup -- see Assets/build/
            // consent-bundle.js's own header for that contract.
            $scripts[] = [
                'packaged' => $key,
                'params'   => $params,
            ];
        }

        $advancedRaw = (string) $coreParametersHelper->get('advanced_scripts_json', '[]');
        $advanced    = json_decode($advancedRaw, true) ?? [];
        foreach ((array) $advanced as $entry) {
            $type = $entry['integration'] ?? null;
            if (IntegrationRegistry::RAW_SRC === $type) {
                $scripts[] = [
                    'id'       => $entry['id'] ?? $type,
                    'src'      => $entry['src'] ?? '',
                    'category' => $entry['category'] ?? 'necessary',
                ];
            } elseif (IntegrationRegistry::RAW_INLINE === $type) {
                $scripts[] = [
                    'id'          => $entry['id'] ?? $type,
                    'textContent' => $entry['textContent'] ?? '',
                    'category'    => $entry['category'] ?? 'necessary',
                ];
            }
        }

        $config = [
            // 'offline' for requests from a configured test domain, else
            // 'hosted' -- see loaderAction()'s own resolution of $mode.
            'mode'              => $mode,
            'backendURL'        => $backendUrl,
            'consentCategories' => array_values($categories),
            'scripts'           => $scripts,
            'disableDefaultCss' => (bool) $coreParametersHelper->get('disable_default_css', false),
            'consentMode'       => (string) $coreParametersHelper->get('consent_mode', 'opt-in'),
            'policyPacks'       => array_values((array) $coreParametersHelper->get('policy_packs', [])),
            'reloadOnRestrict'  => (bool) $coreParametersHelper->get('reload_on_restrict', false),
            'initialUi'         => (string) $coreParametersHelper->get('initial_ui', 'banner'),
            'enableFocusTrap'   => (bool) $coreParametersHelper->get('enable_focus_trap', true),
            'bannerText'        => (string) $coreParametersHelper->get('banner_text', ''),
            'modalText'         => (string) $coreParametersHelper->get('modal_text', ''),
        ];

        return sprintf(
            'window.__C15T_SITE_CONFIG__ = %s;',
            json_encode($config, JSON_THROW_ON_ERROR)
        );
    }
}

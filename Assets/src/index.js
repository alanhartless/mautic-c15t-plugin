import { getOrCreateConsentRuntime } from 'c15t';
import { packagedScripts } from './scripts.js';
import { mountConsentUI } from './banner.js';

/**
 * Entry point for Assets/build/consent-bundle.js (built via `npm run
 * build`, esbuild --bundle --minify --format=iife). Reads
 * window.__C15T_SITE_CONFIG__, which Controller/PublicController.php
 * sets in a small script that MUST run before this bundle (see that
 * controller's own comment on response ordering) -- this file assumes
 * that config already exists at load time, not deferred/event-driven.
 */

function resolveScripts(entries) {
  return entries
    .map((entry) => {
      if (entry.packaged) {
        const helper = packagedScripts[entry.packaged];
        if (!helper) {
          console.warn(`[c15t] unknown packaged integration "${entry.packaged}" -- skipping`);
          return null;
        }
        return helper(entry.params || {});
      }
      // raw-src / raw-inline entries already match the script-loader
      // primitive's shape directly (id + src|textContent + category) --
      // see Controller/PublicController.php's own buildBootstrapJs().
      return entry;
    })
    .filter(Boolean);
}

const siteConfig = window.__C15T_SITE_CONFIG__;

if (!siteConfig) {
  console.error(
    '[c15t] window.__C15T_SITE_CONFIG__ was not set -- consent-bundle.js must be loaded after the per-site config snippet, not before. See Controller/PublicController.php.',
  );
} else {
  const { consentStore } = getOrCreateConsentRuntime({
    mode: siteConfig.mode,
    backendURL: siteConfig.backendURL,
    consentCategories: siteConfig.consentCategories,
    scripts: resolveScripts(siteConfig.scripts || []),
  });

  mountConsentUI(consentStore, {
    disableDefaultCss: Boolean(siteConfig.disableDefaultCss),
  });
}

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

  // Public re-open API -- a visitor who already made a choice gets no
  // banner/dialog on later page loads (that's the whole point), so a site
  // needs SOME way to bring the dialog back (e.g. a footer "Cookie
  // Settings" link). setActiveUI('dialog') sets it unconditionally
  // (unlike 'banner', which only re-shows unprompted if `force: true` is
  // passed -- see c15t's own store implementation, node_modules/c15t/
  // dist/index.js's setActiveUI), so no options are needed here.
  window.wdConsent = {
    openPreferences: () => consentStore.getState().setActiveUI('dialog'),
  };

  // Declarative trigger -- lets a site add a plain
  // `<a href="#" data-wd-consent-trigger>Cookie Settings</a>` with no JS
  // of its own. One delegated listener (not per-element) so it also picks
  // up triggers added to the page after this bundle has already run.
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-wd-consent-trigger]');
    if (!trigger) return;
    event.preventDefault();
    window.wdConsent.openPreferences();
  });
}

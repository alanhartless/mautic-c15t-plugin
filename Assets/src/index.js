import { getOrCreateConsentRuntime } from 'c15t';
import { packagedScripts } from './scripts.js';
import { mountConsentUI } from './banner.js';

/**
 * Reload-on-restrict (Controller/PublicController.php's own
 * 'reload_on_restrict' field) -- registered as an onConsentChanged
 * callback (c15t.com/docs/frameworks/javascript/callbacks), which per
 * c15t's own docs fires "only after an explicit saveConsents() or
 * setConsent() that actually changes the saved consent state." A change
 * counts as MORE RESTRICTIVE when any category present in
 * previousAllowedCategories is absent from the new allowedCategories --
 * i.e. something that was allowed got revoked. Widening consent (opting
 * into something new) never reloads; only revocation does, since that's
 * the case where an already-loaded third-party script may have left
 * state behind a reload is needed to clear.
 */
function buildCallbacks(siteConfig) {
  if (!siteConfig.reloadOnRestrict) {
    return undefined;
  }

  return {
    onConsentChanged: ({ allowedCategories, previousAllowedCategories }) => {
      const becameMoreRestrictive = (previousAllowedCategories || []).some(
        (category) => !(allowedCategories || []).includes(category),
      );
      if (becameMoreRestrictive) {
        window.location.reload();
      }
    },
  };
}

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
  // No policy/jurisdiction config here on purpose -- consent-backend's own
  // c15tInstance() call (a separate repo) owns policyPacks entirely.
  // getOrCreateConsentRuntime() in hosted mode defers to the backend's own
  // /init response for jurisdiction/policy resolution; its real options
  // type has no policyPacks key at all (an earlier version of this file
  // passed one anyway, which silently did nothing -- confirmed live,
  // 2026-08-18, against c15t's own self-host policy-packs guide).
  const { consentStore } = getOrCreateConsentRuntime({
    mode: siteConfig.mode,
    backendURL: siteConfig.backendURL,
    consentCategories: siteConfig.consentCategories,
    scripts: resolveScripts(siteConfig.scripts || []),
    callbacks: buildCallbacks(siteConfig),
  });

  mountConsentUI(consentStore, {
    disableDefaultCss: Boolean(siteConfig.disableDefaultCss),
    enableFocusTrap: siteConfig.enableFocusTrap !== false,
    bannerText: siteConfig.bannerText || '',
    modalText: siteConfig.modalText || '',
  });

  // Public re-open API -- a visitor who already made a choice gets no
  // banner/dialog on later page loads (that's the whole point), so a site
  // needs SOME way to bring the dialog back (e.g. a footer "Cookie
  // Settings" link). setActiveUI('dialog') sets it unconditionally
  // (unlike 'banner', which only re-shows unprompted if `force: true` is
  // passed -- see c15t's own store implementation, node_modules/c15t/
  // dist/index.js's setActiveUI), so no options are needed here.
  window.ccm = {
    openPreferences: () => consentStore.getState().setActiveUI('dialog'),
    // Lets an embedding app link its own logged-in user identity to the
    // anonymous consent record once authenticated
    // (c15t.com/docs/frameworks/javascript/api/location-info#identifyuseruser)
    // -- e.g. window.ccm.identifyUser({ id, identityProvider }) from
    // the app's own auth-success handler. Only meaningful once a visitor
    // has actually authenticated; not called from anywhere in this bundle
    // itself.
    identifyUser: (user) => consentStore.getState().identifyUser(user),
    // Resets consent preferences to their default (unset) state -- for
    // testing: window.ccm.resetConsents() in the console re-triggers
    // the banner on next load without needing to manually clear cookies/
    // storage. c15t's own troubleshooting docs list this as the documented
    // way to force a fresh banner during testing.
    resetConsents: () => consentStore.getState().resetConsents(),
  };

  // Declarative trigger -- lets a site add a plain
  // `<a href="#" data-ccm-trigger>Cookie Settings</a>` with no JS
  // of its own. One delegated listener (not per-element) so it also picks
  // up triggers added to the page after this bundle has already run.
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-ccm-trigger]');
    if (!trigger) return;
    event.preventDefault();
    window.ccm.openPreferences();
  });
}

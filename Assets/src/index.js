import { getOrCreateConsentRuntime, policyPackPresets } from 'c15t';
import { packagedScripts } from './scripts.js';
import { mountConsentUI } from './banner.js';

/**
 * Builds the `policyPacks` array getOrCreateConsentRuntime() consumes
 * (c15t.com/docs/frameworks/javascript/concepts/policy-packs /
 * .../concepts/consent-models) from Controller/PublicController.php's own
 * `consentMode`/`policyPacks` config keys -- see Form/Type/ConfigType.php's
 * own comment for why these are two separate admin fields rather than one.
 *
 * consentMode === 'policy_pack': map each selected preset NAME (e.g.
 * 'europeOptIn') to the real policyPackPresets[name]() call -- unknown
 * names are skipped defensively rather than throwing, same posture as
 * resolveScripts() below for unknown packaged integrations.
 *
 * Any other consentMode ('opt-in'/'opt-out'/'iab'/'none'): build ONE
 * hand-written global policy entry with no region restriction
 * (`match: { isDefault: true }`), using the custom-policy object shape
 * documented on the policy-packs page (id/match/consent/ui) -- there is
 * no simpler standalone "just set the model" init option; a raw model
 * only means something inside a policy entry.
 *
 * UNVERIFIED against a live build (see this plugin's own README "Local
 * validation caveat"): (1) whether `policyPacks` is genuinely the correct
 * top-level init option name for hosted mode -- c15t's own quickstart
 * builds the array but doesn't show the exact call it's passed into, and
 * a separate mention of `offlinePolicy.policyPacks` suggests the option
 * may be nested differently, or differently-named, for hosted vs. offline
 * mode; (2) the disabled model's literal value -- the consent-models page
 * lists it as JS `null`, but the custom-policy object example on the
 * policy-packs page uses the string 'none' instead; 'none' is used here
 * since it's what the actual object-shape example shows and is
 * JSON-round-trippable through window.__C15T_SITE_CONFIG__ unambiguously
 * (unlike `null`, which collides with "field omitted").
 */
function buildPolicyPacks(siteConfig) {
  if ('policy_pack' === siteConfig.consentMode) {
    return (siteConfig.policyPacks || [])
      .map((name) => {
        const preset = policyPackPresets[name];
        if (!preset) {
          console.warn(`[c15t] unknown policy pack "${name}" -- skipping`);
          return null;
        }
        return preset();
      })
      .filter(Boolean);
  }

  const model = siteConfig.consentMode || 'opt-in';
  return [
    {
      id: 'global',
      match: { isDefault: true },
      consent: { model, categories: siteConfig.consentCategories },
      // siteConfig.initialUi (Controller/PublicController.php's own
      // 'initial_ui' field) only applies here, to the hand-written global
      // policy -- a named policy pack preset (europeIab etc.) carries its
      // own ui.mode from c15t itself, not overridden.
      ui: { mode: 'none' === model ? 'none' : siteConfig.initialUi || 'banner' },
    },
  ];
}

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
  const { consentStore } = getOrCreateConsentRuntime({
    mode: siteConfig.mode,
    backendURL: siteConfig.backendURL,
    consentCategories: siteConfig.consentCategories,
    scripts: resolveScripts(siteConfig.scripts || []),
    policyPacks: buildPolicyPacks(siteConfig),
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
  window.wdConsent = {
    openPreferences: () => consentStore.getState().setActiveUI('dialog'),
    // Lets an embedding app link its own logged-in user identity to the
    // anonymous consent record once authenticated
    // (c15t.com/docs/frameworks/javascript/api/location-info#identifyuseruser)
    // -- e.g. window.wdConsent.identifyUser({ id, identityProvider }) from
    // the app's own auth-success handler. Only meaningful once a visitor
    // has actually authenticated; not called from anywhere in this bundle
    // itself.
    identifyUser: (user) => consentStore.getState().identifyUser(user),
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

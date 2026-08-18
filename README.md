# Mautic c15t Consent Bundle

A Mautic plugin that turns your Mautic install into the single embeddable
consent-management endpoint for every site you run: one `<script>` tag,
served from your own Mautic instance, that shows an accessible consent
banner, talks to a self-hosted [c15t](https://c15t.com) backend, gates
Mautic's own tracking (`mtc.js`) on actual consent, and can conditionally
load third-party scripts (Google Analytics/Tag Manager, Meta/Reddit/TikTok/X
pixels, LinkedIn Insight Tag, PostHog, or any raw script you configure) only
once a visitor consents to the relevant category.

## Why a Mautic plugin, not just a JS snippet

c15t's own client libraries need something to talk to (a backend) and
something to render (a UI) -- neither ships a plain "drop this on any site"
option by itself. This plugin makes Mautic that "something":

- **One route, `/consent.js`**, dynamically generated per requesting site
  (resolved from the `Origin`/`Referer` header) -- embed the exact same
  script tag on every site, Mautic figures out which one is asking and
  serves that site's own categories/scripts.
- **Configuration lives in Mautic's own admin** (Plugins -> Consent Manager
  (c15t)) -- native enable/disable toggle, one JSON field per install
  describing every site you've embedded this on.
- **Mautic's own tracking is consent-gated for free** -- the
  `mautic-tracking` packaged integration wraps Mautic's real `mtc.js`
  bootstrap snippet as a normal script-loader entry, so it only loads once
  the `measurement` category is actually consented to.

## Architecture

```
Site (any domain)  --<script src="https://your-mautic.example.com/consent.js">-->  Mautic (this plugin)
                                                                                          |
                                                                                   resolves Origin -> site profile
                                                                                          |
                                                                            Assets/build/consent-bundle.js
                                                                         (banner UI + pre-bundled integrations)
                                                                                          |
                                                                    consentStore -- talks to your c15t backend
                                                                          (mode: 'hosted', backendURL you configure)
```

The **banner/dialog UI is deliberately vanilla JS**, not a framework
component -- it's built directly on c15t's headless store
(`getOrCreateConsentRuntime`), which is what makes it portable enough to
embed on a plain HTML site, a Grav/PHP site, a React app, or anything else,
all via the same script tag.

## Repo layout

- `MauticC15tBundle.php` / `Config/config.php` -- the Mautic plugin bundle
  itself (routes, service registration).
- `Integration/ConsentIntegration.php` -- gives you the native Mautic
  enable/disable toggle plus one settings field (a JSON textarea) for your
  site profiles. See its own docblock for why this is one textarea and not
  a fully dynamic per-field form.
- `Controller/PublicController.php` -- serves `/consent.js`: resolves the
  requesting site, builds its config, concatenates the pre-built bundle.
- `Service/IntegrationRegistry.php` -- the source of truth for which
  third-party integrations are "packaged" (pre-bundled, get a proper admin
  form) vs. "raw" (any script URL or inline snippet, no plugin update
  needed). Every param name in here was confirmed against `@c15t/scripts`'s
  actual installed source, not assumed from its docs.
- `Assets/src/` -- the client bundle's source (banner UI, default CSS,
  packaged integration wiring, Mautic's own tracking snippet).
- `Assets/build/consent-bundle.js` -- **generated, not hand-edited**. Run
  `npm run build` after any change under `Assets/src/` and commit the
  result (no CI/build step wired up yet -- see "Building" below).

## Installing

1. Copy this repo's contents into your Mautic install's
   `docroot/plugins/MauticC15tBundle/` (or wherever your Mautic build maps
   `plugins/` to -- check `composer.json`'s `install-directory-name`).
2. Run `php bin/console cache:clear` and reload Mautic's Plugins page (or
   `php bin/console mautic:plugins:reload` if your Mautic version has it) so
   it picks up the new bundle.
3. Configuration -> Plugins -> **Consent Manager (c15t)** -- enable it, then
   fill in the site profiles JSON. Shape:

```json
[
  {
    "domain": "your-site.example.com",
    "backendURL": "https://consent.example.com/api/c15t",
    "categories": ["necessary", "measurement", "marketing"],
    "disableDefaultCss": false,
    "scripts": [
      { "integration": "mautic-tracking", "params": { "mauticUrl": "https://your-mautic.example.com" } },
      { "integration": "google-tag", "params": { "id": "G-XXXXXXX" } },
      { "integration": "meta-pixel", "params": { "pixelId": "000000000000000" } }
    ]
  }
]
```

4. Embed on the target site: `<script src="https://your-mautic.example.com/consent.js" defer></script>`.

You also need a running c15t backend somewhere (`backendURL` above) --
this plugin is the embedding/config/gating layer, not the consent-storage
backend itself. See [c15t's own self-host docs](https://c15t.com/docs/self-host/quickstart).

## Supported packaged integrations

| Key | Vendor | Required params |
|---|---|---|
| `mautic-tracking` | This Mautic instance's own `mtc.js` | `mauticUrl` |
| `google-tag` | Google Analytics (GA4) | `id` |
| `google-tag-manager` | Google Tag Manager | `id` |
| `posthog` | PostHog | `id` (optional `apiHost`) |
| `meta-pixel` | Meta Pixel | `pixelId` |
| `reddit-pixel` | Reddit Pixel | `pixelId` |
| `tiktok-pixel` | TikTok Pixel | `pixelId` |
| `linkedin-insights` | LinkedIn Insight Tag | `id` |
| `x-pixel` | X (Twitter) Pixel | `pixelId` |

Anything not on this list can still be added per-site without a plugin
update, via the `raw-src` (a script URL) or `raw-inline` (literal inline JS)
integration types -- see `Service/IntegrationRegistry.php`'s own docblock.

## Building

```bash
npm install
npm run build   # -> Assets/build/consent-bundle.js
```

Re-run and commit the result whenever anything under `Assets/src/` changes.

## Local validation caveat

This plugin was authored without a live Mautic install to test against.
Two things specifically are unverified and worth checking on first install:

- **`IntegrationHelper::getIntegrationObject()`'s exact method name/return
  shape** (`Controller/PublicController.php`) -- confirmed the general
  pattern from a real, currently-shipped Mautic plugin
  (`MauticTagManagerBundle`), not this exact call against a running
  instance.
- **`appendToForm()`'s `'features'` form-area string** (`Integration/
  ConsentIntegration.php`) -- the general `AbstractIntegration` mechanism
  is confirmed real; this specific area name was not independently
  verified against a live Configuration -> Plugins screen.
- **Mautic's own tracking bootstrap snippet** (`Assets/src/mautic-
  tracking.js`) -- the content is the standard async-loader pattern
  documented across Mautic's history, not copy-pasted from a live
  instance's own Settings -> "Contact tracking code" page. Confirm it
  matches before relying on it at real tracking volume.

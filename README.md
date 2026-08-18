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
- **Configuration lives in Mautic's own admin** -- a master enable/disable
  toggle on Plugins -> Consent Manager (c15t), and individual fields
  (allowed domains, backend URL, categories, per-integration toggles) on
  Configuration -> Consent Manager (c15t).
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
  itself (routes, service registration, config-screen parameter defaults).
- `Integration/ConsentIntegration.php` -- gives you the native Mautic
  enable/disable toggle on Plugins -> Consent Manager (c15t). See its own
  docblock for why the actual settings live elsewhere.
- `EventListener/ConfigSubscriber.php` / `Form/Type/ConfigType.php` --
  registers the plugin's own panel on Mautic's global Configuration screen.
  `ConfigType` generates one enable toggle + param field(s) per packaged
  integration by looping `IntegrationRegistry::getPackaged()` -- adding an
  integration to that registry grows this form automatically.
- `Resources/views/FormTheme/Config/_config_c15tconfig_widget.html.twig` --
  the Configuration screen's own layout (general fields, categories,
  per-integration panels, advanced JSON), grouped generically off form
  field naming, not a hardcoded per-integration list.
- `Translations/en_US/messages.ini` -- field labels/tooltips, following
  Mautic's own `mautic.<bundle>.<area>.<field>[.tooltip]` key convention.
- `Controller/PublicController.php` -- serves `/consent.js`: checks the
  requesting domain against the configured allowlist, builds the config
  from the Configuration screen's fields, concatenates the pre-built
  bundle.
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
3. Plugins -> **Consent Manager (c15t)** -- toggle it published (this is
   the master fail-closed switch; nothing serves from `/consent.js` while
   it's off).
4. Configuration -> **Consent Manager (c15t)** -- fill in:
   - **Allowed domains** -- one per line, every site that will embed this
     instance's `/consent.js`.
   - **c15t backend URL** -- your self-hosted c15t backend's base URL,
     e.g. `https://consent.example.com/api/c15t`.
   - **Consent categories** -- multi-select of which categories the
     banner offers (`necessary` is always included).
   - **Disable default banner styling** -- turn on if a site will supply
     its own CSS instead.
   - One panel per packaged integration (Mautic tracking, GA4, GTM,
     PostHog, Meta/Reddit/TikTok pixels, LinkedIn Insight Tag, X pixel) --
     each has its own enable toggle and its own parameter field(s) (e.g.
     Meta Pixel's Pixel ID).
   - **Advanced: custom scripts (JSON)** -- optional, for anything not in
     the packaged list. A JSON array of `raw-src`/`raw-inline` entries:

```json
[
  { "integration": "raw-src", "id": "my-script", "src": "https://example.com/widget.js", "category": "functionality" },
  { "integration": "raw-inline", "id": "my-inline", "textContent": "console.log('consented')", "category": "marketing" }
]
```

5. Embed on the target site: `<script src="https://your-mautic.example.com/consent.js" defer></script>`.

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
- **`ConfigType`'s explicit service registration** (`Config/config.php`'s
  `services.other.mautic.c15t.form.config` entry) -- `ConfigType` now
  takes constructor dependencies (`IntegrationRegistry` + `translator`),
  so Symfony's form factory needs to resolve it via the container rather
  than `new ConfigType()`. Core bundles' own Form Types get this wiring
  for free from Symfony's namespace-wide `autoconfigure` pass over their
  `services.yaml`; it's unconfirmed whether a plugin service declared this
  way in `config.php` gets the same `form.type` tagging. If the
  Configuration screen 500s on load after this change, that's the first
  thing to check.
- **Twig's `slice`/`starts with`/`ends with`** (`Resources/views/
  FormTheme/Config/_config_c15tconfig_widget.html.twig`) -- used to group
  each packaged integration's fields into its own panel by field-name
  prefix, without hardcoding the integration list in the template. These
  are standard Twig 3 core features, not verified against Mautic's
  actually-bundled Twig version specifically.
- **Mautic's own tracking bootstrap snippet** (`Assets/src/mautic-
  tracking.js`) -- the content is the standard async-loader pattern
  documented across Mautic's history, not copy-pasted from a live
  instance's own Settings -> "Contact tracking code" page. Confirm it
  matches before relying on it at real tracking volume.

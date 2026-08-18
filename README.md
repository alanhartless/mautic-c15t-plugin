# Mautic c15t Consent Bundle

A Mautic plugin that turns your Mautic install into the single embeddable
consent-management endpoint for every site you run: one `<script>` tag,
served from your own Mautic instance, that shows an accessible consent
banner, talks to a self-hosted [c15t](https://c15t.com) backend, gates
Mautic's own tracking (`mtc.js`) on actual consent, and can conditionally
load third-party scripts (Google Analytics/Tag Manager, Meta/Reddit/TikTok/X
pixels, LinkedIn Insight Tag, PostHog, or any raw script you configure) only
once a visitor consents to the relevant category.

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

5. Embed on the target site -- see "Embedding on a site" below.

You also need a running c15t backend somewhere (`backendURL` above) --
this plugin is the embedding/config/gating layer, not the consent-storage
backend itself. See [c15t's own self-host docs](https://c15t.com/docs/self-host/quickstart).

## Embedding on a site

Add one script tag to the site, ideally in `<head>` so it runs as early as
possible:

```html
<script src="https://your-mautic.example.com/consent.js" defer></script>
```

That's the entire integration -- no other markup, JS, or build step is
needed on the site itself. Once that tag is in place:

- The banner (or, once a visitor has already decided, nothing) mounts
  itself automatically into a `<div id="wd-consent-root">` it creates.
- Every script this instance is configured to load (Mautic's own tracking,
  GA4/GTM, pixels, anything in "Advanced: custom scripts") only actually
  loads once a visitor consents to its category -- there's nothing further
  to gate manually on the site's side.

Two things have to line up before this works, both configured on the
Mautic side (Configuration -> Consent Manager (c15t), see "Installing"
above), not on the site:

1. **The site's domain must be in "Allowed domains".** `/consent.js`
   resolves the requesting site from its `Origin`/`Referer` header and
   refuses (404) anything not on that list -- match it exactly (bare host,
   no scheme, e.g. `www.example.com`).
2. **The c15t backend's own `trustedOrigins` must include the site too.**
   The allowlist above only gates who gets served the loader script; the
   consent runtime's own calls from the visitor's browser to your c15t
   backend (`backendURL`) are a separate cross-origin request that backend
   has to trust independently. See
   [c15t's self-host docs](https://c15t.com/docs/self-host/quickstart).

### Styling the banner

The default banner ships with its own CSS, themeable via custom
properties -- override any of these on the site without touching
"Disable default banner styling":

```css
:root {
  --wd-consent-bg: #ffffff;
  --wd-consent-fg: #111827;
  --wd-consent-muted: #6b7280;
  --wd-consent-border: #e5e7eb;
  --wd-consent-primary: #111827;
  --wd-consent-primary-fg: #ffffff;
  --wd-consent-radius: 12px;
  --wd-consent-shadow: 0 -4px 24px rgba(0, 0, 0, 0.12);
  --wd-consent-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
}
```

For anything beyond variables, turn on "Disable default banner styling" in
Configuration -> Consent Manager (c15t) and style the markup directly --
the same `data-wd-consent-*` hooks the default CSS targets are always
present regardless of that setting: `[data-wd-consent-banner]`,
`[data-wd-consent-dialog]`, `[data-wd-consent-overlay]`,
`[data-wd-consent-actions]`, `[data-wd-consent-primary]`,
`[data-wd-consent-category]`.

### Reopening the banner after a visitor has already decided

The banner only shows once, by design -- after a visitor picks
"Necessary only"/"Accept all"/saves custom preferences, `/consent.js`
mounts nothing on later page loads. To give visitors a way to change their
mind later (a footer "Cookie Settings" link, for example), you need one of:

- **No JS needed** -- add `data-wd-consent-trigger` to any element:

  ```html
  <a href="#" data-wd-consent-trigger>Cookie Settings</a>
  ```

- **Programmatic** -- call the same thing from your own JS:

  ```js
  window.wdConsent.openPreferences();
  ```

Both re-open the preferences dialog (not the banner) with the visitor's
existing choices pre-filled.

## Supported packaged integrations

| Key | Vendor | Required params |
|---|---|---|
| `mautic-tracking` | This Mautic instance's own `mtc.js` | _(none -- auto-detected from Mautic's own `site_url` config)_ |
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

/**
 * Mautic's own tracking bootstrap, wrapped as a script-loader entry
 * (Controller/PublicController.php's 'mautic-tracking' packaged key).
 * Takes `mauticUrl` as a param (Service/IntegrationRegistry.php's own
 * schema for this integration) -- the tracking domain necessarily
 * differs per install of this plugin, so it's supplied per-site via the
 * site profile's own `params`, the same pattern every other packaged
 * integration (meta-pixel's pixelId, google-tag's measurementId) uses --
 * never hardcode a specific installation's Mautic domain here.
 *
 * *** UNVERIFIED CONTENT *** -- the snippet below is the standard
 * async-loader pattern documented across Mautic's history (queue-stub
 * function + async script injection + a queued pageview call), matching
 * the event-driven /mtc.js architecture Mautic core actually uses
 * (CoreBundle\Controller\JsController -- see PublicController.php's own
 * header comment for how that was confirmed). The EXACT current text was
 * NOT confirmed against a live Mautic instance's own Settings -> "Contact
 * tracking code" page -- do that before trusting this at real tracking
 * volume, and replace this string with whatever that page actually shows
 * if it differs.
 */
export function mauticTrackingScript(params = {}) {
  const mauticUrl = params.mauticUrl;
  if (!mauticUrl) {
    console.error('[c15t] mautic-tracking integration requires a mauticUrl param -- see this file\'s own header comment.');
    return null;
  }

  return {
    id: 'mautic-tracking',
    category: 'measurement',
    textContent: `
(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
    w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
    m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
})(window,document,'script','${mauticUrl.replace(/\/$/, '')}/mtc.js','mt');
mt('send', 'pageview');
    `.trim(),
  };
}

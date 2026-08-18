/**
 * Pre-bundled script-loader integration helpers, keyed to match
 * Service/IntegrationRegistry.php's PHP-side keys EXACTLY -- adding a new
 * packaged integration means updating both files (see that PHP class's
 * own header comment). Exposed on window.__c15tScripts so
 * Controller/PublicController.php's generated bootstrap config
 * (window.__C15T_SITE_CONFIG__) can reference integrations by name
 * without index.js needing a big if/else per integration.
 *
 * Every import name below was confirmed against the actual installed
 * @c15t/scripts package (each dist file's own `export { ... }`
 * statement), not assumed from their docs page -- google-tag's example
 * on that page used `googleTag`, which doesn't exist; the real export is
 * `gtag`.
 */
import { metaPixel } from '@c15t/scripts/meta-pixel';
import { gtag } from '@c15t/scripts/google-tag';
import { googleTagManager } from '@c15t/scripts/google-tag-manager';
import { posthog } from '@c15t/scripts/posthog';
import { redditPixel } from '@c15t/scripts/reddit-pixel';
import { tiktokPixel } from '@c15t/scripts/tiktok-pixel';
import { linkedinInsights } from '@c15t/scripts/linkedin-insights';
import { xPixel } from '@c15t/scripts/x-pixel';
import { mauticTrackingScript } from './mautic-tracking.js';

export const packagedScripts = {
  'mautic-tracking': mauticTrackingScript,
  'google-tag': gtag,
  'google-tag-manager': googleTagManager,
  posthog,
  'meta-pixel': metaPixel,
  'reddit-pixel': redditPixel,
  'tiktok-pixel': tiktokPixel,
  'linkedin-insights': linkedinInsights,
  'x-pixel': xPixel,
};

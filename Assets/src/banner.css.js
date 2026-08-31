/**
 * Default banner/dialog styling -- CSS custom properties (--ccm-*)
 * so a site can override individual values without needing the
 * disableDefaultCss escape hatch at all. Injected as a single <style>
 * tag by banner.js; skipped entirely when disableDefaultCss is set in
 * window.__C15T_SITE_CONFIG__ (Controller/PublicController.php), leaving
 * bare, unstyled markup + the same data-ccm-* hooks for a site to
 * style itself.
 */
export const DEFAULT_CSS = `
:root {
  --ccm-bg: #ffffff;
  --ccm-fg: #111827;
  --ccm-muted: #6b7280;
  --ccm-border: #e5e7eb;
  --ccm-primary: #111827;
  --ccm-primary-fg: #ffffff;
  --ccm-radius: 12px;
  --ccm-shadow: 0 -4px 24px rgba(0, 0, 0, 0.12);
  --ccm-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
  --ccm-z: 2147483000;
}

[data-ccm-banner] {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: var(--ccm-z);
  background: var(--ccm-bg);
  color: var(--ccm-fg);
  border-top: 1px solid var(--ccm-border);
  box-shadow: var(--ccm-shadow);
  font-family: var(--ccm-font);
  padding: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
}

[data-ccm-banner] p {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  max-width: 60ch;
  color: var(--ccm-fg);
}

[data-ccm-actions] {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 15px;
}

[data-ccm] button {
  font-family: var(--ccm-font);
  font-size: 14px;
  padding: 8px 16px;
  border-radius: calc(var(--ccm-radius) / 2);
  border: 1px solid var(--ccm-border);
  background: transparent;
  color: var(--ccm-fg);
  cursor: pointer;
}

[data-ccm] button[data-ccm-primary] {
  background: var(--ccm-primary);
  color: var(--ccm-primary-fg);
  border-color: var(--ccm-primary);
}

[data-ccm] button:focus-visible {
  outline: 2px solid var(--ccm-primary);
  outline-offset: 2px;
}

[data-ccm-overlay] {
  position: fixed;
  inset: 0;
  z-index: calc(var(--ccm-z) + 1);
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

[data-ccm-dialog] {
  background: var(--ccm-bg);
  color: var(--ccm-fg);
  border-radius: var(--ccm-radius);
  box-shadow: var(--ccm-shadow);
  font-family: var(--ccm-font);
  max-width: 480px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  padding: 24px;
}

[data-ccm-dialog] h2 {
  margin: 0 0 12px;
  font-size: 18px;
}

[data-ccm-category] {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 0;
  border-bottom: 1px solid var(--ccm-border);
}

[data-ccm-category]:last-of-type {
  border-bottom: none;
}

[data-ccm-category] label {
  font-weight: 600;
  font-size: 14px;
  /* Explicit, not inherited -- the checkbox alignment fix below depends
     on a known line-height to center against. Without this, the label
     silently inherits whatever line-height the embedding page's own base
     styles set, which varies by site and is exactly why the checkbox
     could visually misalign against the label text on some pages and not
     others despite this rule never changing. */
  line-height: 20px;
}

/* Confirmed live: the checkbox visually floats above the label's text
   instead of centering against its first line -- align-items: flex-start
   on the row above (needed so a taller multi-line description doesn't
   drag the checkbox down with it) top-aligns the checkbox against the
   label text's own top edge, not its vertical center. A native checkbox
   renders shorter than the label's 20px line-height, so a small nudge
   closes the gap. */
[data-ccm-category] input[type="checkbox"] {
  margin-top: 3px;
}

[data-ccm-category] p {
  margin: 4px 0 0;
  font-size: 13px;
  color: var(--ccm-muted);
  line-height: 1.4;
}
`;

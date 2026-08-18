/**
 * Default banner/dialog styling -- CSS custom properties (--wd-consent-*)
 * so a site can override individual values without needing the
 * disableDefaultCss escape hatch at all. Injected as a single <style>
 * tag by banner.js; skipped entirely when disableDefaultCss is set in
 * window.__C15T_SITE_CONFIG__ (Controller/PublicController.php), leaving
 * bare, unstyled markup + the same data-wd-consent-* hooks for a site to
 * style itself.
 */
export const DEFAULT_CSS = `
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
  --wd-consent-z: 2147483000;
}

[data-wd-consent-banner] {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: var(--wd-consent-z);
  background: var(--wd-consent-bg);
  color: var(--wd-consent-fg);
  border-top: 1px solid var(--wd-consent-border);
  box-shadow: var(--wd-consent-shadow);
  font-family: var(--wd-consent-font);
  padding: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
}

[data-wd-consent-banner] p {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  max-width: 60ch;
  color: var(--wd-consent-fg);
}

[data-wd-consent-actions] {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

[data-wd-consent] button {
  font-family: var(--wd-consent-font);
  font-size: 14px;
  padding: 8px 16px;
  border-radius: calc(var(--wd-consent-radius) / 2);
  border: 1px solid var(--wd-consent-border);
  background: transparent;
  color: var(--wd-consent-fg);
  cursor: pointer;
}

[data-wd-consent] button[data-wd-consent-primary] {
  background: var(--wd-consent-primary);
  color: var(--wd-consent-primary-fg);
  border-color: var(--wd-consent-primary);
}

[data-wd-consent] button:focus-visible {
  outline: 2px solid var(--wd-consent-primary);
  outline-offset: 2px;
}

[data-wd-consent-overlay] {
  position: fixed;
  inset: 0;
  z-index: calc(var(--wd-consent-z) + 1);
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

[data-wd-consent-dialog] {
  background: var(--wd-consent-bg);
  color: var(--wd-consent-fg);
  border-radius: var(--wd-consent-radius);
  box-shadow: var(--wd-consent-shadow);
  font-family: var(--wd-consent-font);
  max-width: 480px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  padding: 24px;
}

[data-wd-consent-dialog] h2 {
  margin: 0 0 12px;
  font-size: 18px;
}

[data-wd-consent-category] {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 0;
  border-bottom: 1px solid var(--wd-consent-border);
}

[data-wd-consent-category]:last-of-type {
  border-bottom: none;
}

[data-wd-consent-category] label {
  font-weight: 600;
  font-size: 14px;
}

[data-wd-consent-category] p {
  margin: 4px 0 0;
  font-size: 13px;
  color: var(--wd-consent-muted);
  line-height: 1.4;
}
`;

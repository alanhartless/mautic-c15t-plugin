import { DEFAULT_CSS } from './banner.css.js';

/**
 * Accessible vanilla banner + dialog UI, built directly on c15t's headless
 * store (per c15t's own "Building UI" guide -- activeUI/consents/
 * consentCategories/selectedConsents drive rendering; saveConsents/
 * setSelectedConsent/setActiveUI drive actions). This is the ONE UI
 * artifact this plugin ships -- portable by design (no framework), so the
 * same bundle works on Grav, the app, and any future embedding site,
 * which is the whole reason this isn't built as React components (see
 * chat history, TASK-716 discussion, for why @c15t/react's stock
 * <ConsentBanner>/<ConsentDialog> were ruled out).
 *
 * Human-readable category labels/descriptions are NOT supplied by the
 * headless store (that's a UI-layer concern by design) -- CATEGORY_INFO
 * below covers c15t's five documented default categories; an unknown
 * category key falls back to its raw name so nothing silently disappears
 * if a site profile lists something not in this map.
 */

const CATEGORY_INFO = {
  necessary: {
    label: 'Necessary',
    description: 'Required for the site to function. Cannot be turned off.',
  },
  functionality: {
    label: 'Functionality',
    description: 'Remembers choices you make to provide a better experience.',
  },
  experience: {
    label: 'Experience',
    description: 'Helps us understand how the site is used to improve it.',
  },
  measurement: {
    label: 'Analytics',
    description: 'Helps us understand traffic and usage patterns.',
  },
  marketing: {
    label: 'Marketing',
    description: 'Used to show relevant ads and measure their performance.',
  },
};

let stylesInjected = false;

function injectStyles() {
  if (stylesInjected || document.getElementById('ccm-styles')) return;
  const style = document.createElement('style');
  style.id = 'ccm-styles';
  style.textContent = DEFAULT_CSS;
  document.head.appendChild(style);
  stylesInjected = true;
}

/**
 * Traps Tab/Shift+Tab within `container`, moves focus to its first
 * focusable element, and restores focus to `returnFocusTo` on cleanup.
 * Returns a cleanup function. Only called at all when the
 * 'enable_focus_trap' config field is on (default) -- see
 * mountConsentUI()'s own destructured options and its two call sites
 * below; skipping the call skips both the trap AND the initial-focus
 * placement together, not just the Tab-cycling behavior.
 */
function trapFocus(container, returnFocusTo) {
  const focusable = () =>
    Array.from(
      container.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'),
    ).filter((el) => !el.disabled);

  const onKeydown = (event) => {
    if (event.key !== 'Tab') return;
    const items = focusable();
    if (items.length === 0) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  container.addEventListener('keydown', onKeydown);
  const items = focusable();
  if (items.length > 0) items[0].focus();

  return () => {
    container.removeEventListener('keydown', onKeydown);
    if (returnFocusTo && typeof returnFocusTo.focus === 'function') {
      returnFocusTo.focus();
    }
  };
}

const DEFAULT_BANNER_TEXT =
  'We use cookies to run this site and, if you agree, to measure usage and show relevant marketing.';
const DEFAULT_MODAL_TEXT = 'Choose which categories of cookies you allow. Necessary cookies are always on.';

export function mountConsentUI(consentStore, options = {}) {
  const {
    disableDefaultCss = false,
    enableFocusTrap = true,
    bannerText = '',
    modalText = '',
  } = options;
  if (!disableDefaultCss) injectStyles();

  let root = document.getElementById('ccm-root');
  if (!root) {
    root = document.createElement('div');
    root.id = 'ccm-root';
    root.setAttribute('data-ccm', '');
    document.body.appendChild(root);
  }

  let lastFocused = null;
  let cleanupTrap = null;

  function render(state) {
    root.innerHTML = '';
    if (cleanupTrap) {
      cleanupTrap();
      cleanupTrap = null;
    }

    if (state.activeUI === 'banner') {
      renderBanner(state);
    } else if (state.activeUI === 'dialog') {
      renderDialog(state);
    }
    // 'none' -- nothing mounted, root stays empty.
  }

  function renderBanner(state) {
    lastFocused = document.activeElement;

    const banner = document.createElement('div');
    banner.setAttribute('data-ccm-banner', '');
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-label', 'Cookie consent');
    banner.setAttribute('aria-describedby', 'ccm-banner-text');

    const text = document.createElement('p');
    text.id = 'ccm-banner-text';
    text.textContent = bannerText || DEFAULT_BANNER_TEXT;
    banner.appendChild(text);

    const actions = document.createElement('div');
    actions.setAttribute('data-ccm-actions', '');

    actions.appendChild(
      makeButton('Customize', () => consentStore.getState().setActiveUI('dialog')),
    );
    actions.appendChild(
      makeButton('Necessary only', () => consentStore.getState().saveConsents('necessary')),
    );
    actions.appendChild(
      makeButton('Accept all', () => consentStore.getState().saveConsents('all'), true),
    );

    banner.appendChild(actions);
    root.appendChild(banner);

    if (enableFocusTrap) cleanupTrap = trapFocus(banner, lastFocused);
  }

  function renderDialog(state) {
    lastFocused = document.activeElement;

    const overlay = document.createElement('div');
    overlay.setAttribute('data-ccm-overlay', '');

    const dialog = document.createElement('div');
    dialog.setAttribute('data-ccm-dialog', '');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'ccm-dialog-title');

    const title = document.createElement('h2');
    title.id = 'ccm-dialog-title';
    title.textContent = 'Manage cookie preferences';
    dialog.appendChild(title);

    const intro = document.createElement('p');
    intro.id = 'ccm-dialog-text';
    intro.textContent = modalText || DEFAULT_MODAL_TEXT;
    dialog.appendChild(intro);
    dialog.setAttribute('aria-describedby', 'ccm-dialog-text');

    const categories = state.consentCategories && state.consentCategories.length > 0
      ? state.consentCategories
      : ['necessary'];

    for (const category of categories) {
      const info = CATEGORY_INFO[category] || { label: category, description: '' };
      const row = document.createElement('div');
      row.setAttribute('data-ccm-category', '');

      const isNecessary = category === 'necessary';
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.id = `ccm-cat-${category}`;
      checkbox.checked = isNecessary
        ? true
        : Boolean((state.selectedConsents || state.consents || {})[category]);
      checkbox.disabled = isNecessary;
      checkbox.addEventListener('change', () => {
        consentStore.getState().setSelectedConsent(category, checkbox.checked);
      });

      const labelWrap = document.createElement('div');
      const label = document.createElement('label');
      label.htmlFor = checkbox.id;
      label.textContent = info.label;
      const desc = document.createElement('p');
      desc.textContent = info.description;
      labelWrap.appendChild(label);
      labelWrap.appendChild(desc);

      row.appendChild(checkbox);
      row.appendChild(labelWrap);
      dialog.appendChild(row);
    }

    const actions = document.createElement('div');
    actions.setAttribute('data-ccm-actions', '');
    actions.appendChild(
      makeButton('Save preferences', () => consentStore.getState().saveConsents('custom')),
    );
    actions.appendChild(
      makeButton('Accept all', () => consentStore.getState().saveConsents('all'), true),
    );
    dialog.appendChild(actions);

    overlay.addEventListener('keydown', (event) => {
      // Escape returns to the banner rather than silently dismissing with
      // no recorded decision -- deliberate: a compliance-relevant choice
      // shouldn't disappear on a stray keypress.
      if (event.key === 'Escape') {
        consentStore.getState().setActiveUI('banner');
      }
    });

    overlay.appendChild(dialog);
    root.appendChild(overlay);

    if (enableFocusTrap) cleanupTrap = trapFocus(dialog, lastFocused);
  }

  function makeButton(text, onClick, primary = false) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = text;
    if (primary) button.setAttribute('data-ccm-primary', '');
    button.addEventListener('click', onClick);
    return button;
  }

  render(consentStore.getState());
  return consentStore.subscribe(render);
}

/**
 * Minimalist Loader - settings screen.
 *
 * Deferred, so the DOM is parsed by the time this runs. wp.media is the only
 * WordPress global used; everything else is plain DOM.
 */
(() => {
  'use strict';

  const config = window.MinimalistLoaderAdmin ?? {};
  const i18n = config.i18n ?? {};
  const optionName = config.optionName ?? 'minimalist_loader_settings';
  const SEARCH_DEBOUNCE = 250;

  const searchInput = document.getElementById('ml-content-search');
  const resultsBox = document.getElementById('ml-search-results');
  const selectedList = document.getElementById('ml-selected-exclusions');
  const manualInput = document.getElementById('ml-manual-id');
  const addManualButton = document.getElementById('ml-add-manual-id');

  let mediaFrame = null;
  let searchTimer = 0;
  let inFlightSearch = null;

  setUpLogoPicker();
  setUpExclusions();

  function el(tag, props = {}, ...children) {
    const node = Object.assign(document.createElement(tag), props);
    node.append(...children);

    return node;
  }

  function setUpLogoPicker() {
    document.getElementById('ml-select-logo')?.addEventListener('click', () => {
      mediaFrame ??= createMediaFrame();
      mediaFrame?.open();
    });

    document.getElementById('ml-remove-logo')?.addEventListener('click', () => {
      const field = document.getElementById('ml-logo-id');

      if (field instanceof HTMLInputElement) {
        field.value = '';
      }

      document.querySelector('.ml-logo-preview')?.classList.remove('has-logo');
      document.querySelector('.ml-logo-preview')?.replaceChildren();
    });
  }

  function createMediaFrame() {
    if (typeof window.wp?.media !== 'function') {
      return null;
    }

    const frame = window.wp.media({
      title: i18n.mediaTitle,
      button: { text: i18n.mediaButton },
      library: { type: 'image' },
      multiple: false,
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first()?.toJSON();

      if (!attachment) {
        return;
      }

      const field = document.getElementById('ml-logo-id');
      const preview = document.querySelector('.ml-logo-preview');

      if (field instanceof HTMLInputElement) {
        field.value = String(attachment.id);
      }

      preview?.classList.add('has-logo');
      preview?.replaceChildren(el('img', {
        src: attachment.sizes?.medium?.url ?? attachment.url,
        alt: '',
      }));
    });

    return frame;
  }

  function setUpExclusions() {
    if (!searchInput || !resultsBox || !selectedList) {
      return;
    }

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.trim();

      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => void search(term), SEARCH_DEBOUNCE);
    });

    resultsBox.addEventListener('click', (event) => {
      const result = event.target.closest?.('.ml-result');

      if (result) {
        addExclusion(result.dataset);
      }
    });

    selectedList.addEventListener('click', (event) => {
      event.target.closest?.('.ml-remove-exclusion')?.closest('.ml-selected-item')?.remove();
    });

    addManualButton?.addEventListener('click', () => {
      const id = Number.parseInt(manualInput?.value ?? '', 10);

      if (!Number.isInteger(id) || id <= 0) {
        return;
      }

      addExclusion({ id, title: `${i18n.manualId} #${id}`, meta: i18n.manualId });
      manualInput.value = '';
    });

    manualInput?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        // The picker lives inside the settings form; Enter must not submit it.
        event.preventDefault();
        addManualButton?.click();
      }
    });
  }

  async function search(term) {
    // Drop the previous request so a slow early keystroke cannot overwrite a newer result.
    inFlightSearch?.abort();

    if (term === '') {
      resultsBox.replaceChildren();
      return;
    }

    resultsBox.replaceChildren(el('p', { className: 'ml-empty', textContent: i18n.searching ?? '' }));

    inFlightSearch = new AbortController();

    try {
      const url = new URL(config.searchUrl, window.location.origin);
      url.searchParams.set('term', term);

      const response = await fetch(url, {
        headers: { 'X-WP-Nonce': config.nonce ?? '' },
        credentials: 'same-origin',
        signal: inFlightSearch.signal,
      });

      if (!response.ok) {
        throw new Error(`Search failed with status ${response.status}`);
      }

      const payload = await response.json();

      renderResults(Array.isArray(payload?.results) ? payload.results : []);
    } catch (error) {
      if (error.name !== 'AbortError') {
        renderResults([]);
      }
    }
  }

  function renderResults(results) {
    if (results.length === 0) {
      resultsBox.replaceChildren(el('p', { className: 'ml-empty', textContent: i18n.noResults ?? '' }));
      return;
    }

    resultsBox.replaceChildren(...results.map((item) => {
      const button = el(
        'button',
        { type: 'button', className: 'ml-result' },
        el('strong', { textContent: item.title }),
        el('small', { textContent: `${item.meta} - ID ${item.id}` })
      );

      Object.assign(button.dataset, {
        id: String(item.id),
        title: item.title,
        meta: item.meta,
      });

      return button;
    }));
  }

  function addExclusion({ id, title, meta }) {
    const postId = Number.parseInt(id, 10);

    if (!Number.isInteger(postId) || postId <= 0 || isAlreadyExcluded(postId)) {
      return;
    }

    const row = el(
      'div',
      { className: 'ml-selected-item' },
      el('input', {
        type: 'hidden',
        name: `${optionName}[display][excluded_ids][]`,
        value: String(postId),
      }),
      el(
        'span',
        {},
        el('strong', { textContent: title || `${i18n.manualId} #${postId}` }),
        el('small', { textContent: `${meta || i18n.manualId} - ID ${postId}` })
      ),
      el('button', {
        type: 'button',
        className: 'button-link-delete ml-remove-exclusion',
        textContent: i18n.remove ?? 'Remove',
      })
    );

    row.dataset.id = String(postId);
    selectedList.append(row);
  }

  function isAlreadyExcluded(postId) {
    return selectedList.querySelector(`.ml-selected-item[data-id="${postId}"]`) !== null;
  }
})();

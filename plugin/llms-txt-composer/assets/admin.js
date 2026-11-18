/* LLMs.txt Composer: accessible, dependency-free editorial highlight selector. */
(function () {
  'use strict';
  const root = document.getElementById('llms-composer-featured');
  const cfg = window.llmsComposerSearch;
  if (!root || !cfg) return;

  // Show only the options relevant to the chosen document mode. Inputs stay
  // enabled in hidden sections so switching modes never discards authored work.
  const modeSelect = document.getElementById('al-mode');
  if (modeSelect) {
    const groups = document.querySelectorAll('[data-mode-show]');
    const updateMode = () => {
      const mode = modeSelect.value;
      groups.forEach((group) => {
        group.hidden = !group.dataset.modeShow.split(/\s+/).includes(mode);
      });
    };
    modeSelect.addEventListener('change', updateMode);
    updateMode();
  }

  const input = root.querySelector('#al-featured-search');
  const idsField = root.querySelector('#al-high');
  const results = root.querySelector('#al-featured-results');
  const selected = root.querySelector('#al-featured-selected');
  const status = root.querySelector('#al-featured-status');
  let serial = 0;
  let delay;

  function ids() {
    return [...new Set(idsField.value.split(/[\s,]+/).filter((x) => /^[1-9]\d*$/.test(x)).map(Number))].slice(0, 10);
  }
  function syncIds(next) {
    idsField.value = [...new Set(next)].slice(0, 10).join(',');
  }
  function item(id, title, note = '') {
    const li = document.createElement('li');
    li.dataset.featuredId = String(id);
    const heading = document.createElement('strong');
    heading.className = 'llms-featured-title';
    heading.textContent = title;

    const label = document.createElement('label');
    label.append(document.createTextNode(cfg.note + ' '));
    const extra = document.createElement('span');
    extra.className = 'screen-reader-text';
    extra.textContent = title;
    const field = document.createElement('input');
    field.type = 'text';
    field.maxLength = 180;
    field.className = 'widefat';
    field.name = 'amino_llms[featured_descriptions][' + id + ']';
    field.value = note;
    label.append(extra, field);

    const actions = document.createElement('div');
    actions.className = 'llms-featured-actions';
    for (const [action, caption] of [
      ['up', cfg.moveUp], ['down', cfg.moveDown], ['remove', cfg.remove]
    ]) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'button-link';
      button.dataset['featured' + action[0].toUpperCase() + action.slice(1)] = '';
      button.setAttribute('aria-label', caption + ': ' + title);
      button.textContent = caption;
      actions.append(button);
    }
    li.append(heading, label, actions);
    return li;
  }
  function syncFromSelected() {
    syncIds([...selected.querySelectorAll('li[data-featured-id]')].map((li) => Number(li.dataset.featuredId)));
  }
  function reconcile() {
    const chosen = ids();
    selected.querySelectorAll('li[data-featured-id]').forEach((li) => {
      if (!chosen.includes(Number(li.dataset.featuredId))) li.remove();
    });
  }
  idsField.addEventListener('input', reconcile);
  selected.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-featured-remove], button[data-featured-up], button[data-featured-down]');
    if (!button) return;
    const li = button.closest('li[data-featured-id]');
    if (!li) return;
    if (button.hasAttribute('data-featured-remove')) {
      li.remove();
      syncFromSelected();
      return;
    }
    if (button.hasAttribute('data-featured-up') && li.previousElementSibling) {
      selected.insertBefore(li, li.previousElementSibling);
    } else if (button.hasAttribute('data-featured-down') && li.nextElementSibling) {
      li.nextElementSibling.after(li);
    }
    syncFromSelected();
    button.focus();
  });
  results.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-featured-select]');
    if (!button) return;
    const id = Number(button.dataset.featuredSelect);
    const next = ids();
    if (next.includes(id)) {
      status.textContent = cfg.added;
      return;
    }
    if (next.length >= 10) {
      status.textContent = cfg.limit;
      return;
    }
    next.push(id);
    syncIds(next);
    selected.append(item(id, button.dataset.featuredLabel));
    input.value = '';
    results.replaceChildren();
    status.textContent = cfg.added;
    input.focus();
  });
  input.addEventListener('input', () => {
    clearTimeout(delay);
    const myRequest = ++serial;
    results.replaceChildren();
    const query = input.value.trim();
    if (query.length < 2) {
      status.textContent = query ? cfg.min : '';
      return;
    }
    status.textContent = cfg.searching;
    delay = setTimeout(async () => {
      const params = new URLSearchParams({
        action: 'amino_llms_find_posts', nonce: cfg.nonce, q: query
      });
      try {
        const response = await fetch(cfg.ajaxUrl + '?' + params.toString(), {
          credentials: 'same-origin', headers: { Accept: 'application/json' }
        });
        const json = await response.json();
        if (myRequest !== serial) return;
        if (!response.ok || !json.success || !Array.isArray(json.data)) throw new Error('search');
        json.data.forEach((post) => {
          const li = document.createElement('li');
          const button = document.createElement('button');
          button.type = 'button';
          button.dataset.featuredSelect = String(post.id);
          button.dataset.featuredLabel = String(post.title);
          button.textContent = post.title + ' (' + post.type + ')';
          li.append(button);
          results.append(li);
        });
        status.textContent = json.data.length ? cfg.choose : cfg.none;
      } catch (error) {
        if (myRequest === serial) status.textContent = cfg.failed;
      }
    }, 280);
  });
}());

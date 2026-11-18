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
  function markPreviewDirty() {
    const message = document.getElementById('al-preview-message');
    if (message) {
      message.textContent = cfg.previewDirty;
      message.dataset.state = 'dirty';
    }
  }
  function syncIds(next) {
    idsField.value = [...new Set(next)].slice(0, 10).join(',');
    // Programmatic updates do not dispatch form input/change events.
    markPreviewDirty();
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
    const shown = [...selected.querySelectorAll('li[data-featured-id]')].map((li) => Number(li.dataset.featuredId));
    // Do not silently discard IDs entered through the advanced input field.
    syncIds([...shown, ...ids().filter((id) => !shown.includes(id))]);
  }
  function reconcile() {
    const chosen = ids();
    const existing = new Map(
      [...selected.querySelectorAll('li[data-featured-id]')].map((li) => [Number(li.dataset.featuredId), li])
    );
    existing.forEach((li, id) => {
      if (!chosen.includes(id)) li.remove();
    });
    // Reflect advanced-field order in the visible cards as well.
    chosen.forEach((id) => {
      if (existing.has(id)) selected.append(existing.get(id));
    });
  }
  idsField.addEventListener('input', reconcile);
  selected.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-featured-remove], button[data-featured-up], button[data-featured-down]');
    if (!button) return;
    const li = button.closest('li[data-featured-id]');
    if (!li) return;
    if (button.hasAttribute('data-featured-remove')) {
      const remaining = ids().filter((id) => id !== Number(li.dataset.featuredId));
      li.remove();
      syncIds(remaining);
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

  // Preview the entire unsaved form without updating WordPress options or
  // invalidating the public cache. All content is rendered as textarea text.
  const editorForm = root.closest('form');
  const draftButton = document.getElementById('al-preview-draft');
  const previewTarget = document.getElementById('al-markdown-preview');
  const previewMessage = document.getElementById('al-preview-message');
  const previewBytes = document.getElementById('al-preview-bytes');
  if (editorForm && draftButton && previewTarget && previewMessage && previewBytes) {
    const onEdit = (event) => {
      // Search terms are transient UI values, not editorial settings.
      if (event.target && typeof event.target.name === 'string' &&
          event.target.name.startsWith('amino_llms[')) markPreviewDirty();
    };
    editorForm.addEventListener('input', onEdit);
    editorForm.addEventListener('change', onEdit);
    draftButton.addEventListener('click', async () => {
      draftButton.disabled = true;
      previewMessage.textContent = cfg.previewing;
      previewMessage.dataset.state = 'checking';
      try {
        const data = new FormData(editorForm);
        data.set('action', 'amino_llms_preview_draft');
        data.set('nonce', cfg.previewNonce);
        const response = await fetch(cfg.ajaxUrl, {
          method: 'POST', credentials: 'same-origin',
          headers: { Accept: 'application/json' }, body: data
        });
        const result = await response.json();
        if (!response.ok || !result.success || !result.data ||
            typeof result.data.document !== 'string' ||
            typeof result.data.valid !== 'boolean' ||
            typeof result.data.bytes !== 'number') throw new Error('draft');
        previewTarget.value = result.data.document;
        previewBytes.textContent = String(result.data.bytes);
        previewMessage.dataset.state = result.data.valid ? 'valid' : 'invalid';
        previewMessage.textContent = result.data.valid ? cfg.previewReady : cfg.previewInvalid;
      } catch (_) {
        previewMessage.dataset.state = 'invalid';
        previewMessage.textContent = cfg.previewFailed;
      } finally {
        draftButton.disabled = false;
      }
    });
  }

  // The probe is triggered only by an administrator's button click.
  // WordPress performs a separate public HTTP request without admin cookies.
  const probeButton = document.getElementById('al-verify-public-route');
  const probeStatus = document.getElementById('al-public-probe-status');
  if (probeButton && probeStatus) {
    probeButton.addEventListener('click', async () => {
      probeButton.disabled = true;
      probeStatus.textContent = cfg.probing;
      probeStatus.dataset.state = 'checking';
      try {
        const body = new URLSearchParams({
          action: 'amino_llms_probe', nonce: cfg.probeNonce
        });
        const response = await fetch(cfg.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', Accept: 'application/json' },
          body: body.toString()
        });
        const data = await response.json();
        if (!response.ok || !data.success || !data.data) throw new Error('probe');
        probeStatus.dataset.state = data.data.state;
        probeStatus.textContent = data.data.message + ' (HTTP ' + data.data.status +
          (data.data.mime ? ', ' + data.data.mime : '') + ')';
      } catch (_) {
        probeStatus.dataset.state = 'unreachable';
        probeStatus.textContent = cfg.probeFailed;
      } finally {
        probeButton.disabled = false;
      }
    });
  }

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

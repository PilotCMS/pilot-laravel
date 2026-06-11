@if(app(\Pilot\Laravel\Support\InContext::class)->enabled())
    <script>
        (() => {
            if (window.__pilotInContextLoaded) {
                return;
            }

            window.__pilotInContextLoaded = true;

            const BLOCKS_BASE_URL = @json(url('/'.trim((string) config('pilot.in_context.path', '_pilot/in-context'), '/').'/blocks'));
            const CONTENTS_BASE_URL = @json(url('/'.trim((string) config('pilot.in_context.path', '_pilot/in-context'), '/').'/contents'));
            const DEFAULT_LOCALE = @json(config('pilot.default_locale', app()->getLocale()));
            const ROOT_ID = 'pilot-in-context-panel-root';
            const HIGHLIGHT_ID = 'pilot-in-context-highlight';
            const BLOCK_SELECTOR = '[data-pilot-editable="block"]';
            const FIELD_SELECTOR = '[data-pilot-editable="field"]';

            const parentOrigin = (() => {
                try {
                    return document.referrer ? new URL(document.referrer).origin : '*';
                } catch (error) {
                    return '*';
                }
            })();

            const postToEditor = (message) => {
                if (window.parent === window) {
                    return;
                }

                window.parent.postMessage(message, parentOrigin);
            };

            const previewQuery = () => {
                const source = new URLSearchParams(window.location.search);
                const query = new URLSearchParams();

                ['pilot_preview', 'pilot_content', 'pilot_expires', 'pilot_signature'].forEach((key) => {
                    const value = source.get(key);

                    if (value) {
                        query.set(key, value);
                    }
                });

                query.set('locale', document.documentElement.lang || DEFAULT_LOCALE || 'en');

                return query.toString();
            };

            const blockEndpoint = (blockId) => `${BLOCKS_BASE_URL}/${blockId}?${previewQuery()}`;
            const contentId = () => new URLSearchParams(window.location.search).get('pilot_content');
            const contentSyncEndpoint = () => `${CONTENTS_BASE_URL}/${contentId()}/sync?${previewQuery()}`;

            const css = `
                :host {
                    all: initial;
                    --pilot-bg: #0f172a;
                    --pilot-surface: rgba(15, 23, 42, 0.96);
                    --pilot-border: rgba(148, 163, 184, 0.28);
                    --pilot-muted: #94a3b8;
                    --pilot-text: #f8fafc;
                    --pilot-accent: #14b8a6;
                    --pilot-danger: #fb7185;
                    color: var(--pilot-text);
                    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                    pointer-events: none;
                    position: fixed;
                    right: 18px;
                    top: 18px;
                    z-index: 2147483647;
                }

                :host * {
                    box-sizing: border-box;
                    letter-spacing: 0;
                }

                .panel {
                    background: var(--pilot-surface);
                    border: 1px solid var(--pilot-border);
                    border-radius: 8px;
                    box-shadow: 0 22px 56px rgba(15, 23, 42, 0.42);
                    display: flex;
                    flex-direction: column;
                    max-height: calc(100vh - 36px);
                    overflow: hidden;
                    pointer-events: auto;
                    width: min(380px, calc(100vw - 36px));
                }

                .header,
                .footer {
                    align-items: center;
                    display: flex;
                    gap: 10px;
                    justify-content: space-between;
                    padding: 12px 14px;
                }

                .header {
                    border-bottom: 1px solid rgba(148, 163, 184, 0.18);
                }

                .footer {
                    border-top: 1px solid rgba(148, 163, 184, 0.18);
                }

                .title {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                    min-width: 0;
                }

                .title strong,
                .selected strong {
                    color: var(--pilot-text);
                    font-size: 13px;
                    font-weight: 700;
                }

                .title span,
                .muted,
                .status {
                    color: var(--pilot-muted);
                    font-size: 11px;
                }

                .actions {
                    display: flex;
                    gap: 8px;
                }

                button,
                a.button {
                    appearance: none;
                    background: rgba(255, 255, 255, 0.08);
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 6px;
                    color: var(--pilot-text);
                    cursor: pointer;
                    font: inherit;
                    font-size: 12px;
                    line-height: 1;
                    min-height: 30px;
                    padding: 8px 10px;
                    text-decoration: none;
                }

                button:hover,
                a.button:hover {
                    border-color: rgba(20, 184, 166, 0.8);
                }

                button[data-active="true"] {
                    background: rgba(20, 184, 166, 0.22);
                    border-color: rgba(20, 184, 166, 0.85);
                }

                .body {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    min-height: 180px;
                    overflow: auto;
                    padding: 14px;
                }

                .empty,
                .selected {
                    border: 1px solid rgba(148, 163, 184, 0.2);
                    border-radius: 8px;
                    padding: 12px;
                }

                .empty {
                    border-style: dashed;
                    color: var(--pilot-muted);
                    font-size: 13px;
                    line-height: 1.45;
                }

                .selected {
                    background: rgba(255, 255, 255, 0.05);
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .fields {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                }

                .field {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .object-list {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .object-item {
                    background: rgba(255, 255, 255, 0.04);
                    border: 1px solid rgba(148, 163, 184, 0.2);
                    border-radius: 8px;
                    display: grid;
                    gap: 8px;
                    padding: 10px;
                }

                .object-item-header {
                    color: var(--pilot-muted);
                    font-size: 11px;
                    font-weight: 700;
                }

                .object-property {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .object-property span {
                    color: #cbd5e1;
                    font-size: 11px;
                    font-weight: 650;
                }

                .field label {
                    align-items: center;
                    color: #cbd5e1;
                    display: flex;
                    font-size: 12px;
                    font-weight: 650;
                    gap: 8px;
                    justify-content: space-between;
                }

                .field code {
                    color: var(--pilot-muted);
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                    font-size: 10px;
                    font-weight: 500;
                }

                input,
                textarea,
                select {
                    background: rgba(15, 23, 42, 0.9);
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 6px;
                    color: var(--pilot-text);
                    font: inherit;
                    font-size: 13px;
                    line-height: 1.4;
                    outline: none;
                    padding: 9px 10px;
                    width: 100%;
                }

                textarea {
                    min-height: 88px;
                    resize: vertical;
                }

                input:focus,
                textarea:focus,
                select:focus {
                    border-color: var(--pilot-accent);
                    box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.18);
                }

                .checkbox {
                    align-items: center;
                    flex-direction: row;
                    justify-content: space-between;
                }

                .checkbox input {
                    height: 18px;
                    width: 18px;
                }

                .status[data-kind="success"] {
                    color: #5eead4;
                }

                .status[data-kind="error"] {
                    color: var(--pilot-danger);
                }

                .collapsed {
                    width: auto;
                }

                .collapsed .body,
                .collapsed .footer {
                    display: none;
                }

                @media (max-width: 640px) {
                    :host {
                        bottom: 10px;
                        left: 10px;
                        right: 10px;
                        top: auto;
                    }

                    .panel {
                        max-height: 72vh;
                        width: 100%;
                    }
                }
            `;

            const pageCss = `
                ${FIELD_SELECTOR} {
                    cursor: text;
                    outline-offset: 3px;
                    transition: outline-color 120ms ease, background-color 120ms ease;
                }

                ${FIELD_SELECTOR}:hover,
                ${FIELD_SELECTOR}[contenteditable="true"] {
                    background-color: rgba(240, 253, 250, 0.16);
                    outline: 2px solid rgba(20, 184, 166, 0.75);
                }

                ${FIELD_SELECTOR}[contenteditable="true"] {
                    caret-color: currentColor;
                }

                #${HIGHLIGHT_ID} {
                    border: 2px solid #14b8a6;
                    border-radius: 8px;
                    box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.08), 0 12px 32px rgba(15, 23, 42, 0.18);
                    opacity: 0;
                    pointer-events: none;
                    position: fixed;
                    transition: opacity 120ms ease, transform 120ms ease, width 120ms ease, height 120ms ease;
                    z-index: 2147483646;
                }
            `;

            const state = {
                active: true,
                collapsed: false,
                selectedElement: null,
                selectedBlock: null,
                fields: new Map(),
                saveTimer: null,
                host: null,
                root: null,
                panel: null,
                body: null,
                status: null,
                activeButton: null,
                contentUpdatedAt: null,
                contentSyncKey: null,
                selectedBlockUpdatedAt: null,
                saving: false,
                syncTimer: null,
            };

            const editableValue = (element) => {
                if (element.dataset.pilotFieldType === 'richtext') {
                    return element.innerHTML.trim();
                }

                return element.textContent.trim();
            };

            const isPanelElement = (element) => state.host && state.host.contains(element);
            const blockFrom = (target) => target?.closest?.(BLOCK_SELECTOR) ?? null;

            const setStatus = (message, kind = '') => {
                if (! state.status) {
                    return;
                }

                state.status.textContent = message;
                state.status.dataset.kind = kind;
            };

            const updateHighlight = (element) => {
                const highlight = document.getElementById(HIGHLIGHT_ID);

                if (! highlight || ! element || ! state.active) {
                    if (highlight) {
                        highlight.style.opacity = '0';
                    }

                    return;
                }

                const rect = element.getBoundingClientRect();
                highlight.style.height = `${rect.height}px`;
                highlight.style.opacity = '1';
                highlight.style.transform = `translate(${rect.left}px, ${rect.top}px)`;
                highlight.style.width = `${rect.width}px`;
            };

            const fieldValue = (field, data, rawData) => {
                const value = data[field.key] ?? rawData[field.key] ?? field.default ?? '';

                if (Array.isArray(value) || (value && typeof value === 'object')) {
                    return JSON.stringify(value, null, 2);
                }

                return value ?? '';
            };

            const fieldRawValue = (field, block) => (block.data?.[field.key] ?? block.rawData?.[field.key] ?? field.default ?? '');

            const fieldDisplayValue = (field, block) => {
                const value = fieldValue(field, block.data ?? {}, block.rawData ?? {});

                return value === null || value === undefined ? '' : String(value);
            };

            const isPlainObject = (value) => value !== null && typeof value === 'object' && ! Array.isArray(value);
            const isObjectList = (value) => Array.isArray(value) && value.length > 0 && value.every(isPlainObject);

            const objectListKeys = (items) => {
                const keys = [];

                items.forEach((item) => {
                    Object.keys(item).forEach((key) => {
                        if (! keys.includes(key)) {
                            keys.push(key);
                        }
                    });
                });

                return keys;
            };

            const syncKnownTimestamps = (block) => {
                if (! block) {
                    return;
                }

                state.selectedBlockUpdatedAt = block.updatedAt ?? state.selectedBlockUpdatedAt;
                state.contentUpdatedAt = block.content?.updatedAt ?? state.contentUpdatedAt;
                state.contentSyncKey = block.content?.syncKey ?? state.contentSyncKey;
            };

            const isEditing = () => {
                const pageActiveElement = document.activeElement;
                const panelActiveElement = state.root?.activeElement;

                if (pageActiveElement?.closest?.(FIELD_SELECTOR)) {
                    return true;
                }

                return ['INPUT', 'TEXTAREA', 'SELECT'].includes(panelActiveElement?.tagName ?? '');
            };

            const applyBlockToEditableFields = (block) => {
                const fields = block.schema?.fields ?? [];

                fields.forEach((field) => {
                    if (! field.key) {
                        return;
                    }

                    const value = fieldDisplayValue(field, block);

                    document
                        .querySelectorAll(`${FIELD_SELECTOR}[data-pilot-block-id="${block.id}"]`)
                        .forEach((element) => {
                            if (element.dataset.pilotField !== field.key || document.activeElement === element) {
                                return;
                            }

                            if (element.dataset.pilotFieldType === 'richtext') {
                                element.innerHTML = value;
                            } else {
                                element.textContent = value;
                            }

                            element.dataset.pilotOriginalValue = value;
                        });
                });
            };

            const createObjectListInput = (items) => {
                const container = document.createElement('div');
                container.className = 'object-list';
                container.dataset.compositeInput = 'object-list';

                const keys = objectListKeys(items);

                items.forEach((item, index) => {
                    const itemElement = document.createElement('div');
                    itemElement.className = 'object-item';
                    itemElement.dataset.itemIndex = String(index);

                    const header = document.createElement('div');
                    header.className = 'object-item-header';
                    header.textContent = `Item ${index + 1}`;
                    itemElement.appendChild(header);

                    keys.forEach((key) => {
                        const property = document.createElement('label');
                        property.className = 'object-property';

                        const label = document.createElement('span');
                        label.textContent = key;

                        const input = document.createElement('textarea');
                        input.rows = key === 'body' ? 3 : 1;
                        input.dataset.objectKey = key;
                        input.value = item[key] === null || item[key] === undefined ? '' : String(item[key]);

                        property.append(label, input);
                        itemElement.appendChild(property);
                    });

                    container.appendChild(itemElement);
                });

                return container;
            };

            const createInput = (field, value) => {
                if (isObjectList(value)) {
                    return createObjectListInput(value);
                }

                const scalarValue = Array.isArray(value) || isPlainObject(value)
                    ? JSON.stringify(value, null, 2)
                    : (value ?? '');

                if (field.type === 'textarea' || field.type === 'richtext' || field.type === 'repeater') {
                    const textarea = document.createElement('textarea');
                    textarea.value = scalarValue;
                    textarea.rows = field.type === 'richtext' ? 7 : field.type === 'repeater' ? 8 : 4;
                    textarea.spellcheck = field.type !== 'repeater';

                    return textarea;
                }

                if (field.type === 'number') {
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.value = scalarValue;

                    if (field.min !== undefined) {
                        input.min = field.min;
                    }

                    if (field.max !== undefined) {
                        input.max = field.max;
                    }

                    return input;
                }

                if (field.type === 'boolean') {
                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.checked = scalarValue === true || scalarValue === 'true' || scalarValue === 1 || scalarValue === '1';

                    return input;
                }

                if (field.type === 'select' && Array.isArray(field.options)) {
                    const select = document.createElement('select');
                    const empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = 'Select...';
                    select.appendChild(empty);

                    field.options.forEach((option) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = option.value ?? '';
                        optionElement.textContent = option.label ?? option.value ?? '';
                        optionElement.selected = String(optionElement.value) === String(scalarValue);
                        select.appendChild(optionElement);
                    });

                    return select;
                }

                const input = document.createElement('input');
                input.type = field.type === 'image' ? 'url' : 'text';
                input.value = scalarValue;
                input.placeholder = field.placeholder ?? '';

                return input;
            };

            const getInputValue = (field, input) => {
                if (input.dataset.compositeInput === 'object-list') {
                    return Array.from(input.querySelectorAll('.object-item')).map((itemElement) => {
                        const item = {};

                        itemElement.querySelectorAll('[data-object-key]').forEach((propertyInput) => {
                            item[propertyInput.dataset.objectKey] = propertyInput.value;
                        });

                        return item;
                    });
                }

                if (field.type === 'boolean') {
                    return input.checked;
                }

                if (field.type === 'number') {
                    return input.value === '' ? null : Number(input.value);
                }

                if (field.type === 'repeater') {
                    try {
                        return JSON.parse(input.value || '[]');
                    } catch (error) {
                        setStatus(`Invalid JSON for ${field.label ?? field.key}`, 'error');
                        throw error;
                    }
                }

                return input.value;
            };

            const renderEmpty = () => {
                state.body.innerHTML = '';

                const empty = document.createElement('div');
                empty.className = 'empty';
                empty.textContent = 'Click a highlighted Pilot block to edit its fields.';
                state.body.appendChild(empty);
            };

            const renderFields = (block) => {
                state.fields.clear();
                state.body.innerHTML = '';

                const selected = document.createElement('div');
                selected.className = 'selected';
                selected.innerHTML = `
                    <strong>${block.name}</strong>
                    <span class="muted">${block.type} · block #${block.id}</span>
                `;
                state.body.appendChild(selected);

                const fields = block.schema?.fields ?? [];

                if (fields.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'empty';
                    empty.textContent = 'This block type does not have editable schema fields.';
                    state.body.appendChild(empty);

                    return;
                }

                const list = document.createElement('div');
                list.className = 'fields';

                fields.forEach((field) => {
                    if (! field.key) {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = field.type === 'boolean' ? 'field checkbox' : 'field';

                    const label = document.createElement('label');
                    label.innerHTML = `<span>${field.label ?? field.key}</span><code>${field.type ?? 'text'}</code>`;

                    const input = createInput(field, fieldRawValue(field, block));
                    input.dataset.fieldKey = field.key;
                    input.addEventListener('input', () => setStatus('Unsaved changes'));
                    input.addEventListener('change', () => queueSave());
                    input.addEventListener('blur', () => saveNow(), true);

                    wrapper.append(label, input);
                    list.appendChild(wrapper);
                    state.fields.set(field.key, { field, input });
                });

                state.body.appendChild(list);
            };

            const fetchBlock = async (blockId, options = {}) => {
                if (! options.quiet) {
                    setStatus('Loading block...');
                }

                const response = await fetch(blockEndpoint(blockId), {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    throw new Error(`Failed to load block ${blockId}`);
                }

                const payload = await response.json();

                syncKnownTimestamps(payload.block);

                return payload.block;
            };

            const saveFields = async (blockId, fields) => {
                const response = await fetch(blockEndpoint(blockId), {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        fields,
                        locale: document.documentElement.lang || DEFAULT_LOCALE || 'en',
                    }),
                });

                if (! response.ok) {
                    throw new Error('Save failed');
                }

                const payload = await response.json();

                syncKnownTimestamps(payload.block);

                return payload.block;
            };

            const selectBlock = async (element) => {
                const blockId = element.dataset.pilotBlockId;

                if (! blockId) {
                    return;
                }

                state.selectedElement = element;
                updateHighlight(element);

                postToEditor({
                    type: 'pilot-preview-select-block',
                    blockId: Number(blockId),
                    component: element.dataset.pilotComponent,
                    componentPath: element.dataset.pilotComponentPath,
                });

                try {
                    const block = await fetchBlock(blockId);
                    state.selectedBlock = block;
                    renderFields(block);
                    setStatus('Ready');
                } catch (error) {
                    setStatus(error.message, 'error');
                }
            };

            const queueSave = () => {
                setStatus('Unsaved changes');
                clearTimeout(state.saveTimer);
                state.saveTimer = setTimeout(saveNow, 550);
            };

            const saveNow = async () => {
                if (! state.selectedBlock || state.fields.size === 0) {
                    return;
                }

                clearTimeout(state.saveTimer);

                const fields = {};

                for (const [key, binding] of state.fields.entries()) {
                    fields[key] = getInputValue(binding.field, binding.input);
                }

                setStatus('Saving...');
                state.saving = true;

                try {
                    state.selectedBlock = await saveFields(state.selectedBlock.id, fields);
                    renderFields(state.selectedBlock);
                    applyBlockToEditableFields(state.selectedBlock);
                    setStatus('Saved', 'success');
                } catch (error) {
                    setStatus(error.message, 'error');
                } finally {
                    state.saving = false;
                }
            };

            const commitField = async (element) => {
                const value = editableValue(element);

                if (value === element.dataset.pilotOriginalValue) {
                    return;
                }

                element.dataset.pilotOriginalValue = value;
                setStatus('Saving...');
                state.saving = true;

                try {
                    const block = await saveFields(element.dataset.pilotBlockId, {
                        [element.dataset.pilotField]: value,
                    });
                    state.selectedBlock = block;
                    renderFields(block);
                    applyBlockToEditableFields(block);
                    setStatus('Saved', 'success');

                    postToEditor({
                        type: 'pilot-in-context-field-updated',
                        blockId: Number(element.dataset.pilotBlockId),
                        fieldKey: element.dataset.pilotField,
                        value,
                    });
                } catch (error) {
                    setStatus(error.message, 'error');
                } finally {
                    state.saving = false;
                }
            };

            const fetchContentSync = async () => {
                if (! contentId()) {
                    return null;
                }

                const response = await fetch(contentSyncEndpoint(), {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    return null;
                }

                return response.json();
            };

            const syncFromServer = async () => {
                if (state.saving || isEditing()) {
                    return;
                }

                try {
                    const payload = await fetchContentSync();
                    const updatedAt = payload?.content?.updatedAt;
                    const syncKey = payload?.content?.syncKey;

                    if (! updatedAt || ! syncKey) {
                        return;
                    }

                    if (! state.contentSyncKey) {
                        state.contentUpdatedAt = updatedAt;
                        state.contentSyncKey = syncKey;

                        return;
                    }

                    if (syncKey === state.contentSyncKey) {
                        return;
                    }

                    state.contentUpdatedAt = updatedAt;
                    state.contentSyncKey = syncKey;

                    if (! state.selectedBlock) {
                        window.location.reload();

                        return;
                    }

                    const block = await fetchBlock(state.selectedBlock.id, { quiet: true });
                    state.selectedBlock = block;
                    renderFields(block);
                    applyBlockToEditableFields(block);
                    setStatus('Synced', 'success');
                } catch (error) {
                    setStatus('Sync paused', 'error');
                }
            };

            const toggleActive = () => {
                state.active = ! state.active;
                state.activeButton.dataset.active = String(state.active);
                state.activeButton.textContent = state.active ? 'Edit mode' : 'Browse mode';
                updateHighlight(state.active ? state.selectedElement : null);
            };

            const toggleCollapsed = () => {
                state.collapsed = ! state.collapsed;
                state.panel.classList.toggle('collapsed', state.collapsed);
            };

            const buildPanel = () => {
                const host = document.createElement('div');
                host.id = ROOT_ID;

                const root = host.attachShadow({ mode: 'open' });
                const style = document.createElement('style');
                style.textContent = css;

                const panel = document.createElement('div');
                panel.className = 'panel';
                panel.innerHTML = `
                    <div class="header">
                        <div class="title">
                            <strong>Pilot InContext</strong>
                            <span>Preview editing</span>
                        </div>
                        <div class="actions">
                            <button type="button" data-action="active" data-active="true">Edit mode</button>
                            <button type="button" data-action="collapse">Hide</button>
                        </div>
                    </div>
                    <div class="body"></div>
                    <div class="footer">
                        <span class="status">Ready</span>
                        <button type="button" data-action="save">Save</button>
                    </div>
                `;

                root.append(style, panel);
                document.body.appendChild(host);

                state.host = host;
                state.root = root;
                state.panel = panel;
                state.body = root.querySelector('.body');
                state.status = root.querySelector('.status');
                state.activeButton = root.querySelector('[data-action="active"]');

                root.querySelector('[data-action="active"]').addEventListener('click', toggleActive);
                root.querySelector('[data-action="collapse"]').addEventListener('click', toggleCollapsed);
                root.querySelector('[data-action="save"]').addEventListener('click', saveNow);

                renderEmpty();
            };

            const installPageStyles = () => {
                const style = document.createElement('style');
                style.textContent = pageCss;
                document.head.appendChild(style);

                const highlight = document.createElement('div');
                highlight.id = HIGHLIGHT_ID;
                document.body.appendChild(highlight);
            };

            document.addEventListener('mouseover', (event) => {
                if (isPanelElement(event.target)) {
                    return;
                }

                const editable = blockFrom(event.target);

                if (editable) {
                    updateHighlight(editable);
                }
            });

            document.addEventListener('click', (event) => {
                if (! state.active || isPanelElement(event.target)) {
                    return;
                }

                const field = event.target.closest?.(FIELD_SELECTOR);

                if (field) {
                    event.preventDefault();
                    event.stopPropagation();

                    const block = blockFrom(field);

                    if (block) {
                        selectBlock(block);
                    }

                    field.dataset.pilotOriginalValue = editableValue(field);
                    field.setAttribute('contenteditable', 'true');
                    field.focus({ preventScroll: true });

                    const selection = window.getSelection();
                    const range = document.createRange();
                    range.selectNodeContents(field);
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);

                    return;
                }

                const editable = blockFrom(event.target);

                if (! editable) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                selectBlock(editable);
            }, true);

            document.addEventListener('keydown', (event) => {
                const field = event.target.closest?.(FIELD_SELECTOR);

                if (field) {
                    const multiline = ['textarea', 'richtext'].includes(field.dataset.pilotFieldType);

                    if (event.key === 'Enter' && ! multiline) {
                        event.preventDefault();
                        field.blur();
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        field.textContent = field.dataset.pilotOriginalValue || field.textContent;
                        field.blur();
                    }
                }

                if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'e') {
                    event.preventDefault();
                    toggleActive();
                }
            });

            document.addEventListener('blur', (event) => {
                const field = event.target.closest?.(FIELD_SELECTOR);

                if (! field) {
                    return;
                }

                commitField(field);
                field.removeAttribute('contenteditable');
            }, true);

            window.addEventListener('resize', () => updateHighlight(state.selectedElement));
            window.addEventListener('scroll', () => updateHighlight(state.selectedElement), true);

            installPageStyles();
            buildPanel();
            state.syncTimer = window.setInterval(syncFromServer, 1000);
        })();
    </script>
@endif

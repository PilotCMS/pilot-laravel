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
            const CMS_PREVIEW_FRAME_NAME = 'pilot-cms-preview';
            let panelEnabled = (() => {
                if (window.name === CMS_PREVIEW_FRAME_NAME) {
                    return false;
                }

                const value = new URLSearchParams(window.location.search).get('pilot_in_context_panel');

                if (value === null) {
                    return true;
                }

                return ! ['0', 'false', 'off', 'no'].includes(value.toLowerCase());
            })();

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
                    --pilot-bg: #ffffff;
                    --pilot-surface: rgba(255, 255, 255, 0.98);
                    --pilot-border: #e2e8f0;
                    --pilot-muted: #64748b;
                    --pilot-text: #1e293b;
                    --pilot-accent: #14b8a6;
                    --pilot-danger: #e11d48;
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
                    box-shadow: 0 22px 56px rgba(15, 23, 42, 0.2);
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
                    border-bottom: 1px solid var(--pilot-border);
                }

                .footer {
                    border-top: 1px solid var(--pilot-border);
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
                    background: #f8fafc;
                    border: 1px solid var(--pilot-border);
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
                    background: #f0fdfa;
                    border-color: rgba(20, 184, 166, 0.85);
                    color: #0f766e;
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
                    background: #f0fdfa;
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

                .object-item,
                .repeater-item {
                    background: #ffffff;
                    border: 1px solid var(--pilot-border);
                    border-radius: 8px;
                    overflow: hidden;
                }

                .object-item-header {
                    color: var(--pilot-muted);
                    font-size: 11px;
                    font-weight: 700;
                }

                .object-item {
                    display: grid;
                    gap: 8px;
                    padding: 10px;
                }

                .object-property {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .object-property span {
                    color: #475569;
                    font-size: 11px;
                    font-weight: 650;
                }

                .repeater {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .repeater-item[data-expanded="true"] {
                    border-color: rgba(20, 184, 166, 0.6);
                }

                .repeater-item-header {
                    align-items: center;
                    display: flex;
                    gap: 8px;
                    min-height: 48px;
                    padding: 8px 10px;
                }

                .repeater-grip {
                    color: var(--pilot-muted);
                    font-size: 15px;
                    letter-spacing: -3px;
                }

                button.repeater-toggle {
                    align-items: center;
                    background: transparent;
                    border: 0;
                    display: flex;
                    flex: 1;
                    gap: 8px;
                    justify-content: space-between;
                    min-width: 0;
                    padding: 2px;
                    text-align: left;
                }

                .repeater-copy {
                    display: flex;
                    flex: 1;
                    flex-direction: column;
                    gap: 3px;
                    min-width: 0;
                }

                .repeater-title {
                    color: var(--pilot-text);
                    font-size: 12px;
                    font-weight: 700;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .repeater-summary {
                    color: var(--pilot-muted);
                    font-size: 10px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .repeater-caret {
                    color: var(--pilot-muted);
                    font-size: 14px;
                }

                button.repeater-remove {
                    background: transparent;
                    border: 0;
                    color: var(--pilot-muted);
                    min-height: 26px;
                    min-width: 26px;
                    padding: 4px;
                }

                button.repeater-remove:hover {
                    color: var(--pilot-danger);
                }

                .repeater-item-fields {
                    background: #f8fafc;
                    border-top: 1px solid var(--pilot-border);
                    display: none;
                    flex-direction: column;
                    gap: 12px;
                    padding: 12px;
                }

                .repeater-item[data-expanded="true"] .repeater-item-fields {
                    display: flex;
                }

                .repeater-subfield {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .subfield-heading {
                    align-items: center;
                    color: #475569;
                    display: flex;
                    font-size: 11px;
                    font-weight: 650;
                    justify-content: space-between;
                }

                .subfield-heading code,
                .field-help {
                    color: var(--pilot-muted);
                    font-size: 10px;
                }

                button.repeater-add {
                    align-self: flex-start;
                    background: transparent;
                    border-color: rgba(20, 184, 166, 0.45);
                    color: #0f766e;
                }

                .richtext-editor {
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 6px;
                    overflow: hidden;
                }

                .richtext-editor:focus-within {
                    border-color: var(--pilot-accent);
                    box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.18);
                }

                .richtext-toolbar {
                    align-items: center;
                    background: #f8fafc;
                    border-bottom: 1px solid var(--pilot-border);
                    display: flex;
                    flex-wrap: wrap;
                    gap: 3px;
                    padding: 5px;
                }

                button.richtext-command {
                    background: transparent;
                    border: 0;
                    font-size: 11px;
                    min-height: 26px;
                    min-width: 26px;
                    padding: 5px;
                }

                .richtext-surface {
                    color: var(--pilot-text);
                    font-size: 13px;
                    line-height: 1.55;
                    min-height: 132px;
                    outline: none;
                    padding: 10px;
                }

                .richtext-surface:empty::before {
                    color: var(--pilot-muted);
                    content: attr(data-placeholder);
                    pointer-events: none;
                }

                .image-editor {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .image-preview {
                    align-items: center;
                    background: #f8fafc;
                    border: 1px dashed var(--pilot-border);
                    border-radius: 6px;
                    display: none;
                    justify-content: center;
                    min-height: 96px;
                    overflow: hidden;
                    padding: 6px;
                }

                .image-preview[data-visible="true"] {
                    display: flex;
                }

                .image-preview img {
                    display: block;
                    max-height: 132px;
                    max-width: 100%;
                    object-fit: contain;
                }

                .field label {
                    align-items: center;
                    color: #475569;
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
                    background: #ffffff;
                    border: 1px solid var(--pilot-border);
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

                .field-help {
                    line-height: 1.4;
                    margin: 0;
                }

                .status[data-kind="success"] {
                    color: #0f766e;
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
                html[data-pilot-in-context-editing="true"] ${FIELD_SELECTOR} {
                    cursor: text;
                    outline-offset: 3px;
                    transition: outline-color 120ms ease, background-color 120ms ease;
                }

                html[data-pilot-in-context-editing="true"] ${FIELD_SELECTOR}:hover {
                    background-color: rgba(240, 253, 250, 0.16);
                    outline: 2px solid rgba(20, 184, 166, 0.75);
                }

                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${BLOCK_SELECTOR},
                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${FIELD_SELECTOR} {
                    cursor: inherit !important;
                    outline-color: transparent !important;
                    box-shadow: none !important;
                }

                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${BLOCK_SELECTOR}:hover,
                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${FIELD_SELECTOR}:hover {
                    background-color: inherit !important;
                    outline-color: transparent !important;
                }

                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${BLOCK_SELECTOR}::before,
                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) ${BLOCK_SELECTOR}::after,
                html[data-pilot-in-context-panel="true"]:not([data-pilot-in-context-editing="true"]) .pilot-preview-toolbar {
                    display: none !important;
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
                dirtyFields: new Set(),
                originalFieldValues: new Map(),
                fieldVersions: new Map(),
                savingFields: new Set(),
                expandedRepeaters: new Map(),
            };

            const disablePanel = () => {
                panelEnabled = false;
                state.active = false;
                document.documentElement.removeAttribute('data-pilot-in-context-editing');
                document.documentElement.removeAttribute('data-pilot-in-context-panel');

                if (state.syncTimer) {
                    window.clearInterval(state.syncTimer);
                    state.syncTimer = null;
                }

                state.host?.remove();
                state.host = null;
                state.root = null;
                state.panel = null;
                state.body = null;
                state.status = null;
                state.activeButton = null;
            };

            const editingEnabled = () => panelEnabled && state.active && ! state.collapsed;

            const closeInlineEditors = () => {
                document.querySelectorAll(`${FIELD_SELECTOR}[contenteditable="true"]`).forEach((field) => {
                    field.removeAttribute('contenteditable');
                });
            };

            const syncEditingState = () => {
                document.documentElement.toggleAttribute('data-pilot-in-context-editing', editingEnabled());

                if (! editingEnabled()) {
                    closeInlineEditors();
                }

                updateHighlight(editingEnabled() ? state.selectedElement : null);
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

                if (! highlight || ! element || ! editingEnabled()) {
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
            const locale = () => document.documentElement.lang || DEFAULT_LOCALE || 'en';

            const localizedScalar = (value) => {
                if (! isPlainObject(value)) {
                    return value ?? '';
                }

                return value[locale()] ?? value[DEFAULT_LOCALE] ?? value.en ?? Object.values(value)[0] ?? '';
            };

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

            const createRichTextInput = (field, value) => {
                const editor = document.createElement('div');
                editor.className = 'richtext-editor';
                editor.dataset.compositeInput = 'richtext';

                const toolbar = document.createElement('div');
                toolbar.className = 'richtext-toolbar';

                const commands = [
                    ['P', 'formatBlock', 'p', 'Paragraph'],
                    ['H2', 'formatBlock', 'h2', 'Heading'],
                    ['H3', 'formatBlock', 'h3', 'Subheading'],
                    ['B', 'bold', null, 'Bold'],
                    ['I', 'italic', null, 'Italic'],
                    ['❝', 'formatBlock', 'blockquote', 'Quote'],
                    ['•', 'insertUnorderedList', null, 'Bulleted list'],
                    ['1.', 'insertOrderedList', null, 'Numbered list'],
                    ['↗', 'createLink', null, 'Link'],
                    ['×↗', 'unlink', null, 'Remove link'],
                ];

                const surface = document.createElement('div');
                surface.className = 'richtext-surface';
                surface.contentEditable = 'true';
                surface.dataset.richtextSurface = 'true';
                surface.dataset.placeholder = field.placeholder ?? '';
                surface.innerHTML = String(localizedScalar(value) ?? '');
                surface.style.minHeight = `${Math.max(Number(field.rows ?? 6) * 28, 132)}px`;

                commands.forEach(([text, command, argument, title]) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'richtext-command';
                    button.textContent = text;
                    button.title = title;
                    button.setAttribute('aria-label', title);
                    button.addEventListener('mousedown', (event) => event.preventDefault());
                    button.addEventListener('click', () => {
                        surface.focus();

                        let valueArgument = argument;

                        if (command === 'createLink') {
                            valueArgument = window.prompt('Link URL');

                            if (! valueArgument) {
                                return;
                            }
                        }

                        document.execCommand(command, false, valueArgument);
                        surface.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    toolbar.appendChild(button);
                });

                editor.append(toolbar, surface);

                return editor;
            };

            const createImageInput = (field, value) => {
                const editor = document.createElement('div');
                editor.className = 'image-editor';
                editor.dataset.compositeInput = 'image';

                const preview = document.createElement('div');
                preview.className = 'image-preview';
                const image = document.createElement('img');
                image.alt = '';
                preview.appendChild(image);

                const input = document.createElement('input');
                input.type = 'url';
                input.inputMode = 'url';
                input.placeholder = field.placeholder ?? 'Image URL';
                input.value = String(localizedScalar(value) ?? '');

                const updatePreview = () => {
                    image.src = input.value;
                    preview.dataset.visible = String(input.value.trim() !== '');
                };

                input.addEventListener('input', updatePreview);
                image.addEventListener('error', () => {
                    preview.dataset.visible = 'false';
                });
                updatePreview();
                editor.append(preview, input);

                return editor;
            };

            const createScalarInput = (field, value) => {
                if (field.type === 'richtext') {
                    return createRichTextInput(field, value);
                }

                if (field.type === 'image') {
                    return createImageInput(field, value);
                }

                const localizedValue = localizedScalar(value);
                const scalarValue = Array.isArray(localizedValue) || isPlainObject(localizedValue)
                    ? JSON.stringify(value, null, 2)
                    : (localizedValue ?? '');

                if (field.type === 'textarea') {
                    const textarea = document.createElement('textarea');
                    textarea.value = scalarValue;
                    textarea.rows = Number(field.rows ?? 4);
                    textarea.placeholder = field.placeholder ?? '';

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
                    input.setAttribute('aria-label', `${field.label ?? field.key ?? 'Field'} enabled`);
                    input.title = 'Enabled';

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

                if (['link', 'url'].includes(String(field.type ?? '').toLowerCase())
                    || /url|href|link/i.test(String(field.key ?? ''))) {
                    input.inputMode = 'url';
                    input.placeholder ||= 'Type a URL';
                }

                return input;
            };

            const controlValue = (field, input) => {
                if (input.dataset.compositeInput === 'richtext') {
                    return input.querySelector('[data-richtext-surface]')?.innerHTML ?? '';
                }

                if (input.dataset.compositeInput === 'image') {
                    return input.querySelector('input')?.value ?? '';
                }

                if (field.type === 'boolean') {
                    return input.checked;
                }

                if (field.type === 'number') {
                    return input.value === '' ? null : Number(input.value);
                }

                return input.value;
            };

            const repeaterFields = (field, items) => {
                if (Array.isArray(field.fields) && field.fields.length > 0) {
                    return field.fields;
                }

                return objectListKeys(items).map((key) => ({
                    key,
                    label: key,
                    type: key === 'body' ? 'textarea' : 'text',
                    rows: key === 'body' ? 3 : 1,
                }));
            };

            const repeaterItemCopy = (itemElement, field, index) => {
                const controls = Array.from(itemElement.querySelectorAll('[data-repeater-field]'));
                const labelControl = controls.find((control) => ['label', 'name', 'title'].includes(control.dataset.repeaterField));
                const firstControl = controls[0];
                const displayControl = labelControl ?? firstControl;
                const displayValue = displayControl
                    ? String(controlValue({ type: displayControl.dataset.fieldType }, displayControl) ?? '').replace(/<[^>]*>/g, '').trim()
                    : '';
                const summaryValue = firstControl
                    ? String(controlValue({ type: firstControl.dataset.fieldType }, firstControl) ?? '').replace(/<[^>]*>/g, '').trim()
                    : '';

                itemElement.querySelector('.repeater-title').textContent = displayValue || `${field.label ?? 'Item'} ${index + 1}`;
                itemElement.querySelector('.repeater-summary').textContent = summaryValue || 'Empty';
            };

            const setRepeaterExpanded = (container, itemElement, expanded) => {
                container.querySelectorAll('.repeater-item').forEach((item) => {
                    const isTarget = item === itemElement && expanded;
                    item.dataset.expanded = String(isTarget);
                    item.querySelector('.repeater-toggle')?.setAttribute('aria-expanded', String(isTarget));
                    const caret = item.querySelector('.repeater-caret');

                    if (caret) {
                        caret.textContent = isTarget ? '⌄' : '›';
                    }
                });

                if (expanded && itemElement) {
                    state.expandedRepeaters.set(container.dataset.expansionKey, Number(itemElement.dataset.itemIndex));
                } else {
                    state.expandedRepeaters.delete(container.dataset.expansionKey);
                }
            };

            const createRepeaterItem = (container, field, subFields, item, index, expanded = false) => {
                const itemElement = document.createElement('div');
                itemElement.className = 'repeater-item';
                itemElement.dataset.expanded = String(expanded);
                itemElement.dataset.itemIndex = String(index);
                itemElement.__pilotOriginalItem = item;

                const header = document.createElement('div');
                header.className = 'repeater-item-header';

                const grip = document.createElement('span');
                grip.className = 'repeater-grip';
                grip.textContent = '⠿';
                grip.setAttribute('aria-hidden', 'true');

                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'repeater-toggle';
                toggle.setAttribute('aria-expanded', String(expanded));

                const copy = document.createElement('span');
                copy.className = 'repeater-copy';
                const title = document.createElement('span');
                title.className = 'repeater-title';
                const summary = document.createElement('span');
                summary.className = 'repeater-summary';
                copy.append(title, summary);

                const caret = document.createElement('span');
                caret.className = 'repeater-caret';
                caret.textContent = expanded ? '⌄' : '›';
                toggle.append(copy, caret);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'repeater-remove';
                remove.textContent = '×';
                remove.title = 'Remove item';
                remove.setAttribute('aria-label', `Remove item ${index + 1}`);

                const fields = document.createElement('div');
                fields.className = 'repeater-item-fields';

                subFields.forEach((subField) => {
                    if (! subField.key) {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'repeater-subfield';
                    const heading = document.createElement('div');
                    heading.className = 'subfield-heading';
                    const label = document.createElement('span');
                    label.textContent = subField.label ?? subField.key;
                    const badge = document.createElement('code');
                    badge.textContent = subField.type ?? 'text';
                    heading.append(label, badge);

                    const control = createScalarInput(subField, item?.[subField.key] ?? subField.default ?? '');
                    control.dataset.repeaterField = subField.key;
                    control.dataset.fieldType = subField.type ?? 'text';
                    control.dataset.translatable = String(subField.translatable === true);
                    control.__pilotOriginalValue = item?.[subField.key];
                    wrapper.append(heading, control);

                    if (subField.help) {
                        const help = document.createElement('p');
                        help.className = 'field-help';
                        help.textContent = subField.help;
                        wrapper.appendChild(help);
                    }

                    fields.appendChild(wrapper);
                });

                toggle.addEventListener('click', () => {
                    setRepeaterExpanded(container, itemElement, itemElement.dataset.expanded !== 'true');
                });
                remove.addEventListener('click', () => {
                    const wasExpanded = itemElement.dataset.expanded === 'true';
                    itemElement.remove();
                    Array.from(container.querySelectorAll('.repeater-item')).forEach((remaining, itemIndex) => {
                        remaining.dataset.itemIndex = String(itemIndex);
                        repeaterItemCopy(remaining, field, itemIndex);
                    });

                    if (wasExpanded) {
                        state.expandedRepeaters.delete(container.dataset.expansionKey);
                    } else {
                        const expandedItem = container.querySelector('.repeater-item[data-expanded="true"]');

                        if (expandedItem) {
                            state.expandedRepeaters.set(container.dataset.expansionKey, Number(expandedItem.dataset.itemIndex));
                        }
                    }

                    container.dispatchEvent(new Event('input', { bubbles: true }));
                    container.dispatchEvent(new Event('change', { bubbles: true }));
                });

                itemElement.addEventListener('input', () => {
                    repeaterItemCopy(itemElement, field, Number(itemElement.dataset.itemIndex));
                });
                header.append(grip, toggle, remove);
                itemElement.append(header, fields);
                container.appendChild(itemElement);
                repeaterItemCopy(itemElement, field, index);

                return itemElement;
            };

            const createRepeaterInput = (field, value) => {
                const items = Array.isArray(value) ? value : [];
                const subFields = repeaterFields(field, items);
                const container = document.createElement('div');
                container.className = 'repeater';
                container.dataset.compositeInput = 'repeater';
                container.dataset.expansionKey = `${state.selectedBlock?.id ?? 'block'}:${field.key}`;
                container.__pilotSubFields = subFields;
                const expandedIndex = state.expandedRepeaters.get(container.dataset.expansionKey);

                items.forEach((item, index) => {
                    createRepeaterItem(
                        container,
                        field,
                        subFields,
                        isPlainObject(item) ? item : {},
                        index,
                        index === expandedIndex,
                    );
                });

                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'repeater-add';
                add.textContent = /buttons?/i.test(field.key ?? '') ? '+ Add Button' : '+ Add item';
                add.addEventListener('click', () => {
                    const item = {};

                    subFields.forEach((subField) => {
                        item[subField.key] = subField.default ?? (subField.type === 'boolean' ? false : '');
                    });

                    const itemElement = createRepeaterItem(
                        container,
                        field,
                        subFields,
                        item,
                        container.querySelectorAll('.repeater-item').length,
                        true,
                    );
                    container.insertBefore(itemElement, add);
                    setRepeaterExpanded(container, itemElement, true);
                    container.dispatchEvent(new Event('input', { bubbles: true }));
                    container.dispatchEvent(new Event('change', { bubbles: true }));
                });
                container.appendChild(add);

                return container;
            };

            const createInput = (field, value) => {
                if (field.type === 'repeater') {
                    return createRepeaterInput(field, Array.isArray(value) ? value : []);
                }

                if (isObjectList(value)) {
                    return createObjectListInput(value);
                }

                return createScalarInput(field, value);
            };

            const getInputValue = (field, input) => {
                if (input.dataset.compositeInput === 'repeater') {
                    const subFields = input.__pilotSubFields ?? [];

                    return Array.from(input.querySelectorAll(':scope > .repeater-item')).map((itemElement) => {
                        const item = {};

                        itemElement.querySelectorAll('[data-repeater-field]').forEach((control) => {
                            const subField = subFields.find((candidate) => candidate.key === control.dataset.repeaterField)
                                ?? { key: control.dataset.repeaterField, type: control.dataset.fieldType };
                            const value = controlValue(subField, control);

                            if (control.dataset.translatable === 'true') {
                                const localized = isPlainObject(control.__pilotOriginalValue)
                                    ? { ...control.__pilotOriginalValue }
                                    : {};
                                localized[locale()] = value;
                                item[subField.key] = localized;
                            } else {
                                item[subField.key] = value;
                            }
                        });

                        return item;
                    });
                }

                if (input.dataset.compositeInput === 'object-list') {
                    return Array.from(input.querySelectorAll('.object-item')).map((itemElement) => {
                        const item = {};

                        itemElement.querySelectorAll('[data-object-key]').forEach((propertyInput) => {
                            item[propertyInput.dataset.objectKey] = propertyInput.value;
                        });

                        return item;
                    });
                }

                return controlValue(field, input);
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
                state.dirtyFields.clear();
                state.originalFieldValues.clear();
                state.fieldVersions.clear();
                state.savingFields.clear();
                state.body.innerHTML = '';

                const selected = document.createElement('div');
                selected.className = 'selected';
                const selectedName = document.createElement('strong');
                selectedName.textContent = block.name;
                const selectedMeta = document.createElement('span');
                selectedMeta.className = 'muted';
                selectedMeta.textContent = `${block.type} · block #${block.id}`;
                selected.append(selectedName, selectedMeta);
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
                    const labelText = document.createElement('span');
                    labelText.textContent = field.label ?? field.key;
                    const typeLabel = document.createElement('code');
                    typeLabel.textContent = field.type ?? 'text';
                    label.append(labelText, typeLabel);

                    const input = createInput(field, fieldRawValue(field, block));
                    input.dataset.fieldKey = field.key;
                    const initialValue = getInputValue(field, input);
                    state.originalFieldValues.set(field.key, JSON.stringify(initialValue));
                    state.fieldVersions.set(field.key, 0);
                    input.addEventListener('input', () => {
                        state.dirtyFields.add(field.key);
                        state.fieldVersions.set(field.key, (state.fieldVersions.get(field.key) ?? 0) + 1);
                        setStatus('Unsaved changes');
                    });
                    input.addEventListener('change', () => saveNow(field.key));
                    input.addEventListener('blur', () => saveNow(), true);

                    wrapper.append(label, input);

                    if (field.help) {
                        const help = document.createElement('p');
                        help.className = 'field-help';
                        help.textContent = field.help;
                        wrapper.appendChild(help);
                    }

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

                if (! editingEnabled()) {
                    return;
                }

                try {
                    const block = await fetchBlock(blockId);
                    state.selectedBlock = block;
                    renderFields(block);
                    setStatus('Ready');
                } catch (error) {
                    setStatus(error.message, 'error');
                }
            };

            const saveNow = async (fieldKey = null) => {
                if (! state.selectedBlock || state.fields.size === 0) {
                    return;
                }

                clearTimeout(state.saveTimer);

                const fields = {};
                const versions = new Map();
                const keys = fieldKey
                    ? [fieldKey]
                    : Array.from(state.dirtyFields);

                for (const key of keys) {
                    const binding = state.fields.get(key);

                    if (! binding || state.savingFields.has(key)) {
                        continue;
                    }

                    const value = getInputValue(binding.field, binding.input);

                    if (JSON.stringify(value) === state.originalFieldValues.get(key)) {
                        state.dirtyFields.delete(key);
                        continue;
                    }

                    fields[key] = value;
                    versions.set(key, state.fieldVersions.get(key) ?? 0);
                }

                if (Object.keys(fields).length === 0) {
                    if (state.dirtyFields.size === 0) {
                        setStatus('Saved', 'success');
                    }

                    return;
                }

                setStatus('Saving...');
                state.saving = true;
                Object.keys(fields).forEach((key) => state.savingFields.add(key));
                const savingBlockId = state.selectedBlock.id;

                try {
                    const savedBlock = await saveFields(savingBlockId, fields);
                    const stillSelected = Number(state.selectedBlock?.id) === Number(savingBlockId);

                    if (stillSelected) {
                        state.selectedBlock = savedBlock;

                        Object.entries(fields).forEach(([key, value]) => {
                            state.originalFieldValues.set(key, JSON.stringify(value));

                            if ((state.fieldVersions.get(key) ?? 0) === versions.get(key)) {
                                state.dirtyFields.delete(key);
                            }
                        });

                        setStatus('Saved', 'success');
                    }

                    applyBlockToEditableFields(savedBlock);

                    Object.entries(fields).forEach(([key, value]) => {
                        postToEditor({
                            type: 'pilot-in-context-field-updated',
                            blockId: Number(savingBlockId),
                            fieldKey: key,
                            value,
                        });
                    });
                } catch (error) {
                    setStatus(error.message, 'error');
                } finally {
                    Object.keys(fields).forEach((key) => state.savingFields.delete(key));
                    state.saving = state.savingFields.size > 0;
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
                if (state.active) {
                    void saveNow();
                }

                state.active = ! state.active;
                state.activeButton.dataset.active = String(state.active);
                state.activeButton.textContent = state.active ? 'Edit mode' : 'Browse mode';
                syncEditingState();
            };

            const toggleCollapsed = () => {
                if (! state.collapsed) {
                    void saveNow();
                }

                state.collapsed = ! state.collapsed;
                state.panel.classList.toggle('collapsed', state.collapsed);
                state.active = ! state.collapsed;
                state.activeButton.dataset.active = String(state.active);
                state.activeButton.textContent = state.active ? 'Edit mode' : 'Browse mode';
                state.root.querySelector('[data-action="collapse"]').textContent = state.collapsed ? 'Show' : 'Hide';
                syncEditingState();
            };

            const buildPanel = () => {
                document.documentElement.setAttribute('data-pilot-in-context-panel', 'true');

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
                root.querySelector('[data-action="save"]').addEventListener('click', () => saveNow());

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
                if (! editingEnabled() || isPanelElement(event.target)) {
                    return;
                }

                const editable = blockFrom(event.target);

                if (editable) {
                    updateHighlight(editable);
                }
            });

            document.addEventListener('click', (event) => {
                if (! editingEnabled() || isPanelElement(event.target)) {
                    return;
                }

                if (event.target.closest?.('.pilot-preview-toolbar [data-pilot-action]')) {
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
                if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'e') {
                    event.preventDefault();
                    state.collapsed ? toggleCollapsed() : toggleActive();
                }
            });

            window.addEventListener('resize', () => updateHighlight(state.selectedElement));
            window.addEventListener('scroll', () => updateHighlight(state.selectedElement), true);
            window.addEventListener('message', (event) => {
                if (parentOrigin !== '*' && event.origin !== parentOrigin) {
                    return;
                }

                if (event.data?.type === 'pilot-preview-editor-mode' && event.data?.inContextPanel === false) {
                    disablePanel();
                }
            });

            installPageStyles();
            if (panelEnabled) {
                buildPanel();
                syncEditingState();
                state.syncTimer = window.setInterval(syncFromServer, 1000);
            }
        })();
    </script>
@endif

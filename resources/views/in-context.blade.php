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
                    --pilot-bg: #f8fafc;
                    --pilot-surface: #ffffff;
                    --pilot-border: #e2e8f0;
                    --pilot-border-subtle: #f1f5f9;
                    --pilot-muted: #64748b;
                    --pilot-tertiary: #94a3b8;
                    --pilot-text: #0f172a;
                    --pilot-accent: #2563eb;
                    --pilot-accent-soft: #eff6ff;
                    --pilot-danger: #e11d48;
                    color: var(--pilot-text);
                    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                    pointer-events: none;
                    position: fixed;
                    bottom: 0;
                    right: 0;
                    top: 0;
                    z-index: 2147483647;
                }

                :host * {
                    box-sizing: border-box;
                    letter-spacing: 0;
                }

                [hidden] {
                    display: none !important;
                }

                .panel {
                    background: var(--pilot-surface);
                    border-left: 1px solid var(--pilot-border);
                    box-shadow: -12px 0 36px rgba(15, 23, 42, 0.12);
                    display: flex;
                    flex-direction: column;
                    height: 100vh;
                    overflow: hidden;
                    pointer-events: auto;
                    transition: width 180ms ease, box-shadow 180ms ease;
                    width: clamp(320px, 22vw, 420px);
                }

                .panel[data-richtext-expanded="true"] {
                    width: 100vw;
                }

                .header,
                .footer,
                .breadcrumb,
                .header-actions,
                .collapsed-shell {
                    align-items: center;
                    display: flex;
                }

                .header {
                    border-bottom: 1px solid var(--pilot-border);
                    flex: none;
                    justify-content: space-between;
                    min-height: 48px;
                    padding: 8px 12px 8px 16px;
                }

                .footer {
                    border-top: 1px solid var(--pilot-border);
                    flex: none;
                    gap: 10px;
                    justify-content: space-between;
                    min-height: 48px;
                    padding: 8px 12px 8px 16px;
                }

                .breadcrumb {
                    gap: 6px;
                    min-width: 0;
                }

                .crumb {
                    align-items: center;
                    color: var(--pilot-text);
                    display: flex;
                    flex: none;
                    font-size: 13px;
                    font-weight: 650;
                    gap: 6px;
                    min-width: 0;
                }

                .crumb.block-crumb {
                    flex: 1;
                    font-weight: 700;
                }

                .crumb-label {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .crumb-icon {
                    align-items: center;
                    background: var(--pilot-accent-soft);
                    border: 1px solid #dbeafe;
                    border-radius: 5px;
                    color: var(--pilot-accent);
                    display: inline-flex;
                    flex: none;
                    font-size: 11px;
                    font-weight: 750;
                    height: 24px;
                    justify-content: center;
                    width: 24px;
                }

                .breadcrumb-divider {
                    color: #cbd5e1;
                    flex: none;
                    font-size: 16px;
                }

                .header-actions {
                    flex: none;
                    gap: 2px;
                }

                .tabs {
                    align-items: stretch;
                    background: rgba(248, 250, 252, 0.72);
                    border-bottom: 1px solid var(--pilot-border);
                    display: flex;
                    flex: none;
                    height: 42px;
                }

                .tab {
                    align-items: center;
                    border-bottom: 2px solid var(--pilot-accent);
                    color: var(--pilot-text);
                    display: flex;
                    font-size: 12px;
                    font-weight: 650;
                    justify-content: center;
                    width: 100%;
                }

                .icon-button {
                    align-items: center;
                    background: transparent;
                    border: 0;
                    border-radius: 6px;
                    color: var(--pilot-tertiary);
                    display: inline-flex;
                    height: 28px;
                    justify-content: center;
                    min-height: 28px;
                    min-width: 28px;
                    padding: 0;
                    width: 28px;
                }

                .icon-button:hover {
                    background: #f1f5f9;
                    border-color: transparent;
                    color: #475569;
                }

                .icon-button[data-active="true"] {
                    background: var(--pilot-accent-soft);
                    color: var(--pilot-accent);
                }

                .icon-button svg {
                    fill: none;
                    height: 16px;
                    pointer-events: none;
                    stroke: currentColor;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                    stroke-width: 1.8;
                    width: 16px;
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

                button.icon-button {
                    background: transparent;
                    border: 0;
                    border-radius: 6px;
                    color: var(--pilot-tertiary);
                    min-height: 28px;
                    padding: 0;
                }

                button.icon-button:hover {
                    background: #f1f5f9;
                    border-color: transparent;
                    color: #475569;
                }

                button.icon-button[data-active="true"] {
                    background: var(--pilot-accent-soft);
                    border-color: transparent;
                    color: var(--pilot-accent);
                }

                .body {
                    display: flex;
                    flex-direction: column;
                    flex: 1;
                    gap: 28px;
                    min-height: 180px;
                    overflow: auto;
                    padding: 20px;
                }

                .empty {
                    border: 1px dashed var(--pilot-border);
                    border-radius: 8px;
                    color: var(--pilot-tertiary);
                    font-size: 13px;
                    line-height: 1.45;
                    padding: 16px;
                    text-align: center;
                }

                .fields {
                    display: flex;
                    flex-direction: column;
                    gap: 28px;
                }

                .field {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
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
                    border-radius: 9px;
                    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                    overflow: hidden;
                }

                .object-item-header {
                    color: var(--pilot-tertiary);
                    font-size: 10px;
                    font-weight: 700;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
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
                    font-size: 10px;
                    font-weight: 700;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
                }

                .repeater {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .repeater-item[data-expanded="true"] {
                    border-color: #93c5fd;
                }

                .repeater-item:first-child::before {
                    background: var(--pilot-accent);
                    bottom: 0;
                    content: '';
                    left: 0;
                    position: absolute;
                    top: 0;
                    width: 3px;
                }

                .repeater-item {
                    position: relative;
                }

                .repeater-item-header {
                    align-items: center;
                    display: flex;
                    gap: 8px;
                    min-height: 52px;
                    padding: 10px 10px 10px 12px;
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
                    background: rgba(248, 250, 252, 0.72);
                    border-top: 1px solid var(--pilot-border-subtle);
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
                    border: 0;
                    color: var(--pilot-accent);
                    font-size: 10px;
                    font-weight: 700;
                    padding-inline: 0;
                }

                .richtext-editor {
                    border: 1px solid var(--pilot-border);
                    border-radius: 12px;
                    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                    overflow: hidden;
                }

                .richtext-editor:focus-within {
                    border-color: var(--pilot-accent);
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
                }

                .richtext-toolbar {
                    align-items: center;
                    background: #f8fafc;
                    border-bottom: 1px solid var(--pilot-border);
                    display: flex;
                    flex-wrap: wrap;
                    gap: 4px;
                    min-height: 48px;
                    overflow-x: auto;
                    padding: 7px 9px;
                }

                button.richtext-command {
                    background: transparent;
                    border: 0;
                    border-radius: 8px;
                    color: var(--pilot-muted);
                    font-size: 11px;
                    font-weight: 650;
                    min-height: 32px;
                    min-width: 32px;
                    padding: 6px;
                }

                button.richtext-command:hover {
                    background: #ffffff;
                    color: var(--pilot-text);
                }

                button.richtext-command[aria-pressed="true"] {
                    background: #ffffff;
                    box-shadow: inset 0 0 0 1px var(--pilot-border);
                    color: var(--pilot-text);
                }

                .richtext-surface {
                    color: var(--pilot-text);
                    font-size: 14px;
                    line-height: 1.7;
                    min-height: 132px;
                    outline: none;
                    padding: 14px;
                }

                .richtext-source {
                    background: #ffffff;
                    border: 0;
                    border-radius: 0;
                    box-shadow: none;
                    display: none;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    font-size: 12px;
                    line-height: 1.6;
                    min-height: 180px;
                    padding: 14px;
                    resize: vertical;
                }

                .richtext-editor[data-source="true"] .richtext-surface {
                    display: none;
                }

                .richtext-editor[data-source="true"] .richtext-source {
                    display: block;
                }

                .richtext-editor[data-expanded="true"] {
                    border-radius: 0;
                    bottom: 0;
                    display: flex;
                    flex-direction: column;
                    left: 0;
                    position: fixed;
                    right: 0;
                    top: 0;
                    z-index: 10;
                }

                .richtext-editor[data-expanded="true"] .richtext-surface,
                .richtext-editor[data-expanded="true"] .richtext-source {
                    flex: 1;
                    font-size: 16px;
                    line-height: 1.75;
                    min-height: 0 !important;
                    overflow-y: auto;
                    padding: clamp(28px, 5vw, 64px);
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
                    border-radius: 9px;
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
                    font-size: 11px;
                    font-weight: 700;
                    gap: 8px;
                    justify-content: space-between;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
                }

                .field code {
                    background: #f1f5f9;
                    border-radius: 4px;
                    color: var(--pilot-tertiary);
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                    font-size: 10px;
                    font-weight: 500;
                    letter-spacing: 0;
                    padding: 2px 6px;
                    text-transform: none;
                }

                input,
                textarea,
                select {
                    background: #ffffff;
                    border: 1px solid var(--pilot-border);
                    border-radius: 8px;
                    color: var(--pilot-text);
                    font: inherit;
                    font-size: 13px;
                    line-height: 1.4;
                    outline: none;
                    padding: 10px;
                    transition: border-color 120ms ease, box-shadow 120ms ease, background-color 120ms ease;
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
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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
                    color: #16a34a;
                }

                .status[data-kind="error"] {
                    color: var(--pilot-danger);
                }

                .collapsed {
                    box-shadow: none;
                    width: 44px;
                }

                .collapsed .tabs,
                .collapsed .body,
                .collapsed .footer,
                .collapsed > .header {
                    display: none;
                }

                .collapsed-shell {
                    display: none;
                }

                .collapsed .collapsed-shell {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    height: 100%;
                    padding: 8px;
                }

                .collapsed-label {
                    color: var(--pilot-tertiary);
                    font-size: 10px;
                    font-weight: 650;
                    letter-spacing: 0.08em;
                    margin-top: 4px;
                    text-transform: uppercase;
                    transform: rotate(180deg);
                    writing-mode: vertical-rl;
                }

                .status {
                    color: var(--pilot-muted);
                    font-size: 11px;
                }

                button.save-button {
                    background: var(--pilot-text);
                    border-color: var(--pilot-text);
                    color: #ffffff;
                    font-weight: 650;
                }

                button.save-button:hover {
                    background: #1e293b;
                    border-color: #1e293b;
                }

                @media (max-width: 640px) {
                    :host {
                        bottom: 0;
                        left: 0;
                        right: 0;
                        top: auto;
                    }

                    .panel {
                        border-left: 0;
                        border-radius: 14px 14px 0 0;
                        border-top: 1px solid var(--pilot-border);
                        height: min(72vh, 680px);
                        width: 100%;
                    }

                    .panel.collapsed {
                        border-radius: 10px 0 0 0;
                        height: 44px;
                        margin-left: auto;
                        width: 44px;
                    }

                    .collapsed .collapsed-shell {
                        flex-direction: row;
                        padding: 8px;
                    }

                    .collapsed-label {
                        display: none;
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

                return panelActiveElement?.isContentEditable
                    || ['INPUT', 'TEXTAREA', 'SELECT'].includes(panelActiveElement?.tagName ?? '');
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
                    ['U', 'underline', null, 'Underline'],
                    ['❝', 'formatBlock', 'blockquote', 'Quote'],
                    ['•', 'insertUnorderedList', null, 'Bulleted list'],
                    ['1.', 'insertOrderedList', null, 'Numbered list'],
                    ['↗', 'createLink', null, 'Link'],
                    ['×↗', 'unlink', null, 'Remove link'],
                    ['≡', 'justifyLeft', null, 'Align left'],
                    ['≣', 'justifyCenter', null, 'Align center'],
                    ['≡', 'justifyRight', null, 'Align right'],
                ];

                const surface = document.createElement('div');
                surface.className = 'richtext-surface';
                surface.contentEditable = 'true';
                surface.dataset.richtextSurface = 'true';
                surface.dataset.placeholder = field.placeholder ?? '';
                surface.innerHTML = String(localizedScalar(value) ?? '');
                surface.style.minHeight = `${Math.max(Number(field.rows ?? 6) * 28, 132)}px`;

                const source = document.createElement('textarea');
                source.className = 'richtext-source';
                source.value = surface.innerHTML;
                source.spellcheck = false;

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

                const sourceButton = document.createElement('button');
                sourceButton.type = 'button';
                sourceButton.className = 'richtext-command';
                sourceButton.textContent = '</>';
                sourceButton.title = 'Edit HTML source';
                sourceButton.setAttribute('aria-label', sourceButton.title);
                sourceButton.setAttribute('aria-pressed', 'false');
                sourceButton.addEventListener('click', () => {
                    const sourceMode = editor.dataset.source !== 'true';

                    if (sourceMode) {
                        source.value = surface.innerHTML;
                    } else {
                        surface.innerHTML = source.value;
                    }

                    editor.dataset.source = String(sourceMode);
                    sourceButton.setAttribute('aria-pressed', String(sourceMode));
                    (sourceMode ? source : surface).focus();
                });

                const expandButton = document.createElement('button');
                expandButton.type = 'button';
                expandButton.className = 'richtext-command';
                expandButton.textContent = '⛶';
                expandButton.title = 'Expand editor';
                expandButton.setAttribute('aria-label', expandButton.title);
                expandButton.setAttribute('aria-pressed', 'false');
                expandButton.addEventListener('click', () => {
                    const expanded = editor.dataset.expanded !== 'true';
                    editor.dataset.expanded = String(expanded);
                    state.panel.dataset.richtextExpanded = String(expanded);
                    expandButton.title = expanded ? 'Collapse editor' : 'Expand editor';
                    expandButton.setAttribute('aria-label', expandButton.title);
                    expandButton.setAttribute('aria-pressed', String(expanded));
                });

                source.addEventListener('input', () => {
                    surface.innerHTML = source.value;
                    surface.dispatchEvent(new Event('input', { bubbles: true }));
                });
                surface.addEventListener('input', () => {
                    if (editor.dataset.source !== 'true') {
                        source.value = surface.innerHTML;
                    }
                });

                toolbar.append(sourceButton, expandButton);

                editor.append(toolbar, surface, source);

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

                const blockCrumb = state.root?.querySelector('[data-block-crumb]');
                const divider = state.root?.querySelector('[data-breadcrumb-divider]');

                if (blockCrumb) {
                    blockCrumb.hidden = true;
                }

                if (divider) {
                    divider.hidden = true;
                }

                const empty = document.createElement('div');
                empty.className = 'empty';
                empty.textContent = 'Select a highlighted block on the page to edit its content.';
                state.body.appendChild(empty);
            };

            const renderFields = (block) => {
                state.fields.clear();
                state.dirtyFields.clear();
                state.originalFieldValues.clear();
                state.fieldVersions.clear();
                state.savingFields.clear();
                state.body.innerHTML = '';

                const blockCrumb = state.root?.querySelector('[data-block-crumb]');
                const blockLabel = state.root?.querySelector('[data-block-label]');
                const blockInitial = state.root?.querySelector('[data-block-initial]');
                const pageLabel = state.root?.querySelector('[data-page-label]');
                const divider = state.root?.querySelector('[data-breadcrumb-divider]');

                if (blockCrumb) {
                    blockCrumb.hidden = false;
                }

                if (divider) {
                    divider.hidden = false;
                }

                if (blockLabel) {
                    blockLabel.textContent = block.name || block.type || 'Block';
                }

                if (blockInitial) {
                    blockInitial.textContent = String(block.name || block.type || 'B').trim().charAt(0).toUpperCase() || 'B';
                }

                if (pageLabel && block.content?.name) {
                    pageLabel.textContent = block.content.name;
                }

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
                        window.clearTimeout(state.saveTimer);
                        state.saveTimer = window.setTimeout(() => saveNow(), 700);
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
                let savedSuccessfully = false;

                try {
                    const savedBlock = await saveFields(savingBlockId, fields);
                    savedSuccessfully = true;
                    const stillSelected = Number(state.selectedBlock?.id) === Number(savingBlockId);

                    if (stillSelected) {
                        state.selectedBlock = savedBlock;

                        Object.entries(fields).forEach(([key, value]) => {
                            state.originalFieldValues.set(key, JSON.stringify(value));

                            if ((state.fieldVersions.get(key) ?? 0) === versions.get(key)) {
                                state.dirtyFields.delete(key);
                            }
                        });

                        setStatus(state.dirtyFields.size === 0 ? 'Saved' : 'Unsaved changes', state.dirtyFields.size === 0 ? 'success' : '');
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

                    if (savedSuccessfully && ! state.saving && state.dirtyFields.size > 0) {
                        window.clearTimeout(state.saveTimer);
                        state.saveTimer = window.setTimeout(() => saveNow(), 150);
                    }
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
                state.activeButton.title = state.active ? 'Switch to browse mode' : 'Switch to edit mode';
                state.activeButton.setAttribute('aria-label', state.activeButton.title);
                state.activeButton.setAttribute('aria-pressed', String(state.active));
                syncEditingState();
            };

            const toggleCollapsed = () => {
                if (! state.collapsed) {
                    void saveNow();
                }

                state.root.querySelectorAll('.richtext-editor[data-expanded="true"]').forEach((editor) => {
                    editor.dataset.expanded = 'false';
                });
                state.panel.dataset.richtextExpanded = 'false';

                state.collapsed = ! state.collapsed;
                state.panel.classList.toggle('collapsed', state.collapsed);
                state.active = ! state.collapsed;
                state.activeButton.dataset.active = String(state.active);
                state.activeButton.title = state.active ? 'Switch to browse mode' : 'Switch to edit mode';
                state.activeButton.setAttribute('aria-label', state.activeButton.title);
                state.root.querySelector('[data-action="collapse"]').setAttribute('aria-expanded', String(! state.collapsed));
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
                        <div class="breadcrumb">
                            <div class="crumb">
                                <span class="crumb-icon">P</span>
                                <span class="crumb-label" data-page-label>Page</span>
                            </div>
                            <span class="breadcrumb-divider" data-breadcrumb-divider hidden>›</span>
                            <div class="crumb block-crumb" data-block-crumb hidden>
                                <span class="crumb-icon" data-block-initial>B</span>
                                <span class="crumb-label" data-block-label>Block</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button type="button" class="icon-button" data-action="active" data-active="true" title="Switch to browse mode" aria-label="Switch to browse mode" aria-pressed="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>
                            <button type="button" class="icon-button" data-action="collapse" title="Collapse inspector" aria-label="Collapse inspector" aria-expanded="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m10 9-3 3 3 3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="tabs" role="tablist" aria-label="Inspector sections">
                        <div class="tab" role="tab" aria-selected="true">Content</div>
                    </div>
                    <div class="body"></div>
                    <div class="footer">
                        <span class="status">Ready</span>
                        <button type="button" class="save-button" data-action="save">Save</button>
                    </div>
                    <div class="collapsed-shell">
                        <button type="button" class="icon-button" data-action="expand" title="Expand inspector" aria-label="Expand inspector">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/></svg>
                        </button>
                        <span class="collapsed-label">Inspector</span>
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
                root.querySelector('[data-action="expand"]').addEventListener('click', toggleCollapsed);
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
                if (event.key === 'Escape' && state.panel?.dataset.richtextExpanded === 'true') {
                    state.root.querySelectorAll('.richtext-editor[data-expanded="true"]').forEach((editor) => {
                        editor.dataset.expanded = 'false';
                        const button = Array.from(editor.querySelectorAll('.richtext-command'))
                            .find((candidate) => candidate.getAttribute('aria-label')?.includes('Collapse editor'));

                        if (button) {
                            button.title = 'Expand editor';
                            button.setAttribute('aria-label', button.title);
                            button.setAttribute('aria-pressed', 'false');
                        }
                    });
                    state.panel.dataset.richtextExpanded = 'false';

                    return;
                }

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

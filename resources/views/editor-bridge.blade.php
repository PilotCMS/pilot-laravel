@if(config('pilot.editor_bridge.enabled', true))
    <style>
        [data-pilot-editable="block"] {
            position: relative;
            outline: 1px solid transparent;
            outline-offset: 3px;
            cursor: pointer;
            transition: outline-color 120ms ease, box-shadow 120ms ease, background-color 120ms ease;
        }

        [data-pilot-editable="block"]:hover {
            outline-color: #2dd4bf;
            background-color: rgba(240, 253, 250, 0.45);
        }

        [data-pilot-editable="block"][data-pilot-selected="true"] {
            position: relative;
            outline: 2px solid #14b8a6;
            border-color: #00b3b0 !important;
            box-shadow: 0 0 0 6px rgba(20, 184, 166, 0.12);
        }

        [data-pilot-editable="block"]::before {
            content: attr(data-pilot-component);
            position: absolute;
            top: -28px;
            left: 0;
            z-index: 2147483646;
            display: none;
            border-radius: 6px;
            background: #0f172a;
            padding: 4px 8px;
            color: white;
            font: 700 11px/1 ui-sans-serif, system-ui, sans-serif;
            pointer-events: none;
        }

        [data-pilot-editable="block"]:hover::before,
        [data-pilot-editable="block"][data-pilot-selected="true"]::before {
            display: block;
        }

        [data-pilot-editable="block"][data-pilot-selected="true"]::after {
            content: '';
            position: absolute;
            inset: -2px;
            border: 2px solid #00b3b0;
            border-radius: inherit;
            pointer-events: none;
            z-index: 50;
        }

        .pilot-preview-toolbar {
            position: absolute;
            top: -34px;
            right: 0;
            z-index: 2147483647;
            display: none;
            align-items: center;
            gap: 2px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            padding: 2px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        }

        [data-pilot-editable="block"]:hover > .pilot-preview-toolbar,
        [data-pilot-editable="block"][data-pilot-selected="true"] > .pilot-preview-toolbar {
            display: flex;
        }

        .pilot-preview-toolbar button {
            display: flex;
            height: 26px;
            min-width: 26px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #475569;
            font: 700 12px/1 ui-sans-serif, system-ui, sans-serif;
            cursor: pointer;
        }

        .pilot-preview-toolbar button:hover {
            background: #f0fdfa;
            color: #0d9488;
        }
    </style>
    <script>
        (() => {
            const liveRootSelector = '{{ config('pilot.editor_bridge.live_root', '[data-pilot-live-root]') }}';
            const endpoint = @json(\Illuminate\Support\Facades\Route::has('api.preview.render') ? route('api.preview.render') : null);
            const parentOrigin = (() => {
                try {
                    return document.referrer ? new URL(document.referrer).origin : '*';
                } catch (error) {
                    return '*';
                }
            })();

            window.PilotCms = window.PilotCms || {};
            window.PilotCms.livePreview = {
                async render(payload, options = {}) {
                    if (! endpoint) {
                        throw new Error('Pilot live preview endpoint is not available.');
                    }

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            ...payload,
                            locale: options.locale || document.documentElement.lang || '{{ app()->getLocale() }}',
                        }),
                    });

                    if (! response.ok) {
                        throw new Error(`Pilot live preview failed with ${response.status}`);
                    }

                    const result = await response.json();
                    const liveRoot = document.querySelector(options.liveRootSelector || liveRootSelector);

                    if (liveRoot && result.html) {
                        liveRoot.innerHTML = result.html;
                    }

                    return result;
                },
            };

            const shouldDisablePreviewLink = (event, link) => {
                if (! link || window.parent === window || event.defaultPrevented) {
                    return false;
                }

                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                    return false;
                }

                const target = link.getAttribute('target');

                return ! target || target === '_self';
            };

            const disablePreviewLinkNavigation = (event) => {
                const link = event.target.closest('a[href]');

                if (! shouldDisablePreviewLink(event, link)) {
                    return false;
                }

                event.preventDefault();

                return true;
            };

            document.addEventListener('click', disablePreviewLinkNavigation, true);

            const ensurePreviewToolbar = (editable) => {
                if (editable.querySelector(':scope > .pilot-preview-toolbar')) {
                    return;
                }

                const toolbar = document.createElement('div');
                toolbar.className = 'pilot-preview-toolbar';
                toolbar.setAttribute('aria-hidden', 'true');
                toolbar.innerHTML = `
                    <button type="button" data-pilot-action="move-up" title="Move up">↑</button>
                    <button type="button" data-pilot-action="move-down" title="Move down">↓</button>
                    <button type="button" data-pilot-action="duplicate" title="Duplicate">⧉</button>
                    <button type="button" data-pilot-action="delete" title="Delete">×</button>
                `;

                editable.prepend(toolbar);
            };

            const preparePreviewToolbars = () => {
                document.querySelectorAll('[data-pilot-editable="block"]').forEach(ensurePreviewToolbar);
            };

            const syncSelectedBlock = (blockId, shouldScroll = false) => {
                document.querySelectorAll('[data-pilot-selected="true"]').forEach((element) => {
                    element.removeAttribute('data-pilot-selected');
                });

                if (! blockId) {
                    return;
                }

                document.querySelectorAll('[data-pilot-editable="block"]').forEach((element) => {
                    if (element.dataset.pilotBlockId === String(blockId)) {
                        element.setAttribute('data-pilot-selected', 'true');

                        if (shouldScroll) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            };

            preparePreviewToolbars();

            window.addEventListener('message', (event) => {
                if (parentOrigin !== '*' && event.origin !== parentOrigin) {
                    return;
                }

                if (event.data?.type !== 'pilot-preview-sync-selected-block') {
                    return;
                }

                syncSelectedBlock(event.data.blockId, true);
            });

            document.addEventListener('click', (event) => {
                const toolbarButton = event.target.closest('[data-pilot-action]');
                const editable = event.target.closest('[data-pilot-editable="block"]');

                if (! editable || window.parent === window) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                syncSelectedBlock(editable.dataset.pilotBlockId);

                window.parent.postMessage({
                    type: toolbarButton ? 'pilot-preview-block-action' : 'pilot-preview-select-block',
                    action: toolbarButton?.dataset.pilotAction,
                    blockId: Number(editable.dataset.pilotBlockId),
                    component: editable.dataset.pilotComponent,
                    componentPath: editable.dataset.pilotComponentPath,
                }, parentOrigin);
            });
        })();
    </script>
@endif

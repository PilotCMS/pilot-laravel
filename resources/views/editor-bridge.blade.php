@if(config('pilot.editor_bridge.enabled', true))
    <style>
        [data-pilot-editable="block"][data-pilot-selected="true"] {
            position: relative;
            border-color: #00b3b0 !important;
            box-shadow: 0 0 0 2px rgba(0, 179, 176, 0.18);
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

            const syncSelectedBlock = (blockId) => {
                document.querySelectorAll('[data-pilot-selected="true"]').forEach((element) => {
                    element.removeAttribute('data-pilot-selected');
                });

                if (! blockId) {
                    return;
                }

                document.querySelectorAll('[data-pilot-editable="block"]').forEach((element) => {
                    if (element.dataset.pilotBlockId === String(blockId)) {
                        element.setAttribute('data-pilot-selected', 'true');
                    }
                });
            };

            window.addEventListener('message', (event) => {
                if (parentOrigin !== '*' && event.origin !== parentOrigin) {
                    return;
                }

                if (event.data?.type !== 'pilot-preview-sync-selected-block') {
                    return;
                }

                syncSelectedBlock(event.data.blockId);
            });

            document.addEventListener('click', (event) => {
                const editable = event.target.closest('[data-pilot-editable="block"]');

                if (! editable || window.parent === window) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                syncSelectedBlock(editable.dataset.pilotBlockId);

                window.parent.postMessage({
                    type: 'pilot-preview-select-block',
                    blockId: Number(editable.dataset.pilotBlockId),
                    component: editable.dataset.pilotComponent,
                    componentPath: editable.dataset.pilotComponentPath,
                }, parentOrigin);
            });
        })();
    </script>
@endif

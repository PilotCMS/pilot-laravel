@if(config('pilot.editor_bridge.enabled', true))
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

            document.addEventListener('click', (event) => {
                const editable = event.target.closest('[data-pilot-editable="block"]');

                if (! editable || window.parent === window) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

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

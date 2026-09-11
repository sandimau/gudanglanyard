@once
    <style>
        .order-detail-image-thumb,
        .projectmp-detail-image-thumb {
            cursor: zoom-in;
        }

        .image-zoom-preview {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .image-zoom-preview.d-none {
            display: none !important;
        }

        .image-zoom-preview-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
        }

        .image-zoom-preview-panel {
            position: relative;
            z-index: 1;
            width: min(100%, 960px);
            max-height: calc(100% - 2rem);
            background: #fff;
            border-radius: 0.75rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.28);
        }

        .image-zoom-preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .image-zoom-preview-viewport {
            overflow: auto;
            max-height: 70vh;
            padding: 1rem;
            text-align: center;
            background: #f8f9fa;
            cursor: zoom-in;
        }

        .image-zoom-preview-viewport.is-zoomed {
            cursor: grab;
        }

        .image-zoom-preview-viewport.is-dragging {
            cursor: grabbing;
        }

        .image-zoom-preview-img {
            display: inline-block;
            max-width: 100%;
            max-height: 70vh;
            width: auto;
            height: auto;
            user-select: none;
            -webkit-user-drag: none;
        }

        .image-zoom-preview-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
    </style>
    <script>
        (function() {
            if (window.openImageZoomPreview) {
                return;
            }

            const minScale = 0.5;
            const maxScale = 4;
            const step = 0.25;

            let overlay = null;
            let viewport = null;
            let img = null;
            let levelBtn = null;
            let editBtn = null;
            let scale = 1;
            let baseWidth = 0;
            let dragActive = false;
            let dragStartX = 0;
            let dragStartY = 0;
            let scrollStartX = 0;
            let scrollStartY = 0;

            function ensureOverlay() {
                if (overlay) {
                    return overlay;
                }

                overlay = document.createElement('div');
                overlay.id = 'imageZoomPreview';
                overlay.className = 'image-zoom-preview d-none';
                overlay.setAttribute('aria-hidden', 'true');
                overlay.innerHTML =
                    '<div class="image-zoom-preview-backdrop" data-image-zoom-close></div>' +
                    '<div class="image-zoom-preview-panel">' +
                    '  <div class="image-zoom-preview-toolbar">' +
                    '    <div class="btn-group btn-group-sm" role="group" aria-label="Kontrol zoom">' +
                    '      <button type="button" class="btn btn-outline-secondary" data-image-zoom-out title="Perkecil"><i class="bx bx-zoom-out"></i></button>' +
                    '      <button type="button" class="btn btn-outline-secondary px-3" data-image-zoom-level disabled>100%</button>' +
                    '      <button type="button" class="btn btn-outline-secondary" data-image-zoom-in title="Perbesar"><i class="bx bx-zoom-in"></i></button>' +
                    '      <button type="button" class="btn btn-outline-secondary" data-image-zoom-reset title="Reset zoom">Reset</button>' +
                    '    </div>' +
                    '    <small class="text-muted">Scroll mouse atau klik gambar untuk zoom</small>' +
                    '  </div>' +
                    '  <div class="image-zoom-preview-viewport" data-image-zoom-viewport>' +
                    '    <img class="image-zoom-preview-img" data-image-zoom-img alt="Gambar produk">' +
                    '  </div>' +
                    '  <div class="image-zoom-preview-footer">' +
                    '    <a href="#" class="btn btn-primary d-none" data-image-zoom-edit>Edit Gambar</a>' +
                    '    <button type="button" class="btn btn-secondary" data-image-zoom-close>Tutup</button>' +
                    '  </div>' +
                    '</div>';

                document.body.appendChild(overlay);

                viewport = overlay.querySelector('[data-image-zoom-viewport]');
                img = overlay.querySelector('[data-image-zoom-img]');
                levelBtn = overlay.querySelector('[data-image-zoom-level]');
                editBtn = overlay.querySelector('[data-image-zoom-edit]');

                overlay.querySelector('[data-image-zoom-in]').addEventListener('click', zoomIn);
                overlay.querySelector('[data-image-zoom-out]').addEventListener('click', zoomOut);
                overlay.querySelector('[data-image-zoom-reset]').addEventListener('click', resetZoom);

                overlay.addEventListener('click', function(e) {
                    if (e.target.closest('[data-image-zoom-close]')) {
                        e.preventDefault();
                        closePreview();
                    }
                });

                viewport.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        zoomIn();
                    } else {
                        zoomOut();
                    }
                }, { passive: false });

                img.addEventListener('click', function() {
                    if (scale < 2) {
                        setScale(2);
                    } else {
                        resetZoom();
                    }
                });

                img.addEventListener('load', function() {
                    baseWidth = 0;
                    captureBaseWidth();
                    applyZoom();
                });

                viewport.addEventListener('mousedown', function(e) {
                    if (scale <= 1 || e.button !== 0) return;
                    dragActive = true;
                    dragStartX = e.clientX;
                    dragStartY = e.clientY;
                    scrollStartX = viewport.scrollLeft;
                    scrollStartY = viewport.scrollTop;
                    viewport.classList.add('is-dragging');
                });

                window.addEventListener('mousemove', function(e) {
                    if (!dragActive) return;
                    viewport.scrollLeft = scrollStartX - (e.clientX - dragStartX);
                    viewport.scrollTop = scrollStartY - (e.clientY - dragStartY);
                });

                window.addEventListener('mouseup', function() {
                    dragActive = false;
                    if (viewport) {
                        viewport.classList.remove('is-dragging');
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (overlay.classList.contains('d-none')) return;
                    if (e.key === 'Escape') {
                        closePreview();
                    }
                });

                return overlay;
            }

            function updateLevelLabel() {
                if (levelBtn) {
                    levelBtn.textContent = Math.round(scale * 100) + '%';
                }
            }

            function captureBaseWidth() {
                img.style.width = '';
                img.style.maxHeight = '70vh';
                baseWidth = img.offsetWidth || img.naturalWidth || 0;
            }

            function applyZoom() {
                if (!baseWidth) {
                    captureBaseWidth();
                }
                if (!baseWidth) return;

                img.style.maxHeight = 'none';
                img.style.width = Math.round(baseWidth * scale) + 'px';
                viewport.classList.toggle('is-zoomed', scale > 1);
                updateLevelLabel();
            }

            function setScale(nextScale) {
                scale = Math.min(maxScale, Math.max(minScale, nextScale));
                applyZoom();
            }

            function zoomIn() {
                setScale(scale + step);
            }

            function zoomOut() {
                setScale(scale - step);
            }

            function resetZoom() {
                scale = 1;
                applyZoom();
            }

            function closePreview() {
                if (!overlay) return;
                overlay.classList.add('d-none');
                overlay.setAttribute('aria-hidden', 'true');
                img.removeAttribute('src');
                if (editBtn) {
                    editBtn.classList.add('d-none');
                    editBtn.setAttribute('href', '#');
                }
                scale = 1;
                baseWidth = 0;
            }

            function openPreview(imageSrc, editUrl) {
                if (!imageSrc) return;

                ensureOverlay();
                scale = 1;
                baseWidth = 0;
                img.style.width = '';
                img.style.maxHeight = '70vh';
                img.src = imageSrc;

                if (editBtn) {
                    if (editUrl) {
                        editBtn.href = editUrl;
                        editBtn.classList.remove('d-none');
                    } else {
                        editBtn.classList.add('d-none');
                        editBtn.setAttribute('href', '#');
                    }
                }

                overlay.classList.remove('d-none');
                overlay.setAttribute('aria-hidden', 'false');
                updateLevelLabel();
            }

            window.openImageZoomPreview = openPreview;
            window.closeImageZoomPreview = closePreview;

            document.addEventListener('click', function(e) {
                const thumb = e.target.closest('.order-detail-image-thumb, .projectmp-detail-image-thumb');
                if (!thumb) return;

                const imageSrc = thumb.getAttribute('data-image-src');
                if (!imageSrc) return;

                e.preventDefault();
                openPreview(imageSrc, thumb.getAttribute('data-edit-url') || '');
            });
        })();
    </script>
@endonce

(function() {
    const modalEl = document.getElementById('detailOrderModal');
    if (!modalEl) return;

    const modalBody = document.getElementById('detailOrderBody');
    const modalTitle = document.getElementById('detailOrderModalLabel');
    const backBtn = document.getElementById('detailOrderModalBack');
    const imagePreview = document.getElementById('detailOrderImagePreview');
    const imagePreviewImg = imagePreview?.querySelector('.detail-order-image-preview-img');
    const imagePreviewEdit = imagePreview?.querySelector('.detail-order-image-preview-edit');
    const bsModal = new bootstrap.Modal(modalEl);
    let modalWasOpened = false;
    let modalHistory = [];
    let historyIndex = -1;
    let activeLoadRequest = 0;

    const spinner = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;

    function parseHtml(html) {
        return new DOMParser().parseFromString(html, 'text/html');
    }

    function extractContent(doc) {
        const content = doc.querySelector('.body .container-fluid .mb-4') ||
            doc.querySelector('.body .container-fluid') ||
            doc.querySelector('.body');

        return content ? content.innerHTML : '';
    }

    function extractTitle(doc) {
        const breadcrumb = doc.querySelector('.breadcrumb');
        if (breadcrumb) {
            const labels = Array.from(breadcrumb.querySelectorAll('.breadcrumb-item'))
                .map(function(item) {
                    return item.textContent.replace(/\s+/g, ' ').trim();
                })
                .filter(Boolean);

            if (labels.length) {
                return labels.join(' › ');
            }
        }

        const cardTitle = doc.querySelector('.card-title');
        if (cardTitle) {
            return cardTitle.textContent.replace(/\s+/g, ' ').trim();
        }

        const title = doc.querySelector('title');
        if (title) {
            const titleText = title.textContent.trim();
            const parts = titleText.split('|');
            return parts[0].trim() || 'Detail Order';
        }

        return 'Detail Order';
    }

    function shouldSkipModalScript(code) {
        if (!code) return true;

        return /new Autocomplete\s*\(\s*['"]#autocomplete/i.test(code) ||
            /getElementById\s*\(\s*['"]closeBrg/i.test(code) ||
            /initOrderDetailProdukAutocomplete/.test(code) ||
            /function clearProduk\s*\(/.test(code) ||
            /\$\(['"]#print['"]\)/.test(code) ||
            /printArea\s*\(/.test(code);
    }

    function injectPageAssets(doc) {
        document.querySelectorAll('style[data-modal-asset]').forEach(function(el) {
            el.remove();
        });

        doc.querySelectorAll('style').forEach(function(style) {
            const code = style.textContent.trim();
            if (!code) return;

            if (/#closeBrg(Produk)?\b|#autocompleteProduk\b|#autocomplete\b/.test(code) &&
                !/#orderDetailAutocompleteProduk/.test(code)) {
                return;
            }

            const el = document.createElement('style');
            el.textContent = code;
            el.dataset.modalAsset = '1';
            document.head.appendChild(el);
        });
    }

    function runPageScripts(doc) {
        const loadedSrcs = new Set(
            Array.from(document.querySelectorAll('script[src]')).map(function(script) {
                return script.getAttribute('src');
            })
        );

        const scripts = Array.from(doc.querySelectorAll('script'));

        function runNext(index) {
            if (index >= scripts.length) {
                return Promise.resolve();
            }

            const oldScript = scripts[index];
            const src = oldScript.getAttribute('src');

            if (src) {
                if (loadedSrcs.has(src)) {
                    return runNext(index + 1);
                }

                return new Promise(function(resolve) {
                    const script = document.createElement('script');
                    script.src = src;
                    script.onload = function() {
                        loadedSrcs.add(src);
                        resolve(runNext(index + 1));
                    };
                    script.onerror = function() {
                        resolve(runNext(index + 1));
                    };
                    document.body.appendChild(script);
                });
            }

            const code = oldScript.textContent.trim();
            if (code && !shouldSkipModalScript(code)) {
                const script = document.createElement('script');
                script.textContent = code;
                document.body.appendChild(script);
                document.body.removeChild(script);
            }

            return runNext(index + 1);
        }

        return runNext(0);
    }

    function ensureAutocompleteLib() {
        if (typeof Autocomplete !== 'undefined') {
            return Promise.resolve();
        }

        return new Promise(function(resolve, reject) {
            const existing = document.querySelector('script[src*="autocomplete.min.js"]');
            if (existing) {
                if (typeof Autocomplete !== 'undefined') {
                    resolve();
                    return;
                }

                existing.addEventListener('load', function() {
                    resolve();
                });
                existing.addEventListener('error', reject);
                return;
            }

            const script = document.createElement('script');
            script.src = "{{ asset('js/autocomplete.min.js') }}";
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    }

    function initModalOrderDetailAutocomplete() {
        const root = modalBody.querySelector('.order-detail-produk-autocomplete');
        if (!root) return Promise.resolve();

        if (root._orderDetailAutocomplete) {
            root._orderDetailAutocomplete.destroy();
            root._orderDetailAutocomplete = null;
        }

        const produkIdInput = root.querySelector('.order-detail-produk-id');
        const clearWrap = root.querySelector('.order-detail-autocomplete-clear');
        const produkInput = root.querySelector('.autocomplete-input');
        const hargaInput = root.closest('form')?.querySelector('[name="harga"]');

        function showClearBtn() {
            clearWrap.innerHTML =
                '<button type="button" class="btn btn-warning btn-sm text-white order-detail-clear-produk"><i class="bx bx-x-circle"></i></button>';
        }

        function clearProduk() {
            clearWrap.innerHTML = '';
            produkInput.value = '';
            produkIdInput.value = '';
        }

        clearWrap.onclick = function(e) {
            if (e.target.closest('.order-detail-clear-produk')) {
                clearProduk();
            }
        };

        return ensureAutocompleteLib().then(function() {
            root._orderDetailAutocomplete = new Autocomplete(root, {
                search: function(input) {
                    const url = "{{ url('admin/produk/api?q=') }}" + encodeURIComponent(input);
                    return new Promise(function(resolve) {
                        if (input.length < 1) {
                            resolve([]);
                            return;
                        }

                        fetch(url)
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                resolve(data);
                            })
                            .catch(function() {
                                resolve([]);
                            });
                    });
                },
                getResultValue: function(result) {
                    return result.varian ?
                        result.kategori + ' - ' + result.nama + ' - ' + result.varian :
                        result.kategori + ' - ' + result.nama;
                },
                onSubmit: function(result) {
                    produkIdInput.value = result.id;
                    if (hargaInput && result.harga) {
                        hargaInput.value = result.harga;
                    }
                    showClearBtn();
                },
            });
        });
    }

    function initModalInvoicePrint() {
        const printBtn = modalBody.querySelector('#print');
        const printableArea = modalBody.querySelector('.printableArea');
        if (!printBtn || !printableArea) return Promise.resolve();

        if (printBtn.dataset.modalBound === '1') return Promise.resolve();
        printBtn.dataset.modalBound = '1';

        printBtn.addEventListener('click', function() {
            if (typeof jQuery === 'undefined' || typeof jQuery.fn.printArea !== 'function') {
                window.print();
                return;
            }

            jQuery(printableArea).printArea({
                mode: 'iframe',
                popClose: false
            });
        });

        return Promise.resolve();
    }

    function getFlashFromDoc(doc) {
        const content = doc.querySelector('.body .container-fluid .mb-4') ||
            doc.querySelector('.body .container-fluid') ||
            doc.querySelector('.body');

        if (!content) return null;

        const success = content.querySelector('.alert-success');
        if (success) {
            return {
                message: success.textContent.replace(/\s+/g, ' ').trim(),
                type: 'success'
            };
        }

        const danger = content.querySelector('.alert-danger');
        if (danger) {
            return {
                message: danger.textContent.replace(/\s+/g, ' ').trim(),
                type: 'danger'
            };
        }

        return null;
    }

    function stripServerFlashAlerts(container) {
        container.querySelectorAll('.alert-success, .alert-danger').forEach(function(alert) {
            if (!alert.classList.contains('modal-ajax-alert')) {
                alert.remove();
            }
        });
    }

    function renderModalPage(html) {
        closeImagePreview();
        closeInnerModals();

        const doc = parseHtml(html);
        const flash = getFlashFromDoc(doc);
        modalBody.innerHTML = extractContent(doc);
        stripServerFlashAlerts(modalBody);
        modalTitle.textContent = extractTitle(doc);
        injectPageAssets(doc);
        return runPageScripts(doc).then(function() {
            bindModalForms();
            return initModalOrderDetailAutocomplete();
        }).then(function() {
            return initModalInvoicePrint();
        }).then(function() {
            return flash;
        });
    }

    function updateBackButton() {
        if (!backBtn) return;
        backBtn.style.display = historyIndex > 0 ? '' : 'none';
    }

    function resetHistory() {
        modalHistory = [];
        historyIndex = -1;
        updateBackButton();
    }

    function pushHistory(url) {
        modalHistory = modalHistory.slice(0, historyIndex + 1);
        modalHistory.push(url);
        historyIndex = modalHistory.length - 1;
        updateBackButton();
    }

    function replaceCurrentHistory(url) {
        if (historyIndex >= 0) {
            modalHistory[historyIndex] = url;
        }
    }

    function parseFetchErrorMessage(res, fallback) {
        return res.json().then(function(data) {
            if (data.errors) {
                return Object.values(data.errors).flat().join(' ');
            }

            if (data.message) {
                return data.message;
            }

            return fallback;
        }).catch(function() {
            return fallback;
        });
    }

    function showModalAlert(message, type) {
        modalBody.querySelectorAll('.modal-ajax-alert').forEach(function(el) {
            el.remove();
        });

        const alert = document.createElement('div');
        alert.className = 'modal-ajax-alert alert alert-' + type + ' mb-3';
        alert.textContent = message;
        modalBody.prepend(alert);

        setTimeout(function() {
            alert.remove();
        }, 3000);
    }

    function isOrderModalUrl(url) {
        const path = url.pathname;

        if (/\/admin\/orderDetail\//.test(path)) {
            return true;
        }

        if (/\/admin\/order\/\d+/.test(path)) {
            return true;
        }

        if (/\/admin\/belanja\/\d+/.test(path)) {
            return true;
        }

        return false;
    }

    function getModalNavigationUrl(link) {
        if (link.dataset.modalSkip !== undefined) return null;
        if (link.dataset.bsToggle || link.getAttribute('data-bs-toggle')) return null;
        if (link.target === '_blank') return null;

        const href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') return null;

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch (err) {
            return null;
        }

        if (!isOrderModalUrl(url)) return null;

        if (url.origin !== window.location.origin) {
            url = new URL(url.pathname + url.search + url.hash, window.location.origin);
        }

        return url.toString();
    }

    function closeImagePreview() {
        if (!imagePreview) return;

        imagePreview.classList.add('d-none');
        imagePreview.setAttribute('aria-hidden', 'true');

        if (imagePreviewImg) {
            imagePreviewImg.removeAttribute('src');
        }

        if (imagePreviewEdit) {
            imagePreviewEdit.classList.add('d-none');
            imagePreviewEdit.setAttribute('href', '#');
        }
    }

    function openImagePreview(imageSrc, editUrl) {
        if (!imagePreview || !imagePreviewImg) return;

        if (imagePreviewImg) {
            imagePreviewImg.src = imageSrc;
        }

        if (imagePreviewEdit) {
            if (editUrl) {
                imagePreviewEdit.href = editUrl;
                imagePreviewEdit.classList.remove('d-none');
            } else {
                imagePreviewEdit.classList.add('d-none');
                imagePreviewEdit.setAttribute('href', '#');
            }
        }

        imagePreview.classList.remove('d-none');
        imagePreview.setAttribute('aria-hidden', 'false');
    }

    function closeInnerModals() {
        modalBody.querySelectorAll('.modal').forEach(function(innerModal) {
            const instance = bootstrap.Modal.getInstance(innerModal);
            if (instance) {
                instance.hide();
            }
        });
    }

    function navigateInModal(url) {
        closeImagePreview();
        closeInnerModals();

        return loadDetailContent(url, true).catch(function(err) {
            if (err.isStaleRequest) return;
            renderLoadError(err, url);
        });
    }

    function shouldSkipModalLink(link) {
        return !getModalNavigationUrl(link);
    }

    function staleRequestError() {
        const err = new Error('Request sudah tidak aktif.');
        err.isStaleRequest = true;
        return err;
    }

    function loadErrorMessage(status) {
        if (status === 401) {
            return 'Sesi login sudah habis. Silakan login ulang.';
        }

        if (status === 403) {
            return 'Akses ditolak (403). Coba buka halaman detail langsung atau cek permission order detail user ini.';
        }

        return 'Gagal memuat (' + status + ')';
    }

    function renderLoadError(err, url) {
        modalBody.innerHTML = '';

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = err.message || 'Gagal memuat detail.';
        modalBody.appendChild(alert);

        if (url) {
            const link = document.createElement('a');
            link.href = url;
            link.className = 'btn btn-sm btn-outline-primary';
            link.textContent = 'Buka halaman detail';
            link.dataset.modalSkip = '1';
            modalBody.appendChild(link);
        }
    }

    function loadDetailContent(url, showSpinner, trackHistory) {
        const requestId = ++activeLoadRequest;

        if (trackHistory !== false) {
            pushHistory(url);
        }

        if (showSpinner) modalBody.innerHTML = spinner;

        return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                    'Cache-Control': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            })
            .then(function(res) {
                if (requestId !== activeLoadRequest) throw staleRequestError();
                if (!res.ok) throw new Error(loadErrorMessage(res.status));
                return res.text().then(function(html) {
                    if (requestId !== activeLoadRequest) throw staleRequestError();
                    return {
                        html: html,
                        url: res.url || url
                    };
                });
            })
            .then(function(data) {
                if (requestId !== activeLoadRequest) throw staleRequestError();
                replaceCurrentHistory(data.url);
                return renderModalPage(data.html);
            })
            .catch(function(err) {
                if (requestId !== activeLoadRequest) throw staleRequestError();
                throw err;
            });
    }

    function submitJsonForm(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(res) {
                if (!res.ok) {
                    return parseFetchErrorMessage(res, 'Gagal menyimpan. Silakan coba lagi.').then(function(message) {
                        throw new Error(message);
                    });
                }

                return res.json();
            })
            .then(function(data) {
                const reloadUrl = form.dataset.reloadDetail;
                const successMessage = data.message || 'Berhasil disimpan';

                if (reloadUrl) {
                    replaceCurrentHistory(reloadUrl);
                    return loadDetailContent(reloadUrl, false, false).then(function() {
                        showModalAlert(successMessage, 'success');
                    });
                }

                showModalAlert(successMessage, 'success');
            })
            .catch(function(err) {
                showModalAlert(err.message, 'danger');
            })
            .finally(function() {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    function submitHtmlForm(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                redirect: 'follow'
            })
            .then(function(res) {
                return res.text().then(function(html) {
                    if (!res.ok && res.status !== 422) {
                        throw new Error('Gagal menyimpan (' + res.status + ')');
                    }
                    return {
                        html: html,
                        url: res.url || form.action
                    };
                });
            })
            .then(function(data) {
                replaceCurrentHistory(data.url);
                return renderModalPage(data.html).then(function(flash) {
                    if (flash) {
                        showModalAlert(flash.message, flash.type);
                    } else {
                        showModalAlert('Berhasil disimpan', 'success');
                    }
                });
            })
            .catch(function(err) {
                showModalAlert(err.message, 'danger');
            })
            .finally(function() {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    function bindModalForms() {
        modalBody.querySelectorAll('form').forEach(function(form) {
            if (form.dataset.modalBound === '1') return;
            form.dataset.modalBound = '1';

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (form.classList.contains('order-detail-ajax-form')) {
                    submitJsonForm(form);
                    return;
                }

                submitHtmlForm(form);
            });
        });
    }

    function loadDetailInModal(url) {
        modalWasOpened = true;
        resetHistory();
        bsModal.show();

        loadDetailContent(url, true).catch(function(err) {
            if (err.isStaleRequest) return;
            renderLoadError(err, url);
        });
    }

    function goBackInModal() {
        if (historyIndex <= 0) return;

        historyIndex--;
        updateBackButton();

        loadDetailContent(modalHistory[historyIndex], true, false).catch(function(err) {
            if (err.isStaleRequest) return;
            renderLoadError(err, modalHistory[historyIndex]);
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', goBackInModal);
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('form, button')) return;

        const link = e.target.closest('a.popup');
        if (!link) return;

        const modalUrl = getModalNavigationUrl(link);
        if (!modalUrl) return;

        e.preventDefault();
        loadDetailInModal(modalUrl);
    });

    modalEl.addEventListener('click', function(e) {
        if (e.target.closest('[data-image-preview-close]')) {
            e.preventDefault();
            closeImagePreview();
            return;
        }

        const thumb = e.target.closest('.order-detail-image-thumb');
        if (thumb && modalBody.contains(thumb)) {
            e.preventDefault();
            openImagePreview(thumb.dataset.imageSrc, thumb.dataset.editUrl || '');
            return;
        }

        const link = e.target.closest('a[href]');
        if (!link || !modalBody.contains(link)) return;
        if (shouldSkipModalLink(link)) return;

        const modalUrl = getModalNavigationUrl(link);
        if (!modalUrl) return;

        e.preventDefault();
        e.stopPropagation();
        navigateInModal(modalUrl);
    }, true);

    if (imagePreviewEdit) {
        imagePreviewEdit.addEventListener('click', function(e) {
            const href = imagePreviewEdit.getAttribute('href');
            if (!href || href === '#') return;

            const modalUrl = getModalNavigationUrl(imagePreviewEdit);
            if (!modalUrl) return;

            e.preventDefault();
            navigateInModal(modalUrl);
        });
    }

    modalEl.addEventListener('hide.bs.modal', function(e) {
        if (modalBody.querySelector('.modal.show')) {
            e.preventDefault();
            closeInnerModals();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        activeLoadRequest++;
        closeImagePreview();
        resetHistory();
        modalWasOpened = false;
    });
})();

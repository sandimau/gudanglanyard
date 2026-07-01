(function() {
    const modalEl = document.getElementById('detailHutangModal');
    if (!modalEl) return;

    const modalBody = document.getElementById('detailHutangBody');
    const modalTitle = document.getElementById('detailHutangModalLabel');
    const backBtn = document.getElementById('detailHutangModalBack');
    const bsModal = new bootstrap.Modal(modalEl);
    let modalHistory = [];
    let historyIndex = -1;
    let shouldReloadIndex = false;

    const spinner = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;

    function resolveModalUrl(url) {
        const parsed = new URL(url, window.location.href);
        parsed.protocol = window.location.protocol;
        parsed.host = window.location.host;
        return parsed.href;
    }

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
        const cardTitle = doc.querySelector('.card-title');
        if (cardTitle) {
            return cardTitle.textContent.replace(/\s+/g, ' ').trim();
        }

        const title = doc.querySelector('title');
        if (title) {
            const parts = title.textContent.trim().split('|');
            return parts[0].trim() || 'Detail';
        }

        return 'Detail';
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
            if (code) {
                const script = document.createElement('script');
                script.textContent = code;
                document.body.appendChild(script);
                document.body.removeChild(script);
            }

            return runNext(index + 1);
        }

        return runNext(0);
    }

    function getFlashFromDoc(doc) {
        const content = doc.querySelector('.body .container-fluid') || doc.querySelector('.body');
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

    function getValidationErrorsFromHtml(doc) {
        const messages = [];

        doc.querySelectorAll('.invalid-feedback').forEach(function(el) {
            const text = el.textContent.replace(/\s+/g, ' ').trim();
            if (text) {
                messages.push(text);
            }
        });

        return messages;
    }

    function renderModalPage(html) {
        const doc = parseHtml(html);
        const flash = getFlashFromDoc(doc);
        const validationErrors = getValidationErrorsFromHtml(doc);
        modalBody.innerHTML = extractContent(doc);
        stripServerFlashAlerts(modalBody);
        modalTitle.textContent = extractTitle(doc);
        return runPageScripts(doc).then(function() {
            bindModalForms();
            return {
                flash: flash,
                validationErrors: validationErrors
            };
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

    function clearFormValidationErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function(el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback.modal-field-error').forEach(function(el) {
            el.remove();
        });
    }

    function applyValidationErrorsToForm(form, errors) {
        clearFormValidationErrors(form);

        const messages = [];

        Object.keys(errors).forEach(function(field) {
            const fieldMessages = errors[field];
            if (!fieldMessages || !fieldMessages.length) return;

            messages.push(fieldMessages[0]);

            const input = form.querySelector('[name="' + field + '"]');
            if (!input) return;

            input.classList.add('is-invalid');

            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback modal-field-error d-block';
            feedback.textContent = fieldMessages[0];

            const group = input.closest('.form-group, .mb-3, [class*="col-"]') || input.parentElement;
            if (group) {
                group.appendChild(feedback);
            }
        });

        showModalAlert(messages.length ? messages.join(' ') : 'Periksa kembali isian formulir.', 'danger');
    }

    function getValidationErrorsObjectFromHtml(doc) {
        const errors = {};

        doc.querySelectorAll('.form-group, .mb-3, [class*="col-"]').forEach(function(group) {
            const input = group.querySelector('.is-invalid[name]');
            const feedback = group.querySelector('.invalid-feedback');

            if (!input || !feedback) return;

            const field = input.getAttribute('name');
            const text = feedback.textContent.replace(/\s+/g, ' ').trim();

            if (field && text) {
                errors[field] = [text];
            }
        });

        return errors;
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
        }, 4000);
    }

    function isHutangModalUrl(url) {
        return /\/admin\/hutang\b/.test(url.pathname);
    }

    function shouldSkipModalLink(link) {
        if (link.dataset.modalSkip !== undefined) return true;
        if (link.dataset.bsToggle || link.getAttribute('data-bs-toggle')) return true;
        if (link.dataset.bsDismiss || link.getAttribute('data-bs-dismiss')) return true;
        if (link.target === '_blank') return true;

        const href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') return true;

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch (err) {
            return true;
        }

        if (url.origin !== window.location.origin) return true;

        return !isHutangModalUrl(url);
    }

    function loadDetailContent(url, showSpinner, trackHistory) {
        const resolvedUrl = resolveModalUrl(url);

        if (trackHistory !== false) {
            pushHistory(resolvedUrl);
        }

        if (showSpinner) modalBody.innerHTML = spinner;

        return fetch(resolvedUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/json',
                    'Cache-Control': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Gagal memuat (' + res.status + ')');
                return res.text().then(function(html) {
                    return {
                        html: html,
                        url: res.url || resolvedUrl
                    };
                });
            })
            .then(function(data) {
                if (!data || !data.html) {
                    return data;
                }

                replaceCurrentHistory(data.url);
                return renderModalPage(data.html);
            });
    }

    function handleModalSaveResult(result, fallbackMessage) {
        if (result.validationErrors && result.validationErrors.length) {
            showModalAlert(result.validationErrors.join(' '), 'danger');
            return;
        }

        if (result.flash) {
            if (result.flash.type === 'success') {
                shouldReloadIndex = true;
            }
            showModalAlert(result.flash.message, result.flash.type);
            return;
        }

        if (fallbackMessage) {
            shouldReloadIndex = true;
            showModalAlert(fallbackMessage, 'success');
        }
    }

    function submitModalForm(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(resolveModalUrl(form.action), {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html'
                },
                credentials: 'same-origin',
                redirect: 'follow'
            })
            .then(function(res) {
                return res.text().then(function(html) {
                    if (res.status === 422) {
                        const validationErrors = getValidationErrorsObjectFromHtml(parseHtml(html));

                        if (Object.keys(validationErrors).length) {
                            return {
                                validationErrors: validationErrors,
                                form: form
                            };
                        }
                    }

                    if (!res.ok) {
                        const validationErrors = getValidationErrorsFromHtml(parseHtml(html));
                        const message = validationErrors.length ?
                            validationErrors.join(' ') :
                            'Gagal menyimpan. Silakan coba lagi.';
                        throw new Error(message);
                    }

                    return {
                        html: html,
                        url: res.url || form.action
                    };
                });
            })
            .then(function(data) {
                if (data.validationErrors) {
                    applyValidationErrorsToForm(data.form, data.validationErrors);
                    return;
                }

                replaceCurrentHistory(data.url);
                return renderModalPage(data.html).then(function(result) {
                    handleModalSaveResult(result, null);
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
                submitModalForm(form);
            });
        });
    }

    function navigateInModal(url) {
        return loadDetailContent(url, true).catch(function(err) {
            modalBody.innerHTML =
                '<div class="alert alert-danger">' + err.message + '</div>';
        });
    }

    function loadDetailInModal(url) {
        resetHistory();
        bsModal.show();

        loadDetailContent(url, true).catch(function(err) {
            modalBody.innerHTML =
                '<div class="alert alert-danger">' + err.message + '</div>';
        });
    }

    function goBackInModal() {
        if (historyIndex <= 0) return;

        historyIndex--;
        updateBackButton();

        loadDetailContent(modalHistory[historyIndex], true, false).catch(function(err) {
            modalBody.innerHTML =
                '<div class="alert alert-danger">' + err.message + '</div>';
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', goBackInModal);
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a.popup-hutang');
        if (!link) return;

        e.preventDefault();
        loadDetailInModal(link.getAttribute('href'));
    });

    modalEl.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link || !modalBody.contains(link)) return;
        if (shouldSkipModalLink(link)) return;

        e.preventDefault();
        e.stopPropagation();
        navigateInModal(link.href);
    }, true);

    modalEl.addEventListener('hidden.bs.modal', function() {
        resetHistory();

        if (shouldReloadIndex) {
            shouldReloadIndex = false;
            window.location.reload();
        }
    });
})();

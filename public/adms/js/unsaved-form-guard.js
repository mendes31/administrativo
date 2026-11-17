/**
 * Cadastros: o botão de gravar só habilita depois de uma alteração,
 * e sair da página com pendência pede confirmação no aviso do sistema.
 * Para ficar de fora: data-unsaved-guard="off" no form.
 */
(function () {
    var SAVE_LABEL = /^(salvar|atualizar|cadastrar|gravar|registrar)\b/i;
    var states = [];
    var leaving = false;
    var booted = false;
    var dialog = null;
    var dialogModal = null;
    var pendingYes = null;
    var accepted = false;
    var sentinel = false;

    window.AdmsUnsavedFormGuard = {
        markDirty: function (form) {
            states.forEach(function (state) {
                if (state.form === form) {
                    state.touched = true;
                    state.forced = true;
                    sync(state);
                }
            });
        },
        markClean: function (form) {
            states.forEach(function (state) {
                if (state.form === form) {
                    state.forced = false;
                    state.touched = false;
                    state.baseline = serialize(form);
                    sync(state);
                }
            });
        }
    };

    function labelOf(btn) {
        if (!btn) {
            return '';
        }
        if (btn.tagName === 'INPUT') {
            return String(btn.value || '').replace(/\s+/g, ' ').trim();
        }
        return String(btn.innerText || btn.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function setLabel(btn, text) {
        if (btn.tagName === 'INPUT') {
            btn.value = text;
            return;
        }
        var replaced = false;
        Array.prototype.forEach.call(btn.childNodes, function (node) {
            if (node.nodeType === 3 && String(node.textContent || '').trim() !== '') {
                node.textContent = (String(node.textContent).match(/^\s*/) || [''])[0] + text;
                replaced = true;
            }
        });
        if (!replaced) {
            btn.textContent = text;
        }
    }

    function isDataField(el) {
        if (!el || !el.name) {
            return false;
        }
        var type = String(el.type || '').toLowerCase();
        if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset' || type === 'image') {
            return false;
        }
        if (el.name === 'csrf_token') {
            return false;
        }
        return true;
    }

    function fieldsOf(form) {
        var list = [];
        document.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.form === form) {
                list.push(el);
            }
        });
        return list;
    }

    function isEditForm(form) {
        var fields = fieldsOf(form);
        for (var i = 0; i < fields.length; i++) {
            var el = fields[i];
            if (String(el.type || '').toLowerCase() !== 'hidden') {
                continue;
            }
            if ((el.name || '') !== 'id') {
                continue;
            }
            var value = String(el.value || '').trim();
            if (value !== '' && value !== '0') {
                return true;
            }
        }
        return false;
    }

    function serialize(form) {
        var chunks = [];
        fieldsOf(form).forEach(function (el) {
            if (!isDataField(el)) {
                return;
            }
            var type = String(el.type || '').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                chunks.push(el.name + '\t' + (el.checked ? '1' : '0') + '\t' + el.value);
                return;
            }
            if (type === 'file') {
                chunks.push(el.name + '\t' + (el.value || ''));
                return;
            }
            if (el.tagName === 'SELECT' && el.multiple) {
                var selected = Array.prototype.map.call(el.selectedOptions || [], function (opt) {
                    return opt.value;
                }).join(',');
                chunks.push(el.name + '\t' + selected);
                return;
            }
            chunks.push(el.name + '\t' + (el.value || ''));
        });
        return chunks.join('\n');
    }

    function saveButtons(form) {
        var buttons = [];
        document.querySelectorAll('button, input[type="submit"]').forEach(function (btn) {
            if (btn.form !== form) {
                return;
            }
            if (SAVE_LABEL.test(labelOf(btn))) {
                buttons.push(btn);
            }
        });
        return buttons;
    }

    function shouldGuard(form) {
        if (!form || form.getAttribute('data-unsaved-guard') === 'off') {
            return false;
        }
        if (form.getAttribute('data-unsaved-guard') === 'on') {
            return false;
        }
        var method = String(form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') {
            return false;
        }
        var marker = ((form.id || '') + ' ' + (form.className || '') + ' ' + (form.getAttribute('name') || '')).toLowerCase();
        if (/filter|filtro|search|busca/.test(marker)) {
            return false;
        }
        var hasField = fieldsOf(form).some(isDataField);
        if (!hasField) {
            return false;
        }
        return saveButtons(form).length > 0;
    }

    function relabel(form) {
        var edit = isEditForm(form);
        saveButtons(form).forEach(function (btn) {
            if (btn.getAttribute('data-unsaved-relabeled') === '1') {
                return;
            }
            var label = labelOf(btn);
            if (edit && /^salvar$/i.test(label)) {
                setLabel(btn, 'Atualizar');
            } else if (/^cadastrar$/i.test(label)) {
                setLabel(btn, 'Salvar');
            }
            btn.setAttribute('data-unsaved-relabeled', '1');
        });
    }

    function anyDirty(exceptForm) {
        for (var i = 0; i < states.length; i++) {
            if (exceptForm && states[i].form === exceptForm) {
                continue;
            }
            if (states[i].dirty) {
                return true;
            }
        }
        return false;
    }

    function sync(state) {
        var now = serialize(state.form);
        state.dirty = !!state.forced || now !== state.baseline;
        saveButtons(state.form).forEach(function (btn) {
            if (!btn.hasAttribute('data-unsaved-title')) {
                btn.setAttribute('data-unsaved-title', btn.getAttribute('title') || '');
            }
            btn.disabled = !state.dirty;
            btn.title = state.dirty
                ? btn.getAttribute('data-unsaved-title')
                : 'Altere algum campo para habilitar.';
        });
        if (state.dirty) {
            armBack();
        }
    }

    function ensureDialog() {
        if (dialog) {
            return dialog;
        }
        dialog = document.createElement('div');
        dialog.className = 'modal fade';
        dialog.id = 'adms-unsaved-dialog';
        dialog.tabIndex = -1;
        dialog.setAttribute('aria-hidden', 'true');
        dialog.innerHTML =
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content" style="border:0;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.18);">' +
                    '<div class="modal-header" style="border-bottom:0;padding-bottom:0;">' +
                        '<h5 class="modal-title" id="adms-unsaved-title">' +
                            '<i class="fas fa-exclamation-triangle text-warning me-2"></i>' +
                            '<span></span>' +
                        '</h5>' +
                    '</div>' +
                    '<div class="modal-body pt-2" id="adms-unsaved-text"></div>' +
                    '<div class="modal-footer" style="border-top:0;">' +
                        '<button type="button" class="btn btn-light" data-adms-unsaved="stay">Continuar editando</button>' +
                        '<button type="button" class="btn btn-warning" data-adms-unsaved="leave">Sair sem salvar</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(dialog);
        dialog.querySelector('[data-adms-unsaved="stay"]').addEventListener('click', function () {
            if (dialogModal) {
                dialogModal.hide();
            }
        });
        dialog.querySelector('[data-adms-unsaved="leave"]').addEventListener('click', function () {
            accepted = true;
            if (dialogModal) {
                dialogModal.hide();
            }
        });
        dialog.addEventListener('hidden.bs.modal', function () {
            var yes = pendingYes;
            pendingYes = null;
            if (accepted && typeof yes === 'function') {
                accepted = false;
                yes();
                return;
            }
            accepted = false;
        });
        if (window.bootstrap && window.bootstrap.Modal) {
            dialogModal = window.bootstrap.Modal.getOrCreateInstance(dialog, {
                backdrop: 'static',
                keyboard: true
            });
        }
        return dialog;
    }

    function ask(options, onYes) {
        ensureDialog();
        if (!dialogModal) {
            if (window.confirm((options.title || '') + '\n\n' + (options.text || ''))) {
                onYes();
            }
            return;
        }
        dialog.querySelector('#adms-unsaved-title span').textContent = options.title;
        dialog.querySelector('#adms-unsaved-text').textContent = options.text;
        var leaveBtn = dialog.querySelector('[data-adms-unsaved="leave"]');
        leaveBtn.textContent = options.confirmLabel;
        pendingYes = onYes;
        accepted = false;
        dialogModal.show();
        window.setTimeout(function () {
            var stay = dialog.querySelector('[data-adms-unsaved="stay"]');
            if (stay) {
                stay.focus();
            }
        }, 150);
    }

    function armBack() {
        if (sentinel || leaving) {
            return;
        }
        try {
            history.pushState({ admsUnsaved: 1 }, '', window.location.href);
            sentinel = true;
        } catch (e) {
            sentinel = false;
        }
    }

    function bind(form) {
        if (!shouldGuard(form)) {
            return;
        }
        relabel(form);
        var state = {
            form: form,
            baseline: serialize(form),
            dirty: false,
            touched: false
        };
        form.setAttribute('data-unsaved-guard', 'on');
        states.push(state);
        sync(state);

        function touch() {
            state.touched = true;
            sync(state);
        }

        form.addEventListener('input', touch);
        form.addEventListener('change', touch);
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }
            if (!anyDirty(form)) {
                leaving = true;
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            ask({
                title: 'Alterações em outra parte da tela',
                text: 'Há alterações que não entram neste salvamento. Se continuar, elas serão descartadas.',
                confirmLabel: 'Continuar'
            }, function () {
                leaving = true;
                form.submit();
            });
            return;
        });

        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                sync(state);
            });
            observer.observe(form, { childList: true, subtree: true });
        }

        state.refreshBaseline = function () {
            if (state.touched || state.dirty || state.forced) {
                return;
            }
            state.baseline = serialize(form);
            sync(state);
        };
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var forms = [];
        if (scope.tagName === 'FORM') {
            forms.push(scope);
        }
        scope.querySelectorAll('form').forEach(function (form) {
            forms.push(form);
        });
        forms.forEach(bind);
    }

    document.addEventListener('input', function (event) {
        var el = event.target;
        if (!el || !el.form) {
            return;
        }
        for (var i = 0; i < states.length; i++) {
            if (states[i].form === el.form) {
                states[i].touched = true;
                sync(states[i]);
            }
        }
    }, true);

    document.addEventListener('change', function (event) {
        var el = event.target;
        if (!el || !el.form) {
            return;
        }
        for (var i = 0; i < states.length; i++) {
            if (states[i].form === el.form) {
                states[i].touched = true;
                sync(states[i]);
            }
        }
    }, true);

    document.addEventListener('click', function (event) {
        if (leaving || event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) {
            return;
        }
        var target = (link.getAttribute('target') || '').toLowerCase();
        if (target && target !== '_self') {
            return;
        }
        if (link.hasAttribute('download')) {
            return;
        }
        var href = link.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
            return;
        }
        var next;
        try {
            next = new URL(link.href, window.location.href);
        } catch (e) {
            return;
        }
        if (next.pathname === window.location.pathname && next.search === window.location.search && next.hash) {
            return;
        }
        if (!anyDirty(null)) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
        }
        ask({
            title: 'Alterações não salvas',
            text: 'Há alterações nesta tela que ainda não foram gravadas. Se sair agora, elas serão descartadas.',
            confirmLabel: 'Sair sem salvar'
        }, function () {
            leaving = true;
            window.location.href = link.href;
        });
    }, true);

    window.addEventListener('popstate', function () {
        if (!sentinel) {
            return;
        }
        sentinel = false;
        if (leaving || !anyDirty(null)) {
            history.back();
            return;
        }
        armBack();
        ask({
            title: 'Alterações não salvas',
            text: 'Há alterações nesta tela que ainda não foram gravadas. Se sair agora, elas serão descartadas.',
            confirmLabel: 'Sair sem salvar'
        }, function () {
            leaving = true;
            sentinel = false;
            history.go(-2);
        });
    });

    function boot() {
        if (booted) {
            return;
        }
        booted = true;
        scan(document);
        window.addEventListener('load', function () {
            states.forEach(function (state) {
                state.refreshBaseline();
            });
        });
        if (window.MutationObserver && document.body) {
            var timer = 0;
            var observer = new MutationObserver(function (records) {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    records.forEach(function (record) {
                        Array.prototype.forEach.call(record.addedNodes || [], function (node) {
                            if (node.nodeType === 1) {
                                scan(node);
                            }
                        });
                    });
                }, 50);
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

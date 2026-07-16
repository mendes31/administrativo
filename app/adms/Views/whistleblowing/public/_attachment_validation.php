<?php
/**
 * Validação + acumulação de anexos (permite adicionar em várias seleções).
 * Expectativa no HTML:
 * - .canal-attachments-field
 * - input.canal-attachments-input[name="attachments[]"]
 * - button.canal-attachments-add
 * - .canal-attachment-list
 * - .canal-attachment-feedback
 */
$maxBytes = \App\adms\Models\Services\WhistleblowingUploadService::maxFileSizeBytes();
$maxLabel = \App\adms\Models\Services\WhistleblowingUploadService::maxFileSizeLabel();
$exts = \App\adms\Models\Services\WhistleblowingUploadService::allowedExtensions();
?>
<script>
(function () {
    var MAX_BYTES = <?= (int) $maxBytes ?>;
    var MAX_LABEL = <?= json_encode($maxLabel, JSON_UNESCAPED_UNICODE) ?>;
    var ALLOWED = <?= json_encode($exts, JSON_UNESCAPED_UNICODE) ?>;
    var ALLOWED_LABEL = ALLOWED.map(function (e) { return e.toUpperCase(); }).join(', ');
    var MAX_FILES = 10;

    function formatMb(bytes) {
        return (bytes / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
    }

    function fileKey(file) {
        return [file.name, file.size, file.lastModified].join('|');
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function validateFile(file) {
        var name = file.name || 'arquivo';
        var ext = (name.split('.').pop() || '').toLowerCase();
        if (ALLOWED.indexOf(ext) === -1) {
            return '«' + name + '»: tipo não permitido. Use: ' + ALLOWED_LABEL + '.';
        }
        if (file.size <= 0) {
            return '«' + name + '»: arquivo vazio.';
        }
        if (file.size > MAX_BYTES) {
            return '«' + name + '»: tamanho ' + formatMb(file.size)
                + ' ultrapassa o máximo de ' + MAX_LABEL
                + '. Comprima o vídeo ou envie um arquivo menor.';
        }
        return null;
    }

    function syncInput(input, files) {
        if (typeof DataTransfer === 'undefined') {
            return false;
        }
        var dt = new DataTransfer();
        for (var i = 0; i < files.length; i++) {
            dt.items.add(files[i]);
        }
        input.files = dt.files;
        return true;
    }

    function renderList(wrapper, files, onRemove) {
        var list = wrapper.querySelector('.canal-attachment-list');
        var addBtn = wrapper.querySelector('.canal-attachments-add');
        if (!list) {
            return;
        }

        if (files.length === 0) {
            list.innerHTML = '<div class="text-muted small border rounded p-2 bg-light">'
                + 'Nenhum arquivo selecionado ainda.</div>';
            if (addBtn) {
                addBtn.innerHTML = '<i class="fas fa-paperclip me-1"></i>Adicionar arquivos';
            }
            return;
        }

        if (addBtn) {
            addBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Adicionar mais arquivos ('
                + files.length + '/' + MAX_FILES + ')';
        }

        var items = '';
        for (var i = 0; i < files.length; i++) {
            items += '<li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">'
                + '<span class="small text-break me-2">'
                + '<i class="fas fa-check-circle text-success me-1"></i>'
                + escapeHtml(files[i].name)
                + ' <span class="text-muted">(' + formatMb(files[i].size) + ')</span></span>'
                + '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" data-remove-index="'
                + i + '">Remover</button></li>';
        }

        list.innerHTML = '<div class="small fw-semibold text-success mb-1">'
            + files.length + ' arquivo(s) pronto(s) para envio</div>'
            + '<ul class="list-group list-group-flush border rounded mb-0">' + items + '</ul>';

        var buttons = list.querySelectorAll('[data-remove-index]');
        for (var b = 0; b < buttons.length; b++) {
            buttons[b].addEventListener('click', function (ev) {
                var idx = parseInt(ev.currentTarget.getAttribute('data-remove-index'), 10);
                onRemove(idx);
            });
        }
    }

    function showFeedback(wrapper, errors) {
        var box = wrapper.querySelector('.canal-attachment-feedback');
        if (!box) {
            return;
        }
        if (!errors || errors.length === 0) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }
        var lis = '';
        for (var i = 0; i < errors.length; i++) {
            lis += '<li>' + escapeHtml(errors[i]) + '</li>';
        }
        box.classList.remove('d-none');
        box.innerHTML = '<strong>Corrija os anexos antes de enviar:</strong>'
            + '<ul class="mb-0 mt-1">' + lis + '</ul>';
    }

    var fields = document.querySelectorAll('.canal-attachments-field');
    for (var f = 0; f < fields.length; f++) {
        (function (wrapper) {
            var input = wrapper.querySelector('.canal-attachments-input');
            var addBtn = wrapper.querySelector('.canal-attachments-add');
            var form = input ? input.closest('form') : null;
            if (!input) {
                return;
            }

            var selected = [];

            function refresh(errors) {
                syncInput(input, selected);
                renderList(wrapper, selected, function (index) {
                    selected.splice(index, 1);
                    refresh([]);
                });
                showFeedback(wrapper, errors || []);
            }

            refresh([]);

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    input.click();
                });
            }

            input.addEventListener('change', function () {
                var incoming = [];
                if (input.files) {
                    for (var i = 0; i < input.files.length; i++) {
                        incoming.push(input.files[i]);
                    }
                }

                var errors = [];
                var existingKeys = selected.map(fileKey);

                for (var j = 0; j < incoming.length; j++) {
                    var file = incoming[j];
                    var err = validateFile(file);
                    if (err) {
                        errors.push(err);
                        continue;
                    }
                    var key = fileKey(file);
                    if (existingKeys.indexOf(key) !== -1) {
                        continue;
                    }
                    if (selected.length >= MAX_FILES) {
                        errors.push('Limite de ' + MAX_FILES + ' arquivos por envio.');
                        break;
                    }
                    selected.push(file);
                    existingKeys.push(key);
                }

                input.value = '';
                refresh(errors);
            });

            if (form) {
                form.addEventListener('submit', function (event) {
                    var errors = [];
                    for (var i = 0; i < selected.length; i++) {
                        var err = validateFile(selected[i]);
                        if (err) {
                            errors.push(err);
                        }
                    }
                    if (errors.length === 0) {
                        if (!syncInput(input, selected) && selected.length > 0) {
                            event.preventDefault();
                            showFeedback(wrapper, [
                                'Seu navegador não permite anexar vários arquivos em etapas. Selecione todos de uma vez (Ctrl+clique) ou atualize o navegador.'
                            ]);
                            return;
                        }
                        return;
                    }
                    event.preventDefault();
                    showFeedback(wrapper, errors);
                    if (addBtn) {
                        addBtn.focus();
                    }
                    var feedbackBox = wrapper.querySelector('.canal-attachment-feedback');
                    if (feedbackBox && feedbackBox.scrollIntoView) {
                        feedbackBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
        })(fields[f]);
    }
})();
</script>

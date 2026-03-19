<?php
use App\adms\Helpers\CSRFHelper;

/** @var array $policy */
$policy = $this->data['policy'] ?? [];
$notifyDeps = $this->data['notify_departments'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-1 d-flex flex-column flex-md-row align-items-md-center gap-2">
                <h2 class="mt-3 text-primary fw-bold mb-0 mobile-hide-page-title" style="font-size: 1.7rem; letter-spacing: -1px;">
                    Editar Política Interna
                </h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0 mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0 mobile-hide-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="text-decoration-none">Políticas Internas</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Editar</li>
                    </ol>
                </nav>
            </div>

            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 18px; background: #fff;">
                <div class="card-body p-4">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <form method="POST" enctype="multipart/form-data" style="max-width: 700px; margin: 0 auto;">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('update_policy'); ?>">

                        <div class="row g-3 mb-3" style="margin-bottom: 2.5rem !important;">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label fw-semibold">Título *</label>
                                <input type="text"
                                       class="form-control form-control-lg rounded-3"
                                       id="titulo"
                                       name="titulo"
                                       required
                                       maxlength="255"
                                       value="<?php echo htmlspecialchars($policy['titulo'] ?? ''); ?>"
                                       placeholder="Título da política interna">
                            </div>

                            <div class="col-md-6">
                                <label for="categoria_id" class="form-label fw-semibold">Categoria *</label>
                                <select class="form-select form-select-lg rounded-3"
                                        id="categoria_id"
                                        name="categoria_id"
                                        required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                        <?php
                                        $selected = ((int)($policy['categoria_id'] ?? 0) === (int)$categoria['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int) $categoria['id']; ?>" <?= $selected; ?>>
                                            <?= htmlspecialchars($categoria['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="categoria" id="categoria_nome_hidden" value="<?php echo htmlspecialchars($policy['categoria'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-semibold">Departamento responsável *</label>
                                <select class="form-select form-select-lg rounded-3"
                                        id="department_id"
                                        name="department_id"
                                        required>
                                    <option value="">Selecione o departamento</option>
                                    <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                        <?php
                                        $selected = ((int)($policy['department_id'] ?? 0) === (int)$dep['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int) $dep['id']; ?>" <?= $selected; ?>>
                                            <?= htmlspecialchars($dep['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="publish_at" class="form-label fw-semibold">Publicar em</label>
                                <input type="datetime-local"
                                       class="form-control form-control-lg rounded-3"
                                       id="publish_at"
                                       name="publish_at"
                                       value="<?php echo !empty($policy['publish_at']) ? date('Y-m-d\TH:i', strtotime($policy['publish_at'])) : ''; ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="expire_at" class="form-label fw-semibold">Expira em</label>
                                <input type="datetime-local"
                                       class="form-control form-control-lg rounded-3"
                                       id="expire_at"
                                       name="expire_at"
                                       value="<?php echo !empty($policy['expire_at']) ? date('Y-m-d\TH:i', strtotime($policy['expire_at'])) : ''; ?>">
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="mb-3">
                            <label for="conteudo" class="form-label fw-semibold">Conteúdo da política *</label>
                            <textarea class="form-control form-control-lg rounded-3"
                                      id="conteudo"
                                      name="conteudo"
                                      rows="8"
                                      placeholder="Descreva a política interna em detalhes..."><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', $policy['conteudo'] ?? ''); ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="urgente" name="urgente"
                                        <?php echo !empty($policy['urgente']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-danger" for="urgente">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                    </label>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_ack" name="requires_ack"
                                        <?php echo !empty($policy['requires_ack']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="requires_ack">
                                        Exigir ciência dos colaboradores
                                    </label>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                        <?php echo !empty($policy['ativo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-success" for="ativo">
                                        <i class="fas fa-check me-1"></i>Ativa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notificar" name="notificar"
                                    <?php echo !empty($policy['notificar']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="notificar">
                                    Enviar notificação via WhatsApp
                                </label>
                            </div>
                            <label class="form-label small mb-1">Departamentos a notificar (opcional)</label>
                            <div class="border rounded p-2 bg-light" style="max-height: 130px; overflow-y: auto;">
                                <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                    <?php
                                    $checked = in_array((int) $dep['id'], $notifyDeps, true) ? 'checked' : '';
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="notify_departments[]"
                                               value="<?= (int) $dep['id']; ?>"
                                               id="notify_dep_<?= (int) $dep['id']; ?>"
                                            <?= $checked; ?>>
                                        <label class="form-check-label"
                                               for="notify_dep_<?= (int) $dep['id']; ?>">
                                            <?= htmlspecialchars($dep['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text small mt-1 mb-1">
                                Marque os setores que devem receber a notificação. Nenhum marcado = todos recebem.
                            </div>
                        </div>

                        <!-- Botões principais de ação (antes dos uploads) -->
                        <div class="d-flex gap-2 justify-content-end mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                               class="btn btn-outline-secondary btn-lg rounded-3 px-4">
                                Voltar
                            </a>

                            <button type="submit"
                                    class="btn btn-primary btn-lg rounded-3 px-4 fw-bold">
                                Salvar Alterações
                            </button>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Imagem (opcional)</label>
                                <div class="dropzone rounded-3 border border-2 border-dashed p-2 text-center bg-light position-relative"
                                     style="min-height: 60px; cursor: pointer;">
                                    <input type="file"
                                           class="d-none"
                                           id="imagem"
                                           name="imagem"
                                           accept=".png,.jpg,.jpeg,.gif,.webp"
                                           onchange="previewImagem(this)">
                                    <label for="imagem"
                                           id="imagem-label"
                                           class="w-100 h-100 d-flex flex-column align-items-center justify-content-center"
                                           style="cursor:pointer;">
                                        <i class="fas fa-image fa-2x mb-2 text-secondary"></i>
                                        <span class="text-muted">Clique para fazer upload ou arraste uma imagem aqui</span>
                                        <span class="small text-muted">Formatos: PNG, JPG, JPEG, GIF, WEBP. Máx. 20MB</span>
                                    </label>
                                    <div id="preview-imagem" class="mt-2">
                                        <?php if (!empty($policy['imagem'])): ?>
                                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($policy['imagem']); ?>"
                                                 alt="Imagem atual"
                                                 style="max-width: 100px; max-height: 100px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                            <div class="small mt-1 text-muted">
                                                <?php echo htmlspecialchars(basename($policy['imagem'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Anexo (opcional)</label>
                                <div class="dropzone rounded-3 border border-2 border-dashed p-2 text-center bg-light position-relative"
                                     style="min-height: 60px; cursor: pointer;">
                                    <input type="file"
                                           class="d-none"
                                           id="anexo"
                                           name="anexo"
                                           accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rar"
                                           onchange="previewAnexo(this)">
                                    <label for="anexo"
                                           id="anexo-label"
                                           class="w-100 h-100 d-flex flex-column align-items-center justify-content-center"
                                           style="cursor:pointer;">
                                        <i class="fas fa-paperclip fa-2x mb-2 text-secondary"></i>
                                        <span class="text-muted">Clique para fazer upload ou arraste um arquivo aqui</span>
                                        <span class="small text-muted">Formatos: PDF, DOC(X), TXT, XLS(X), CSV, ZIP, RAR. Máx. 20MB</span>
                                    </label>
                                    <div id="preview-anexo" class="mt-2">
                                        <?php if (!empty($policy['anexo'])): ?>
                                            <i class="fas fa-paperclip fa-2x me-2"></i>
                                            <span class="small text-muted">
                                                <?php echo htmlspecialchars(basename($policy['anexo'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-control, .form-select {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    font-size: 1.05rem;
    min-height: 46px;
}
.form-label {
    color: #3a3a3a;
    font-size: 1.02rem;
}
.dropzone {
    transition: border-color 0.2s, background 0.2s;
}
.dropzone:hover,
.dropzone:focus-within {
    border-color: #0d6efd;
    background: #f3f6ff;
}
.wysiwyg-source {
    display: none;
}
.wysiwyg-wrap {
    margin-top: 0.5rem;
}
.wysiwyg-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
    padding: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 0.5rem;
}
.wysiwyg-btn {
    border: 1px solid #dfe6ef;
    background: #fff;
    border-radius: 10px;
    padding: 0.25rem 0.5rem;
    font-size: 0.9rem;
    cursor: pointer;
}
.wysiwyg-btn:hover {
    background: #eef6ff;
}
.wysiwyg-select {
    border: 1px solid #dfe6ef;
    background: #fff;
    border-radius: 10px;
    padding: 0.25rem 0.5rem;
    font-size: 0.9rem;
}
.wysiwyg-sep {
    width: 1px;
    height: 22px;
    background: #e9ecef;
    margin: 0 0.1rem;
}
.wysiwyg-editor {
    min-height: 220px;
    border: 1px solid #e9ecef;
    background: #fff;
    border-radius: 12px;
    padding: 0.9rem;
    overflow: auto;
}
.wysiwyg-editor:focus {
    outline: none;
    border-color: #0d6efd;
}
.dropzone-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.dropzone-preview img {
    max-width: 64px;
    max-height: 64px;
    border-radius: 8px;
    object-fit: cover;
}
.dropzone-remove-btn {
    position: absolute;
    top: 4px;
    right: 6px;
    border: none;
    background: rgba(0,0,0,0.05);
    border-radius: 999px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
}
.dropzone-remove-btn:hover {
    background: rgba(0,0,0,0.12);
}
.btn-primary {
    background: #0d6efd;
    border: none;
}
.btn-primary:hover {
    background: #0b5ed7;
}
.btn-outline-secondary {
    border: 2px solid #e9ecef;
}
@media (max-width: 991.98px) {
    .card-body form { max-width: 100% !important; }
}
</style>

<script>
document.getElementById('categoria_id')?.addEventListener('change', function () {
    const sel   = this;
    const nome  = sel.options[sel.selectedIndex]?.text || '';
    const hidden = document.getElementById('categoria_nome_hidden');
    if (hidden) hidden.value = nome;
});

function previewImagem(input) {
    const preview = document.getElementById('preview-imagem');
    if (!preview) return;
    preview.innerHTML = '';

    const label = document.getElementById('imagem-label');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            preview.innerHTML =
                `<div class="dropzone-preview">
                    <a href="${dataUrl}" target="_blank" rel="noopener">
                        <img src="${dataUrl}" alt="Pré-visualização">
                    </a>
                    <span class="small text-muted text-truncate" style="max-width: 140px;">${file.name}</span>
                </div>`;
        };
        reader.readAsDataURL(file);
        if (label) {
            label.innerText = file.name;
        }
    }
}

function previewAnexo(input) {
    const preview = document.getElementById('preview-anexo');
    if (!preview) return;
    preview.innerHTML = '';

    const label = document.getElementById('anexo-label');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        let icon = 'fa-file';
        if (['pdf'].includes(ext)) icon = 'fa-file-pdf text-danger';
        else if (['doc','docx'].includes(ext)) icon = 'fa-file-word text-primary';
        else if (['xls','xlsx','csv'].includes(ext)) icon = 'fa-file-excel text-success';
        else if (['txt'].includes(ext)) icon = 'fa-file-alt text-secondary';
        else if (['zip','rar'].includes(ext)) icon = 'fa-file-archive text-warning';

        preview.innerHTML =
            `<div class="dropzone-preview">
                <i class="fas ${icon} fa-2x"></i>
                <span class="small text-muted text-truncate" style="max-width: 160px;">${file.name}</span>
            </div>`;

        if (label) {
            label.innerText = file.name;
        }
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#conteudo',
    base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
    menubar: false,
    branding: false,
    toolbar_location: 'top',
    statusbar: false,
    plugins: 'lists link code',
    toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | outdent indent | removeformat | link | code',
    height: 420,
    language: 'pt_BR',
    language_url: "<?php echo $_ENV['URL_ADM']; ?>public/js/tinymce/langs/pt_BR.js",
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
});

document.querySelector('form')?.addEventListener('submit', function () {
    // Garante que o conteúdo do editor é sincronizado para o textarea antes do POST.
    window.tinymce?.triggerSave();
});
</script>

<script>
(function () {
    const textarea = document.getElementById('conteudo');
    const wrap = document.querySelector('[data-wysiwyg-for="conteudo"]');
    const editor = document.getElementById('conteudo_editor');
    if (!textarea || !wrap || !editor) return;

    function syncToTextarea() {
        textarea.value = editor.innerHTML;
    }

    editor.innerHTML = textarea.value || '';
    editor.addEventListener('input', syncToTextarea);
    syncToTextarea();

    function getSelectionRange() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return null;
        return sel.getRangeAt(0);
    }

    function isRangeInEditor(range) {
        if (!range) return false;
        return editor.contains(range.commonAncestorContainer);
    }    function wrapRangeWithTag(tagName) {
        const range = getSelectionRange();
        if (!range || range.collapsed || !isRangeInEditor(range)) return false;
        try {
            const contents = range.extractContents();
            const wrapper = document.createElement(tagName);
            wrapper.appendChild(contents);
            range.insertNode(wrapper);
            window.getSelection()?.removeAllRanges();
            editor.focus();
            return true;
        } catch (e) {
            return false;
        }
    }

    function replaceRangeWithList(ordered) {
        const range = getSelectionRange();
        if (!range || range.collapsed || !isRangeInEditor(range)) return false;
        try {
            const text = range.toString();
            const lines = text
                .split(/\r?\n/)
                .map(l => l.trim())
                .filter(Boolean);
            if (lines.length === 0) return false;

            const listEl = document.createElement(ordered ? 'ol' : 'ul');
            for (const line of lines) {
                const li = document.createElement('li');
                li.textContent = line;
                listEl.appendChild(li);
            }

            range.deleteContents();
            range.insertNode(listEl);
            window.getSelection()?.removeAllRanges();
            editor.focus();
            return true;
        } catch (e) {
            return false;
        }
    }    wrap.addEventListener('click', function (e) {
        const btn = e.target.closest('button.wysiwyg-btn');
        if (!btn) return;
        e.preventDefault();

        const cmd = btn.getAttribute('data-cmd');
        const action = btn.getAttribute('data-action');

        if (cmd) {
            const inlineMap = {
                bold: 'strong',
                italic: 'em',
                underline: 'u',
                strikeThrough: 's',
            };

            if (inlineMap[cmd]) {
                const ok = wrapRangeWithTag(inlineMap[cmd]);
                if (ok) {
                    syncToTextarea();
                    return;
                }
            }

            if (cmd === 'insertUnorderedList') {
                const ok = replaceRangeWithList(false);
                if (ok) {
                    syncToTextarea();
                    return;
                }
            }
            if (cmd === 'insertOrderedList') {
                const ok = replaceRangeWithList(true);
                if (ok) {
                    syncToTextarea();
                    return;
                }
            }

            editor.focus();
            document.execCommand(cmd, false, null);
            syncToTextarea();
            return;
        }

        if (action === 'link') {
            const url = window.prompt('Informe a URL (ex.: https://...):');
            if (!url) return;
            const range = getSelectionRange();
            if (range && !range.collapsed && isRangeInEditor(range)) {
                try {
                    const contents = range.extractContents();
                    const a = document.createElement('a');
                    a.href = url;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    a.appendChild(contents);
                    range.insertNode(a);
                    window.getSelection()?.removeAllRanges();
                    editor.focus();
                    syncToTextarea();
                    return;
                } catch (err) {
                    // fallback abaixo
                }
            }
            editor.focus();
            document.execCommand('createLink', false, url);
            syncToTextarea();
            return;
        }

        if (action === 'code') {
            const range = getSelectionRange();
            if (range && !range.collapsed && isRangeInEditor(range)) {
                try {
                    const contents = range.extractContents();
                    const codeEl = document.createElement('code');
                    codeEl.appendChild(contents);
                    range.insertNode(codeEl);
                    window.getSelection()?.removeAllRanges();
                    editor.focus();
                    syncToTextarea();
                    return;
                } catch (err) {
                    // fallback abaixo
                }
            }
            editor.focus();
            document.execCommand('insertHTML', false, '<code></code>');
            syncToTextarea();
            return;
        }

        if (action === 'fullscreen') {
            const isFull = document.body.classList.contains('wysiwyg-fullscreen');
            if (isFull) {
                document.body.classList.remove('wysiwyg-fullscreen');
                document.body.style.overflow = '';
                editor.style.maxHeight = '';
            } else {
                document.body.classList.add('wysiwyg-fullscreen');
                document.body.style.overflow = 'hidden';
                editor.style.maxHeight = '80vh';
            }
            editor.focus();
            return;
        }
    });

    const formatSelect = wrap.querySelector('select[data-action="formatselect"]');
    if (formatSelect) {
        formatSelect.addEventListener('change', function () {
            editor.focus();
            document.execCommand('formatBlock', false, this.value.toUpperCase());
            syncToTextarea();
        });
    }
})();
</script>

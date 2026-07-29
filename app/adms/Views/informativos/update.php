<?php
use App\adms\Helpers\CSRFHelper;
$informativo = $this->data['informativo'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Editar Informativo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none">Informativos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Informativo</h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('update_informativo'); ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3" style="margin-bottom: 2.5rem !important;">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255" 
                                   value="<?php echo \App\adms\Helpers\TextEncodingHelper::escape($informativo['titulo'] ?? ''); ?>" 
                                   placeholder="Digite o título do informativo">
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label for="conteudo" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg rounded-3" id="conteudo" name="conteudo" rows="10" 
                                      placeholder="Digite o conteúdo do informativo"><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', \App\adms\Helpers\TextEncodingHelper::decodeEntities($informativo['conteudo'] ?? '')); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3 mt-2">
                            <label for="categoria_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                    <?php $selected = ((int)($informativo['categoria_id'] ?? 0) === (int)$categoria['id']) ? 'selected' : ''; ?>
                                    <option value="<?= (int)$categoria['id'] ?>" <?= $selected ?>>
                                        <?= \App\adms\Helpers\TextEncodingHelper::escape($categoria['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="categoria" id="categoria_nome_hidden" value="<?= \App\adms\Helpers\TextEncodingHelper::escape($informativo['categoria'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <?php $deptLocked = !empty($this->data['department_select_locked']); ?>
                            <label for="department_id" class="form-label">Departamento (publicante) <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required <?= $deptLocked ? 'disabled' : '' ?>>
                                <?php if (!$deptLocked): ?>
                                    <option value="">Selecione o departamento</option>
                                <?php endif; ?>
                                <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                    <?php $selected = ((int)($informativo['department_id'] ?? 0) === (int)$dep['id']) ? 'selected' : ''; ?>
                                    <option value="<?= (int)$dep['id'] ?>" <?= $selected ?>><?= \App\adms\Helpers\TextEncodingHelper::escape($dep['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($deptLocked && !empty($informativo['department_id'])): ?>
                                <input type="hidden" name="department_id" value="<?= (int)$informativo['department_id'] ?>">
                            <?php endif; ?>
                            <?php if ($deptLocked): ?>
                                <div class="form-text">Somente perfis com permissão total podem alterar o departamento publicante.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 row g-2">
                            <?php
                            $pubDate = !empty($informativo['publish_at']) ? date('Y-m-d', strtotime((string) $informativo['publish_at'])) : '';
                            $pubTime = !empty($informativo['publish_at']) ? date('H:i', strtotime((string) $informativo['publish_at'])) : '';
                            $expDate = !empty($informativo['expire_at']) ? date('Y-m-d', strtotime((string) $informativo['expire_at'])) : '';
                            $expTime = !empty($informativo['expire_at']) ? date('H:i', strtotime((string) $informativo['expire_at'])) : '';
                            ?>
                            <div class="col-md-6">
                                <label class="form-label">Publicar em</label>
                                <div class="row g-2 align-items-center">
                                    <div class="col-7 col-sm-8">
                                        <input type="date"
                                               class="form-control"
                                               id="publish_at_date"
                                               name="publish_at_date"
                                               value="<?= htmlspecialchars($pubDate, ENT_QUOTES, 'UTF-8') ?>"
                                               aria-label="Data de publicação">
                                    </div>
                                    <div class="col-5 col-sm-4">
                                        <input type="time"
                                               class="form-control"
                                               id="publish_at_time"
                                               name="publish_at_time"
                                               step="60"
                                               value="<?= htmlspecialchars($pubTime, ENT_QUOTES, 'UTF-8') ?>"
                                               aria-label="Hora de publicação">
                                    </div>
                                </div>
                                <div class="form-text">Data e horário. Vazio = imediato. Sem hora = 00:00.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expira em</label>
                                <div class="row g-2 align-items-center">
                                    <div class="col-7 col-sm-8">
                                        <input type="date"
                                               class="form-control"
                                               id="expire_at_date"
                                               name="expire_at_date"
                                               value="<?= htmlspecialchars($expDate, ENT_QUOTES, 'UTF-8') ?>"
                                               aria-label="Data de expiração">
                                    </div>
                                    <div class="col-5 col-sm-4">
                                        <input type="time"
                                               class="form-control"
                                               id="expire_at_time"
                                               name="expire_at_time"
                                               step="60"
                                               value="<?= htmlspecialchars($expTime, ENT_QUOTES, 'UTF-8') ?>"
                                               aria-label="Hora de expiração">
                                    </div>
                                </div>
                                <div class="form-text">Data e horário. Vazio = não expira. Sem hora = 23:59.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagem" class="form-label">Imagem</label>
                            <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Máximo 5MB.</div>
                            
                            <?php if (!empty($informativo['imagem'])): ?>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <div>
                                        <small class="text-muted">Imagem atual:</small>
                                        <div class="mt-1">
                                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['imagem']); ?>" 
                                                 class="img-thumbnail" style="max-width: 150px; max-height: 150px;" alt="Imagem atual">
                                        </div>
                                    </div>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>remove-informativo-imagem/<?php echo $informativo['id']; ?>"
                                       class="btn btn-outline-danger btn-sm ms-2"
                                       onclick="return confirm('Deseja realmente remover a imagem?');"
                                       title="Remover imagem">
                                       <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="anexo" class="form-label">Anexo</label>
                            <input type="file" class="form-control" id="anexo" name="anexo" accept=".pdf,.doc,.docx,.txt">
                            <div class="form-text">Formatos aceitos: PDF, DOC, DOCX, TXT. Máximo 5MB.</div>
                            
                            <?php if (!empty($informativo['anexo'])): ?>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <div>
                                        <small class="text-muted">Anexo atual:</small>
                                        <div class="mt-1">
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($informativo['anexo']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-paperclip me-1"></i>
                                                <?php echo pathinfo($informativo['anexo'], PATHINFO_FILENAME); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>remove-informativo-anexo/<?php echo $informativo['id']; ?>"
                                       class="btn btn-outline-danger btn-sm ms-2"
                                       onclick="return confirm('Deseja realmente remover o anexo?');"
                                       title="Remover anexo">
                                       <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="urgente" name="urgente" 
                                       <?= $informativo['urgente'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="urgente">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>Marcar como urgente
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requires_ack" name="requires_ack" 
                                       <?= !empty($informativo['requires_ack']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="requires_ack">
                                    Exigir ciência do usuário
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                       <?= $informativo['ativo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativo">
                                    <i class="fas fa-check text-success me-1"></i>Ativo
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="notificar" name="notificar"
                                       <?= !empty($informativo['notificar']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notificar">
                                    Enviar notificação via WhatsApp
                                </label>
                            </div>
                            <label class="form-label small mb-1">Departamentos a notificar (opcional)</label>
                            <div class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $notifyDeps = $this->data['notify_departments_ids'] ?? [];
                                foreach (($this->data['all_departments_for_notify'] ?? $this->data['departments'] ?? []) as $dep):
                                    $checked = in_array((int)$dep['id'], $notifyDeps, true) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notify_departments[]" value="<?= (int)$dep['id']; ?>" id="notify_dep_<?= (int)$dep['id']; ?>" <?= $checked; ?>>
                                        <label class="form-check-label" for="notify_dep_<?= (int)$dep['id']; ?>"><?= htmlspecialchars($dep['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text small mt-1">
                                Marque os setores que devem receber a notificação. Nenhum marcado = todos recebem.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Atualizar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo $informativo['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-eye me-2"></i>Visualizar
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos"
                       class="btn btn-outline-secondary d-none d-md-inline-block">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="button"
                            class="btn btn-outline-secondary d-inline d-md-none"
                            onclick="window.history.back();">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview da nova imagem
document.getElementById('imagem').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Remover preview anterior se existir
            const existingPreview = document.getElementById('image-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Criar novo preview
            const preview = document.createElement('div');
            preview.id = 'image-preview';
            preview.className = 'mt-2';
            preview.innerHTML = `
                <small class="text-muted">Nova imagem:</small>
                <div class="mt-1">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-width: 150px; max-height: 150px;" alt="Preview">
                </div>
            `;
            
            document.getElementById('imagem').parentNode.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
});

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    const titulo = document.getElementById('titulo').value.trim();
    // Garante que o TinyMCE sincronize o conteúdo para o textarea antes de validar
    if (window.tinymce) {
        tinymce.triggerSave();
    }
    const conteudo = document.getElementById('conteudo').value.trim();
    const categoria = document.getElementById('categoria_id').value;
    
    if (!titulo) {
        e.preventDefault();
        alert('O título é obrigatório!');
        document.getElementById('titulo').focus();
        return false;
    }
    
    if (!conteudo) {
        e.preventDefault();
        alert('O conteúdo é obrigatório!');
        document.getElementById('conteudo').focus();
        return false;
    }
    
    if (!categoria) {
        e.preventDefault();
        alert('A categoria é obrigatória!');
        document.getElementById('categoria_id').focus();
        return false;
    }
    const depSel = document.getElementById('department_id').value;
    if (!depSel) {
        e.preventDefault();
        alert('O departamento é obrigatório!');
        document.getElementById('department_id').focus();
        return false;
    }
});

// Preencher campo legado com nome da categoria
document.getElementById('categoria_id')?.addEventListener('change', function(){
  const sel = this;
  const nome = sel.options[sel.selectedIndex]?.text || '';
  const hidden = document.getElementById('categoria_nome_hidden');
  if (hidden) hidden.value = nome;
});
</script> 

<style>
.wysiwyg-source { display: none; }
.wysiwyg-wrap { margin-top: 0.5rem; }
.wysiwyg-toolbar {
    display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center;
    padding: 0.5rem; background: #f8fafc; border: 1px solid #e9ecef; border-radius: 12px;
    margin-bottom: 0.5rem;
}
.wysiwyg-btn {
    border: 1px solid #dfe6ef; background: #fff; border-radius: 10px;
    padding: 0.25rem 0.5rem; font-size: 0.9rem; cursor: pointer;
}
.wysiwyg-btn:hover { background: #eef6ff; }
.wysiwyg-select {
    border: 1px solid #dfe6ef; background: #fff; border-radius: 10px;
    padding: 0.25rem 0.5rem; font-size: 0.9rem;
}
.wysiwyg-sep { width: 1px; height: 22px; background: #e9ecef; margin: 0 0.1rem; }
.wysiwyg-editor {
    min-height: 220px; border: 1px solid #e9ecef; background: #fff; border-radius: 12px;
    padding: 0.9rem; overflow: auto;
}
.wysiwyg-editor:focus { outline: none; border-color: #198754; }
</style>

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
    }

    function wrapRangeWithTag(tagName) {
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
    }

    wrap.addEventListener('click', function (e) {
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
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    // Evita href relativo (ex.: vagas-internas/1) que quebra em /view-informativo/{id}
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false,
    document_base_url: "<?php echo $_ENV['URL_ADM']; ?>"
});
</script>
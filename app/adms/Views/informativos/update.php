<?php
use App\adms\Helpers\CSRFHelper;
$informativo = $this->data['informativo'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Informativo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
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
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255" 
                                   value="<?php echo htmlspecialchars($informativo['titulo']); ?>" 
                                   placeholder="Digite o título do informativo">
                        </div>
                        
                        <div class="mb-3">
                            <label for="conteudo" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="conteudo" name="conteudo" rows="10" required 
                                      placeholder="Digite o conteúdo do informativo"><?php echo htmlspecialchars($informativo['conteudo']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                    <?php $selected = ((int)($informativo['categoria_id'] ?? 0) === (int)$categoria['id']) ? 'selected' : ''; ?>
                                    <option value="<?= (int)$categoria['id'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($categoria['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="categoria" id="categoria_nome_hidden" value="<?= htmlspecialchars($informativo['categoria'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="department_id" class="form-label">Departamento (publicante) <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Selecione o departamento</option>
                                <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                    <?php $selected = ((int)($informativo['department_id'] ?? 0) === (int)$dep['id']) ? 'selected' : ''; ?>
                                    <option value="<?= (int)$dep['id'] ?>" <?= $selected ?>><?= htmlspecialchars($dep['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 row g-2">
                            <div class="col-md-6">
                                <label for="publish_at" class="form-label">Publicar em</label>
                                <input type="datetime-local" class="form-control" id="publish_at" name="publish_at" value="<?= !empty($informativo['publish_at']) ? date('Y-m-d\TH:i', strtotime($informativo['publish_at'])) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="expire_at" class="form-label">Expira em</label>
                                <input type="datetime-local" class="form-control" id="expire_at" name="expire_at" value="<?= !empty($informativo['expire_at']) ? date('Y-m-d\TH:i', strtotime($informativo['expire_at'])) : '' ?>">
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
                                foreach (($this->data['departments'] ?? []) as $dep):
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
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
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
<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-1 d-flex flex-column flex-md-row align-items-md-center gap-2">
                <h2 class="mt-3 text-success fw-bold mb-0" style="font-size: 1.7rem; letter-spacing: -1px;">🟢 Publicar Novo Comunicado</h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none">Informativos</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Novo</li>
                    </ol>
                </nav>
            </div>
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 18px; background: #fff;">
                <div class="card-body p-4">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    <form method="POST" enctype="multipart/form-data" style="max-width: 700px; margin: 0 auto;">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('create_informativo'); ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label fw-semibold">Título *</label>
                                <input type="text" class="form-control form-control-lg rounded-3" id="titulo" name="titulo" required maxlength="255" placeholder="Digite o título do comunicado">
                            </div>
                            <div class="col-md-6">
                                <label for="categoria_id" class="form-label fw-semibold">Categoria *</label>
                                <select class="form-select form-select-lg rounded-3" id="categoria_id" name="categoria_id" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach (($this->data['categorias'] ?? []) as $categoria): ?>
                                        <option value="<?= (int)$categoria['id'] ?>"><?= htmlspecialchars($categoria['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="categoria" id="categoria_nome_hidden">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-semibold">Departamento (publicante) *</label>
                                <select class="form-select form-select-lg rounded-3" id="department_id" name="department_id" required>
                                    <option value="">Selecione o departamento</option>
                                    <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                                        <option value="<?= (int)$dep['id'] ?>"><?= htmlspecialchars($dep['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="publish_at" class="form-label fw-semibold">Publicar em</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="publish_at" name="publish_at">
                            </div>
                            <div class="col-md-3">
                                <label for="expire_at" class="form-label fw-semibold">Expira em</label>
                                <input type="datetime-local" class="form-control form-control-lg rounded-3" id="expire_at" name="expire_at">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="conteudo" class="form-label fw-semibold">Conteúdo *</label>
                            <textarea class="form-control form-control-lg rounded-3" id="conteudo" name="conteudo" rows="6" required placeholder="Digite o conteúdo do comunicado... (Suporte a Markdown disponível)"></textarea>
                            <div class="form-text">Dica: Use <b>**negrito**</b>, <i>*itálico*</i>, <code>`código`</code> e outros formatos Markdown</div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Imagem</label>
                                <div class="dropzone rounded-3 border border-2 border-dashed p-4 text-center bg-light position-relative" style="min-height: 120px; cursor: pointer;">
                                    <input type="file" class="d-none" id="imagem" name="imagem" accept=".png,.jpg,.jpeg,.gif" onchange="previewImagem(this)">
                                    <label for="imagem" id="imagem-label" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="cursor:pointer;">
                                        <i class="fas fa-image fa-2x mb-2 text-secondary"></i>
                                        <span class="text-muted">Clique para fazer upload ou arraste uma imagem aqui</span>
                                        <span class="small text-muted">Formatos aceitos: PNG, JPG, JPEG, GIF. Máx. 10MB</span>
                                    </label>
                                    <div id="preview-imagem" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Anexo</label>
                                <div class="dropzone rounded-3 border border-2 border-dashed p-4 text-center bg-light position-relative" style="min-height: 120px; cursor: pointer;">
                                    <input type="file" class="d-none" id="anexo" name="anexo" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rar" onchange="previewAnexo(this)">
                                    <label for="anexo" id="anexo-label" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="cursor:pointer;">
                                        <i class="fas fa-paperclip fa-2x mb-2 text-secondary"></i>
                                        <span class="text-muted">Clique para fazer upload ou arraste um arquivo aqui</span>
                                        <span class="small text-muted">Formatos aceitos: PDF, DOC, DOCX, TXT, XLS, XLSX, CSV, ZIP, RAR. Máx. 10MB</span>
                                    </label>
                                    <div id="preview-anexo" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="urgente" name="urgente">
                                    <label class="form-check-label fw-semibold text-danger" for="urgente">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_ack" name="requires_ack">
                                    <label class="form-check-label fw-semibold" for="requires_ack">
                                        Exigir ciência do usuário
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" checked>
                                    <label class="form-check-label fw-semibold text-success" for="ativo">
                                        <i class="fas fa-check me-1"></i>Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-secondary btn-lg rounded-3 px-4" onclick="window.location.href='<?php echo $_ENV['URL_ADM']; ?>list-informativos'">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-success btn-lg rounded-3 px-4 fw-bold">
                                Publicar Comunicado
                            </button>
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
    font-size: 1.08rem;
    min-height: 48px;
}
.form-label {
    color: #3a3a3a;
    font-size: 1.05rem;
}
.dropzone {
    transition: border-color 0.2s;
}
.dropzone:hover, .dropzone:focus-within {
    border-color: #198754;
    background: #f1fdf6;
}
.btn-success {
    background: #16c172;
    border: none;
}
.btn-success:hover {
    background: #119e5e;
}
.btn-outline-secondary {
    border: 2px solid #e9ecef;
}
@media (max-width: 991.98px) {
    .card-body form { max-width: 100% !important; }
}
</style> 
<script>
// Manter campo legado com o nome da categoria selecionada
document.getElementById('categoria_id')?.addEventListener('change', function(){
  const sel = this;
  const nome = sel.options[sel.selectedIndex]?.text || '';
  const hidden = document.getElementById('categoria_nome_hidden');
  if (hidden) hidden.value = nome;
});
function previewImagem(input) {
    const preview = document.getElementById('preview-imagem');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Pré-visualização" style="max-width: 100px; max-height: 100px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);"> <div class='small mt-1 text-muted'>${file.name}</div>`;
        };
        reader.readAsDataURL(file);
        document.getElementById('imagem-label').innerText = file.name;
    } else {
        document.getElementById('imagem-label').innerText = 'Clique para fazer upload ou arraste uma imagem aqui';
    }
}
function previewAnexo(input) {
    const preview = document.getElementById('preview-anexo');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        let icon = 'fa-file';
        if(['pdf'].includes(ext)) icon = 'fa-file-pdf text-danger';
        else if(['doc','docx'].includes(ext)) icon = 'fa-file-word text-primary';
        else if(['xls','xlsx','csv'].includes(ext)) icon = 'fa-file-excel text-success';
        else if(['txt'].includes(ext)) icon = 'fa-file-alt text-secondary';
        else if(['zip','rar'].includes(ext)) icon = 'fa-file-archive text-warning';
        preview.innerHTML = `<i class="fas ${icon} fa-2x me-2"></i> <span class='small text-muted'>${file.name}</span>`;
        document.getElementById('anexo-label').innerText = file.name;
    } else {
        document.getElementById('anexo-label').innerText = 'Clique para fazer upload ou arraste um arquivo aqui';
    }
}
</script> 
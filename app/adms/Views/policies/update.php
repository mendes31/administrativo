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
                <h2 class="mt-3 text-primary fw-bold mb-0" style="font-size: 1.7rem; letter-spacing: -1px;">
                    Editar Política Interna
                </h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mt-2 mt-md-0">
                    <ol class="breadcrumb mb-0">
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
                                      required
                                      placeholder="Descreva a política interna em detalhes..."><?php echo htmlspecialchars($policy['conteudo'] ?? ''); ?></textarea>
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

                        <div class="form-sticky-footer d-flex gap-2 justify-content-end mt-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int) ($policy['id'] ?? 0); ?>"
                               class="btn btn-outline-secondary btn-lg rounded-3 px-4">
                                Voltar
                            </a>

                            <button type="submit"
                                    class="btn btn-primary btn-lg rounded-3 px-4 fw-bold">
                                Salvar Alterações
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

.form-sticky-footer {
    position: sticky;
    bottom: 0;
    z-index: 5;
    background: #fff;
    padding-top: 0.75rem;
    padding-bottom: 0.5rem;
    border-top: 1px solid #e9ecef;
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


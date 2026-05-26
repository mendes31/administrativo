<?php

use App\adms\Helpers\CSRFHelper;

$isEdit = !empty($this->data['ticket']);
$ticket = $this->data['ticket'] ?? [];
$form = $this->data['form'] ?? $ticket;

$pageTitle = $isEdit ? 'Editar Chamado' : 'Novo Chamado';
$formAction = $isEdit
    ? $_ENV['URL_ADM'] . 'sac-update-ticket/' . ($ticket['id'] ?? '')
    : $_ENV['URL_ADM'] . 'sac-create-ticket';

$statuses = ['Aberto', 'Em análise', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];
$priorities = ['Baixa', 'Média', 'Alta', 'Urgente'];
$channels = ['WhatsApp', 'E-mail', 'Telefone', 'Portal'];

$preSelectedClient = $form['client_id'] ?? ($_GET['client_id'] ?? '');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-ticket-alt me-2"></i><?= $pageTitle ?></h2>

        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-tickets" class="text-decoration-none">Chamados</a></li>
            <li class="breadcrumb-item"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?= $pageTitle ?></span>
            <span class="ms-auto d-sm-flex flex-row">
                <?php if (in_array('SacListTickets', $this->data['buttonPermission'])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>sac-list-tickets" class="btn btn-info btn-sm me-1 mb-1"><i class="fa-solid fa-list-ul"></i> Listar</a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="<?= $formAction ?>" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('sac_ticket_form') ?>">

                <div class="col-md-8">
                    <label for="subject" class="form-label">Assunto <span class="text-danger">*</span></label>
                    <input type="text" name="subject" id="subject" class="form-control" required value="<?= htmlspecialchars($form['subject'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label for="client_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select name="client_id" id="client_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($this->data['clients'] ?? [] as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $preSelectedClient == $client['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['razao_social'] ?? $client['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="product" class="form-label">Produto</label>
                    <input type="text" name="product" id="product" class="form-control" placeholder="Nome do produto" value="<?= htmlspecialchars($form['product'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="batch" class="form-label">Lote</label>
                    <input type="text" name="batch" id="batch" class="form-control" placeholder="Nº do lote" value="<?= htmlspecialchars($form['batch'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label for="category_id" class="form-label">Categoria</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($this->data['categories'] ?? [] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($form['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="priority" class="form-label">Prioridade</label>
                    <select name="priority" id="priority" class="form-select">
                        <?php foreach ($priorities as $p): ?>
                            <option value="<?= $p ?>" <?= ($form['priority'] ?? 'Média') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="channel" class="form-label">Canal</label>
                    <select name="channel" id="channel" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($channels as $ch): ?>
                            <option value="<?= $ch ?>" <?= ($form['channel'] ?? '') === $ch ? 'selected' : '' ?>><?= $ch ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="assigned_user_id" class="form-label">Atendente</label>
                    <select name="assigned_user_id" id="assigned_user_id" class="form-select">
                        <option value="">Nenhum</option>
                        <?php foreach ($this->data['users'] ?? [] as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($form['assigned_user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="department_id" class="form-label">Departamento</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($this->data['departments'] ?? [] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($form['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isEdit): ?>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st ?>" <?= ($form['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="6" required><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>

                <?php if ($isEdit && !empty($this->data['attachments'])): ?>
                    <div class="col-12">
                        <label class="form-label fw-semibold"><i class="fas fa-paperclip me-1"></i>Anexos existentes</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Arquivo</th>
                                        <th style="width:100px">Tamanho</th>
                                        <th style="width:140px">Data</th>
                                        <th style="width:60px" class="text-center">Excluir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['attachments'] as $att): ?>
                                        <?php
                                        $ext = strtolower(pathinfo($att['file_name'] ?? '', PATHINFO_EXTENSION));
                                        $iconMap = [
                                            'pdf' => 'fa-file-pdf text-danger',
                                            'doc' => 'fa-file-word text-primary', 'docx' => 'fa-file-word text-primary',
                                            'xls' => 'fa-file-excel text-success', 'xlsx' => 'fa-file-excel text-success', 'csv' => 'fa-file-csv text-success',
                                            'jpg' => 'fa-file-image text-info', 'jpeg' => 'fa-file-image text-info', 'png' => 'fa-file-image text-info', 'gif' => 'fa-file-image text-info', 'webp' => 'fa-file-image text-info',
                                            'zip' => 'fa-file-archive text-warning', 'rar' => 'fa-file-archive text-warning',
                                        ];
                                        $icon = $iconMap[$ext] ?? 'fa-file text-secondary';
                                        $size = (int)($att['file_size'] ?? 0);
                                        $sizeLabel = $size > 1048576 ? number_format($size / 1048576, 1) . ' MB' : number_format($size / 1024, 1) . ' KB';
                                        ?>
                                        <tr>
                                            <td class="text-center"><i class="fas <?= $icon ?> fa-lg"></i></td>
                                            <td>
                                                <a href="<?= $_ENV['URL_ADM'] ?>../<?= htmlspecialchars($att['file_path'] ?? '') ?>" target="_blank" class="text-decoration-none">
                                                    <?= htmlspecialchars($att['file_name'] ?? 'Arquivo') ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?= $sizeLabel ?></td>
                                            <td class="small text-muted"><?= !empty($att['created_at']) ? date('d/m/Y H:i', strtotime($att['created_at'])) : '' ?></td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input type="checkbox" name="delete_attachments[]" value="<?= $att['id'] ?>" class="form-check-input" title="Marcar para excluir">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Marque os anexos que deseja excluir e clique em Atualizar.</small>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label fw-semibold"><i class="fas fa-upload me-1"></i>Adicionar Anexos</label>
                    <div id="sacDropZone" class="border border-2 border-dashed rounded p-4 text-center bg-light" style="cursor:pointer; min-height:120px; transition: all .2s ease;">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2 d-block"></i>
                        <p class="mb-1 text-muted">Arraste arquivos aqui ou <span class="text-primary fw-semibold">clique para selecionar</span></p>
                        <small class="text-muted">Fotos, PDFs, Documentos, Planilhas (máx. 10 MB por arquivo)</small>
                        <input type="file" name="attachments[]" id="sacFileInput" class="d-none" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar,.ppt,.pptx">
                    </div>
                    <div id="sacFileList" class="mt-2"></div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                </div>
            </form>

            <script>
            (function() {
                const dropZone = document.getElementById('sacDropZone');
                const fileInput = document.getElementById('sacFileInput');
                const fileList = document.getElementById('sacFileList');
                const maxSize = 10 * 1024 * 1024;
                let dt = new DataTransfer();

                dropZone.addEventListener('click', () => fileInput.click());

                dropZone.addEventListener('dragover', e => {
                    e.preventDefault();
                    dropZone.classList.add('border-primary', 'bg-white');
                });
                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('border-primary', 'bg-white');
                });
                dropZone.addEventListener('drop', e => {
                    e.preventDefault();
                    dropZone.classList.remove('border-primary', 'bg-white');
                    addFiles(e.dataTransfer.files);
                });

                fileInput.addEventListener('change', () => {
                    addFiles(fileInput.files);
                });

                function addFiles(files) {
                    for (const f of files) {
                        if (f.size > maxSize) {
                            alert('Arquivo "' + f.name + '" excede o limite de 10 MB.');
                            continue;
                        }
                        dt.items.add(f);
                    }
                    fileInput.files = dt.files;
                    renderList();
                }

                function removeFile(idx) {
                    dt.items.remove(idx);
                    fileInput.files = dt.files;
                    renderList();
                }

                function renderList() {
                    if (dt.files.length === 0) { fileList.innerHTML = ''; return; }

                    const iconMap = {
                        'pdf': 'fa-file-pdf text-danger',
                        'doc': 'fa-file-word text-primary', 'docx': 'fa-file-word text-primary',
                        'xls': 'fa-file-excel text-success', 'xlsx': 'fa-file-excel text-success', 'csv': 'fa-file-csv text-success',
                        'jpg': 'fa-file-image text-info', 'jpeg': 'fa-file-image text-info', 'png': 'fa-file-image text-info',
                        'gif': 'fa-file-image text-info', 'webp': 'fa-file-image text-info',
                        'zip': 'fa-file-archive text-warning', 'rar': 'fa-file-archive text-warning',
                        'ppt': 'fa-file-powerpoint text-danger', 'pptx': 'fa-file-powerpoint text-danger',
                        'txt': 'fa-file-alt text-secondary',
                    };

                    let html = '<div class="list-group list-group-flush">';
                    for (let i = 0; i < dt.files.length; i++) {
                        const f = dt.files[i];
                        const ext = f.name.split('.').pop().toLowerCase();
                        const icon = iconMap[ext] || 'fa-file text-secondary';
                        const size = f.size > 1048576
                            ? (f.size / 1048576).toFixed(1) + ' MB'
                            : (f.size / 1024).toFixed(1) + ' KB';

                        const isImg = ['jpg','jpeg','png','gif','webp'].includes(ext);
                        let thumb = '';
                        if (isImg) {
                            thumb = '<img src="' + URL.createObjectURL(f) + '" class="rounded me-2" style="width:40px;height:40px;object-fit:cover;" alt="">';
                        } else {
                            thumb = '<i class="fas ' + icon + ' fa-lg me-2"></i>';
                        }

                        html += '<div class="list-group-item d-flex align-items-center py-2 px-3">'
                            + thumb
                            + '<div class="flex-grow-1"><span class="small fw-semibold">' + f.name + '</span><br><small class="text-muted">' + size + '</small></div>'
                            + '<button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="window.__sacRemoveFile(' + i + ')" title="Remover"><i class="fas fa-times"></i></button>'
                            + '</div>';
                    }
                    html += '</div>';
                    fileList.innerHTML = html;
                }

                window.__sacRemoveFile = removeFile;
            })();
            </script>

        </div>
    </div>
</div>

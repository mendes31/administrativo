<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'] ?? [];
$nc = $this->data['nc'] ?? [];
$anexos = $this->data['anexos'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamento_acao_corretiva');
$ncId = (int) ($item['adms_sst_equipamento_nao_conformidade_id'] ?? $nc['id'] ?? 0);
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$action = $isEdit
    ? 'sst-update-equipamento-acao-corretiva/' . (int) $item['id']
    : 'sst-create-equipamento-acao-corretiva';
$statusOptions = ['Pendente', 'Em andamento', 'Concluído', 'Cancelado'];
?>
<div class="container-fluid px-3 px-md-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0"><i class="fas fa-tasks me-2"></i><?= $isEdit ? 'Editar' : 'Nova' ?> ação corretiva</h2>
        <ol class="breadcrumb mb-0 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>sst-view-equipamento-nao-conformidade/<?= $ncId ?>"><?= htmlspecialchars($nc['codigo'] ?? 'NC') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar AC' : 'Nova AC' ?></li>
        </ol>
    </div>
    <div class="alert alert-light border small mb-3">
        Vinculada à <?= htmlspecialchars($nc['codigo'] ?? 'NC') ?>
        <?php if (!empty($nc['descricao'])): ?> — <?= htmlspecialchars($nc['descricao']) ?><?php endif; ?>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= htmlspecialchars($urlAdm . $action) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="adms_sst_equipamento_nao_conformidade_id" value="<?= $ncId ?>">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="titulo">Título *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" required maxlength="255"
                               value="<?= htmlspecialchars($item['titulo'] ?? '') ?>"
                               placeholder="Ex.: Substituir extintor / reparar casco">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= $opt ?>" <?= (($item['status'] ?? 'Pendente') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição da ação</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="responsavel_adms_user_id">Responsável</label>
                        <select name="responsavel_adms_user_id" id="responsavel_adms_user_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= ((int) ($item['responsavel_adms_user_id'] ?? 0) === (int) $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name'] ?? '') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prazo">Prazo</label>
                        <input type="date" name="prazo" id="prazo" class="form-control" value="<?= htmlspecialchars($item['prazo'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_conclusao">Data conclusão</label>
                        <input type="date" name="data_conclusao" id="data_conclusao" class="form-control" value="<?= htmlspecialchars($item['data_conclusao'] ?? '') ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isEdit && $anexos !== []): ?>
                    <div class="col-12 mb-3">
                        <label class="form-label">Evidências atuais</label>
                        <?php foreach ($anexos as $anexo): ?>
                        <div class="form-check border rounded px-2 py-1 mb-1">
                            <input class="form-check-input" type="checkbox" name="delete_anexos[]" value="<?= (int) $anexo['id'] ?>" id="del_ac_<?= (int) $anexo['id'] ?>">
                            <label class="form-check-label small" for="del_ac_<?= (int) $anexo['id'] ?>">
                                Excluir —
                                <a href="<?= htmlspecialchars($urlAdm) ?>sst-view-anexo/<?= (int) $anexo['id'] ?>" target="_blank">
                                    <?= htmlspecialchars($anexo['file_name'] ?? '') ?>
                                </a>
                                (<?= !empty($anexo['created_at']) ? date('d/m/Y H:i', strtotime((string) $anexo['created_at'])) : '' ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="fotos">Fotos de evidência</label>
                        <input type="file" name="fotos[]" id="fotos" class="form-control" accept="image/*" capture="environment" multiple>
                        <div class="form-text">Salvas com data e hora. Use ao concluir a ação (antes/depois da correção).</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= htmlspecialchars($urlAdm) ?>sst-view-equipamento-nao-conformidade/<?= $ncId ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

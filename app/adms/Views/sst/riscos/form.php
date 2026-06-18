<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoGrupoHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_riscos_form');
$action = $isEdit ? 'sst-update-risco/' . (int)$item['id'] : 'sst-create-risco';
$grupoSelecionado = $item['grupo_risco'] ?? $item['tipo'] ?? '';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-exclamation-triangle me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Risco Ocupacional</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-riscos">Riscos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h6 class="text-muted text-uppercase small mb-3">Dados básicos</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="codigo">Código interno</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" maxlength="20"
                               value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" placeholder="RIS001">
                        <div class="form-text">Identificação única no catálogo.</div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="grupo_risco">Grupo de risco *</label>
                        <select name="grupo_risco" id="grupo_risco" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (SstRiscoGrupoHelper::all() as $grupo): ?>
                                <option value="<?= htmlspecialchars($grupo) ?>" <?= ($grupoSelecionado === $grupo) ? 'selected' : '' ?>><?= htmlspecialchars($grupo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="text-muted text-uppercase small mb-3">Controle SST</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="necessita_monitoramento_medico" id="necessita_monitoramento_medico"
                                   class="form-check-input" value="1" <?= !empty($item['necessita_monitoramento_medico']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="necessita_monitoramento_medico">Necessita monitoramento médico (PCMSO)</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="necessita_epi" id="necessita_epi"
                                   class="form-check-input" value="1" <?= !empty($item['necessita_epi']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="necessita_epi">Necessita EPI</label>
                        </div>
                    </div>
                </div>

                <?php if ($isEdit): ?>
                <div class="alert alert-light border small mb-0">
                    <i class="fas fa-link me-1"></i>
                    Vínculos deste risco: configure em
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-exame?search=<?= urlencode($item['nome'] ?? '') ?>">Exames por risco</a>,
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-necessidade">Necess. EPI</a> e
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-cargo">Riscos por cargo</a>.
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-riscos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

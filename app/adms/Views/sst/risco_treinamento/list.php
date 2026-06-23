<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_risco_treinamento');
?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-link me-2"></i>Treinamentos por Risco</h2>
    <div class="card mb-4 border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Risco</th><th>Treinamento</th><th>Validade</th><th>Obrig.</th></tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['risco_nome'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['treinamento_nome'] ?? '-') ?></td>
                                <td><?= !empty($item['validade_meses']) ? (int)$item['validade_meses'] . ' meses' : '-' ?></td>
                                <td><?= !empty($item['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['items'] as $item): ?>
                        <div class="card mb-2 shadow-sm"><div class="card-body py-2">
                            <div class="fw-bold small"><?= htmlspecialchars($item['treinamento_nome'] ?? '') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($item['risco_nome'] ?? '') ?></div>
                            <?php if (!empty($item['obrigatorio'])): ?><span class="badge bg-warning text-dark">Obrigatório</span><?php endif; ?>
                        </div></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Nenhum vínculo. Configure na visualização do risco (aba Treinamentos).</div>
            <?php endif; ?>
        </div>
    </div>
</div>

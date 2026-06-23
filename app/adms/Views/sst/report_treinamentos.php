<?php
use App\adms\Helpers\SstTreinamentoStatusHelper;
?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-chart-bar me-2"></i>Relatório Treinamentos SST</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Colaborador</label>
            <select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status vínculo</label>
            <select name="status" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach (SstTreinamentoStatusHelper::labels() as $k => $lbl): ?><option value="<?= htmlspecialchars($k) ?>" <?= ($this->data['filters']['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Vencimento</label>
            <select name="status_vencimento" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
                <option value="a_vencer" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'a_vencer' ? 'selected' : '' ?>>A vencer (30 dias)</option>
                <option value="valido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'valido' ? 'selected' : '' ?>>Dentro do prazo</option>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Colaborador</th><th>Treinamento</th><th>Status</th><th>Realização</th><th>Validade</th><th>Motivo</th></tr></thead>
            <tbody>
            <?php foreach ($this->data['items'] ?? [] as $r):
                $st = (string)($r['status'] ?? '');
            ?><tr>
                <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['treinamento_nome'] ?? '') ?></td>
                <td><span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span></td>
                <td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td>
                <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>
                <td><?= htmlspecialchars($r['motivo'] ?? '') ?></td>
            </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>

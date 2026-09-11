<?php
use App\adms\Helpers\SstTreinamentoStatusHelper;
?>
<div class="container-fluid px-4">
    <h2 class="mt-3"><i class="fas fa-chart-bar me-2"></i>Relatório Treinamentos SST</h2>
    <p class="text-muted small">Obrigações da matriz (direto, risco e GHE) com validade. Não se limita aos vínculos sincronizados.</p>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Colaborador</label>
            <select name="adms_user_id" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Todos</option>
            <?php foreach (SstTreinamentoStatusHelper::labels() as $k => $lbl): ?><option value="<?= htmlspecialchars($k) ?>" <?= ($this->data['filters']['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Vencimento</label>
            <select name="status_vencimento" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="pendente" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                <option value="vencido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
                <option value="a_vencer" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'a_vencer' ? 'selected' : '' ?>>A vencer (30 dias)</option>
                <option value="valido" <?= ($this->data['filters']['status_vencimento'] ?? '') === 'valido' ? 'selected' : '' ?>>Dentro do prazo</option>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
    </form>
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Colaborador</th><th>Treinamento</th><th>Status</th><th>Realização</th><th>Validade</th><th>Motivo</th></tr></thead>
            <tbody>
            <?php if (empty($this->data['items'])): ?>
            <tr><td colspan="6" class="text-muted">Nenhum treinamento encontrado para os filtros.</td></tr>
            <?php else: foreach ($this->data['items'] ?? [] as $r):
                $st = (string)($r['status'] ?? '');
            ?><tr>
                <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['treinamento_nome'] ?? '') ?></td>
                <td><span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span></td>
                <td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td>
                <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>
                <td><?= htmlspecialchars($r['motivo'] ?? '') ?></td>
            </tr><?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="d-block d-md-none">
        <?php foreach ($this->data['items'] ?? [] as $r):
            $st = (string)($r['status'] ?? '');
        ?>
        <div class="card mb-2 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="fw-bold small"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></div>
                <div class="small"><?= htmlspecialchars($r['treinamento_nome'] ?? '') ?></div>
                <span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span>
                <div class="small text-muted mt-1">
                    Realização: <?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?>
                    · Validade: <?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary">Voltar</a>
</div>

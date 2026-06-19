<?php
$items = $this->data['items'] ?? [];
$filters = $this->data['filters'] ?? [];
$epis = $this->data['epis'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-history me-2"></i>Movimentações de estoque EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-estoque">Estoque EPI</a></li>
            <li class="breadcrumb-item active">Histórico</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Histórico</span>
            <span class="ms-auto d-flex gap-1">
                <?php if (in_array('SstListEpiEstoque', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-estoque" class="btn btn-outline-secondary btn-sm">Posição</a>
                <?php endif; ?>
                <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Movimentar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">EPI</label>
                    <select name="adms_sst_epi_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($epis as $ep): ?>
                        <option value="<?= (int)$ep['id'] ?>" <?= ((int)($filters['adms_sst_epi_id'] ?? 0) === (int)$ep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ep['nome'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_movimento" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach (['Entrada', 'Saída', 'Ajuste', 'Entrega', 'Devolução'] as $t): ?>
                        <option value="<?= $t ?>" <?= (($filters['tipo_movimento'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="EPI ou documento">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                </div>
            </form>
            <?php if ($items === []): ?>
            <div class="alert alert-warning mb-0">Nenhuma movimentação encontrada.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr>
                        <th>Data</th><th>EPI</th><th>Tipo</th><th>Qtd</th><th>Saldo após</th><th>Documento</th><th>Responsável</th><th>Obs.</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $qty = (int)($r['quantidade'] ?? 0);
                        $tipo = (string)($r['tipo_movimento'] ?? '');
                        $neg = $tipo === 'Ajuste' ? $qty < 0 : in_array($tipo, ['Saída', 'Entrega'], true);
                    ?>
                    <tr>
                        <td><?= !empty($r['data_movimento']) ? date('d/m/Y', strtotime($r['data_movimento'])) : '-' ?></td>
                        <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>
                        <td><span class="badge bg-<?= $neg ? 'danger' : 'success' ?>"><?= htmlspecialchars($tipo) ?></span></td>
                        <td><?= $tipo === 'Ajuste' ? ($qty > 0 ? '+' : '') . $qty : abs($qty) ?></td>
                        <td><?= isset($r['saldo_apos']) ? (int)$r['saldo_apos'] : '-' ?></td>
                        <td><?= htmlspecialchars($r['documento_ref'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['responsavel_nome'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars($r['observacoes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

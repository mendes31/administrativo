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
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis">EPIs</a></li>
            <li class="breadcrumb-item active">Movimentações</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Histórico</span>
            <span class="ms-auto d-flex gap-1">
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis" class="btn btn-outline-secondary btn-sm">EPIs</a>
                <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Movimentar (lote)</a>
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
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="DOCNUM, EPI, CA, motivo…">
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
                        <th>DOCNUM</th><th>Data</th><th>EPI</th><th>Tipo</th><th>Qtd</th><th class="text-end">Valor unit.</th><th class="text-end">Total</th><th>Tam.</th><th>CA</th><th>Saldo após</th><th>Motivo</th><th>Documento</th><th>Responsável</th><th>Obs.</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $qty = (int)($r['quantidade'] ?? 0);
                        $tipo = (string)($r['tipo_movimento'] ?? '');
                        $neg = $tipo === 'Ajuste' ? $qty < 0 : in_array($tipo, ['Saída', 'Entrega'], true);
                        $vu = isset($r['valor_unitario']) && $r['valor_unitario'] !== null && $r['valor_unitario'] !== ''
                            ? (float) $r['valor_unitario'] : null;
                        $vt = isset($r['valor_total']) && $r['valor_total'] !== null && $r['valor_total'] !== ''
                            ? (float) $r['valor_total'] : null;
                        $docCodigo = trim((string)($r['doc_codigo'] ?? ''));
                    ?>
                    <tr>
                        <td><code class="small"><?= $docCodigo !== '' ? htmlspecialchars($docCodigo) : '—' ?></code></td>
                        <td><?= !empty($r['data_movimento']) ? date('d/m/Y', strtotime($r['data_movimento'])) : '-' ?></td>
                        <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>
                        <td><span class="badge bg-<?= $neg ? 'danger' : 'success' ?>"><?= htmlspecialchars($tipo) ?></span></td>
                        <td><?= $tipo === 'Ajuste' ? ($qty > 0 ? '+' : '') . $qty : abs($qty) ?></td>
                        <td class="text-end"><?= $vu !== null ? 'R$ ' . number_format($vu, 2, ',', '.') : '—' ?></td>
                        <td class="text-end"><?= $vt !== null ? 'R$ ' . number_format($vt, 2, ',', '.') : '—' ?></td>
                        <td><?= htmlspecialchars($r['tamanho'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['ca_numero'] ?? '-') ?></td>
                        <td><?= isset($r['saldo_apos']) ? (int)$r['saldo_apos'] : '-' ?></td>
                        <td class="small"><?php
                            $mot = trim((string)($r['motivo'] ?? ''));
                            $just = trim((string)($r['justificativa'] ?? ''));
                            if ($mot !== '' || $just !== '') {
                                echo htmlspecialchars($mot !== '' ? $mot : '');
                                if ($just !== '') {
                                    echo ($mot !== '' ? ': ' : '') . htmlspecialchars(mb_strimwidth($just, 0, 60, '…'));
                                }
                            } else {
                                echo '—';
                            }
                        ?></td>
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

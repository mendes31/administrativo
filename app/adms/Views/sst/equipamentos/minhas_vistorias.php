<?php
$resumo = $this->data['resumo'] ?? ['pendentes' => 0, 'vencidas' => 0, 'hoje' => 0];
$aba = $_GET['aba'] ?? 'pendentes';
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-tasks me-2"></i>Minhas vistorias</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active">Minhas vistorias</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="row mb-3 g-2">
        <div class="col-md-4"><div class="card border-primary"><div class="card-body py-2"><small class="text-muted">Pendentes</small><div class="fs-4 fw-bold"><?= (int)$resumo['pendentes'] ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-warning"><div class="card-body py-2"><small class="text-muted">Vencidas</small><div class="fs-4 fw-bold text-warning"><?= (int)$resumo['vencidas'] ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-info"><div class="card-body py-2"><small class="text-muted">Vencem hoje</small><div class="fs-4 fw-bold"><?= (int)$resumo['hoje'] ?></div></div></div></div>
    </div>
    <div class="card mb-3 border-light shadow">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item"><a class="nav-link <?= $aba !== 'concluidas' ? 'active' : '' ?>" href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias">Abertas</a></li>
                <li class="nav-item"><a class="nav-link <?= $aba === 'concluidas' ? 'active' : '' ?>" href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias?aba=concluidas">Concluídas</a></li>
            </ul>
        </div>
        <div class="card-body">
            <p class="text-muted small">Equipamentos sem responsável ou departamento aparecem para todos com permissão nesta página.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Competência</th><th>Equipamento</th><th>Tipo</th><th>Local</th><th>Prevista</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr class="<?= ($r['status'] ?? '') === 'Vencida' ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($r['competencia'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['equipamento_codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tipo_nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['localizacao'] ?? '-') ?></td>
                            <td><?= !empty($r['data_prevista']) ? date('d/m/Y', strtotime($r['data_prevista'])) : '-' ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td>
                                <?php if (in_array('SstExecuteEquipamentoVistoria', $perms, true)): ?>
                                <a href="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= (int)$r['id'] ?>" class="btn btn-sm btn-primary"><?= ($r['status'] ?? '') === 'Concluída' ? 'Ver' : 'Executar' ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (($this->data['items'] ?? []) === []): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhuma vistoria nesta fila.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>

<?php
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-clipboard-check me-2"></i>Vistorias de equipamentos</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Vistorias</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Todas as vistorias</span>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias" class="btn btn-primary btn-sm ms-auto"><i class="fas fa-tasks"></i> Minhas vistorias</a>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3"><label class="form-label small">Busca</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Competência</label><input type="month" name="competencia" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['competencia'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option><?php foreach (['Pendente','Em andamento','Vencida','Concluída','Cancelada'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Competência</th><th>Equipamento</th><th>Tipo</th><th>Local</th><th>Prevista</th><th>Status</th><th>Resultado</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr class="<?= ($r['status'] ?? '') === 'Vencida' ? 'table-warning' : (($r['resultado'] ?? '') === 'Não conforme' ? 'table-danger' : '') ?>">
                            <td><?= htmlspecialchars($r['competencia'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['equipamento_codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tipo_nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['localizacao'] ?? '-') ?></td>
                            <td><?= !empty($r['data_prevista']) ? date('d/m/Y', strtotime($r['data_prevista'])) : '-' ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['resultado'] ?? '-') ?></td>
                            <td>
                                <?php if (in_array('SstExecuteEquipamentoVistoria', $perms, true)): ?>
                                <a href="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><?= ($r['status'] ?? '') === 'Concluída' ? 'Ver' : 'Executar' ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>

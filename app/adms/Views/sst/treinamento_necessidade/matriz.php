<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoDisplayHelper;

$perms = $this->data['buttonPermission'] ?? [];
$positions = $this->data['positions'] ?? [];
$departments = $this->data['departments'] ?? [];
$resumo = $this->data['resumo_cargos'] ?? [];
$positionId = (int) ($this->data['position_id'] ?? 0);
$departmentId = (int) ($this->data['department_id'] ?? 0);
$treinamentos = $this->data['treinamentos'] ?? [];
$vinculadosMap = $this->data['vinculadosMap'] ?? [];
$podeSalvar = in_array('SstSaveMatrizTreinamentoCargo', $perms, true);
$podeSync = in_array('SstSyncTreinamentoVinculos', $perms, true);
$csrf = CSRFHelper::generateCSRFToken('sst_matriz_treinamento_cargo');

$positionName = '';
foreach ($positions as $p) {
    if ((int)($p['id'] ?? 0) === $positionId) {
        $positionName = (string) ($p['name'] ?? '');
        break;
    }
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-th me-2"></i>Matriz Treinamentos × Cargo</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-necessidade">Necessidades</a></li>
            <li class="breadcrumb-item active">Matriz por cargo</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">Selecionar cargo</div>
        <div class="card-body">
            <form method="get" action="<?= $_ENV['URL_ADM']; ?>sst-matriz-treinamento-cargo" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Cargo *</label>
                    <select name="adms_position_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">Selecione o cargo...</option>
                        <?php foreach ($positions as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $positionId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Departamento (opcional)</label>
                    <select name="adms_department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os departamentos</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= $departmentId === (int)$d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-necessidade" class="btn btn-outline-secondary btn-sm">Ver listagem detalhada</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($positionId <= 0): ?>
        <div class="card shadow-sm">
            <div class="card-header">Resumo por cargo</div>
            <div class="card-body">
                <?php if ($resumo === []): ?>
                    <div class="alert alert-warning mb-0">Nenhum cargo cadastrado.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Cargo</th><th>Treinamentos obrigatórios</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($resumo as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['cargo_nome'] ?? '') ?></td>
                                    <td><span class="badge bg-primary"><?= (int)($row['total_treinamentos'] ?? 0) ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-matriz-treinamento-cargo/<?= (int)$row['id'] ?>" class="btn btn-outline-primary btn-sm">Editar matriz</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header hstack gap-2 flex-wrap">
                <span><strong><?= htmlspecialchars($positionName) ?></strong> — treinamentos obrigatórios</span>
                <?php if ($podeSync): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-sync-treinamento-vinculos" class="btn btn-outline-secondary btn-sm ms-auto">
                        <i class="fas fa-sync me-1"></i>Sincronizar vínculos
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$podeSalvar): ?>
                    <div class="alert alert-info">Sem permissão para editar. Visualização apenas.</div>
                <?php endif; ?>
                <?php if (empty($treinamentos)): ?>
                    <div class="alert alert-warning mb-0">Cadastre treinamentos SST ativos primeiro.</div>
                <?php else: ?>
                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-matriz-treinamento-cargo">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="adms_position_id" value="<?= $positionId ?>">
                    <input type="hidden" name="adms_department_id" value="<?= $departmentId ?>">
                    <p class="small text-muted">Marque os treinamentos exigidos para este cargo. Colaboradores admitidos receberão pendências após sincronizar vínculos.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th width="40"></th><th>Código</th><th>Treinamento</th><th>NR</th><th>Carga</th><th>Reciclagem</th></tr></thead>
                            <tbody>
                            <?php foreach ($treinamentos as $tr):
                                $trId = (int)($tr['id'] ?? 0);
                                if ($trId <= 0) continue;
                                $checked = isset($vinculadosMap[$trId]);
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="treinamentos[]" value="<?= $trId ?>" id="mtr_<?= $trId ?>" <?= $checked ? 'checked' : '' ?> <?= !$podeSalvar ? 'disabled' : '' ?>>
                                    </td>
                                    <td><label class="mb-0" for="mtr_<?= $trId ?>"><?= htmlspecialchars($tr['codigo'] ?? '-') ?></label></td>
                                    <td><?= htmlspecialchars($tr['nome'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($tr['nr_referencia'] ?? '-') ?></td>
                                    <td><?= SstTreinamentoDisplayHelper::cargaHorariaHoras(isset($tr['carga_horaria_minutos']) ? (int)$tr['carga_horaria_minutos'] : null) ?></td>
                                    <td><?= SstTreinamentoDisplayHelper::validadeReciclagem(isset($tr['validade_meses']) ? (int)$tr['validade_meses'] : null) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($podeSalvar): ?>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar matriz do cargo</button>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

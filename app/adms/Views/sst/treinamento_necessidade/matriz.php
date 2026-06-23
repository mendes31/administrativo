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
$viaRiscoMap = $this->data['viaRiscoMap'] ?? [];
$viaGheMap = $this->data['viaGheMap'] ?? [];
$directMap = $this->data['directMap'] ?? [];
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
$departmentName = '';
if ($departmentId > 0) {
    foreach ($departments as $d) {
        if ((int)($d['id'] ?? 0) === $departmentId) {
            $departmentName = (string) ($d['name'] ?? '');
            break;
        }
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

    <div class="alert alert-light border small mb-3">
        <strong>Como usar:</strong>
        <ul class="mb-0 ps-3">
            <li><span class="badge bg-warning text-dark">Via risco</span> — configurado em <a href="<?= $_ENV['URL_ADM']; ?>sst-list-riscos">Riscos</a> (exposição cargo/setor + treinamentos do risco). Somente leitura aqui.</li>
            <li><span class="badge bg-primary">Matriz direta</span> — obrigação <em>inherente ao cargo</em>, independente de risco (ex.: Integração para todo Operador).</li>
            <li><span class="badge bg-info text-dark">Via GHE</span> — colaboradores do cargo em um <a href="<?= $_ENV['URL_ADM']; ?>sst-list-ghe">GHE</a>. Somente leitura aqui.</li>
        </ul>
    </div>

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
                    <label class="form-label">Departamento (refina risco/GHE)</label>
                    <select name="adms_department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos (colaboradores ativos do cargo)</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= $departmentId === (int)$d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-necessidade" class="btn btn-outline-secondary btn-sm">Listagem detalhada</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($positionId <= 0): ?>
        <div class="card shadow-sm">
            <div class="card-header">Resumo por cargo <small class="text-muted">(visão consolidada)</small></div>
            <div class="card-body">
                <?php if ($resumo === []): ?>
                    <div class="alert alert-warning mb-0">Nenhum cargo cadastrado.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr>
                                <th>Cargo</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Direto</th>
                                <th class="text-center">Risco</th>
                                <th class="text-center">GHE</th>
                                <th></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($resumo as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['cargo_nome'] ?? '') ?></td>
                                    <td class="text-center"><span class="badge bg-dark"><?= (int)($row['total_efetivo'] ?? 0) ?></span></td>
                                    <td class="text-center"><span class="badge bg-primary"><?= (int)($row['total_diretos'] ?? 0) ?></span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark"><?= (int)($row['total_via_risco'] ?? 0) ?></span></td>
                                    <td class="text-center"><span class="badge bg-info text-dark"><?= (int)($row['total_via_ghe'] ?? 0) ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-matriz-treinamento-cargo/<?= (int)$row['id'] ?>" class="btn btn-outline-primary btn-sm">Ver / editar</a>
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
        <?php
        $totalEfetivo = count(array_unique(array_merge(
            array_keys($viaRiscoMap),
            array_keys($viaGheMap),
            array_keys($directMap)
        )));
        ?>
        <div class="card shadow-sm">
            <div class="card-header hstack gap-2 flex-wrap">
                <span>
                    <strong><?= htmlspecialchars($positionName) ?></strong>
                    <?php if ($departmentName !== ''): ?> — dept. <?= htmlspecialchars($departmentName) ?><?php endif; ?>
                    <span class="badge bg-dark ms-1"><?= $totalEfetivo ?> treinamento(s) efetivo(s)</span>
                </span>
                <?php if ($podeSync): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-sync-treinamento-vinculos" class="btn btn-outline-secondary btn-sm ms-auto">
                        <i class="fas fa-sync me-1"></i>Sincronizar vínculos
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$podeSalvar): ?>
                    <div class="alert alert-info py-2">Sem permissão para editar vínculos diretos. Visualização consolidada.</div>
                <?php endif; ?>
                <?php if (empty($treinamentos)): ?>
                    <div class="alert alert-warning mb-0">Cadastre treinamentos SST ativos primeiro.</div>
                <?php else: ?>
                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-matriz-treinamento-cargo">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="adms_position_id" value="<?= $positionId ?>">
                    <input type="hidden" name="adms_department_id" value="<?= $departmentId ?>">
                    <p class="small text-muted mb-2">
                        Marque apenas treinamentos <strong>diretos na matriz</strong>. Itens via risco/GHE vêm de outras telas e não são gravados aqui.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr>
                                <th width="40"></th><th>Origem</th><th>Código</th><th>Treinamento</th><th>NR</th><th>Carga</th><th>Reciclagem</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($treinamentos as $tr):
                                $trId = (int)($tr['id'] ?? 0);
                                if ($trId <= 0) continue;
                                $fromRisco = isset($viaRiscoMap[$trId]);
                                $fromGhe = isset($viaGheMap[$trId]);
                                $fromDirect = isset($directMap[$trId]);
                                $somenteLeitura = ($fromRisco || $fromGhe) && !$fromDirect;
                                $checked = $fromDirect || $fromRisco || $fromGhe;
                            ?>
                                <tr class="<?= $somenteLeitura ? 'table-light' : '' ?>">
                                    <td class="text-center">
                                        <?php if ($somenteLeitura): ?>
                                            <input class="form-check-input" type="checkbox" checked disabled title="Vinculado via risco/GHE — edite na origem">
                                            <?php /* não envia no POST */ ?>
                                        <?php else: ?>
                                            <input class="form-check-input" type="checkbox" name="treinamentos[]" value="<?= $trId ?>"
                                                   id="mtr_<?= $trId ?>" <?= $fromDirect ? 'checked' : '' ?> <?= !$podeSalvar ? 'disabled' : '' ?>>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($fromDirect): ?><span class="badge bg-primary">Matriz</span><?php endif; ?>
                                        <?php if ($fromRisco): ?><span class="badge bg-warning text-dark">Risco</span><?php endif; ?>
                                        <?php if ($fromGhe): ?><span class="badge bg-info text-dark">GHE</span><?php endif; ?>
                                        <?php if (!$fromDirect && !$fromRisco && !$fromGhe): ?><span class="text-muted">—</span><?php endif; ?>
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
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar vínculos diretos da matriz</button>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

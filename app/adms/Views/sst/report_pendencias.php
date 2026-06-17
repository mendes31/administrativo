<?php
$report = $this->data['report'] ?? [];
$filters = $this->data['filters'] ?? [];
$episObr = $report['epis_obrigatorios'] ?? [];
$examesObr = $report['exames_obrigatorios'] ?? [];
$treinamentosObr = $report['treinamentos_obrigatorios'] ?? [];
$incluirTreinamentos = !empty($this->data['incluirTreinamentos']);
$afastAtivos = $report['afastamentos_ativos'] ?? [];
$acidentesAbertos = $report['acidentes_abertos'] ?? [];

function sstPendenciaRow(array $r, bool $mobile = false): void {
    $nome = htmlspecialchars($r['colaborador_nome'] ?? $r['name'] ?? '-');
    $item = htmlspecialchars($r['epi_nome'] ?? $r['exame_nome'] ?? $r['treinamento_nome'] ?? '-');
    $sit = $r['situacao_label'] ?? ($r['situacao'] ?? '-');
    $badge = $r['situacao_badge'] ?? 'secondary';
    $uid = (int) ($r['adms_user_id'] ?? $r['adms_user_id'] ?? 0);
    if ($mobile) {
        echo '<div class="card mb-2 shadow-sm"><div class="card-body py-2">';
        echo '<div class="fw-semibold">' . $nome . '</div>';
        echo '<div class="small text-muted">' . $item . '</div>';
        echo '<span class="badge bg-' . htmlspecialchars($badge) . ' mt-1">' . htmlspecialchars($sit) . '</span>';
        if ($uid > 0) {
            echo ' <a href="' . $_ENV['URL_ADM'] . 'sst-employee-profile/' . $uid . '" class="btn btn-link btn-sm p-0 ms-2">Perfil SST</a>';
        }
        echo '</div></div>';
        return;
    }
    echo '<tr>';
    echo '<td>' . $nome . '</td>';
    echo '<td>' . htmlspecialchars($r['departamento_nome'] ?? $r['name_dep'] ?? '-') . '</td>';
    echo '<td>' . $item . '</td>';
    echo '<td><span class="badge bg-' . htmlspecialchars($badge) . '">' . htmlspecialchars($sit) . '</span></td>';
    echo '<td class="text-center">';
    if ($uid > 0) {
        echo '<a href="' . $_ENV['URL_ADM'] . 'sst-employee-profile/' . $uid . '" class="btn btn-info btn-sm" title="Perfil"><i class="fa-regular fa-eye"></i></a>';
    }
    echo '</td></tr>';
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-clipboard-list me-2"></i>Pendências SST</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">Pendências</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-body">
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sstPendFilters">
                    <i class="fa fa-filter me-1"></i> Filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="sstPendFilters">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Colaborador</label>
                        <select name="adms_user_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= ($filters['adms_user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Departamento</label>
                        <select name="adms_department_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= ($filters['adms_department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Situação</label>
                        <select name="situacao" class="form-select form-select-sm">
                            <?php foreach ($this->data['situacoes'] ?? [] as $val => $lbl): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($filters['situacao'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-report-pendencias" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-epi-obr">EPIs obrigatórios <span class="badge bg-danger"><?= count($episObr) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exame-obr">Exames obrigatórios <span class="badge bg-danger"><?= count($examesObr) ?></span></button></li>
        <?php if ($incluirTreinamentos): ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-trein-obr">Treinamentos obrigatórios <span class="badge bg-danger"><?= count($treinamentosObr) ?></span></button></li>
        <?php endif; ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-afast">Afastamentos ativos <span class="badge bg-warning text-dark"><?= count($afastAtivos) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-acid">Acidentes abertos <span class="badge bg-primary"><?= count($acidentesAbertos) ?></span></button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-epi-obr">
            <p class="text-muted small">Cruzamento de <strong>Necessidades de EPI</strong> (vínculos) com <strong>Entregas</strong> registradas.</p>
            <?php if (empty($episObr)): ?>
                <p class="text-muted">Nenhuma pendência de EPI por vínculo.</p>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead><tr><th>Colaborador</th><th>Departamento</th><th>EPI</th><th>Situação</th><th class="text-center">Ações</th></tr></thead>
                        <tbody><?php foreach ($episObr as $r) { sstPendenciaRow($r, false); } ?></tbody>
                    </table>
                </div>
                <div class="d-block d-md-none"><?php foreach ($episObr as $r) { sstPendenciaRow($r, true); } ?></div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="tab-exame-obr">
            <p class="text-muted small">Cruzamento de <strong>Necessidades de exame</strong> (vínculos) com <strong>ASOs</strong> registrados.</p>
            <?php if (empty($examesObr)): ?>
                <p class="text-muted">Nenhuma pendência de exame por vínculo.</p>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead><tr><th>Colaborador</th><th>Departamento</th><th>Exame</th><th>Situação</th><th class="text-center">Ações</th></tr></thead>
                        <tbody><?php foreach ($examesObr as $r) { sstPendenciaRow($r, false); } ?></tbody>
                    </table>
                </div>
                <div class="d-block d-md-none"><?php foreach ($examesObr as $r) { sstPendenciaRow($r, true); } ?></div>
            <?php endif; ?>
        </div>
        <?php if ($incluirTreinamentos): ?>
        <div class="tab-pane fade" id="tab-trein-obr">
            <p class="text-muted small">Cruzamento de <strong>Treinamentos obrigatórios por cargo</strong> com <strong>vínculos em Treinamentos</strong>.</p>
            <?php if (empty($treinamentosObr)): ?>
                <p class="text-muted">Nenhuma pendência de treinamento por vínculo de cargo.</p>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead><tr><th>Colaborador</th><th>Departamento</th><th>Treinamento</th><th>Situação</th><th class="text-center">Ações</th></tr></thead>
                        <tbody><?php foreach ($treinamentosObr as $r) { sstPendenciaRow($r, false); } ?></tbody>
                    </table>
                </div>
                <div class="d-block d-md-none"><?php foreach ($treinamentosObr as $r) { sstPendenciaRow($r, true); } ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="tab-pane fade" id="tab-afast">
            <?php if (empty($afastAtivos)): ?><p class="text-muted">Nenhum afastamento ativo.</p>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Colaborador</th><th>Tipo</th><th>Início</th><th>Fim</th></tr></thead><tbody>
                    <?php foreach ($afastAtivos as $r): ?><tr>
                        <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                        <td><?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?></td>
                        <td><?= !empty($r['data_fim']) ? date('d/m/Y', strtotime($r['data_fim'])) : '-' ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div>
                <div class="d-block d-md-none"><?php foreach ($afastAtivos as $r): ?>
                    <div class="card mb-2 shadow-sm"><div class="card-body py-2">
                        <div class="fw-semibold"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></div>
                        <div class="small"><?= htmlspecialchars($r['tipo'] ?? '') ?> — <?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?></div>
                    </div></div>
                <?php endforeach; ?></div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="tab-acid">
            <?php if (empty($acidentesAbertos)): ?><p class="text-muted">Nenhum acidente aberto.</p>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Colaborador</th><th>Tipo</th><th>Data</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($acidentesAbertos as $r): ?><tr>
                        <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                        <td><?= !empty($r['data_ocorrencia']) ? date('d/m/Y H:i', strtotime($r['data_ocorrencia'])) : '-' ?></td>
                        <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
    </div>

    <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="btn btn-secondary mt-3">Voltar ao dashboard</a>
</div>

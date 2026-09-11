<?php ?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-heartbeat me-2"></i>Dashboard SST</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">SST</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php
    $golive = $this->data['golive'] ?? null;
    $permsDash = $this->data['buttonPermission'] ?? [];
    $showGolive = is_array($golive) && empty($golive['complete']);
    ?>
    <?php if ($showGolive): ?>
    <div class="card border-primary mb-4" data-adms-help-section="secao-piloto">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <span><i class="fas fa-flag-checkered me-1"></i> Primeiros passos — piloto SST</span>
            <span class="badge text-bg-primary ms-auto">
                <?= (int) ($golive['required_done'] ?? 0) ?>/<?= (int) ($golive['required_total'] ?? 0) ?> obrigatórios
            </span>
        </div>
        <div class="card-body py-2">
            <p class="small text-muted mb-2">Complete a base abaixo para as pendências nascerem. Itens opcionais (equipamentos e alertas) podem andar em paralelo.</p>
            <ul class="list-group list-group-flush">
                <?php foreach ($golive['items'] ?? [] as $step): ?>
                    <?php
                    $done = !empty($step['done']);
                    $permOk = in_array($step['permission'] ?? '', $permsDash, true)
                        || ($step['permission'] ?? '') === 'SstDashboard';
                    $href = $permOk && !empty($step['url']) ? ($_ENV['URL_ADM'] . $step['url']) : '';
                    ?>
                    <li class="list-group-item d-flex flex-wrap align-items-start gap-2 px-0">
                        <span class="badge <?= $done ? 'text-bg-success' : ($step['required'] ? 'text-bg-warning' : 'text-bg-secondary') ?>">
                            <?= $done ? 'Ok' : ($step['required'] ? 'Falta' : 'Opcional') ?>
                        </span>
                        <div class="flex-grow-1">
                            <div>
                                <?php if ($href !== '' && !$done): ?>
                                    <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars((string) $step['label']) ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $step['label']) ?>
                                <?php endif; ?>
                                <?php if (($step['count'] ?? 0) > 0): ?>
                                    <span class="text-muted small">(<?= (int) $step['count'] ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$done && ($step['hint'] ?? '') !== ''): ?>
                                <div class="small text-muted"><?= htmlspecialchars((string) $step['hint']) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    <?php $asosAguardando = (int) ($this->data['asos_aguardando_count'] ?? 0); ?>
    <div data-adms-help-section="secao-indicadores">
    <?php if ($asosAguardando > 0): ?>
        <div class="alert alert-warning py-2 mb-3 d-flex flex-wrap align-items-center gap-2">
            <span><i class="fas fa-clipboard-check me-1"></i>
                <strong><?= $asosAguardando ?></strong> ASO(s) aguardando lançamento de resultados (gerados pelos vínculos).</span>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos" class="btn btn-sm btn-warning ms-auto">Ir para fila de ASOs</a>
        </div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-link text-danger fa-2x"></i></div>
                    <div>
                        <h6 class="text-muted mb-1">Pendências (vínculos)</h6>
                        <h3 class="mb-0 fw-bold"><?= (int)($this->data['pendencias_vinculo_count'] ?? 0) ?></h3>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-report-pendencias" class="small">Ver relatório</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-stethoscope text-warning fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Exames Vencidos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['pending_exams_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-hard-hat text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">EPIs Vencidos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['expired_epis_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-graduation-cap text-warning fa-2x"></i></div>
                    <div>
                        <h6 class="text-muted mb-1">Treinamentos pendentes</h6>
                        <h3 class="mb-0 fw-bold"><?= (int)($this->data['treinamentos_pendentes_count'] ?? 0) ?></h3>
                        <span class="small text-muted"><?= (int)($this->data['treinamentos_vencidos_count'] ?? 0) ?> vencido(s)</span>
                        <?php if (in_array('SstReportTreinamentos', $this->data['buttonPermission'] ?? [], true)): ?>
                        <div><a href="<?= $_ENV['URL_ADM']; ?>sst-report-treinamentos?status_vencimento=pendente" class="small">Ver relatório</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3"><i class="fas fa-ambulance text-primary fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Acidentes abertos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['open_accidents_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3"><i class="fas fa-procedures text-info fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Afastamentos ativos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['afastamentos_ativos_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3"><i class="fas fa-boxes-stacked text-secondary fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">EPI estoque baixo</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['low_stock_epis_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-tasks text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Planos de ação vencidos</h6><h3 class="mb-0 fw-bold"><?= (int)($this->data['planos_acao_vencidos_count'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div data-adms-help-section="secao-atalhos">
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fa-solid fa-database me-1"></i> Cadastros e vínculos</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <?php
                    $cadastros = [
                        ['SstListCids', 'sst-list-cids', 'CIDs'],
                        ['SstListEpis', 'sst-list-epis', 'EPIs'],
                        ['SstListExames', 'sst-list-exames', 'Exames'],
                        ['SstListMedicos', 'sst-list-medicos', 'Médicos'],
                        ['SstListEpiNecessidade', 'sst-list-epi-necessidade', 'Necess. EPI'],
                        ['SstListExameNecessidade', 'sst-list-exame-necessidade', 'Necess. exame'],
                        ['SstListTreinamentos', 'sst-list-treinamentos', 'Treinamentos'],
                        ['SstListTreinamentoNecessidade', 'sst-list-treinamento-necessidade', 'Necess. trein.'],
                        ['SstMatrizTreinamentoCargo', 'sst-matriz-treinamento-cargo', 'Matriz cargo'],
                        ['SstListGhe', 'sst-list-ghe', 'GHE'],
                        ['SstListRiscos', 'sst-list-riscos', 'Riscos'],
                    ];
                    $perms = $this->data['buttonPermission'] ?? [];
                    foreach ($cadastros as [$perm, $url, $label]) {
                        if (in_array($perm, $perms, true)) {
                            echo '<a href="' . $_ENV['URL_ADM'] . $url . '" class="btn btn-outline-secondary btn-sm">' . htmlspecialchars($label) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fa-solid fa-clipboard-list me-1"></i> Registros</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <?php
                    $registros = [
                        ['SstListAcidentes', 'sst-list-acidentes', 'Acidentes'],
                        ['SstListAfastamentos', 'sst-list-afastamentos', 'Afastamentos'],
                        ['SstListAsos', 'sst-list-asos', 'ASOs'],
                        ['SstEncaminhamentoAso', 'sst-encaminhamento-aso', 'Encaminhamento ASO'],
                        ['SstListEpiFichas', 'sst-list-epi-fichas', 'Fichas EPI'],
                        ['SstListTreinamentoVinculos', 'sst-list-treinamento-vinculos', 'Vínc. trein.'],
                    ];
                    foreach ($registros as [$perm, $url, $label]) {
                        if (in_array($perm, $perms, true)) {
                            echo '<a href="' . $_ENV['URL_ADM'] . $url . '" class="btn btn-outline-primary btn-sm">' . htmlspecialchars($label) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fa-solid fa-chart-bar me-1"></i> Relatórios</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <?php
                    $relatorios = [
                        ['SstReportPendencias', 'sst-report-pendencias', 'Pendências'],
                        ['SstReportEpis', 'sst-report-epis', 'EPIs'],
                        ['SstReportExames', 'sst-report-exames', 'Exames'],
                        ['SstReportAfastamentos', 'sst-report-afastamentos', 'Afastamentos'],
                        ['SstReportTreinamentos', 'sst-report-treinamentos', 'Treinamentos'],
                        ['SstReportConformidade', 'sst-report-conformidade', 'Conformidade'],
                    ];
                    foreach ($relatorios as [$perm, $url, $label]) {
                        if (in_array($perm, $perms, true)) {
                            echo '<a href="' . $_ENV['URL_ADM'] . $url . '" class="btn btn-outline-info btn-sm">' . htmlspecialchars($label) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div data-adms-help-section="secao-pendencias">
    <div class="row">
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm"><div class="card-header">Pendências por vínculo (EPI)</div><div class="card-body p-0">
                <?php if (empty($this->data['pendencias_epi_amostra'])): ?><p class="p-3 text-muted mb-0">Nenhuma.</p>
                <?php else: foreach ($this->data['pendencias_epi_amostra'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small">
                        <?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['epi_nome'] ?? '') ?>
                        <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?> ms-1"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm"><div class="card-header">Pendências por vínculo (ASO)</div><div class="card-body p-0">
                <?php if (empty($this->data['pendencias_exame_amostra'])): ?><p class="p-3 text-muted mb-0">Nenhuma.</p>
                <?php else: foreach ($this->data['pendencias_exame_amostra'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small">
                        <?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['exame_nome'] ?? '') ?>
                        <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?> ms-1"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>
                        <?php if (($r['situacao'] ?? '') === 'aso_aguardando_resultados' && !empty($r['aso_aguardando_id'])): ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-registrar-resultados-aso/<?= (int) $r['aso_aguardando_id'] ?>" class="ms-1 small">Lançar resultados</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
        <?php
        $pendTreinAmostra = $this->data['pendencias_treinamento_amostra'] ?? [];
        ?>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm"><div class="card-header">Pendências de treinamento (matriz)</div><div class="card-body p-0">
                <?php if (empty($pendTreinAmostra)): ?>
                    <p class="p-3 text-muted mb-0">Nenhuma.</p>
                <?php else: foreach ($pendTreinAmostra as $r): ?>
                    <div class="px-3 py-2 border-bottom small">
                        <?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['treinamento_nome'] ?? '') ?>
                        <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?> ms-1"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>
                        <?php if (!empty($r['data_validade'])): ?>
                            <span class="text-muted"> · val. <?= date('d/m/Y', strtotime((string) $r['data_validade'])) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm"><div class="card-header">Acidentes abertos</div><div class="card-body p-0">
                <?php if (empty($this->data['open_accidents'])): ?><p class="p-3 text-muted mb-0">Nenhum.</p>
                <?php else: foreach ($this->data['open_accidents'] as $r): ?>
                    <div class="px-3 py-2 border-bottom small"><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($r['tipo'] ?? '') ?></div>
                <?php endforeach; endif; ?>
            </div></div>
        </div>
    </div>
    </div>
</div>
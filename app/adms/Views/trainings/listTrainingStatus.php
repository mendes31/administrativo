    <?php
// Filtros
$performanceFilter = $_GET['performance'] ?? '';
$codigoFiltro = trim($_GET['codigo'] ?? '');
?>
<style>
.table-sticky-header thead th,
.table-sticky-header tbody td {
    text-align: left !important;
    vertical-align: middle;
}
.table-sticky-header th.id-col { width: 4%; min-width: 40px; }
.table-sticky-header th.codigo-col { width: 8%; min-width: 90px; }
.table-sticky-header th.treinamento-col { width: 22%; min-width: 180px; }
.table-sticky-header th.colaborador-col { width: 16%; min-width: 140px; }
.table-sticky-header th.departamento-col { width: 14%; min-width: 120px; }
.table-sticky-header th.cargo-col { width: 10%; min-width: 90px; }
.table-sticky-header th.status-col { width: 10%; min-width: 90px; }
.table-sticky-header th.prazo-col { width: 10%; min-width: 110px; }
.table-sticky-header th.acoes-col { width: 6%; min-width: 60px; text-align: center !important; }
.sticky-cards {
    position: static;
}
.sticky-top-bloco {
    position: static;
    background: #fff;
    padding-top: 10px;
    padding-bottom: 10px;
    box-shadow: 0 2px 4px -2px rgba(0,0,0,0.04);
}
.table-scroll {
    max-height: 60vh;
    overflow-y: auto;
}
.performance-reprovado {
    background-color: #ffcccc !important;
    color: #b20000 !important;
    font-weight: bold !important;
}
.performance-exame {
    background-color: #fff3cd !important;
    color: #b8860b !important;
    font-weight: bold !important;
}
.performance-aprovado {
    background-color: #d4edda !important;
    color: #155724 !important;
    font-weight: bold !important;
}
</style>
<div class="<?= $responsiveClasses['container'] ?? 'container-fluid px-4' ?>">
    <div class="sticky-top-bloco">
        <div class="mb-1 hstack gap-2">
            <h2 class="mt-3">Status de Treinamentos por Colaborador</h2>
            <ol class="breadcrumb mb-3 mt-3 ms-auto">
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item">Status de Treinamentos</li>
            </ol>
        </div>

        <!-- Cards de Estatísticas por Status -->
        <?php $statusCounts = $this->data['statusCounts'] ?? []; ?>
        <div class="<?= $responsiveClasses['cards'] ?? 'row mb-4' ?>">
            <div class="<?= $responsiveClasses['card_cols'] ?? 'col-md-2' ?>">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary">
                            <i class="fas fa-users"></i>
                            <?= number_format($statusCounts['todos'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Todos</p>
                    </div>
                </div>
            </div>
            <div class="<?= $responsiveClasses['card_cols'] ?? 'col-md-2' ?>">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h3 class="text-success">
                            <i class="fas fa-check-circle"></i>
                            <?= number_format($statusCounts['dentro_do_prazo'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Dentro do Prazo</p>
                    </div>
                </div>
            </div>
            <div class="<?= $responsiveClasses['card_cols'] ?? 'col-md-2' ?>">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= number_format($statusCounts['proximo_vencimento'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Próximo do Vencimento</p>
                    </div>
                </div>
            </div>
            <div class="<?= $responsiveClasses['card_cols'] ?? 'col-md-2' ?>">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h3 class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= number_format($statusCounts['vencido'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Vencido</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h3 class="text-info">
                            <i class="fas fa-calendar-alt"></i>
                            <?= number_format($statusCounts['agendado'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Agendado</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <h3 class="text-secondary">
                            <i class="fas fa-check"></i>
                            <?= number_format($statusCounts['concluido'] ?? 0) ?>
                        </h3>
                        <p class="card-text">Concluído</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <label for="colaborador" class="form-label">Colaborador</label>
                    <select name="colaborador" id="colaborador" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($this->data['filters']['colaborador'] ?? '') == $user['id'] ? 'selected' : '' ?>><?= $user['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="departamento" class="form-label">Departamento</label>
                    <select name="departamento" id="departamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= ($this->data['filters']['departamento'] ?? '') == $dep['id'] ? 'selected' : '' ?>><?= $dep['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="cargo" class="form-label">Cargo</label>
                    <select name="cargo" id="cargo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listPositions'] ?? []) as $pos): ?>
                            <option value="<?= $pos['id'] ?>" <?= ($this->data['filters']['cargo'] ?? '') == $pos['id'] ? 'selected' : '' ?>><?= $pos['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="treinamento" class="form-label">Treinamento</label>
                    <select name="treinamento" id="treinamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listTrainings'] ?? []) as $trein): ?>
                            <option value="<?= $trein['id'] ?>" <?= ($this->data['filters']['treinamento'] ?? '') == $trein['id'] ? 'selected' : '' ?>><?= $trein['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Digite parte do código" value="<?= htmlspecialchars($this->data['filters']['codigo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="dentro_do_prazo" <?= ($this->data['filters']['status'] ?? '') == 'dentro_do_prazo' ? 'selected' : '' ?>>Dentro do Prazo</option>
                        <option value="proximo_vencimento" <?= ($this->data['filters']['status'] ?? '') == 'proximo_vencimento' ? 'selected' : '' ?>>Próximo do Vencimento</option>
                        <option value="vencido" <?= ($this->data['filters']['status'] ?? '') == 'vencido' ? 'selected' : '' ?>>Vencido</option>
                        <option value="agendado" <?= ($this->data['filters']['status'] ?? '') == 'agendado' ? 'selected' : '' ?>>Agendado</option>
                        <option value="concluido" <?= ($this->data['filters']['status'] ?? '') == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="area_responsavel_id" class="form-label">Área Responsável</label>
                    <select name="area_responsavel_id" id="area_responsavel_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= ($this->data['filters']['area_responsavel_id'] ?? '') == $dep['id'] ? 'selected' : '' ?>><?= $dep['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="area_elaborador_id" class="form-label">Área Elaborador</label>
                    <select name="area_elaborador_id" id="area_elaborador_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= ($this->data['filters']['area_elaborador_id'] ?? '') == $dep['id'] ? 'selected' : '' ?>><?= $dep['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- <div class="col-md-2">
                    <label for="performance" class="form-label">Aproveitamento</label>
                    <select name="performance" id="performance" class="form-select">
                        <option value="">Todos</option>
                        <option value="aprovado" <?= $performanceFilter === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        <option value="exame" <?= $performanceFilter === 'exame' ? 'selected' : '' ?>>Exame</option>
                        <option value="reprovado" <?= $performanceFilter === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                        <option value="vazio" <?= $performanceFilter === 'vazio' ? 'selected' : '' ?>>Vazio</option>
                    </select>
                </div> -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="<?= $_ENV['URL_ADM'] ?>list-training-status" class="btn btn-secondary w-100">Limpar</a>
                </div>
            </form>
            <div class="table-responsive table-scroll d-none d-md-block">
                <table class="table table-striped table-hover table-sticky-header" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th class="id-col">ID</th>
                            <th class="codigo-col">Código</th>
                            <th class="treinamento-col">Treinamento</th>
                            <th class="colaborador-col">Colaborador</th>
                            <th class="departamento-col">Departamento</th>
                            <th class="cargo-col">Cargo</th>
                            <!-- <th>Data Realização</th> -->
                            <!-- <th>Data Agendada</th> -->
                            <!-- <th>Vencimento</th> -->
                            <th class="status-col">Status</th>
                            <!-- <th>Nota</th> -->
                            <!-- <th>Aproveitamento</th> -->
                            <th class="prazo-col">Prazo Treinamento</th>
                            <th class="prazo-col">Agendamento</th>
                            <th class="acoes-col text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $matrix = $this->data['matrix'] ?? $this->data['trainingStatus'] ?? [];
                        ?>
                        <?php foreach (
                            $matrix as $row): ?>
                            <?php 
                            $status = $row['status_dinamico'] ?? $row['status'] ?? 'pendente';
                            $performance = getPerformanceStatus($row['nota'] ?? null);
                            // Filtro por performance
                            $filterMatch = false;
                            if ($performanceFilter === '' ||
                                ($performanceFilter === 'aprovado' && $performance['label'] === 'Aprovado') ||
                                ($performanceFilter === 'exame' && $performance['label'] === 'Exame') ||
                                ($performanceFilter === 'reprovado' && $performance['label'] === 'Reprovado') ||
                                ($performanceFilter === 'vazio' && $performance['label'] === '')) {
                                $filterMatch = true;
                            }
                            if (!$filterMatch) continue;
                            // Filtro por código (parcial)
                            $matchCodigo = true;
                            if ($codigoFiltro !== '') {
                                $matchCodigo = (stripos($row['codigo'], $codigoFiltro) !== false);
                            }
                            if (!$matchCodigo) continue;
                            ?>
                            <tr>
                                <td><?= $row['training_user_id'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($row['codigo']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['training_name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['codigo']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <!-- <td>
                                    <?php if (!empty($row['data_realizacao'])): ?>
                                        <?= (new DateTime($row['data_realizacao']))->format('d/m/Y') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td> -->
                                <!-- <td>
                                    <?php if (!empty($row['data_agendada'])): ?>
                                        <?= (new DateTime($row['data_agendada']))->format('d/m/Y') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td> -->
                                <!-- <td>
                                    <?php if (!empty($row['reciclagem']) && !empty($row['reciclagem_periodo']) && !empty($row['data_realizacao'])): ?>
                                        <?php 
                                        $dataVencimento = new DateTime($row['data_realizacao']);
                                        $dataVencimento->add(new DateInterval('P' . $row['reciclagem_periodo'] . 'M'));
                                        echo $dataVencimento->format('d/m/Y');
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td> -->
                                <td>
                                    <?php
                                    $statusClass = match($status) {
                                        'em_dia' => 'bg-success',
                                        'pendente' => 'bg-warning text-dark',
                                        'vencido' => 'bg-danger',
                                        'proximo_vencimento' => 'bg-warning',
                                        'agendado' => 'bg-info text-dark',
                                        'dentro_do_prazo' => 'bg-primary text-white',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = match($status) {
                                        'em_dia' => 'Em Dia',
                                        'pendente' => 'Pendente',
                                        'vencido' => 'Vencido',
                                        'proximo_vencimento' => 'Próximo Vencimento',
                                        'agendado' => 'Agendado',
                                        'dentro_do_prazo' => 'Dentro do Prazo',
                                        default => ucfirst($status)
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <!-- <td><?= htmlspecialchars($row['nota'] ?? '-') ?></td> -->
                                <!-- <td class="<?= $performance['class'] ?>"><?= $performance['label'] ?></td> -->
                                <td>
                                    <?php if (!empty($row['data_limite_primeiro_treinamento'])): ?>
                                        <?= (new DateTime($row['data_limite_primeiro_treinamento']))->format('d/m/Y') ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $dataAgendada = $row['data_agendada'] ?? null;
                                    if (!empty($dataAgendada) && $dataAgendada !== '0000-00-00') {
                                        echo (new DateTime($dataAgendada))->format('d/m/Y');
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $_ENV['URL_ADM'] ?>schedule-training/<?= $row['user_id'] ?>/<?= $row['training_id'] ?>" class="btn btn-sm btn-info mb-1" title="Agendar"><i class="fas fa-calendar-plus"></i></a>
                                    <?php if (($row['status_dinamico'] ?? $row['status'] ?? '') !== 'concluido'): ?>
                                        <a href="<?= $_ENV['URL_ADM'] ?>apply-training/<?= $row['user_id'] ?>/<?= $row['training_id'] ?>" class="btn btn-sm btn-success mb-1" title="Aplicar"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <!-- <a href="<?= $_ENV['URL_ADM'] ?>training-history/<?= $row['user_id'] ?>-<?= $row['training_id'] ?>" class="btn btn-sm btn-secondary mb-1" title="Histórico"><i class="fas fa-history"></i></a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- CARDS MOBILE -->
            <div class="d-block d-md-none">
                <?php foreach ($matrix as $i => $row): ?>
                    <?php 
                    $status = $row['status_dinamico'] ?? $row['status'] ?? 'pendente';
                    $performance = getPerformanceStatus($row['nota'] ?? null);
                    // Filtro por performance
                    $filterMatch = false;
                    if ($performanceFilter === '' ||
                        ($performanceFilter === 'aprovado' && $performance['label'] === 'Aprovado') ||
                        ($performanceFilter === 'exame' && $performance['label'] === 'Exame') ||
                        ($performanceFilter === 'reprovado' && $performance['label'] === 'Reprovado') ||
                        ($performanceFilter === 'vazio' && $performance['label'] === '')) {
                        $filterMatch = true;
                    }
                    if (!$filterMatch) continue;
                    // Filtro por código (parcial)
                    $matchCodigo = true;
                    if ($codigoFiltro !== '') {
                        $matchCodigo = (stripos($row['codigo'], $codigoFiltro) !== false);
                    }
                    if (!$matchCodigo) continue;
                    ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1"><b><?= htmlspecialchars($row['training_name']) ?></b></h5>
                                    <div class="mb-1"><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></div>
                                    <div class="mb-1"><b>Colaborador:</b> <?= htmlspecialchars($row['user_name']) ?></div>
                                    <!-- <div class="mb-1"><b>Data Realização:</b> <?php if (!empty($row['data_realizacao'])): ?><?= (new DateTime($row['data_realizacao']))->format('d/m/Y') ?><?php else: ?><span class="text-muted">-</span><?php endif; ?></div> -->
                                </div>
                                <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardDetails<?= $i ?>" aria-expanded="false" aria-controls="cardDetails<?= $i ?>">Ver mais</button>
                            </div>
                            <div class="collapse mt-2" id="cardDetails<?= $i ?>">
                                <div><b>Código:</b> <?= htmlspecialchars($row['codigo']) ?></div>
                                <div><b>Departamento:</b> <?= htmlspecialchars($row['department']) ?></div>
                                <div><b>Cargo:</b> <?= htmlspecialchars($row['position']) ?></div>
                                <!-- <div><b>Data Agendada:</b> <?php if (!empty($row['data_agendada'])): ?><?= (new DateTime($row['data_agendada']))->format('d/m/Y') ?><?php else: ?><span class="text-muted">-</span><?php endif; ?></div> -->
                                <!-- <div><b>Vencimento:</b> <?php if (!empty($row['reciclagem']) && !empty($row['reciclagem_periodo']) && !empty($row['data_realizacao'])): ?><?php $dataVencimento = new DateTime($row['data_realizacao']); $dataVencimento->add(new DateInterval('P' . $row['reciclagem_periodo'] . 'M')); echo $dataVencimento->format('d/m/Y'); ?><?php else: ?><span class="text-muted">N/A</span><?php endif; ?></div> -->
                                <!-- <div><b>Nota:</b> <?= htmlspecialchars($row['nota'] ?? '-') ?></div> -->
                                <!-- <div><b>Aproveitamento:</b> <span class="<?= $performance['class'] ?>"><?= $performance['label'] ?></span></div> -->
                                <div><b>Prazo Treinamento:</b> <?php if (!empty($row['data_limite_primeiro_treinamento'])): ?><?= (new DateTime($row['data_limite_primeiro_treinamento']))->format('d/m/Y') ?><?php else: ?><span class="text-muted">-</span><?php endif; ?></div>
                                <div><b>Agendado:</b> <?php 
                                    $dataAgendada = $row['data_agendada'] ?? null;
                                    if (!empty($dataAgendada) && $dataAgendada !== '0000-00-00') {
                                        echo (new DateTime($dataAgendada))->format('d/m/Y');
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                ?></div>
                                <div class="mt-2">
                                    <a href="<?= $_ENV['URL_ADM'] ?>schedule-training/<?= $row['user_id'] ?>/<?= $row['training_id'] ?>" class="btn btn-sm btn-info mb-1" title="Agendar"><i class="fas fa-calendar-plus"></i></a>
                                    <?php if (($row['status_dinamico'] ?? $row['status'] ?? '') !== 'concluido'): ?>
                                        <a href="<?= $_ENV['URL_ADM'] ?>apply-training/<?= $row['user_id'] ?>/<?= $row['training_id'] ?>" class="btn btn-sm btn-success mb-1" title="Aplicar"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <!-- <a href="<?= $_ENV['URL_ADM'] ?>training-history/<?= $row['user_id'] ?>-<?= $row['training_id'] ?>" class="btn btn-sm btn-secondary mb-1" title="Histórico"><i class="fas fa-history"></i></a> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Função para determinar o aproveitamento
function getPerformanceStatus($grade) {
    if ($grade === null || $grade === '' || $grade === '-') {
        return ['label' => '', 'class' => ''];
    } elseif ($grade <= 5) {
        return ['label' => 'Reprovado', 'class' => 'performance-reprovado'];
    } elseif ($grade < 7) {
        return ['label' => 'Exame', 'class' => 'performance-exame'];
    } else {
        return ['label' => 'Aprovado', 'class' => 'performance-aprovado'];
    }
}
?> 
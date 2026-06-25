<?php
use App\adms\Helpers\FormatHelper;

// Função para determinar o aproveitamento (apenas Aprovado >= 7 ou Reprovado)
function getPerformanceStatus(mixed $grade): array {
    if ($grade === null || $grade === '' || $grade === '-') {
        return ['label' => '', 'class' => ''];
    } elseif ($grade >= 7) {
        return ['label' => 'Aprovado', 'class' => 'performance-aprovado'];
    } else {
        return ['label' => 'Reprovado', 'class' => 'performance-reprovado'];
    }
}
$performanceFilter = $_GET['performance'] ?? '';

// Faixa de paginação segura para evitar "Mostrando 1 até 0 de 0".
$pageInfoPage = (int)($this->data['page'] ?? 1);
$pageInfoPerPage = (int)($this->data['per_page'] ?? 20);
$pageInfoTotal = (int)($this->data['total'] ?? 0);
$pageInfoFrom = $pageInfoTotal > 0 ? (($pageInfoPage - 1) * $pageInfoPerPage) + 1 : 0;
$pageInfoTo = $pageInfoTotal > 0 ? min($pageInfoPage * $pageInfoPerPage, $pageInfoTotal) : 0;
?>
<style>
.performance-reprovado {
    background-color: #ffcccc !important;
    color: #b20000 !important;
    font-weight: bold !important;
}

.performance-aprovado {
    background-color: #d4edda !important;
    color: #155724 !important;
    font-weight: bold !important;
}
.table thead th,
.table tbody td {
    text-align: left !important;
    vertical-align: middle;
    white-space: normal !important; /* permite quebra de linha */
}
.thead-green th {
    color: #ffffff !important;
}
.table thead th {
    color: #ffffff !important;
}
table thead th {
    color: #ffffff !important;
}
thead th {
    color: #ffffff !important;
}
#completedTrainingsTable thead th {
    color: #ffffff !important;
}
#completedTrainingsTable .thead-green th {
    color: #ffffff !important;
}
#completedTrainingsTable .table thead th {
    color: #ffffff !important;
}
/* Dimensionamento para caber 100% sem rolagem */
.table th.colaborador-col { width: 12%; min-width: 100px; }
.table th.treinamento-col { width: 18%; min-width: 140px; }
.table th.codigo-col { width: 7%; min-width: 60px; }
.table th.data-realizacao-col { width: 8%; min-width: 80px; }
.table th.data-avaliacao-col { width: 8%; min-width: 80px; }
.table th.horas-col { width: 6%; min-width: 60px; }
.table th.instrutor-col { width: 10%; min-width: 90px; }
.table th.nota-col { width: 6%; min-width: 60px; }
.table th.aproveitamento-col { width: 8%; min-width: 80px; }
.table th.tipo-col { width: 8%; min-width: 70px; }
.table th.observacoes-col { width: 9%; min-width: 80px; }
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
</style>
<div class="container-fluid px-4">
    <div class="mb-1 d-flex align-items-center justify-content-between" style="min-height:48px;">
        <h2 class="mt-3 mb-0">Matriz de Treinamentos Realizados</h2>
        <ol class="breadcrumb mb-0 mt-3 ms-auto bg-transparent p-0">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Matriz de Treinamentos Realizados</li>
        </ol>
    </div>
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary">
                        <i class="fas fa-users"></i>
                        <?= number_format($this->data['summary']['total_colaboradores'] ?? 0) ?>
                    </h3>
                    <p class="card-text">Total Colaboradores Treinados</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info">
                        <i class="fas fa-graduation-cap"></i>
                        <?= number_format($this->data['summary']['total_treinamentos'] ?? 0) ?>
                    </h3>
                    <p class="card-text">Total de Treinamentos</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success">
                        <i class="fas fa-check-circle"></i>
                        <?= number_format($this->data['summary']['total_aprovados'] ?? 0) ?>
                    </h3>
                    <p class="card-text">Total Treinamentos Aprovados</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger">
                        <i class="fas fa-times-circle"></i>
                        <?= number_format($this->data['summary']['total_reprovados'] ?? 0) ?>
                    </h3>
                    <p class="card-text">Total Treinamentos Reprovados</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning">
                        <i class="fas fa-star"></i>
                        <?= number_format($this->data['summary']['media_nota'] ?? 0) ?>
                    </h3>
                    <p class="card-text">Média Nota Geral</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <h3 class="text-secondary">
                        <i class="fas fa-clock"></i>
                        <?= $this->data['summary']['total_horas'] ?? 0 ?>
                    </h3>
                    <p class="card-text">Total de Horas</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Treinamentos Realizados por Colaborador</h5>
            <div>
                <a href="<?= $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?') ?>export=excel" class="btn btn-success btn-sm me-2"><i class="fas fa-file-excel me-1"></i>Exportar Excel</a>
                <a href="<?= $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?') ?>export=pdf" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>Exportar PDF</a>
            </div>
        </div>
        <div class="card-body pt-2">
            <form method="GET" class="row g-3 align-items-end mb-0">
                <div class="col-md-3">
                    <label for="colaborador" class="form-label mb-1">Colaborador</label>
                    <select name="colaborador" id="colaborador" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($this->data['filters']['colaborador'] ?? '') == $user['id'] ? 'selected' : '' ?>><?= $user['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="treinamento" class="form-label mb-1">Treinamento</label>
                    <select name="treinamento" id="treinamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['listTrainings'] ?? []) as $trein): ?>
                            <option value="<?= $trein['id'] ?>" <?= ($this->data['filters']['treinamento'] ?? '') == $trein['id'] ? 'selected' : '' ?>><?= $trein['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="mes" class="form-label mb-1">Mês</label>
                    <select name="mes" id="mes" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $meses = [
                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                        ];
                        foreach ($meses as $num => $nome): ?>
                            <option value="<?= $num ?>" <?= ($this->data['filters']['mes'] ?? '') == $num ? 'selected' : '' ?>><?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="ano" class="form-label mb-1">Ano</label>
                    <select name="ano" id="ano" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $anoAtual = date('Y');
                        for ($ano = $anoAtual; $ano >= $anoAtual - 5; $ano--): ?>
                            <option value="<?= $ano ?>" <?= ($this->data['filters']['ano'] ?? '') == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="codigo" class="form-label mb-1">Código</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Buscar por código" value="<?= htmlspecialchars($this->data['filters']['codigo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="performance" class="form-label mb-1">Aproveitamento</label>
                    <select name="performance" id="performance" class="form-select">
                        <option value="">Todos</option>
                        <option value="aprovado" <?= $performanceFilter === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        <option value="reprovado" <?= $performanceFilter === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                        <option value="vazio" <?= $performanceFilter === 'vazio' ? 'selected' : '' ?>>Vazio</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>completed-trainings-matrix?limpar=1" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fas fa-times"></i> Limpar</a>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label for="per_page" class="form-label mb-1 me-2">Exibir</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 20) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="ms-2">por página</span>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive table-scroll" style="max-height: 70vh;">
        <table id="completedTrainingsTable" class="table table-striped table-hover" style="table-layout: auto; width: 100%;">
            <thead class="thead-green" style="color: #ffffff !important;">
                <tr>
                    <?php
                    // Parâmetros atuais
                    $params = $_GET;
                    $sort = $_GET['sort'] ?? '';
                    $order = $_GET['order'] ?? 'asc';
                    function sort_link(string $col, string $label, string $sort, string $order, array $params): string {
                        $params['sort'] = $col;
                        $params['order'] = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
                        $icon = '';
                        if ($sort === $col) {
                            $icon = $order === 'asc' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
                        }
                        $url = '?' . http_build_query($params);
                        return '<a href="' . $url . '" class="text-decoration-none" style="color: #ffffff !important;">' . $label . $icon . '</a>';
                    }
                    ?>
                    <th class="colaborador-col"><?= sort_link('user_name', 'Colaborador', $sort, $order, $params) ?></th>
                    <th class="treinamento-col"><?= sort_link('training_name', 'Treinamento', $sort, $order, $params) ?></th>
                    <th class="codigo-col"><?= sort_link('training_code', 'Código', $sort, $order, $params) ?></th>
                    <th class="data-realizacao-col"><?= sort_link('data_realizacao', 'Data Realização', $sort, $order, $params) ?></th>
                    <th class="data-avaliacao-col"><?= sort_link('data_avaliacao', 'Data Avaliação', $sort, $order, $params) ?></th>
                    <th class="horas-col"><?= sort_link('carga_horaria', 'Horas', $sort, $order, $params) ?></th>
                    <th class="instrutor-col"><?= sort_link('instrutor_nome', 'Instrutor', $sort, $order, $params) ?></th>
                    <th class="nota-col"><?= sort_link('nota', 'Nota', $sort, $order, $params) ?></th>
                    <th class="aproveitamento-col" style="color: #ffffff !important;">Aproveitamento</th>
                    <th class="tipo-col" style="color: #ffffff !important;">Tipo do Treinamento</th>
                    <th class="observacoes-col"><?= sort_link('observacoes', 'Observações', $sort, $order, $params) ?></th>
                    <th class="acoes-col" style="color: #ffffff !important; width: 100px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($this->data['matrix'])): ?>
                    <?php foreach ($this->data['matrix'] as $item):
                        $performance = getPerformanceStatus($item['nota'] ?? null);
                        // Filtro
                        $filterMatch = false;
                        if ($performanceFilter === '' ||
                            ($performanceFilter === 'aprovado' && $performance['label'] === 'Aprovado') ||
                            ($performanceFilter === 'reprovado' && $performance['label'] === 'Reprovado') ||
                            ($performanceFilter === 'vazio' && $performance['label'] === '')) {
                            $filterMatch = true;
                        }
                        if (!$filterMatch) continue;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['user_name']) ?></td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($item['training_name']) ?></strong>
                                    <?php if (!empty($item['training_version'])): ?>
                                        <br><small class="text-muted" style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 3px; border: 1px solid #dee2e6;">v<?= htmlspecialchars($item['training_version']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($item['training_code']) ?></td>
                            <td>
                                <?php if (!empty($item['data_realizacao'])): ?>
                                    <?= (new DateTime($item['data_realizacao']))->format('d/m/Y') ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['data_avaliacao'])): ?>
                                    <?= (new DateTime($item['data_avaliacao']))->format('d/m/Y') ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['carga_horaria'])): ?>
                                    <?= substr($item['carga_horaria'], 0, 5) ?>h
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($item['instrutor_nome'])) {
                                    echo htmlspecialchars($item['instrutor_nome']);
                                } elseif (!empty($item['instructor_user_name'])) {
                                    echo htmlspecialchars($item['instructor_user_name']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($item['nota'] ?? '-') ?></td>
                            <td class="<?= $performance['class'] ?>"><?= $performance['label'] ?></td>
                            <td>
                                <?php if (($item['tipo_treinamento'] ?? '') === 'Inicial'): ?>
                                    <span class="badge bg-info">Inicial</span>
                                <?php elseif (($item['tipo_treinamento'] ?? '') === 'Continuo'): ?>
                                    <span class="badge bg-warning">Contínuo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['observacoes'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($item['application_id'])): ?>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (in_array('EditCompletedTraining', $this->data['buttonPermission'] ?? [])): ?>
                                            <button type="button" class="btn btn-primary" onclick="openEditModal(<?= (int)$item['application_id'] ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array('DeleteCompletedTraining', $this->data['buttonPermission'] ?? [])): ?>
                                            <button type="button"
                                                class="btn btn-danger btn-delete-training"
                                                data-application-id="<?= (int)$item['application_id'] ?>"
                                                data-user-name="<?= htmlspecialchars($item['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-training-name="<?= htmlspecialchars($item['training_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-training-version="<?= htmlspecialchars($item['training_version'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-is-current-version="<?= (int)($item['is_current_version'] ?? 1) ?>"
                                                title="Cancelar realização">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="12" class="text-center text-muted">Nenhum treinamento realizado encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- CARDS MOBILE -->
    <div class="d-block d-md-none">
        <?php if (!empty($this->data['matrix'])): ?>
            <?php foreach ($this->data['matrix'] as $item):
                $performance = getPerformanceStatus($item['nota'] ?? null);
                // Filtro
                $filterMatch = false;
                if ($performanceFilter === '' ||
                    ($performanceFilter === 'aprovado' && $performance['label'] === 'Aprovado') ||
                    ($performanceFilter === 'reprovado' && $performance['label'] === 'Reprovado') ||
                    ($performanceFilter === 'vazio' && $performance['label'] === '')) {
                    $filterMatch = true;
                }
                if (!$filterMatch) continue;
            ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">
                                    <strong><?= htmlspecialchars($item['training_name']) ?></strong>
                                    <?php if (!empty($item['training_version'])): ?>
                                        <br><small class="text-muted" style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 3px; border: 1px solid #dee2e6;">v<?= htmlspecialchars($item['training_version']) ?></small>
                                    <?php endif; ?>
                                </h6>
                                <div class="mb-1">
                                    <small class="text-muted">Colaborador:</small><br>
                                    <strong><?= htmlspecialchars($item['user_name']) ?></strong>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardDetails<?= $item['user_id'] . '_' . $item['training_id'] ?>" aria-expanded="false" aria-controls="cardDetails<?= $item['user_id'] . '_' . $item['training_id'] ?>">
                                Ver mais
                            </button>
                        </div>
                        
                        <div class="collapse mt-3" id="cardDetails<?= $item['user_id'] . '_' . $item['training_id'] ?>">
                            <hr class="my-2">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Código:</small><br>
                                    <strong><?= htmlspecialchars($item['training_code']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Nota:</small><br>
                                    <strong><?= htmlspecialchars($item['nota'] ?? '-') ?></strong>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Aproveitamento:</small><br>
                                    <span class="badge <?= $performance['class'] ?>"><?= $performance['label'] ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Tipo do Treinamento:</small><br>
                                    <?php if (($item['tipo_treinamento'] ?? '') === 'Inicial'): ?>
                                        <span class="badge bg-info">Inicial</span>
                                    <?php elseif (($item['tipo_treinamento'] ?? '') === 'Continuo'): ?>
                                        <span class="badge bg-warning">Contínuo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-12">
                                    <small class="text-muted">Observações:</small><br>
                                    <strong><?= htmlspecialchars($item['observacoes'] ?? '-') ?></strong>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Data Realização:</small><br>
                                    <strong>
                                        <?php if (!empty($item['data_realizacao'])): ?>
                                            <?= (new DateTime($item['data_realizacao']))->format('d/m/Y') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Data Avaliação:</small><br>
                                    <strong>
                                        <?php if (!empty($item['data_avaliacao'])): ?>
                                            <?= (new DateTime($item['data_avaliacao']))->format('d/m/Y') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </strong>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Horas:</small><br>
                                    <strong>
                                        <?php if (!empty($item['carga_horaria'])): ?>
                                            <?= substr($item['carga_horaria'], 0, 5) ?>h
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Instrutor:</small><br>
                                    <strong>
                                        <?php
                                        if (!empty($item['instrutor_nome'])) {
                                            echo htmlspecialchars($item['instrutor_nome']);
                                        } elseif (!empty($item['instructor_user_name'])) {
                                            echo htmlspecialchars($item['instructor_user_name']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                            
                            <?php if (!empty($item['observacoes'])): ?>
                            <div class="mb-2">
                                <small class="text-muted">Observações:</small><br>
                                <span><?= htmlspecialchars($item['observacoes']) ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($item['application_id']) && (
                                in_array('EditCompletedTraining', $this->data['buttonPermission'] ?? [])
                                || in_array('DeleteCompletedTraining', $this->data['buttonPermission'] ?? [])
                            )): ?>
                            <div class="d-flex gap-2 mt-2">
                                <?php if (in_array('EditCompletedTraining', $this->data['buttonPermission'] ?? [])): ?>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="openEditModal(<?= (int)$item['application_id'] ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array('DeleteCompletedTraining', $this->data['buttonPermission'] ?? [])): ?>
                                    <button type="button"
                                        class="btn btn-sm btn-danger btn-delete-training"
                                        data-application-id="<?= (int)$item['application_id'] ?>"
                                        data-user-name="<?= htmlspecialchars($item['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-training-name="<?= htmlspecialchars($item['training_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-training-version="<?= htmlspecialchars($item['training_version'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-is-current-version="<?= (int)($item['is_current_version'] ?? 1) ?>">
                                        <i class="fas fa-trash"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Nenhum treinamento realizado encontrado.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Paginação
    $page = $this->data['page'] ?? 1;
    $perPage = $this->data['per_page'] ?? 20;
    $total = $this->data['total'] ?? 0;
    $totalPages = max(1, ceil($total / $perPage));
    $params = $_GET;
    function pageUrl(int $n, array $params): string {
        $params['page'] = $n;
        return '?' . http_build_query($params);
    }
    ?>
    
    <!-- Paginação Desktop -->
    <nav aria-label="Navegação de páginas" class="d-none d-md-block">
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <small class="text-muted">
                    Mostrando <?= $pageInfoFrom ?> até <?= $pageInfoTo ?> de <?= $total ?> registro(s)
                </small>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="btn-group" role="group">
                <?php
                $start = max(1, $page - 1);
                $end = min($totalPages, $page + 1);
                
                // Mostrar primeira página se não estiver no início
                if ($start > 1): ?>
                    <a href="<?= pageUrl(1, $params) ?>" class="btn btn-outline-primary btn-sm">1</a>
                    <?php if ($start > 2): ?>
                        <span class="btn btn-outline-primary btn-sm disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= pageUrl($i, $params) ?>" class="btn btn-<?= $i == $page ? 'primary' : 'outline-primary' ?> btn-sm"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span class="btn btn-outline-primary btn-sm disabled">...</span>
                    <?php endif; ?>
                    <a href="<?= pageUrl($totalPages, $params) ?>" class="btn btn-outline-primary btn-sm">Última</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Paginação Mobile -->
    <nav aria-label="Navegação de páginas" class="d-block d-md-none">
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <small class="text-muted">
                    Mostrando <?= $pageInfoFrom ?> até <?= $pageInfoTo ?> de <?= $total ?> registro(s)
                </small>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="btn-group" role="group">
                <?php
                $start = max(1, $page - 1);
                $end = min($totalPages, $page + 1);
                
                // Mostrar primeira página se não estiver no início
                if ($start > 1): ?>
                    <a href="<?= pageUrl(1, $params) ?>" class="btn btn-outline-primary btn-sm">1</a>
                    <?php if ($start > 2): ?>
                        <span class="btn btn-outline-primary btn-sm disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= pageUrl($i, $params) ?>" class="btn btn-<?= $i == $page ? 'primary' : 'outline-primary' ?> btn-sm"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span class="btn btn-outline-primary btn-sm disabled">...</span>
                    <?php endif; ?>
                    <a href="<?= pageUrl($totalPages, $params) ?>" class="btn btn-outline-primary btn-sm">Última</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</div>

<!-- Modal de Edição -->
<div class="modal fade" id="editTrainingModal" tabindex="-1" aria-labelledby="editTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTrainingModalLabel">Editar Treinamento Realizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="editTrainingForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_application_id" name="application_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_data_realizacao" class="form-label">Data de Realização *</label>
                            <input type="date" class="form-control" id="edit_data_realizacao" name="data_realizacao" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_data_avaliacao" class="form-label">Data de Avaliação</label>
                            <input type="date" class="form-control" id="edit_data_avaliacao" name="data_avaliacao">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_nota" class="form-label">Nota</label>
                            <input type="number" class="form-control" id="edit_nota" name="nota" step="0.01" min="0" max="10">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_instrutor_nome" class="form-label">Nome do Instrutor</label>
                            <input type="text" class="form-control" id="edit_instrutor_nome" name="instrutor_nome">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-warning">
                        <strong>Atenção:</strong> Para salvar as alterações, é necessário informar uma justificativa e confirmar com sua senha.
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_justificativa" class="form-label">Justificativa *</label>
                            <textarea class="form-control" id="edit_justificativa" name="justificativa" rows="3" required placeholder="Informe o motivo da alteração"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_password" class="form-label">Senha de Confirmação *</label>
                            <input type="password" class="form-control" id="edit_password" name="password" required placeholder="Digite sua senha para confirmar a alteração">
                        </div>
                    </div>
                    
                    <div id="edit_error_message" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="deleteTrainingModal" tabindex="-1" aria-labelledby="deleteTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTrainingModalLabel">Cancelar Treinamento Realizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="deleteTrainingForm">
                <div class="modal-body">
                    <input type="hidden" id="delete_application_id" name="application_id">

                    <div class="alert alert-danger" id="delete_version_warning">
                        <strong>Atenção:</strong> esta ação remove o registro da <strong>Matriz de Realizados</strong>
                        e devolve o treinamento para o <strong>Status de Treinamentos</strong> como pendente.
                    </div>

                    <p class="mb-1"><strong>Colaborador:</strong> <span id="delete_user_name"></span></p>
                    <p class="mb-3"><strong>Treinamento:</strong> <span id="delete_training_name"></span></p>

                    <div class="mb-3">
                        <label for="delete_justificativa" class="form-label">Justificativa *</label>
                        <textarea class="form-control" id="delete_justificativa" name="justificativa" rows="3" required placeholder="Informe o motivo do cancelamento"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Senha de Confirmação *</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required placeholder="Digite sua senha para confirmar o cancelamento">
                    </div>

                    <div id="delete_error_message" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(applicationId, userName, trainingName, trainingVersion, isCurrentVersion) {
    document.getElementById('deleteTrainingForm').reset();
    document.getElementById('delete_error_message').classList.add('d-none');
    document.getElementById('delete_application_id').value = applicationId;
    document.getElementById('delete_user_name').textContent = userName || '';
    document.getElementById('delete_training_name').textContent = trainingName || '';

    const warning = document.getElementById('delete_version_warning');
    if (String(isCurrentVersion) === '0') {
        warning.innerHTML = '<strong>Atenção:</strong> este registro é da <strong>versão v' + (trainingVersion || '?') + '</strong>. '
            + 'Se houver conclusão copiada na versão atual, ela também será cancelada e a pendência será reaberta na <strong>versão vigente</strong>.';
    } else {
        warning.innerHTML = '<strong>Atenção:</strong> esta ação remove o registro da <strong>Matriz de Realizados</strong> '
            + 'e devolve o treinamento para o <strong>Status de Treinamentos</strong> como pendente.';
    }

    const modal = new bootstrap.Modal(document.getElementById('deleteTrainingModal'));
    modal.show();
}

document.addEventListener('click', function(event) {
    const button = event.target.closest('.btn-delete-training');
    if (!button) {
        return;
    }

    openDeleteModal(
        parseInt(button.dataset.applicationId, 10),
        button.dataset.userName || '',
        button.dataset.trainingName || '',
        button.dataset.trainingVersion || '',
        button.dataset.isCurrentVersion || '1'
    );
});

function openEditModal(applicationId) {
    // Limpar formulário
    document.getElementById('editTrainingForm').reset();
    document.getElementById('edit_error_message').classList.add('d-none');
    document.getElementById('edit_application_id').value = applicationId;
    
    // Carregar dados do registro
    fetch('<?= $_ENV['URL_ADM'] ?>completed-trainings-matrix/get-application?id=' + applicationId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro HTTP: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    const app = data.application;
                    document.getElementById('edit_data_realizacao').value = app.data_realizacao || '';
                    document.getElementById('edit_data_avaliacao').value = app.data_avaliacao || '';
                    document.getElementById('edit_nota').value = app.nota || '';
                    document.getElementById('edit_instrutor_nome').value = app.instrutor_nome || '';
                    document.getElementById('edit_observacoes').value = app.observacoes || '';
                    
                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('editTrainingModal'));
                    modal.show();
                } else {
                    alert('Erro ao carregar dados do treinamento: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro ao parsear JSON:', e);
                console.error('Resposta recebida:', text);
                alert('Erro ao processar resposta do servidor. Verifique o console para mais detalhes.');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro ao carregar dados do treinamento: ' + error.message);
        });
}

// Submeter formulário
document.getElementById('editTrainingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const errorDiv = document.getElementById('edit_error_message');
    errorDiv.classList.add('d-none');
    
    fetch('<?= $_ENV['URL_ADM'] ?>completed-trainings-matrix/update-application', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fechar modal e recarregar página
            const modal = bootstrap.Modal.getInstance(document.getElementById('editTrainingModal'));
            modal.hide();
            window.location.reload();
        } else {
            // Mostrar erro
            errorDiv.textContent = data.message || 'Erro ao salvar alterações';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        errorDiv.textContent = 'Erro ao processar requisição';
        errorDiv.classList.remove('d-none');
    });
});

document.getElementById('deleteTrainingForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const errorDiv = document.getElementById('delete_error_message');
    errorDiv.classList.add('d-none');

    if (!confirm('Tem certeza que deseja cancelar este treinamento realizado?')) {
        return;
    }

    fetch('<?= $_ENV['URL_ADM'] ?>completed-trainings-matrix/delete-application', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTrainingModal'));
            modal.hide();
            window.location.reload();
        } else {
            errorDiv.textContent = data.message || 'Erro ao cancelar treinamento';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        errorDiv.textContent = 'Erro ao processar requisição';
        errorDiv.classList.remove('d-none');
    });
});
</script> 
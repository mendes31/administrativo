<?php
use App\adms\Helpers\FormatHelper;

// Função para determinar o aproveitamento (apenas Aprovado >= 7 ou Reprovado)
function getPerformanceStatus($grade) {
    if ($grade === null || $grade === '' || $grade === '-') {
        return ['label' => '', 'class' => ''];
    } elseif ($grade >= 7) {
        return ['label' => 'Aprovado', 'class' => 'performance-aprovado'];
    } else {
        return ['label' => 'Reprovado', 'class' => 'performance-reprovado'];
    }
}
$performanceFilter = $_GET['performance'] ?? '';
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
                    <a href="<?= $_ENV['URL_ADM'] ?>completed-trainings-matrix" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fas fa-times"></i> Limpar</a>
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
    <div class="table-responsive" style="max-height: 70vh;">
        <table class="table table-striped table-hover" style="table-layout: fixed; width: 100%; min-width:900px;">
            <thead style="position:sticky;top:0;z-index:1;background:#f8f9fa;">
                <tr>
                    <?php
                    // Parâmetros atuais
                    $params = $_GET;
                    $sort = $_GET['sort'] ?? '';
                    $order = $_GET['order'] ?? 'asc';
                    function sort_link($col, $label, $sort, $order, $params) {
                        $params['sort'] = $col;
                        $params['order'] = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
                        $icon = '';
                        if ($sort === $col) {
                            $icon = $order === 'asc' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
                        }
                        $url = '?' . http_build_query($params);
                        return '<a href="' . $url . '" class="text-decoration-none text-dark">' . $label . $icon . '</a>';
                    }
                    ?>
                    <th><?= sort_link('user_name', 'Colaborador', $sort, $order, $params) ?></th>
                    <th><?= sort_link('training_name', 'Treinamento', $sort, $order, $params) ?></th>
                    <th><?= sort_link('training_code', 'Código', $sort, $order, $params) ?></th>
                    <th><?= sort_link('data_realizacao', 'Data Realização', $sort, $order, $params) ?></th>
                    <th><?= sort_link('data_avaliacao', 'Data Avaliação', $sort, $order, $params) ?></th>
                    <th><?= sort_link('carga_horaria', 'Horas', $sort, $order, $params) ?></th>
                    <th><?= sort_link('instrutor_nome', 'Instrutor', $sort, $order, $params) ?></th>
                    <th><?= sort_link('nota', 'Nota', $sort, $order, $params) ?></th>
                    <th>Aproveitamento</th>
                    <th><?= sort_link('observacoes', 'Observações', $sort, $order, $params) ?></th>
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
                            <td><?= htmlspecialchars($item['observacoes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted">Nenhum treinamento realizado encontrado.</td></tr>
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
    function pageUrl($n, $params) {
        $params['page'] = $n;
        return '?' . http_build_query($params);
    }
    ?>
    
    <!-- Paginação Desktop -->
    <nav aria-label="Navegação de páginas" class="d-none d-md-block">
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <small class="text-muted">
                    Mostrando <?= (($page - 1) * $perPage) + 1 ?> até <?= min($page * $perPage, $total) ?> de <?= $total ?> registro(s)
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
                    Mostrando <?= (($page - 1) * $perPage) + 1 ?> até <?= min($page * $perPage, $total) ?> de <?= $total ?> registro(s)
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
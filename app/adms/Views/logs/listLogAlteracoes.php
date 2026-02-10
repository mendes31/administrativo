<?php
// Remove o parâmetro 'url' da query string para exportação
$getParams = $_GET;
unset($getParams['url']);
$queryString = http_build_query($getParams);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Log de Modificações</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Log de Modificações</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span>Listar</span>
            <?php if (!empty($this->data['return_url'])): ?>
                <a href="<?= htmlspecialchars($this->data['return_url']); ?>"
                   class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para o cadastro
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end" id="filtroLogAlteracoes">
                <div class="col-md-2">
                    <label for="tabela" class="form-label mb-1">Tabela</label>
                    <input type="text" name="tabela" id="tabela" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['tabela'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="objeto_id" class="form-label mb-1">ID do Objeto</label>
                    <input type="text" name="objeto_id" id="objeto_id" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['objeto_id'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="identificador" class="form-label mb-1">Identificador</label>
                    <input type="text" name="identificador" id="identificador" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['identificador'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="usuario_nome" class="form-label mb-1">Usuário</label>
                    <input type="text" name="usuario_nome" id="usuario_nome" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['usuario_nome'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label mb-1">Data Inicial</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['data_inicio'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_fim" class="form-label mb-1">Data Final</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['data_fim'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="tipo" class="form-label mb-1">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="INSERT" <?= ($this->data['filtros']['tipo'] ?? '') === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                        <option value="UPDATE" <?= ($this->data['filtros']['tipo'] ?? '') === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                        <option value="DELETE" <?= ($this->data['filtros']['tipo'] ?? '') === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="input-group input-group-sm mb-2">
                        <label for="per_page" class="input-group-text">Mostrar</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.page.value=1; this.form.submit();">
                            <option value="10" <?= ($this->data['per_page'] ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ($this->data['per_page'] ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ($this->data['per_page'] ?? 10) == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= ($this->data['per_page'] ?? 10) == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                        <span class="input-group-text">registros</span>
                    </div>
                </div>
                <input type="hidden" name="page" value="<?= htmlspecialchars($this->data['pagina_atual'] ?? 1) ?>">
            </form>
            <div class="row mb-3">
                <div class="col text-end">
                    <button type="submit" form="filtroLogAlteracoes" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="?" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Limpar Filtro</a>
                    
                    <!-- Botões de Exportação -->
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] . 'export-log-csv?' . $queryString ?>"><i class="bi bi-file-earmark-text"></i> CSV</a></li>
                            <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] . 'export-log-excel?' . $queryString ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] . 'export-log-pdf?' . $queryString ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tabela Desktop -->
            <div class="table-responsive d-none d-md-block log-desktop list-desktop">
                <table id="logAlteracoesTable" class="table table-bordered table-hover table-striped table-sm">
                    <thead class="table-success">
                        <tr>
                            <th class="text-start" style="width: 4%; padding-left: 8px;">
                                <?php
                                $currentOrder = $this->data['order_by'] ?? 'id';
                                $currentDirection = $this->data['order_direction'] ?? 'DESC';
                                $newDirection = ($currentOrder === 'id' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'id', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    # 
                                    <?php if ($currentOrder === 'id'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 10%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'tabela' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'tabela', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    Tabela
                                    <?php if ($currentOrder === 'tabela'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 6%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'objeto_id' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'objeto_id', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    ID Objeto
                                    <?php if ($currentOrder === 'objeto_id'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 13%; padding-left: 8px;">Identificador</th>
                            <th class="text-start" style="width: 12%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'usuario_nome' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'usuario_nome', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    Usuário
                                    <?php if ($currentOrder === 'usuario_nome'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 10%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'data_alteracao' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'data_alteracao', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    Data
                                    <?php if ($currentOrder === 'data_alteracao'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 7%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'tipo_operacao' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'tipo_operacao', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    Tipo
                                    <?php if ($currentOrder === 'tipo_operacao'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 9%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'ip' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'ip', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    IP
                                    <?php if ($currentOrder === 'ip'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 12%; padding-left: 8px;">
                                <?php
                                $newDirection = ($currentOrder === 'hostname' && $currentDirection === 'DESC') ? 'ASC' : 'DESC';
                                $url = '?' . http_build_query(array_merge($_GET, ['order_by' => 'hostname', 'order_direction' => $newDirection]));
                                ?>
                                <a href="<?= $url ?>" class="text-white text-decoration-none">
                                    Hostname
                                    <?php if ($currentOrder === 'hostname'): ?>
                                        <i class="fas fa-sort-<?= $currentDirection === 'DESC' ? 'down' : 'up' ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-start" style="width: 7%; padding-left: 8px;">Ver Registro</th>
                            <th class="text-start" style="width: 6%; padding-left: 8px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['logs'])): ?>
                            <?php foreach ($this->data['logs'] as $log): ?>
                                <tr class="alteration-row">
                                    <td class="text-start fw-semibold text-primary" style="padding-left: 8px;">
                                        <i class="fas fa-hashtag me-1"></i><?= $log['id'] ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <i class="fas fa-database text-primary me-1"></i><?= htmlspecialchars($log['tabela']) ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <i class="fas fa-key text-info me-1"></i><?= $log['objeto_id'] ?>
                                    </td>
                                    <td class="text-start text-break" style="padding-left: 8px;">
                                        <i class="fas fa-tag text-success me-1"></i><?= htmlspecialchars($log['identificador']) ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <i class="fas fa-user text-success me-1"></i><?= $log['usuario_nome'] ? htmlspecialchars($log['usuario_nome']) : $log['usuario_id'] ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <i class="fas fa-calendar-alt text-warning me-1"></i><?= date('d/m/Y H:i', strtotime($log['data_alteracao'])) ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <?php
                                        $tipoClass = match($log['tipo_operacao']) {
                                            'INSERT' => 'badge bg-success',
                                            'UPDATE' => 'badge bg-warning text-dark',
                                            'DELETE' => 'badge bg-danger',
                                            default => 'badge bg-secondary'
                                        };
                                        ?>
                                        <span class="<?= $tipoClass ?>"><?= htmlspecialchars($log['tipo_operacao']) ?></span>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <i class="fas fa-globe text-danger me-1"></i><?= htmlspecialchars($log['ip']) ?>
                                    </td>
                                    <td class="text-start text-break" style="padding-left: 8px;">
                                        <i class="fas fa-desktop text-info me-1"></i><?= htmlspecialchars($log['hostname'] ?? 'N/A') ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <?php if (!empty($log['link_registro'])): ?>
                                            <a href="<?= $log['link_registro'] ?>" class="btn btn-info btn-sm" target="_blank" title="Ver registro original">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start" style="padding-left: 8px;">
                                        <a href="<?= $_ENV['URL_ADM'] . 'view-log-alteracao/' . $log['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i>Nenhum log encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Mostrando <?php
                            $primeiro = ($this->data['total_registros'] > 0) ? (($this->data['pagina_atual'] - 1) * $this->data['per_page'] + 1) : 0;
                            $ultimo = min($this->data['pagina_atual'] * $this->data['per_page'], $this->data['total_registros']);
                            echo $primeiro . ' até ' . $ultimo . ' de ' . $this->data['total_registros'] . ' registro(s)';
                        ?>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item <?= ($this->data['pagina_atual'] <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1, 'per_page' => $this->data['per_page']])) ?>">Primeiro</a>
                            </li>
                            <li class="page-item <?= ($this->data['pagina_atual'] <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $this->data['pagina_atual'] - 1), 'per_page' => $this->data['per_page']])) ?>">Anterior</a>
                            </li>
                            <?php
                            $totalPaginas = $this->data['total_paginas'];
                            $paginaAtual = $this->data['pagina_atual'];
                            $range = 2;
                            $start = max(1, $paginaAtual - $range);
                            $end = min($totalPaginas, $paginaAtual + $range);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= ($i == $paginaAtual) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i, 'per_page' => $this->data['per_page']])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPaginas, $paginaAtual + 1), 'per_page' => $this->data['per_page']])) ?>">Próximo</a>
                            </li>
                            <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPaginas, 'per_page' => $this->data['per_page']])) ?>">Último</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <?php if (!empty($this->data['detalhes_registro'])): ?>
                <?php
                // Agrupar detalhes por instância (log_alteracao_id)
                $grupos = [];
                foreach ($this->data['detalhes_registro'] as $det) {
                    $grupos[$det['log_alteracao_id']][] = $det;
                }
                ?>
                <hr class="my-4">
                <h5>Detalhes das modificações deste registro (agrupados por instância)</h5>

                <div class="d-flex justify-content-end mb-2 flex-wrap gap-2">
                    <a href="<?= $_ENV['URL_ADM'] . 'export-log-excel?' . $queryString . '&detalhes=1' ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] . 'export-log-pdf?' . $queryString . '&detalhes=1' ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Imprimir PDF
                    </a>
                </div>

                <div class="table-responsive border rounded detalhes-log-wrapper" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-striped table-sm">
                        <thead class="table-secondary">
                            <tr>
                                <th style="width: 4%;">#</th>
                                <th style="width: 14%;">Data</th>
                                <th style="width: 8%;">Tipo</th>
                                <th style="width: 20%;">Campo modificado</th>
                                <th style="width: 27%;">Valor anterior</th>
                                <th style="width: 27%;">Novo valor</th>
                                <th style="width: 12%;">Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $instancia = 1; ?>
                            <?php foreach ($grupos as $logId => $detalhes): ?>
                                <tr class="table-active">
                                    <td colspan="7">
                                        <strong>Instância <?= $instancia++; ?></strong>
                                        &mdash; ID Log: <?= $logId; ?>
                                    </td>
                                </tr>
                                <?php foreach ($detalhes as $det): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= date('d/m/Y H:i', strtotime($det['data_alteracao'])); ?></td>
                                        <td>
                                            <?php
                                            $tipo = strtoupper($det['tipo_operacao'] ?? '');
                                            $tipoClass = match($tipo) {
                                                'INSERT' => 'badge bg-success',
                                                'UPDATE' => 'badge bg-warning text-dark',
                                                'DELETE' => 'badge bg-danger',
                                                default => 'badge bg-secondary'
                                            };
                                            ?>
                                            <span class="<?= $tipoClass ?>"><?= htmlspecialchars($tipo); ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($det['campo'] ?? ''); ?></td>
                                        <td>
                                            <?php if (!empty($det['valor_anterior'])): ?>
                                                <pre class="log-pre log-pre-old mb-0"><?= htmlspecialchars($det['valor_anterior']); ?></pre>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($det['valor_novo'])): ?>
                                                <pre class="log-pre log-pre-new mb-0"><?= htmlspecialchars($det['valor_novo']); ?></pre>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($det['usuario_nome'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Cards Mobile -->
            <div class="d-block d-md-none log-mobile list-mobile">
                <?php if (!empty($this->data['logs'])): ?>
                    <?php foreach ($this->data['logs'] as $i => $log): ?>
                        <div class="card mb-3 shadow-sm border-0">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tag text-success me-2"></i>
                                        <span class="fw-semibold text-primary"><?= htmlspecialchars($log['identificador']) ?></span>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cardLogAltDetails<?= $i ?>" aria-expanded="false" aria-controls="cardLogAltDetails<?= $i ?>">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-cog text-secondary me-2"></i>
                                            <span class="fw-semibold text-secondary">Tipo:</span>
                                        </div>
                                        <div class="ms-4">
                                            <?php
                                            $tipoClass = match($log['tipo_operacao']) {
                                                'INSERT' => 'badge bg-success',
                                                'UPDATE' => 'badge bg-warning text-dark',
                                                'DELETE' => 'badge bg-danger',
                                                default => 'badge bg-secondary'
                                            };
                                            ?>
                                            <span class="<?= $tipoClass ?>"><?= htmlspecialchars($log['tipo_operacao']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar-alt text-warning me-2"></i>
                                            <span class="fw-semibold text-warning">Data/Hora:</span>
                                        </div>
                                        <div class="ms-4">
                                            <span class="badge bg-warning text-dark"><?= date('d/m/Y H:i', strtotime($log['data_alteracao'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse mt-3" id="cardLogAltDetails<?= $i ?>">
                                    <div class="border-top pt-3">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-hashtag text-primary me-2"></i>
                                                    <span class="fw-semibold text-primary">ID:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-primary text-white"><?= $log['id'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-database text-primary me-2"></i>
                                                    <span class="fw-semibold text-primary">Tabela:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-primary text-white"><?= htmlspecialchars($log['tabela']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-key text-info me-2"></i>
                                                    <span class="fw-semibold text-info">Objeto:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-info text-white"><?= $log['objeto_id'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-user text-success me-2"></i>
                                                    <span class="fw-semibold text-success">Usuário:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-success text-white"><?= $log['usuario_nome'] ? htmlspecialchars($log['usuario_nome']) : $log['usuario_id'] ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-globe text-danger me-2"></i>
                                                    <span class="fw-semibold text-danger">IP:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-danger text-white"><?= htmlspecialchars($log['ip']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-desktop text-info me-2"></i>
                                                    <span class="fw-semibold text-info">Hostname:</span>
                                                </div>
                                                <div class="ms-4">
                                                    <span class="badge bg-info text-white"><?= htmlspecialchars($log['hostname'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="d-flex gap-2">
                                                    <?php if (!empty($log['link_registro'])): ?>
                                                        <a href="<?= $log['link_registro'] ?>" class="btn btn-info btn-sm" target="_blank" title="Ver registro original">
                                                            <i class="fas fa-eye me-1"></i>Ver registro
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="<?= $_ENV['URL_ADM'] . 'view-log-alteracao/' . $log['id'] ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-info-circle me-1"></i>Detalhes
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum log encontrado.
                    </div>
                <?php endif; ?>

                <!-- Paginação Mobile -->
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php
                            $primeiro = ($this->data['total_registros'] > 0) ? (($this->data['pagina_atual'] - 1) * $this->data['per_page'] + 1) : 0;
                            $ultimo = min($this->data['pagina_atual'] * $this->data['per_page'], $this->data['total_registros']);
                            if ($this->data['total_registros'] > 0) {
                                echo 'Mostrando ' . $primeiro . ' até ' . $ultimo . ' de ' . $this->data['total_registros'] . ' registro(s)';
                            } else {
                                echo 'Exibindo 0 registro(s) nesta página.';
                            }
                        ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination mb-0 pagination-sm">
                                <li class="page-item <?= ($this->data['pagina_atual'] <= 1) ? 'disabled' : '' ?>" title="Primeiro">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1, 'per_page' => $this->data['per_page']])) ?>" aria-label="Primeiro">&laquo;</a>
                                </li>
                                <li class="page-item <?= ($this->data['pagina_atual'] <= 1) ? 'disabled' : '' ?>" title="Anterior">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $this->data['pagina_atual'] - 1), 'per_page' => $this->data['per_page']])) ?>" aria-label="Anterior">&lsaquo;</a>
                                </li>
                                <?php
                                $totalPaginas = $this->data['total_paginas'];
                                $paginaAtual = $this->data['pagina_atual'];
                                $range = 1;
                                $start = max(1, $paginaAtual - $range);
                                $end = min($totalPaginas, $paginaAtual + $range);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="page-item <?= ($i == $paginaAtual) ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i, 'per_page' => $this->data['per_page']])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>" title="Próximo">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPaginas, $paginaAtual + 1), 'per_page' => $this->data['per_page']])) ?>" aria-label="Próximo">&rsaquo;</a>
                                </li>
                                <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>" title="Último">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPaginas, 'per_page' => $this->data['per_page']])) ?>" aria-label="Último">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para a tabela de logs de alterações */
.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 123, 255, 0.05);
}

.table-striped > tbody > tr:nth-of-type(even) > td {
    background-color: rgba(40, 167, 69, 0.05);
}

.alteration-row:hover {
    background-color: rgba(0, 123, 255, 0.1) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.table th {
    border-top: none;
    font-weight: 600;
    letter-spacing: 0.5px;
    background-color: #28a745 !important;
    color: white !important;
    border-color: #1e7e34 !important;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    padding: 8px 6px;
    text-align: left;
    font-size: 0.9em;
}

.table th {
    text-align: left;
    padding-left: 8px;
    white-space: nowrap;
}

/* Estilos para links de ordenação */
.table th a {
    color: white !important;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.table th a:hover {
    color: #f8f9fa !important;
    text-decoration: none !important;
}

.table th a i {
    font-size: 0.8em;
}

/* Garantir que todas as colunas fiquem visíveis */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

#logAlteracoesTable {
    min-width: 100%;
    table-layout: fixed;
}

/* Ajustar larguras específicas das colunas */
#logAlteracoesTable th:nth-child(1), #logAlteracoesTable td:nth-child(1) { width: 5%; min-width: 50px; }
#logAlteracoesTable th:nth-child(2), #logAlteracoesTable td:nth-child(2) { width: 12%; min-width: 100px; }
#logAlteracoesTable th:nth-child(3), #logAlteracoesTable td:nth-child(3) { width: 8%; min-width: 70px; }
#logAlteracoesTable th:nth-child(4), #logAlteracoesTable td:nth-child(4) { width: 15%; min-width: 120px; }
#logAlteracoesTable th:nth-child(5), #logAlteracoesTable td:nth-child(5) { width: 15%; min-width: 120px; }
#logAlteracoesTable th:nth-child(6), #logAlteracoesTable td:nth-child(6) { width: 12%; min-width: 100px; }
#logAlteracoesTable th:nth-child(7), #logAlteracoesTable td:nth-child(7) { width: 8%; min-width: 70px; }
#logAlteracoesTable th:nth-child(8), #logAlteracoesTable td:nth-child(8) { width: 10%; min-width: 80px; }
#logAlteracoesTable th:nth-child(9), #logAlteracoesTable td:nth-child(9) { width: 8%; min-width: 70px; }
#logAlteracoesTable th:nth-child(10), #logAlteracoesTable td:nth-child(10) { width: 7%; min-width: 60px; }

/* CSS específico para colunas com texto longo */
#logAlteracoesTable th:nth-child(4), #logAlteracoesTable td:nth-child(4) {
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    overflow-wrap: break-word !important;
    hyphens: auto !important;
    line-height: 1.3 !important;
}

/* Otimizar para telas menores */
@media (max-width: 1200px) {
    #logAlteracoesTable th:nth-child(4), #logAlteracoesTable td:nth-child(4) { 
        width: 12%; 
        min-width: 100px; 
    }
    #logAlteracoesTable th:nth-child(5), #logAlteracoesTable td:nth-child(5) { 
        width: 12%; 
        min-width: 100px; 
    }
}

@media (max-width: 992px) {
    .table td {
        font-size: 0.85em;
        padding: 6px 4px;
    }
    
    #logAlteracoesTable th:nth-child(2), #logAlteracoesTable td:nth-child(2) { 
        width: 10%; 
        min-width: 80px; 
    }
    #logAlteracoesTable th:nth-child(4), #logAlteracoesTable td:nth-child(4) { 
        width: 14%; 
        min-width: 100px; 
        font-size: 0.8em;
    }
    #logAlteracoesTable th:nth-child(5), #logAlteracoesTable td:nth-child(5) { 
        width: 12%; 
        min-width: 100px; 
    }
}

/* Estilos para badges */
.badge {
    font-size: 0.85em;
    padding: 6px 10px;
    border-radius: 6px;
    font-weight: 500;
}

/* Estilos para cards mobile */
.log-mobile .card {
    border-left: 4px solid #28a745;
    transition: all 0.2s ease;
}

.log-mobile .card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.log-mobile .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

/* Responsividade */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9em;
    }
    
    .badge {
        font-size: 0.8em;
        padding: 4px 8px;
    }
}

/* Estilos para filtros */
.form-label {
    font-weight: 600;
    color: #495057;
}

.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Botões de ação */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Paginação */
.pagination .page-link {
    border-radius: 6px;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    color: #495057;
    transition: all 0.2s ease;
}

.pagination .page-link:hover {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.pagination .page-item.active .page-link {
    background-color: #28a745;
    border-color: #28a745;
}
</style> 
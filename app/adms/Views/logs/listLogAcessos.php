<?php
// Remove o parâmetro 'url' da query string para exportação
$getParams = $_GET;
unset($getParams['url']);
$queryString = http_build_query($getParams);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Log de Acessos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Log de Acessos</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end" id="filtroLogAcessos">
                <div class="col-md-2">
                    <label for="usuario_nome" class="form-label mb-1">Usuário</label>
                    <input type="text" name="usuario_nome" id="usuario_nome" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['usuario_nome'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="tipo_acesso" class="form-label mb-1">Tipo</label>
                    <select name="tipo_acesso" id="tipo_acesso" class="form-select">
                        <option value="">Todos</option>
                        <option value="LOGIN" <?= ($this->data['filtros']['tipo_acesso'] ?? '') === 'LOGIN' ? 'selected' : '' ?>>LOGIN</option>
                        <option value="LOGOUT" <?= ($this->data['filtros']['tipo_acesso'] ?? '') === 'LOGOUT' ? 'selected' : '' ?>>LOGOUT</option>
                        <option value="LOGOUT_TIMEOUT" <?= ($this->data['filtros']['tipo_acesso'] ?? '') === 'LOGOUT_TIMEOUT' ? 'selected' : '' ?>>LOGOUT_TIMEOUT</option>
                        <option value="LOGOUT_CONCURRENT" <?= ($this->data['filtros']['tipo_acesso'] ?? '') === 'LOGOUT_CONCURRENT' ? 'selected' : '' ?>>LOGOUT_CONCURRENT</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="ip" class="form-label mb-1">IP</label>
                    <input type="text" name="ip" id="ip" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['ip'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label mb-1">Data Inicial</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['data_inicio'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_fim" class="form-label mb-1">Data Final</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['data_fim'] ?? '') ?>">
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
                    <button type="submit" form="filtroLogAcessos" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="?" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Limpar Filtro</a>
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] . 'export-log-acessos-excel?' . $queryString ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] . 'export-log-acessos-pdf?' . $queryString ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tabela Desktop -->
            <div class="table-responsive d-none d-md-block log-desktop list-desktop">
                <table id="logTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>IP</th>
                            <th>User Agent</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['logs'])): ?>
                            <?php foreach ($this->data['logs'] as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['usuario_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['usuario_email'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $tipoClass = match($log['tipo_acesso']) {
                                            'LOGIN' => 'badge bg-success',
                                            'LOGOUT' => 'badge bg-secondary',
                                            'LOGOUT_TIMEOUT' => 'badge bg-warning text-dark',
                                            'LOGOUT_CONCURRENT' => 'badge bg-info text-dark',
                                            default => 'badge bg-secondary'
                                        };
                                        ?>
                                        <span class="<?= $tipoClass ?>"><?= htmlspecialchars($log['tipo_acesso']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip']) ?></td>
                                    <td><?= htmlspecialchars($log['user_agent']) ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['data_acesso'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7">Nenhum log encontrado.</td></tr>
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

            <!-- Cards Mobile -->
            <div class="d-block d-md-none log-mobile list-mobile">
                <?php if (!empty($this->data['logs'])): ?>
                    <?php foreach ($this->data['logs'] as $i => $log): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($log['usuario_nome'] ?? '-') ?></b></h5>
                                        <div class="mb-1">
                                            <b>Tipo:</b> 
                                            <?php
                                            $tipoClass = match($log['tipo_acesso']) {
                                                'LOGIN' => 'badge bg-success',
                                                'LOGOUT' => 'badge bg-secondary',
                                                'LOGOUT_TIMEOUT' => 'badge bg-warning text-dark',
                                                'LOGOUT_CONCURRENT' => 'badge bg-info text-dark',
                                                default => 'badge bg-secondary'
                                            };
                                            ?>
                                            <span class="<?= $tipoClass ?>"><?= htmlspecialchars($log['tipo_acesso']) ?></span>
                                        </div>
                                        <div class="mb-1"><b>Data/Hora:</b> <?= date('d/m/Y H:i:s', strtotime($log['data_acesso'])) ?></div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardLogDetails<?= $i ?>" aria-expanded="false" aria-controls="cardLogDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardLogDetails<?= $i ?>">
                                    <div><b>ID:</b> <?= $log['id'] ?></div>
                                    <div><b>Email:</b> <?= htmlspecialchars($log['usuario_email'] ?? '-') ?></div>
                                    <div><b>IP:</b> <?= htmlspecialchars($log['ip']) ?></div>
                                    <div class="mt-1"><b>User Agent:</b>
                                        <div class="small text-break"><?= htmlspecialchars($log['user_agent']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">Nenhum log encontrado.</div>
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
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/logs-list.js"></script>
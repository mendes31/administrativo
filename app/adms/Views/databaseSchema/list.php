<?php
$filterModule = htmlspecialchars((string) ($this->data['filter_module'] ?? ''));
$filterSearch = htmlspecialchars((string) ($this->data['filter_search'] ?? ''));
$databaseName = htmlspecialchars((string) ($this->data['database_name'] ?? ''));
$appVersion = htmlspecialchars((string) ($_ENV['APP_VERSION'] ?? '1.0'));
$canView = in_array('ViewDatabaseTable', $this->data['buttonPermission'] ?? [], true);
$urlAdm = htmlspecialchars((string) ($_ENV['URL_ADM'] ?? ''));
$totalTables = (int) ($this->data['pagination']['total'] ?? count($this->data['tables'] ?? []));
$catalogUpdatedAt = trim((string) ($this->data['catalog_updated_at'] ?? ''));
$canRefresh = in_array('ListDatabaseTables', $this->data['menuPermission'] ?? [], true);
?>
<div class="container-fluid px-4 db-schema-page">
    <div class="db-schema-breadcrumb text-muted small mb-2 mt-3">
        <strong class="text-dark"><?= $databaseName ?></strong>
        <span class="mx-1">|</span>
        <span>Sistema administrativo</span>
        <span class="mx-1">|</span>
        <span>Biblioteca de tabelas</span>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="h4 mb-0">Biblioteca — Base de dados do sistema</h2>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($catalogUpdatedAt !== ''): ?>
            <span class="text-muted small">Atualizado em <?= htmlspecialchars($catalogUpdatedAt) ?></span>
            <?php endif; ?>
            <span class="text-muted small fw-semibold"><?= $totalTables ?> TABELAS</span>
            <?php if ($canRefresh): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Consultar o INFORMATION_SCHEMA pode levar alguns segundos. Deseja atualizar o catálogo agora?');">
                <input type="hidden" name="refresh_catalog" value="1">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-rotate"></i> Atualizar catálogo
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <p class="text-muted small mb-3">
                Catálogo MySQL da base <strong><?= $databaseName ?></strong> em cache local.
                A lista carrega automaticamente; ao abrir uma tabela, colunas e índices são sincronizados.
                Após migrations, use <strong>Atualizar catálogo</strong> para incluir novas tabelas e colunas.
            </p>

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="q" class="form-label mb-1 small">Buscar</label>
                    <input type="search" name="q" id="q" value="<?= $filterSearch ?>" class="form-control form-control-sm" placeholder="Tabela ou descrição…">
                </div>
                <div class="col-md-3">
                    <label for="module" class="form-label mb-1 small">Módulo</label>
                    <select name="module" id="module" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['modules'] ?? [] as $mod): ?>
                            <option value="<?= htmlspecialchars($mod) ?>" <?= $filterModule === $mod ? 'selected' : '' ?>><?= htmlspecialchars($mod) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="per_page" class="form-label mb-1 small">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm">
                        <?php foreach ([10, 25, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (int) ($this->data['per_page'] ?? 25) === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="list-database-tables" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                </div>
            </form>

            <?php if (!empty($this->data['tables'])): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle db-schema-list">
                    <thead class="table-primary">
                        <tr>
                            <th>Schema</th>
                            <th>Produto</th>
                            <th>Versão</th>
                            <th>Tabela</th>
                            <th>Descrição</th>
                            <th class="text-center">Colunas</th>
                            <th class="text-center">Índices</th>
                            <th>Módulo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['tables'] as $row):
                            $tableName = (string) ($row['table_name'] ?? '');
                            $tableNameEsc = htmlspecialchars($tableName);
                            $comment = trim((string) ($row['table_comment'] ?? ''));
                            $viewUrl = $urlAdm . 'view-database-table/' . rawurlencode(str_replace('_', '-', $tableName));
                        ?>
                        <tr>
                            <td><code><?= $databaseName ?></code></td>
                            <td>MySQL / Sistema</td>
                            <td><?= $appVersion ?></td>
                            <td>
                                <?php if ($canView): ?>
                                <a href="<?= htmlspecialchars($viewUrl) ?>" class="text-decoration-none"><code class="db-table-link"><?= $tableNameEsc ?></code></a>
                                <?php else: ?>
                                <code><?= $tableNameEsc ?></code>
                                <?php endif; ?>
                            </td>
                            <td><?= $comment !== '' ? htmlspecialchars($comment) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center"><?= (int) ($row['column_count'] ?? 0) ?></td>
                            <td class="text-center"><?= (int) ($row['index_count'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($row['module'] ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $paginationHtml = $this->data['pagination']['html'] ?? '';
            if ($paginationHtml !== '') {
                echo $paginationHtml;
            }
            if (!empty($this->data['pagination']['total'])): ?>
            <p class="text-muted small mt-2 mb-0">
                Mostrando <?= (int) $this->data['pagination']['first_item'] ?>
                até <?= (int) $this->data['pagination']['last_item'] ?>
                de <?= (int) $this->data['pagination']['total'] ?> tabela(s)
            </p>
            <?php endif; ?>
            <?php else: ?>
            <div class="alert alert-warning mb-0">Nenhuma tabela encontrada com os filtros informados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.db-schema-list thead th { font-size: .78rem; text-transform: uppercase; }
.db-table-link { color: #0d6efd; font-weight: 600; }
.db-table-link:hover { text-decoration: underline !important; }
</style>

<?php
$table = $this->data['table'] ?? [];
$tableName = (string) ($this->data['table_name'] ?? '');
$tableNameEsc = htmlspecialchars($tableName);
$databaseName = htmlspecialchars((string) ($this->data['database_name'] ?? ''));
$module = htmlspecialchars((string) ($table['module'] ?? 'Geral'));
$description = htmlspecialchars(trim((string) ($table['table_comment'] ?? '')) ?: '—');
$columnCount = (int) ($this->data['column_count'] ?? 0);
$indexCount = (int) ($this->data['index_count'] ?? 0);
$canList = in_array('ListDatabaseTables', $this->data['buttonPermission'] ?? [], true);
$urlAdm = htmlspecialchars((string) ($_ENV['URL_ADM'] ?? ''));
$appVersion = htmlspecialchars((string) ($_ENV['APP_VERSION'] ?? '1.0'));
$createDdl = (string) ($this->data['create_ddl'] ?? '');

$dbTableUrl = static function (string $name) use ($urlAdm): string {
    return $urlAdm . 'view-database-table/' . rawurlencode(str_replace('_', '-', $name));
};
?>
<div class="container-fluid px-4 db-schema-page">
    <div class="db-schema-breadcrumb text-muted small mb-2 mt-3">
        <a href="<?= $urlAdm ?>list-database-tables" class="text-decoration-none"><?= $databaseName ?></a>
        <span class="mx-1">|</span>
        <span><?= $module ?></span>
        <span class="mx-1">|</span>
        <strong class="text-dark"><?= $tableNameEsc ?></strong>
        <?php if ($description !== '—'): ?>
        <span class="ms-1 text-muted"><?= $description ?></span>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="h4 mb-0">
            <code><?= $tableNameEsc ?></code>
            <?php if ($description !== '—'): ?>
            <span class="text-muted fw-normal fs-6"><?= $description ?></span>
            <?php endif; ?>
        </h2>
        <div class="text-muted small fw-semibold">
            <?= $columnCount ?> COLUNAS | <?= $indexCount ?> ÍNDICES
        </div>
    </div>

    <?php if ($canList): ?>
    <p class="mb-3">
        <a href="<?= $urlAdm ?>list-database-tables" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Voltar à biblioteca
        </a>
    </p>
    <?php endif; ?>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 db-schema-summary">
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
                    <tr>
                        <td><code><?= $databaseName ?></code></td>
                        <td>MySQL / Sistema</td>
                        <td><?= $appVersion ?></td>
                        <td><code><?= $tableNameEsc ?></code></td>
                        <td><?= $description ?></td>
                        <td class="text-center"><?= $columnCount ?></td>
                        <td class="text-center"><?= $indexCount ?></td>
                        <td><?= $module ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header p-0 bg-white border-bottom-0">
            <ul class="nav nav-tabs db-schema-sql-tabs" id="dbSqlTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-select" data-bs-toggle="tab" data-bs-target="#pane-select" type="button" role="tab">Select SQL</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-insert" data-bs-toggle="tab" data-bs-target="#pane-insert" type="button" role="tab">Insert SQL</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-update" data-bs-toggle="tab" data-bs-target="#pane-update" type="button" role="tab">Update SQL</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-create" data-bs-toggle="tab" data-bs-target="#pane-create" type="button" role="tab">Create SQL</button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content pt-3">
            <div class="tab-pane fade show active" id="pane-select" role="tabpanel">
                <textarea id="sql-select" class="form-control font-monospace small db-schema-sql-box" rows="4" readonly></textarea>
            </div>
            <div class="tab-pane fade" id="pane-insert" role="tabpanel">
                <textarea id="sql-insert" class="form-control font-monospace small db-schema-sql-box" rows="4" readonly></textarea>
            </div>
            <div class="tab-pane fade" id="pane-update" role="tabpanel">
                <textarea id="sql-update" class="form-control font-monospace small db-schema-sql-box" rows="4" readonly></textarea>
            </div>
            <div class="tab-pane fade" id="pane-create" role="tabpanel">
                <textarea id="sql-create" class="form-control font-monospace small db-schema-sql-box" rows="12" readonly><?= htmlspecialchars($createDdl) ?></textarea>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0" for="db-col-per-page">Mostrar</label>
                <select id="db-col-per-page" class="form-select form-select-sm" style="width:auto;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="9999">Todas</option>
                </select>
                <span class="small text-muted">colunas</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0" for="db-col-search">Buscar</label>
                <input type="search" id="db-col-search" class="form-control form-control-sm" placeholder="Campo ou descrição…" style="min-width:200px;">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle mb-0 db-schema-columns" id="db-columns-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:2rem;"><input type="checkbox" id="db-col-check-all" checked title="Marcar todos"></th>
                        <th>Col#</th>
                        <th>Campo</th>
                        <th>Descrição</th>
                        <th>Tipo SQL</th>
                        <th>Tamanho</th>
                        <th>Decimais</th>
                        <th>Relação</th>
                        <th>Padrão</th>
                        <th>Restrições</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->data['columns'] ?? [] as $col):
                        $field = (string) ($col['COLUMN_NAME'] ?? '');
                        $fieldEsc = htmlspecialchars($field);
                        $desc = htmlspecialchars(trim((string) ($col['COLUMN_COMMENT'] ?? '')) ?: '—');
                        $relTable = (string) ($col['relation_table'] ?? '');
                        $defaultVal = $col['COLUMN_DEFAULT'];
                        $defaultDisplay = $defaultVal === null ? 'NULL' : (string) $defaultVal;
                    ?>
                    <tr class="db-col-row" data-field="<?= $fieldEsc ?>" data-search="<?= htmlspecialchars(strtolower($field . ' ' . ($col['COLUMN_COMMENT'] ?? '') . ' ' . ($col['base_type'] ?? ''))) ?>">
                        <td><input type="checkbox" class="db-col-check" value="<?= $fieldEsc ?>" checked></td>
                        <td><?= (int) ($col['ORDINAL_POSITION'] ?? 0) ?></td>
                        <td><code class="db-field-name"><?= $fieldEsc ?></code></td>
                        <td><?= $desc ?></td>
                        <td><?= htmlspecialchars((string) ($col['base_type'] ?? '')) ?></td>
                        <td><?= ($col['length'] ?? '') !== '' ? htmlspecialchars((string) $col['length']) : '—' ?></td>
                        <td><?= ($col['decimals'] ?? '') !== '' ? htmlspecialchars((string) $col['decimals']) : '—' ?></td>
                        <td>
                            <?php if ($relTable !== ''): ?>
                            <a href="<?= htmlspecialchars($dbTableUrl($relTable)) ?>" class="text-decoration-none"><code><?= htmlspecialchars($relTable) ?></code></a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="small"><?= htmlspecialchars($defaultDisplay) ?></code></td>
                        <td class="small"><?= htmlspecialchars((string) ($col['constraints_label'] ?? '—')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted" id="db-col-footer"></div>
    </div>

    <?php if (!empty($this->data['indexes'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Índices</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr><th>Nome</th><th>Único</th><th>Colunas</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($this->data['indexes'] as $idx): ?>
                    <tr>
                        <td><code><?= htmlspecialchars((string) ($idx['INDEX_NAME'] ?? '')) ?></code></td>
                        <td><?= ((int) ($idx['NON_UNIQUE'] ?? 1)) === 0 ? 'Sim' : 'Não' ?></td>
                        <td><code><?= htmlspecialchars((string) ($idx['columns'] ?? '')) ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.db-schema-page .db-schema-summary thead th { font-size: .78rem; text-transform: uppercase; }
.db-schema-sql-tabs .nav-link { font-size: .85rem; font-weight: 600; color: #495057; }
.db-schema-sql-tabs .nav-link.active { color: #0d6efd; border-bottom: 2px solid #0d6efd; }
.db-schema-sql-box { background: #f8f9fa; font-size: .8rem; }
.db-schema-columns thead th { font-size: .75rem; white-space: nowrap; }
.db-schema-columns .db-field-name { color: #0d6efd; font-weight: 600; }
.db-col-row.is-hidden { display: none; }
</style>

<script>
(function () {
    var tableName = <?= json_encode($tableName, JSON_UNESCAPED_UNICODE) ?>;
    var rows = document.querySelectorAll('#db-columns-table .db-col-row');
    var checks = document.querySelectorAll('.db-col-check');
    var checkAll = document.getElementById('db-col-check-all');
    var searchInput = document.getElementById('db-col-search');
    var perPage = document.getElementById('db-col-per-page');
    var footer = document.getElementById('db-col-footer');
    var sqlSelect = document.getElementById('sql-select');
    var sqlInsert = document.getElementById('sql-insert');
    var sqlUpdate = document.getElementById('sql-update');

    function selectedFields() {
        var fields = [];
        document.querySelectorAll('.db-col-check:checked').forEach(function (cb) {
            fields.push(cb.value);
        });
        return fields;
    }

    function quote(fields) {
        return fields.map(function (f) { return '`' + f + '`'; });
    }

    function updateSql() {
        var fields = selectedFields();
        var q = quote(fields);
        if (fields.length === 0) {
            sqlSelect.value = '-- Selecione ao menos uma coluna';
            sqlInsert.value = sqlSelect.value;
            sqlUpdate.value = sqlSelect.value;
            return;
        }
        sqlSelect.value = 'SELECT ' + q.join(', ') + '\nFROM `' + tableName + '`\nLIMIT 25;';
        sqlInsert.value = 'INSERT INTO `' + tableName + '` (' + q.join(', ') + ')\nVALUES (' + fields.map(function () { return '?'; }).join(', ') + ');';
        var setParts = fields.map(function (f) { return '`' + f + '` = ?'; });
        sqlUpdate.value = 'UPDATE `' + tableName + '`\nSET ' + setParts.join(',\n    ') + '\nWHERE `id` = ?;';
    }

    function applyFilters() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var limit = parseInt(perPage.value, 10) || 25;
        var shown = 0;
        var matched = 0;
        rows.forEach(function (row) {
            var blob = row.getAttribute('data-search') || '';
            var match = q === '' || blob.indexOf(q) !== -1;
            row.classList.toggle('is-hidden', !match);
            if (match) {
                matched++;
                if (shown < limit) {
                    row.style.display = '';
                    shown++;
                } else {
                    row.style.display = 'none';
                }
            } else {
                row.style.display = 'none';
            }
        });
        footer.textContent = 'Exibindo ' + shown + ' de ' + matched + ' coluna(s)' + (q !== '' ? ' (filtradas)' : '');
    }

    checks.forEach(function (cb) { cb.addEventListener('change', updateSql); });
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checks.forEach(function (cb) { cb.checked = checkAll.checked; });
            updateSql();
        });
    }
    searchInput.addEventListener('input', applyFilters);
    perPage.addEventListener('change', applyFilters);

    updateSql();
    applyFilters();
})();
</script>

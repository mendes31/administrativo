<?php
$getParams = $_GET;
unset($getParams['url']);
$queryString = http_build_query($getParams);
$canExport = in_array('ExportRhCandidatoAnexoAccessLogsExcel', $this->data['buttonPermission'] ?? [], true);
$filtros = $this->data['filtros'] ?? [];
$logs = $this->data['logs'] ?? [];
$total = (int) ($this->data['total_registros'] ?? 0);
$page = (int) ($this->data['pagina_atual'] ?? 1);
$totalPages = (int) ($this->data['total_paginas'] ?? 1);
$perPage = (int) ($this->data['per_page'] ?? 50);
$base = $_ENV['URL_ADM'] ?? '';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Log de download de currículos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($base) ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Auditoria LGPD</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-file-download me-1"></i>Downloads de anexos de candidatos</span>
            <span class="ms-auto text-muted small"><?= $total ?> registro(s)</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="get" class="row g-2 mb-3 align-items-end" id="filtroAnexoAccessLogs">
                <div class="col-md-2">
                    <label for="actor_nome" class="form-label mb-1">Quem baixou</label>
                    <input type="text" name="actor_nome" id="actor_nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['actor_nome'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="candidato_nome" class="form-label mb-1">Candidato</label>
                    <input type="text" name="candidato_nome" id="candidato_nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['candidato_nome'] ?? '')) ?>">
                </div>
                <div class="col-md-1">
                    <label for="candidato_id" class="form-label mb-1">ID cand.</label>
                    <input type="number" name="candidato_id" id="candidato_id" class="form-control form-control-sm" min="1"
                           value="<?= htmlspecialchars((string) ($filtros['candidato_id'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="source" class="form-label mb-1">Fonte</label>
                    <select name="source" id="source" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="authorized_controller" <?= ($filtros['source'] ?? '') === 'authorized_controller' ? 'selected' : '' ?>>authorized_controller</option>
                        <option value="legacy_file_server" <?= ($filtros['source'] ?? '') === 'legacy_file_server' ? 'selected' : '' ?>>legacy_file_server</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="ip" class="form-label mb-1">IP</label>
                    <input type="text" name="ip" id="ip" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['ip'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label mb-1">Data inicial</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['data_inicio'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_fim" class="form-label mb-1">Data final</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($filtros['data_fim'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="per_page" class="form-label mb-1">Por página</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm">
                        <?php foreach ([10, 20, 50, 100] as $n): ?>
                            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="page" value="1">
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="<?= htmlspecialchars($base) ?>list-rh-candidato-anexo-access-logs" class="btn btn-outline-secondary btn-sm">Limpar</a>
                    <?php if ($canExport): ?>
                        <a href="<?= htmlspecialchars($base) ?>export-rh-candidato-anexo-access-logs-excel?<?= htmlspecialchars($queryString) ?>"
                           class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i>Excel</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Quem baixou</th>
                            <th>Candidato</th>
                            <th>Anexo</th>
                            <th>Fonte</th>
                            <th>Modo</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum download registrado com os filtros atuais.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $candId = (int) ($log['rh_candidato_id'] ?? 0);
                                $candNome = (string) ($log['candidato_nome'] ?? '');
                                ?>
                                <tr>
                                    <td><?= (int) ($log['id'] ?? 0) ?></td>
                                    <td class="text-nowrap">
                                        <?= !empty($log['created_at'])
                                            ? htmlspecialchars(date('d/m/Y H:i:s', strtotime((string) $log['created_at'])))
                                            : '—' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) ($log['actor_name'] ?? '—')) ?>
                                        <?php if (!empty($log['actor_email'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars((string) $log['actor_email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($candId > 0): ?>
                                            <a href="<?= htmlspecialchars($base) ?>rh-candidatos-view/<?= $candId ?>">
                                                #<?= $candId ?>
                                                <?= $candNome !== '' ? ' — ' . htmlspecialchars(mb_substr($candNome, 0, 40)) : '' ?>
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($log['rh_candidato_anexo_id']) ? '#' . (int) $log['rh_candidato_anexo_id'] : '—' ?>
                                        <?php if (!empty($log['anexo_tipo'])): ?>
                                            <span class="badge text-bg-light border"><?= htmlspecialchars((string) $log['anexo_tipo']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="small"><?= htmlspecialchars((string) ($log['source'] ?? '')) ?></code></td>
                                    <td><?= htmlspecialchars((string) ($log['delivery_mode'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($log['ip_address'] ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação do log">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $q = $filtros;
                        $q['per_page'] = $perPage;
                        for ($p = 1; $p <= $totalPages; $p++):
                            $q['page'] = $p;
                            ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars(http_build_query($q)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

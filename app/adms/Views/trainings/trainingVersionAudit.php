<?php
$filters = $this->data['filters'] ?? ['family' => '', 'codigo' => ''];
$rows = $this->data['rows'] ?? [];
$alerts = $this->data['alerts'] ?? [];
$alertCounts = $this->data['alertCounts'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Auditoria de Versões</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a></li>
            <li class="breadcrumb-item">Auditoria</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-shield-alt me-2"></i>Auditoria de inconsistências de versionamento</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('ListTrainings', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary btn-sm mb-1"><i class="fas fa-list me-1"></i>Treinamentos</a>
                <?php endif; ?>
                <?php if (in_array('ListTrainingStatus', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-info btn-sm mb-1"><i class="fas fa-chart-bar me-1"></i>Status</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="family" class="form-label mb-1">Família</label>
                    <input type="text" name="family" id="family" class="form-control" value="<?= htmlspecialchars((string)($filters['family'] ?? '')) ?>" placeholder="Ex.: POP-CQ-0005">
                </div>
                <div class="col-md-4">
                    <label for="codigo" class="form-label mb-1">Código</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" value="<?= htmlspecialchars((string)($filters['codigo'] ?? '')) ?>" placeholder="Ex.: POP-CQ-0005">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>training-version-audit?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-eraser me-1"></i>Limpar</a>
                </div>
            </form>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="alert alert-warning mb-0 py-2">
                        <strong><?= (int)($alertCounts['families_with_invalid_current_count'] ?? 0) ?></strong> famílias com quantidade inválida de versão atual.
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-warning mb-0 py-2">
                        <strong><?= (int)($alertCounts['families_with_invalid_active_count'] ?? 0) ?></strong> famílias com quantidade inválida de versão ativa.
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-danger mb-0 py-2">
                        <strong><?= (int)($alertCounts['current_versions_not_active'] ?? 0) ?></strong> versões atuais marcadas como inativas.
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-success">
                        <tr>
                            <th>Família</th>
                            <th>Código</th>
                            <th>ID</th>
                            <th>Versão</th>
                            <th>Status versão</th>
                            <th>Status treinamento</th>
                            <th>Versão origem</th>
                            <th>Resumo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($row['familia'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars((string)($row['codigo'] ?? '-')) ?></td>
                                    <td><?= (int)($row['training_id'] ?? 0) ?></td>
                                    <td>v<?= htmlspecialchars((string)($row['versao'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ((int)($row['is_current_version'] ?? 0) === 1): ?>
                                            <span class="badge bg-success">Atual</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Anterior</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)($row['ativo'] ?? 0) === 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($row['versao_origem_id']) ? (int)$row['versao_origem_id'] : '-' ?></td>
                                    <td><?= htmlspecialchars((string)($row['resumo_alteracoes'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum registro encontrado para os filtros informados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <h6>Famílias com qtd. atual diferente de 1</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Família</th><th>Total</th><th>Atuais</th></tr></thead>
                            <tbody>
                            <?php foreach (($alerts['families_with_invalid_current_count'] ?? []) as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($item['familia'] ?? '-')) ?></td>
                                    <td><?= (int)($item['total_versoes'] ?? 0) ?></td>
                                    <td><?= (int)($item['qtd_atuais'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alerts['families_with_invalid_current_count'])): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sem inconsistências.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Famílias com qtd. ativa diferente de 1</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Família</th><th>Total</th><th>Ativas</th></tr></thead>
                            <tbody>
                            <?php foreach (($alerts['families_with_invalid_active_count'] ?? []) as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($item['familia'] ?? '-')) ?></td>
                                    <td><?= (int)($item['total_versoes'] ?? 0) ?></td>
                                    <td><?= (int)($item['qtd_ativas'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alerts['families_with_invalid_active_count'])): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sem inconsistências.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Versões atuais inativas</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Família</th><th>ID</th><th>Versão</th></tr></thead>
                            <tbody>
                            <?php foreach (($alerts['current_versions_not_active'] ?? []) as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($item['familia'] ?? '-')) ?></td>
                                    <td><?= (int)($item['training_id'] ?? 0) ?></td>
                                    <td>v<?= htmlspecialchars((string)($item['versao'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alerts['current_versions_not_active'])): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sem inconsistências.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


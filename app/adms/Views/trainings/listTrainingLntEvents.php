<?php
$filters = $this->data['filters'] ?? [];
$pagination = $this->data['pagination'] ?? ['page' => 1, 'pages' => 1, 'total' => 0];
$items = $this->data['items'] ?? [];

$eventTypes = [
    '' => 'Todos',
    'novo_colaborador' => 'Novo colaborador',
    'novo_cargo' => 'Novo cargo',
    'colaborador_desligado' => 'Colaborador desligado',
    'alteracao_cargo' => 'Alteração de cargo',
];

function lntFmtDate(?string $d): string {
    if (!$d || $d === '0000-00-00') return '-';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : '-';
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Eventos LNT (RH)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-trainings" class="text-decoration-none">Treinamentos</a></li>
            <li class="breadcrumb-item">Eventos LNT</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Registro de movimentações de RH que impactam o <strong>LNT</strong>: novos colaboradores, novos cargos, desligamentos e alterações de cargo.
                A equipe de treinamentos recebe notificação in-app e, diariamente, um e-mail com o resumo do dia anterior (se habilitado em Notificações Automáticas).
            </p>

            <form method="get" class="row g-2 align-items-end mb-3">
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="event_type" class="form-select form-select-sm">
                        <?php foreach ($eventTypes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= ($filters['event_type'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">De</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Até</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Busca</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Nome, CPF, setor ou cargo" value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm me-1">Filtrar</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-training-lnt-events" class="btn btn-secondary btn-sm me-1">Limpar</a>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-training-lnt-events?<?= http_build_query(array_merge($filters, ['export' => 'csv'])) ?>" class="btn btn-success btn-sm">Exportar CSV</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Setor</th>
                            <th>Cargo</th>
                            <th>Admissão</th>
                            <th>Desligamento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="9" class="text-center text-muted">Nenhum evento encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $row): ?>
                                <tr>
                                    <td><?= lntFmtDate(substr((string)($row['created_at'] ?? ''), 0, 10)) ?> <?= htmlspecialchars(substr((string)($row['created_at'] ?? ''), 11, 5)) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['action_label'] ?? '') ?></span></td>
                                    <td><?= htmlspecialchars($row['collaborator_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['collaborator_cpf'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['department_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['position_name'] ?? '-') ?></td>
                                    <td><?= lntFmtDate($row['data_admissao'] ?? null) ?></td>
                                    <td><?= lntFmtDate($row['data_desligamento'] ?? null) ?></td>
                                    <td>
                                        <?php if (!empty($row['user_id'])): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>matrix-by-user?colaborador=<?= (int)$row['user_id'] ?>&export=lnt" class="btn btn-outline-primary btn-sm" title="Gerar LNT">LNT</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($pagination['pages'] ?? 1) > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= (int)$pagination['pages']; $p++): ?>
                            <li class="page-item <?= $p === (int)$pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $_ENV['URL_ADM'] ?>list-training-lnt-events?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

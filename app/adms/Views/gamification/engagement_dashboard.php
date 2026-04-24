<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0">Dashboard de engajamento</h2>
        <form method="get" class="ms-md-auto d-flex align-items-center gap-2">
            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($this->data['month_ref'] ?? date('Y-m'))) ?>">
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </form>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php $kpi = $this->data['indicators'] ?? []; ?>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-light shadow h-100"><div class="card-body">
                <div class="text-muted small">Usuários ativos no mês</div>
                <div class="fs-4 fw-semibold"><?= (int)($kpi['active_users'] ?? 0) ?></div>
            </div></div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-light shadow h-100"><div class="card-body">
                <div class="text-muted small">Pontos distribuídos no mês</div>
                <div class="fs-4 fw-semibold"><?= (int)($kpi['total_points_month'] ?? 0) ?></div>
            </div></div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-light shadow h-100"><div class="card-body">
                <div class="text-muted small">Bloqueios anti-fraude (mês)</div>
                <div class="fs-4 fw-semibold"><?= (int)($kpi['anti_fraud_blocks'] ?? 0) ?></div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Ranking por setor (mês)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Setor</th><th class="text-end">Pontos</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['department_engagement'] ?? []) as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($row['department_name'] ?? '')) ?></td>
                                    <td class="text-end"><?= (int)($row['total_points'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Níveis configurados</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Nível</th><th class="text-end">Pontos mínimos</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['levels'] ?? []) as $level): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($level['name'] ?? '')) ?></td>
                                    <td class="text-end"><?= (int)($level['min_points'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Badges ativos</div>
                <ul class="list-group list-group-flush">
                    <?php foreach (($this->data['badges'] ?? []) as $badge): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars((string)($badge['name'] ?? '')) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars((string)($badge['description'] ?? '')) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Missões semanais</div>
                <ul class="list-group list-group-flush">
                    <?php foreach (($this->data['weekly_missions'] ?? []) as $mission): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars((string)($mission['title'] ?? '')) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars((string)($mission['description'] ?? '')) ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= (int)($mission['reward_points'] ?? 0) ?> pts</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-light shadow">
                <div class="card-header">Bloqueios anti-fraude recentes</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Colaborador</th><th>Evento</th><th>Motivo</th><th>Data</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['anti_fraud_events'] ?? []) as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($event['user_name'] ?? '')) ?></td>
                                    <td><code><?= htmlspecialchars((string)($event['event_key'] ?? '')) ?></code></td>
                                    <td><?= htmlspecialchars((string)($event['reason'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($event['created_at'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

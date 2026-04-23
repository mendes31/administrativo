<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0">Regras de pontos — Timeline</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Comunicação Interna</li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-sliders-h me-2"></i>Regras configuráveis</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Chave</th>
                        <th>Título</th>
                        <th>Pontos</th>
                        <th>Máx./dia</th>
                        <th>Máx. total</th>
                        <th>Ativo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['rules'] ?? [] as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string)($r['event_key'] ?? '')) ?></code></td>
                            <td><?= htmlspecialchars((string)($r['title'] ?? '')) ?></td>
                            <td><?= (int)($r['points'] ?? 0) ?></td>
                            <td><?= $r['max_awards_per_user_per_day'] !== null ? (int)$r['max_awards_per_user_per_day'] : '—' ?></td>
                            <td><?= $r['max_awards_per_user_total'] !== null ? (int)$r['max_awards_per_user_total'] : '—' ?></td>
                            <td>
                                <?php if (!empty($r['is_active'])): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (in_array('UpdateGamificationTimelineRule', $this->data['buttonPermission'] ?? [], true)) { ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-timeline-rule/<?= (int)$r['id'] ?>"
                                       class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0"><i class="fas fa-trophy text-warning me-2"></i>Ranking de pontos</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <p class="text-muted small">Soma dos pontos registados no sistema (timeline e quizzes concluídos com pontuação).</p>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:4rem;">#</th>
                        <th>Colaborador</th>
                        <th class="text-end">Pontos</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rows = $this->data['leaderboard'] ?? [];
                    $pos = 0;
                    foreach ($rows as $r):
                        $pos++;
                        ?>
                        <tr>
                            <td class="text-center fw-semibold"><?= $pos ?></td>
                            <td><?= htmlspecialchars((string)($r['user_name'] ?? '')) ?></td>
                            <td class="text-end fw-bold"><?= (int)($r['total_points'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($rows === []): ?>
                <div class="p-4 text-muted text-center">Ainda não há pontos registados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

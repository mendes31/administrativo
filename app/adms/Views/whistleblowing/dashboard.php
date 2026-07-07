<?php $stats = $this->data['stats'] ?? []; ?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-chart-pie me-2"></i>Dashboard — Canal de Denúncias</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Canal de Denúncias</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3"><i class="fas fa-folder text-primary fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Total ativas</h6><h3 class="mb-0 fw-bold"><?= (int)($stats['total'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-hourglass-half text-warning fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Pendentes triagem</h6><h3 class="mb-0 fw-bold"><?= (int)($stats['pending'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-exclamation-triangle text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Críticas abertas</h6><h3 class="mb-0 fw-bold"><?= (int)($stats['critical'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3"><i class="fas fa-search text-info fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Em investigação</h6><h3 class="mb-0 fw-bold"><?= (int)($stats['investigation'] ?? 0) ?></h3></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-light shadow h-100">
                <div class="card-header fw-semibold">Tempo médio de resposta</div>
                <div class="card-body"><h2 class="mb-0"><?= number_format((float)($stats['avg_response_hours'] ?? 0), 1) ?> h</h2></div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-light shadow h-100">
                <div class="card-header fw-semibold">Tempo médio de encerramento</div>
                <div class="card-body"><h2 class="mb-0"><?= number_format((float)($stats['avg_closure_hours'] ?? 0), 1) ?> h</h2></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por status</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_status'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por classificação</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_category'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por risco</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_risk'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-light shadow">
        <div class="card-header hstack">
            <span>Denúncias recentes</span>
            <a href="<?php echo $_ENV['URL_ADM']; ?>denuncias" class="btn btn-sm btn-outline-primary ms-auto">Ver todas</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Protocolo</th><th>Classificação</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($stats['recent'] ?? [] as $r): ?>
                        <tr>
                            <td><a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['protocol']) ?></a></td>
                            <td><?= htmlspecialchars((string)$r['category']) ?></td>
                            <td><?= htmlspecialchars((string)$r['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime((string)$r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

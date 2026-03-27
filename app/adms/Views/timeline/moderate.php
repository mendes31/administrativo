<?php $csrf = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_moderate'); ?>
<div class="container-fluid px-4">
    <h2 class="mt-3">
        <i class="fas fa-shield-alt text-warning me-2"></i>Moderação — Timeline
    </h2>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>timeline">Timeline</a></li>
        <li class="breadcrumb-item active">Moderação</li>
    </ol>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php $reports = $this->data['reports'] ?? []; ?>
    <?php if (empty($reports)): ?>
        <div class="alert alert-info">Nenhuma denúncia pendente.</div>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Post</th>
                        <th>Motivo</th>
                        <th>Denunciante</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><?php echo (int)($r['id'] ?? 0); ?></td>
                            <td class="small"><?php echo htmlspecialchars(mb_substr((string)($r['post_excerpt'] ?? ''), 0, 80)); ?>...</td>
                            <td><?php echo htmlspecialchars($r['reason'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['reporter_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="action" value="hide_post">
                                    <input type="hidden" name="post_id" value="<?php echo (int)($r['post_id'] ?? 0); ?>">
                                    <input type="hidden" name="report_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Ocultar post</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="action" value="dismiss">
                                    <input type="hidden" name="report_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Arquivar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-md-none">
            <?php foreach ($reports as $r): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="small text-muted mb-1">#<?php echo (int)($r['id'] ?? 0); ?> · <?php echo htmlspecialchars($r['created_at'] ?? ''); ?></div>
                        <p class="small mb-2"><?php echo htmlspecialchars($r['reason'] ?? ''); ?></p>
                        <p class="small mb-2"><?php echo htmlspecialchars(mb_substr((string)($r['post_excerpt'] ?? ''), 0, 200)); ?></p>
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="hide_post">
                            <input type="hidden" name="post_id" value="<?php echo (int)($r['post_id'] ?? 0); ?>">
                            <input type="hidden" name="report_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Ocultar post</button>
                        </form>
                        <form method="post" class="d-grid gap-2 mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="dismiss">
                            <input type="hidden" name="report_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Arquivar denúncia</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

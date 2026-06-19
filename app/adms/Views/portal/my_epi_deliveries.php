<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$pendentes = $this->data['pendentes'] ?? [];
$fichas = $this->data['fichas'] ?? [];
$episEntregues = $this->data['epis_entregues'] ?? [];
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i>Meus EPIs</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Meus EPIs</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($pendentes !== []): ?>
    <div class="alert alert-warning">
        <strong><?= count($pendentes) ?> ficha(s) aguardando sua confirmação.</strong>
        Confirme o recebimento para concluir a entrega.
    </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 class="mb-0">Fichas de entrega</h5></div>
        <div class="card-body p-0">
            <?php if ($fichas === []): ?>
            <p class="text-muted p-3 mb-0">Nenhuma ficha de entrega registrada.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Data</th><th>Itens</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($fichas as $f): ?>
                    <tr>
                        <td><?= (int)$f['id'] ?></td>
                        <td><?= !empty($f['data_entrega']) ? date('d/m/Y', strtotime($f['data_entrega'])) : '-' ?></td>
                        <td><?= (int)($f['total_itens'] ?? 0) ?></td>
                        <td>
                            <?php $st = (string)($f['status_assinatura'] ?? ''); ?>
                            <span class="badge bg-<?= $st === 'Assinado' ? 'success' : ($st === 'Pendente' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($st) ?></span>
                        </td>
                        <td>
                            <?php if ($st === 'Pendente'): ?>
                            <a href="<?= htmlspecialchars($urlAdm) ?>sign-epi-ficha/<?= (int)$f['id'] ?>" class="btn btn-primary btn-sm">Assinar</a>
                            <?php else: ?>
                            <a href="<?= htmlspecialchars($urlAdm) ?>sign-epi-ficha/<?= (int)$f['id'] ?>" class="btn btn-outline-secondary btn-sm">Ver</a>
                            <a href="<?= htmlspecialchars($urlAdm) ?>view-epi-ficha-pdf/<?= (int)$f['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">PDF</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 class="mb-0">Histórico — todos os EPIs entregues</h5></div>
        <div class="card-body p-0">
            <?php if ($episEntregues === []): ?>
            <p class="text-muted p-3 mb-0">Nenhum EPI entregue no histórico.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Data</th><th>EPI</th><th>CA</th><th>Qtde</th><th>Prev. troca</th><th>Origem</th></tr></thead>
                    <tbody>
                    <?php foreach ($episEntregues as $e): ?>
                    <tr>
                        <td><?= !empty($e['data_entrega']) ? date('d/m/Y', strtotime($e['data_entrega'])) : '-' ?></td>
                        <td><?= htmlspecialchars($e['epi_nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['ca'] ?? '-') ?></td>
                        <td><?= (int)($e['quantidade'] ?? 0) ?></td>
                        <td><?= !empty($e['data_prevista_troca']) ? date('d/m/Y', strtotime($e['data_prevista_troca'])) : '-' ?></td>
                        <td class="small"><?= ($e['origem'] ?? '') === 'ficha' ? 'Ficha #' . (int)($e['ficha_id'] ?? 0) : 'Legado' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

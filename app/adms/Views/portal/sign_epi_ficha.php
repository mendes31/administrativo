<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$ficha = $this->data['ficha'] ?? [];
$itens = $this->data['itens'] ?? [];
$csrf = (string)($this->data['csrf_token'] ?? '');
$infoOnly = $this->data['info_only'] ?? null;
$fichaId = (int)($ficha['id'] ?? 0);
$status = (string)($ficha['status_assinatura'] ?? '');
$hash = (string)($ficha['pdf_hash_sha256'] ?? '');
$hashShort = $hash !== '' ? substr($hash, 0, 12) . '…' : '—';
$dataEntrega = !empty($ficha['data_entrega']) ? date('d/m/Y', strtotime($ficha['data_entrega'])) : '-';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
                <h2 class="mt-3 mb-0">Confirmar recebimento de EPI</h2>
                <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>my-epi-deliveries">Meus EPIs</a></li>
                    <li class="breadcrumb-item active">Ficha #<?= $fichaId ?></li>
                </ol>
            </div>
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-md-4">
                    <p class="text-muted small mb-2">Ficha #<?= $fichaId ?> · Entrega em <?= htmlspecialchars($dataEntrega) ?></p>
                    <p class="small mb-3">Hash do documento (SHA-256, resumo): <code><?= htmlspecialchars($hashShort) ?></code></p>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>EPI</th><th>Tam.</th><th>CA</th><th>Qtde</th><th>Prev. substituição</th></tr></thead>
                            <tbody>
                            <?php foreach ($itens as $i): ?>
                            <tr>
                                <td><?= htmlspecialchars($i['epi_nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($i['tamanho'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($i['ca_utilizado'] ?? $i['epi_ca_catalogo'] ?? '-') ?></td>
                                <td><?= (int)($i['quantidade'] ?? 1) ?></td>
                                <td><?= !empty($i['data_prevista_troca']) ? date('d/m/Y', strtotime($i['data_prevista_troca'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($infoOnly !== null): ?>
                    <div class="alert <?= $status === 'Assinado' ? 'alert-success' : 'alert-info' ?> mb-3"><?= htmlspecialchars($infoOnly) ?></div>
                    <?php if ($status === 'Assinado' && !empty($ficha['pdf_storage_path'])): ?>
                    <a href="<?= htmlspecialchars($urlAdm) ?>view-epi-ficha-pdf/<?= $fichaId ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> Ver PDF</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="small mb-3">
                        Você está autenticado no portal. Ao confirmar, declara ter recebido os EPIs listados e ter sido orientado quanto ao uso,
                        conforme NR-06. O sistema registra data/hora, IP e integridade do documento.
                    </p>
                    <div class="mb-3">
                        <a href="<?= htmlspecialchars($urlAdm) ?>view-epi-ficha-pdf/<?= $fichaId ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf me-1"></i> Visualizar PDF antes de confirmar</a>
                    </div>
                    <form method="post" action="<?= htmlspecialchars($urlAdm) ?>sign-epi-ficha/<?= $fichaId ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="confirm_session">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="aceite" required>
                            <label class="form-check-label" for="aceite">
                                Declaro ter recebido os EPIs acima e ter sido orientado quanto ao uso, guarda e conservação.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirmar recebimento</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

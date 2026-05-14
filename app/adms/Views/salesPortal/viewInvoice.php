<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Fatura</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-launchpad" class="text-decoration-none">Painel</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-invoices" class="text-decoration-none">Faturas</a></li>
            <li class="breadcrumb-item active">DocEntry <?= (int) ($this->data['doc_entry'] ?? 0); ?></li>
        </ol>
    </div>

    <div class="mb-3">
        <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-list-invoices' . (!empty($this->data['connection_id']) ? '?connection=' . (int) $this->data['connection_id'] : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Voltar à lista
        </a>
    </div>

    <?php if (!empty($this->data['sl_unavailable'])) { ?>
        <div class="alert alert-warning">
            <strong>Service Layer não configurada para este ecrã.</strong>
            A conexão SAP activa na base precisa de URL que aponte directamente ao endpoint da Service Layer (caminho com <code>b1s</code>).
        </div>
    <?php } elseif (!empty($this->data['sl_error'])) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $this->data['sl_error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } elseif (!empty($this->data['invoice']) && is_array($this->data['invoice'])) {
        $inv = $this->data['invoice'];
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">Cabeçalho</div>
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-6 col-md-3"><span class="text-muted">DocNum</span><br><strong><?= htmlspecialchars((string) ($inv['DocNum'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted">DocEntry</span><br><strong><?= htmlspecialchars((string) ($inv['DocEntry'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Data</span><br><?= htmlspecialchars((string) ($inv['DocDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Estado</span><br><?= htmlspecialchars((string) ($inv['DocumentStatus'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-4"><span class="text-muted">CardCode</span><br><code><?= htmlspecialchars((string) ($inv['CardCode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                    <div class="col-12 col-md-8"><span class="text-muted">Nome PN</span><br><?= htmlspecialchars((string) ($inv['CardName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Total documento</span><br><?= htmlspecialchars((string) ($inv['DocTotal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Moeda</span><br><?= htmlspecialchars((string) ($inv['DocCurrency'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-12 col-md-6"><span class="text-muted">Observações</span><br><?= nl2br(htmlspecialchars((string) ($inv['Comments'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Linhas</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>ItemCode</th>
                        <th>Descrição</th>
                        <th class="text-end">Qtd</th>
                        <th class="text-end">Preço</th>
                        <th class="text-end">Total linha</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $lines = $this->data['document_lines'] ?? [];
                    if ($lines === []) { ?>
                        <tr><td colspan="6" class="text-muted text-center py-3">Sem linhas devolvidas (expanda DocumentLines na API ou documento vazio).</td></tr>
                    <?php } else {
                        foreach ($lines as $line) {
                            if (!is_array($line)) {
                                continue;
                            }
                            $ln = $line['LineNum'] ?? '';
                            $ic = $line['ItemCode'] ?? '';
                            $ds = $line['ItemDescription'] ?? ($line['Dscription'] ?? '');
                            $qt = $line['Quantity'] ?? '';
                            $pr = $line['Price'] ?? ($line['PriceAfterVAT'] ?? '');
                            $lt = $line['LineTotal'] ?? '';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $ln, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?= htmlspecialchars((string) $ic, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?= htmlspecialchars((string) $ds, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?= htmlspecialchars((string) $qt, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?= htmlspecialchars((string) $pr, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?= htmlspecialchars((string) $lt, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php }
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>
</div>

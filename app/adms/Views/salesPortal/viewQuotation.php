<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Cotação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-launchpad" class="text-decoration-none">Painel</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-quotations" class="text-decoration-none">Cotações</a></li>
            <li class="breadcrumb-item active">DocEntry <?= (int) ($this->data['doc_entry'] ?? 0); ?></li>
        </ol>
    </div>

    <div class="mb-3">
        <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-list-quotations' . (!empty($this->data['connection_id']) ? '?connection=' . (int) $this->data['connection_id'] : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">
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
    <?php } elseif (!empty($this->data['quotation']) && is_array($this->data['quotation'])) {
        $q = $this->data['quotation'];
        $showCopyTo = !empty($this->data['quotation_can_copy_to_sales_order'])
            && in_array('SalesPortalConvertQuotationToOrder', $this->data['buttonPermission'] ?? [], true)
            && !empty($this->data['csrf_token_convert_quotation']);
        $convConn = !empty($this->data['connection_id']) ? (int) $this->data['connection_id'] : 0;
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">Cabeçalho</div>
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-6 col-md-3"><span class="text-muted">DocNum</span><br><strong><?= htmlspecialchars((string) ($q['DocNum'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted">DocEntry</span><br><strong><?= htmlspecialchars((string) ($q['DocEntry'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Data</span><br><?= htmlspecialchars((string) ($q['DocDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Estado</span><br><?= htmlspecialchars((string) ($q['DocumentStatus'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-4"><span class="text-muted">CardCode</span><br><code><?= htmlspecialchars((string) ($q['CardCode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                    <div class="col-12 col-md-8"><span class="text-muted">Nome PN</span><br><?= htmlspecialchars((string) ($q['CardName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Total documento</span><br><?= htmlspecialchars((string) ($q['DocTotal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-6 col-md-3"><span class="text-muted">Moeda</span><br><?= htmlspecialchars((string) ($q['DocCurrency'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-12 col-md-6"><span class="text-muted">Observações</span><br><?= nl2br(htmlspecialchars((string) ($q['Comments'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
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

        <?php if ($showCopyTo) { ?>
            <form id="salesPortalCopyQuotationToOrderForm" method="post" action="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-convert-quotation-to-order', ENT_QUOTES, 'UTF-8'); ?>"
                  class="d-none"
                  onsubmit="return confirm('Será criado um pedido de venda ligado a esta cotação no SAP e a cotação será fechada. Continuar?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $this->data['csrf_token_convert_quotation'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="doc_entry" value="<?= (int) ($this->data['doc_entry'] ?? 0); ?>">
                <?php if ($convConn > 0) { ?>
                    <input type="hidden" name="connection" value="<?= $convConn; ?>">
                <?php } ?>
            </form>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3 mt-2 border rounded bg-light shadow-sm">
                <span class="small text-muted mb-0">Documento no SAP — use quando a cotação estiver aberta e ainda não tiver sido copiada para pedido.</span>
                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="salesPortalCopyToMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        Copiar para
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="salesPortalCopyToMenuBtn">
                        <li>
                            <button type="submit" form="salesPortalCopyQuotationToOrderForm" class="dropdown-item">
                                <i class="fa-solid fa-file-invoice me-1"></i> Pedido de venda
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-item disabled small text-muted">Entrega</span></li>
                        <li><span class="dropdown-item disabled small text-muted">Nota fiscal de saída</span></li>
                        <li><span class="dropdown-item disabled small text-muted">NF de entrega futura</span></li>
                    </ul>
                </div>
            </div>
        <?php } elseif (in_array('SalesPortalConvertQuotationToOrder', $this->data['buttonPermission'] ?? [], true)
            && !empty($this->data['csrf_token_convert_quotation'])
            && empty($this->data['quotation_can_copy_to_sales_order'])) { ?>
            <div class="alert alert-secondary small mt-2 mb-0 py-2">
                Esta cotação já está fechada ou cancelada no SAP — <strong>Copiar para</strong> pedido de venda não está disponível.
            </div>
        <?php } ?>
    <?php } ?>
</div>

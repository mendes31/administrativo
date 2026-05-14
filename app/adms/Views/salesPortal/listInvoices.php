<?php
$spConnQ = !empty($this->data['connection_id']) ? '?connection=' . (int) $this->data['connection_id'] : '';
$spLaunchpadHref = htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-launchpad' . $spConnQ, ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap align-items-center">
        <h2 class="mt-3">Faturas (SAP B1)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $spLaunchpadHref; ?>" class="text-decoration-none">Painel</a></li>
            <li class="breadcrumb-item active">Faturas</li>
        </ol>
    </div>
    <div class="d-md-none mb-2">
        <a href="<?= $spLaunchpadHref; ?>" class="btn btn-outline-secondary btn-sm w-100">Painel</a>
    </div>

    <?php if (!empty($this->data['sl_unavailable'])) { ?>
        <div class="alert alert-warning">
            <strong>Service Layer não configurada para este ecrã.</strong>
            A conexão SAP activa na base precisa de URL que aponte directamente ao endpoint da Service Layer (caminho com <code>b1s</code>),
            para sessão e cookies OData.
        </div>
    <?php } elseif (!empty($this->data['sl_error'])) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $this->data['sl_error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-invoices" class="row g-3 align-items-end">
                <?php if (!empty($this->data['connection_id'])) { ?>
                    <input type="hidden" name="connection" value="<?= (int) $this->data['connection_id']; ?>">
                <?php } ?>
                <div class="col-12 col-md-4">
                    <label for="card_code" class="form-label">CardCode (opcional)</label>
                    <input type="text" class="form-control" id="card_code" name="card_code" maxlength="20"
                           pattern="[A-Za-z0-9_-]{1,20}"
                           value="<?= htmlspecialchars((string) ($this->data['card_code_filter'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Ex.: C00001">
                </div>
                <div class="col-12 col-md-2">
                    <label for="top" class="form-label">Máx. linhas</label>
                    <input type="number" class="form-control" id="top" name="top" min="1" max="200"
                           value="<?= (int) ($this->data['top_filter'] ?? 50); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-invoices" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">Sem CardCode, são listadas as faturas mais recentes até ao limite indicado (ordenadas por DocEntry).</p>
        </div>
    </div>

    <?php if (empty($this->data['sl_unavailable']) && empty($this->data['sl_error'])) { ?>
        <div class="card shadow-sm">
            <div class="card-header">Resultados</div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>DocNum</th>
                        <th>DocEntry</th>
                        <th>Data</th>
                        <th>CardCode</th>
                        <th>Nome PN</th>
                        <th class="text-end">Total</th>
                        <th>Moeda</th>
                        <th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rows = $this->data['invoices'] ?? [];
                    if ($rows === []) { ?>
                        <tr><td colspan="8" class="text-muted text-center py-4">Nenhuma fatura encontrada.</td></tr>
                    <?php } else {
                        foreach ($rows as $invRow) {
                            if (!is_array($invRow)) {
                                continue;
                            }
                            $num = $invRow['DocNum'] ?? '';
                            $de = isset($invRow['DocEntry']) ? (int) $invRow['DocEntry'] : 0;
                            $date = $invRow['DocDate'] ?? '';
                            $cc = $invRow['CardCode'] ?? '';
                            $name = $invRow['CardName'] ?? '';
                            $total = $invRow['DocTotal'] ?? '';
                            $cur = $invRow['DocCurrency'] ?? '';
                            $st = $invRow['DocumentStatus'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <?php
                                    $canView = $de > 0 && in_array('SalesPortalViewInvoice', $this->data['buttonPermission'] ?? [], true);
                                    if ($canView) {
                                        $href = $_ENV['URL_ADM'] . 'sales-portal-view-invoice?doc_entry=' . $de;
                                        if (!empty($this->data['connection_id'])) {
                                            $href .= '&connection=' . (int) $this->data['connection_id'];
                                        }
                                        ?>
                                        <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $num, ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php } else { ?>
                                        <?= htmlspecialchars((string) $num, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php } ?>
                                </td>
                                <td><?= $de > 0 ? (int) $de : '—'; ?></td>
                                <td><?= htmlspecialchars((string) $date, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?= htmlspecialchars((string) $cc, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?= htmlspecialchars((string) $total, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $cur, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $st, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php }
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>
</div>

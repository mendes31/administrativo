<?php
$connQ = !empty($this->data['connection_id']) ? '?connection=' . (int) $this->data['connection_id'] : '';
$today = date('Y-m-d');
$initialLineRows = 5;
?>
<style>
/* Coluna principal: altura fixa à viewport (ajustada por JS) — scroll só na zona das linhas */
.sales-portal-quotation-layout {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
}
.sales-portal-quotation-compact {
    font-size: 0.68rem;
}
.sales-portal-quotation-compact .sap-quotation-shell {
    border: 1px solid #b0b0b0;
    background: #fafafa;
    flex: 1 1 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.sales-portal-quotation-compact .sap-quotation-header {
    background: linear-gradient(180deg, #f2f2f2 0%, #e8e8e8 100%);
    border-bottom: 1px solid #c8c8c8;
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
}
.sales-portal-quotation-compact .sap-quotation-branch {
    background: #f7f7f7;
    border-bottom: 1px solid #d8d8d8;
}
.sales-portal-quotation-compact .sap-field-row {
    --sap-label-w: 7rem;
    margin-bottom: 0.06rem;
}
@media (min-width: 1200px) {
    .sales-portal-quotation-compact .sap-field-row { --sap-label-w: 7.75rem; }
}
.sales-portal-quotation-compact .sap-field-row > .sap-label {
    flex: 0 0 var(--sap-label-w);
    max-width: var(--sap-label-w);
    text-align: right;
    padding-right: 0.25rem;
    color: #333;
    font-weight: 500;
    line-height: 1.15;
    font-size: 0.65rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sales-portal-quotation-compact .sap-field-row > .sap-control {
    flex: 1 1 auto;
    min-width: 0;
}
.sales-portal-quotation-compact .sap-docnum-readonly {
    max-width: 5.25rem;
    text-align: right;
}
.sales-portal-quotation-compact .sap-quotation-lines-scroll {
    flex: 1 1 0;
    min-height: 0;
    overflow-x: auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
.sales-portal-quotation-compact .sap-grid-wrap {
    background: #fff;
    border: 1px solid #ccc;
}
.sales-portal-quotation-compact .sap-grid-wrap thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    box-shadow: 0 1px 0 #dee2e6;
    font-size: 0.62rem;
    padding: 0.12rem 0.2rem;
}
.sales-portal-quotation-compact .table-compact-lines > :not(caption) > * > * {
    padding: 0.1rem 0.25rem;
    vertical-align: middle;
}
.sales-portal-quotation-compact .table-compact-lines .form-control-sm {
    padding: 0.04rem 0.22rem;
    min-height: 1.24rem;
    font-size: 0.68rem;
}
.sales-portal-quotation-compact .table-compact-lines .btn-sm {
    padding: 0.04rem 0.25rem;
    font-size: 0.68rem;
    line-height: 1.1;
}
.sales-portal-quotation-compact .sap-totals-panel {
    background: #f3f3f3;
    border: 1px solid #c0c0c0;
    font-size: 0.65rem;
    padding: 0.35rem !important;
}
.sales-portal-quotation-compact .sap-totals-panel .form-control-sm {
    background: #e9e9e9;
    text-align: right;
    min-height: 1.15rem;
    padding: 0.03rem 0.2rem;
    font-size: 0.64rem;
}
.sales-portal-quotation-compact .sap-footer-band {
    background: #ececec;
    border-top: 1px solid #c8c8c8;
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
}
.sales-portal-quotation-compact .sap-header-right-col {
    border-top: 1px solid #c8c8c8;
}
@media (min-width: 992px) {
    .sales-portal-quotation-compact .sap-header-right-col {
        border-top: none;
        border-left: 1px solid #c8c8c8;
    }
}
.sales-portal-quotation-compact .breadcrumb { font-size: 0.62rem; margin-bottom: 0 !important; }
.sales-portal-quotation-compact .sap-page-title { font-size: 0.78rem; }
.sales-portal-quotation-compact #comments {
    resize: vertical;
    min-height: 1.75rem;
    max-height: 4rem;
    line-height: 1.2;
}

/* Mobile: rótulos por cima, campos a largura útil; sem altura fixa (scroll da página) */
@media (max-width: 767.98px) {
    .sales-portal-quotation-layout {
        display: block;
        overflow: visible;
        height: auto !important;
        max-height: none !important;
        min-height: auto;
    }
    .sales-portal-quotation-compact {
        font-size: 0.8rem;
    }
    /* Evita colapso a 0: filho flex com flex-basis 0 + pai sem altura definida */
    .sales-portal-quotation-compact .sap-quotation-shell {
        flex: 0 1 auto !important;
        min-height: auto !important;
        overflow: visible !important;
    }
    .sales-portal-quotation-compact .sap-quotation-lines-scroll.flex-grow-1 {
        flex-grow: 0 !important;
    }
    .sales-portal-quotation-compact > .hstack.gap-1 {
        flex-direction: column;
        align-items: stretch;
        gap: 0.35rem !important;
    }
    .sales-portal-quotation-compact > .hstack .breadcrumb {
        margin-left: 0 !important;
        width: 100%;
    }
    .sales-portal-quotation-compact .sap-page-title {
        font-size: 0.95rem;
    }
    .sales-portal-quotation-compact .sap-field-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.2rem;
        margin-bottom: 0.45rem !important;
    }
    .sales-portal-quotation-compact .sap-field-row > .sap-label {
        flex: none !important;
        max-width: none !important;
        width: 100%;
        text-align: left;
        padding-right: 0;
        white-space: normal;
        font-size: 0.75rem;
    }
    .sales-portal-quotation-compact .sap-field-row > .sap-control {
        width: 100%;
        max-width: none !important;
        min-width: 0;
    }
    .sales-portal-quotation-compact .sap-field-row .sap-control .form-control,
    .sales-portal-quotation-compact .sap-field-row .sap-control .form-control-sm {
        width: 100%;
        max-width: 100% !important;
    }
    .sales-portal-quotation-compact .sap-field-row .input-group {
        width: 100%;
        max-width: none !important;
        flex-wrap: wrap;
    }
    .sales-portal-quotation-compact .sap-field-row .input-group.flex-nowrap {
        flex-wrap: wrap;
    }
    .sales-portal-quotation-compact .sap-field-row .input-group > .form-control,
    .sales-portal-quotation-compact .sap-field-row .input-group > .input-group-text {
        min-width: 0;
    }
    .sales-portal-quotation-compact .sap-field-row .input-group .btn {
        flex-shrink: 0;
    }
    .sales-portal-quotation-compact .sap-docnum-readonly {
        max-width: none;
    }
    .sales-portal-quotation-compact .sap-quotation-lines-scroll {
        flex: none;
        min-height: 12rem;
        max-height: none;
        overflow-x: auto;
        overflow-y: visible;
    }
    .sales-portal-quotation-compact .sap-grid-wrap {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .sales-portal-quotation-compact .table-compact-lines .form-control-sm {
        min-height: 2rem;
        font-size: 0.8rem;
    }
    .sales-portal-quotation-compact .table-compact-lines .btn-sm {
        min-height: 2rem;
        min-width: 2.25rem;
    }
}
</style>
<div class="container-fluid px-1 px-md-2 py-0 sales-portal-quotation-compact sales-portal-quotation-layout" id="salesPortalQuotationLayoutRoot">

    <div class="hstack gap-1 flex-wrap align-items-center flex-shrink-0 py-0">
        <div class="fw-semibold mb-0 sap-page-title">Cotação de vendas</div>
        <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-list-quotations' . $connQ, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm py-0 px-1">Voltar</a>
        <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-launchpad' . $connQ, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm py-0 px-1 d-md-none">Painel</a>
        <ol class="breadcrumb py-0 ms-auto flex-wrap mb-0">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-launchpad' . $connQ, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">Painel</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-list-quotations' . $connQ, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">Cotações</a></li>
            <li class="breadcrumb-item active">Nova</li>
        </ol>
    </div>

    <template id="salesPortalQuotationLineTemplate">
        <tr>
            <td class="text-muted text-center js-line-num">0</td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_item_code[]" maxlength="50" placeholder="" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm bg-light" data-sales-line-desc="1" readonly tabindex="-1" placeholder="">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_quantity[]" inputmode="decimal" placeholder="" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_unit_price[]" inputmode="decimal" placeholder="" title="Opcional" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_warehouse_code[]" maxlength="15" placeholder="" title="WarehouseCode" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_usage[]" inputmode="numeric" maxlength="5" placeholder="" title="Usage (inteiro)" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="line_tax_code[]" maxlength="20" placeholder="" title="TaxCode" autocomplete="off">
            </td>
            <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
            <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
            <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-secondary btn-sm salesPortalItemPickerOpen" title="SAP" style="background:#f5e6a8;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </td>
        </tr>
    </template>

    <form method="post" action="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-save-quotation', ENT_QUOTES, 'UTF-8'); ?>"
          class="sap-quotation-shell rounded mb-0 overflow-hidden"
          title="POST Quotations — campos vazios não enviados; totais no SAP após gravar.">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($this->data['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($this->data['connection_id'])) { ?>
            <input type="hidden" name="connection" value="<?= (int) $this->data['connection_id']; ?>">
        <?php } ?>

        <div class="flex-shrink-0">
        <div class="sap-quotation-header px-2 py-0">
            <div class="row g-1">
                <div class="col-lg-6">
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Cliente <span class="text-danger">*</span></span>
                        <div class="sap-control">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" id="card_code" name="card_code" required maxlength="20"
                                       pattern="[A-Za-z0-9_-]{1,20}" placeholder="CardCode" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-sm border-secondary" id="salesPortalBpPickerOpen" title="SAP" style="background:#f5e6a8;">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Nome</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm bg-light" id="card_name_display" readonly tabindex="-1" placeholder="">
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Pessoa contato</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm" id="contact_person" name="contact_person" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Nº ref. cliente</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm" id="num_at_card" name="num_at_card" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Moeda</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm text-uppercase" id="doc_currency" name="doc_currency" maxlength="3" placeholder="BRL" title="DocCurrency">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 sap-header-right-col ps-lg-2 pt-1 pt-lg-0">
                    <div class="d-flex sap-field-row align-items-center flex-wrap gap-1">
                        <span class="sap-label">Nº</span>
                        <div class="sap-control">
                            <div class="input-group input-group-sm flex-nowrap" style="max-width: 17rem;">
                                <span class="input-group-text py-0 px-1" style="font-size:0.65rem;">Série</span>
                                <input type="number" class="form-control form-control-sm" id="series" name="series" min="0" step="1" title="Series">
                                <span class="input-group-text py-0 px-1" style="font-size:0.65rem;">Doc.</span>
                                <input type="text" class="form-control form-control-sm bg-light sap-docnum-readonly" readonly tabindex="-1" value="—" aria-label="Nº após gravar">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Status</span>
                        <div class="sap-control">
                            <span class="badge bg-light text-dark border" style="font-size:0.65rem;">Aberto (novo)</span>
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Data lanç.</span>
                        <div class="sap-control" style="max-width: 10.5rem;">
                            <input type="date" class="form-control form-control-sm" id="doc_date" name="doc_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Válido até</span>
                        <div class="sap-control" style="max-width: 10.5rem;">
                            <input type="date" class="form-control form-control-sm" id="doc_due_date" name="doc_due_date" title="DocDueDate">
                        </div>
                    </div>
                    <div class="d-flex sap-field-row align-items-center">
                        <span class="sap-label">Data doc.</span>
                        <div class="sap-control" style="max-width: 10.5rem;">
                            <input type="date" class="form-control form-control-sm bg-light" id="doc_date_display" readonly tabindex="-1" title="Espelha data lanç.">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sap-quotation-branch px-2 py-0">
            <div class="row g-1 align-items-center">
                <div class="col-md-4">
                    <div class="d-flex sap-field-row align-items-center mb-0">
                        <span class="sap-label">Filial (BPL)</span>
                        <div class="sap-control">
                            <input type="number" class="form-control form-control-sm" id="bpl_id" name="bpl_id" min="0" step="1" style="max-width: 7rem;" title="BPL_IDAssignedToInvoice">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex sap-field-row align-items-center mb-0">
                        <span class="sap-label">Pagamento</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm" id="payment_group_code" name="payment_group_code" maxlength="15" placeholder="PaymentGroupCode" title="Condição de pagamento (número ou código)">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex sap-field-row align-items-center mb-0">
                        <span class="sap-label">Projeto</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm" id="project_code" name="project_code" maxlength="20" placeholder="Cód. projeto" title="Project (cabeçalho)">
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex sap-field-row align-items-center mb-0">
                        <span class="sap-label">CNPJ</span>
                        <div class="sap-control">
                            <input type="text" class="form-control form-control-sm bg-light" readonly tabindex="-1" id="sap_branch_cnpj_display" title="No SAP">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="sap-quotation-lines-scroll flex-grow-1 px-0">
        <ul class="nav nav-tabs border-bottom-0 px-2 mb-0" role="tablist" style="background:#dedede;font-size:0.68rem;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-0 px-2 rounded-0 fw-semibold" type="button" disabled style="background:#fff;border-color:#b0b0b0 #b0b0b0 #fff;">Conteúdo</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-0 px-2 rounded-0 text-muted" type="button" disabled>Logística</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-0 px-2 rounded-0 text-muted" type="button" disabled>Contab.</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-0 px-2 rounded-0 text-muted" type="button" disabled>Imposto</button>
            </li>
        </ul>

        <div class="px-2 py-0 border-bottom text-muted bg-white" style="font-size:0.68rem;">
            <span class="me-2">Tipo item: <strong>Item</strong></span>
            <span>Resumo: <strong>—</strong></span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 px-2 py-1 border-bottom bg-white">
            <button type="button" class="btn btn-outline-primary btn-sm py-0" id="salesPortalAddQuotationLine">+ Adicionar linha</button>
            <span class="text-muted" style="font-size:0.68rem;" id="salesPortalLineCountLabel"></span>
        </div>

        <div class="sap-grid-wrap mx-2 mb-0 mt-1 rounded-top border-top-0">
            <table class="table table-sm table-bordered align-middle mb-0 table-compact-lines">
                <thead class="table-secondary">
                <tr class="text-nowrap">
                    <th class="text-center" style="width:1.75rem">#</th>
                    <th style="width:8%">Nº item</th>
                    <th>Descr.</th>
                    <th style="width:6%">Qtd. <span class="text-danger">*</span></th>
                    <th style="width:6%">P.unit.</th>
                    <th style="width:5%" title="WarehouseCode">Dep.</th>
                    <th style="width:4%" title="Usage">Util.</th>
                    <th style="width:5%" title="TaxCode">Imp.</th>
                    <th style="width:4%">%d.</th>
                    <th style="width:6%">Pós-d.</th>
                    <th style="width:6%">Tot.</th>
                    <th style="width:2rem"></th>
                </tr>
                </thead>
                <tbody id="salesPortalLinesBody">
                <?php for ($r = 0; $r < $initialLineRows; $r++) { ?>
                    <tr>
                        <td class="text-muted text-center js-line-num"><?= $r + 1; ?></td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_item_code[]" maxlength="50" autocomplete="off">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm bg-light" data-sales-line-desc="1" readonly tabindex="-1">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_quantity[]" inputmode="decimal" autocomplete="off">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_unit_price[]" inputmode="decimal" autocomplete="off">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_warehouse_code[]" maxlength="15" autocomplete="off" title="WarehouseCode">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_usage[]" inputmode="numeric" maxlength="5" autocomplete="off" title="Usage">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_tax_code[]" maxlength="20" autocomplete="off" title="TaxCode">
                        </td>
                        <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
                        <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
                        <td><input type="text" class="form-control form-control-sm bg-light text-end" readonly tabindex="-1" value="—" aria-hidden="true"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm salesPortalItemPickerOpen" title="SAP" style="background:#f5e6a8;"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted px-2 mb-0 mt-0 lh-1" style="font-size:0.6rem;">Depósito / utilização / imposto por linha quando o B1 exigir · + linha até 200 · scroll só aqui</p>
        </div>

        <div class="sap-footer-band px-2 py-0 flex-shrink-0">
            <div class="row g-1">
                <div class="col-lg-7">
                    <div class="row g-1">
                        <div class="col-md-6">
                            <div class="d-flex sap-field-row align-items-center">
                                <span class="sap-label">Vendedor</span>
                                <div class="sap-control">
                                    <input type="number" class="form-control form-control-sm" id="sales_person_code" name="sales_person_code" min="0" step="1" placeholder="Cód." title="SalesPersonCode">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex sap-field-row align-items-center">
                                <span class="sap-label">Titular</span>
                                <div class="sap-control">
                                    <input type="number" class="form-control form-control-sm" id="documents_owner" name="documents_owner" min="0" step="1" placeholder="Cód." title="DocumentsOwner">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex sap-field-row align-items-start">
                                <span class="sap-label pt-0">Obs.</span>
                                <div class="sap-control">
                                    <textarea class="form-control form-control-sm" id="comments" name="comments" rows="1" maxlength="2000"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="sap-totals-panel p-1 h-100">
                        <div class="fw-semibold text-muted border-bottom pb-0 mb-0" style="font-size:0.6rem;">Totais (SAP)</div>
                        <div class="d-flex sap-field-row align-items-center mb-0">
                            <span class="sap-label">Ant.desc.</span>
                            <div class="sap-control"><input type="text" class="form-control form-control-sm" readonly tabindex="-1" value="—"></div>
                        </div>
                        <div class="d-flex sap-field-row align-items-center mb-0">
                            <span class="sap-label">Desc.</span>
                            <div class="sap-control"><input type="text" class="form-control form-control-sm" readonly tabindex="-1" value="—"></div>
                        </div>
                        <div class="d-flex sap-field-row align-items-center mb-0">
                            <span class="sap-label">Imposto</span>
                            <div class="sap-control"><input type="text" class="form-control form-control-sm" readonly tabindex="-1" value="—"></div>
                        </div>
                        <div class="d-flex sap-field-row align-items-center mb-0">
                            <span class="sap-label fw-bold">Total</span>
                            <div class="sap-control"><input type="text" class="form-control form-control-sm fw-bold" readonly tabindex="-1" value="—"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sap-actions-bar px-2 py-0 d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
            <button type="submit" class="btn btn-primary btn-sm py-0 px-2">OK</button>
            <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sales-portal-list-quotations' . $connQ, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light btn-sm border-secondary py-0">Cancelar</a>
        </div>
    </form>
</div>
<script>
(function () {
    function isSalesPortalMobileLayout() {
        return typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 767.98px)').matches;
    }

    function fitQuotationPageHeight() {
        var root = document.getElementById('salesPortalQuotationLayoutRoot');
        if (!root) { return; }
        if (isSalesPortalMobileLayout()) {
            root.style.height = '';
            root.style.maxHeight = '';
            return;
        }
        var rect = root.getBoundingClientRect();
        var footer = document.querySelector('#layoutSidenav_content footer.adms-footer');
        var footerTop = footer ? footer.getBoundingClientRect().top : window.innerHeight;
        var bottomPad = 2;
        var h = Math.floor(footerTop - rect.top - bottomPad);
        if (h < 240) { h = 240; }
        root.style.height = h + 'px';
        root.style.maxHeight = h + 'px';
    }

    window.addEventListener('resize', fitQuotationPageHeight);
    window.addEventListener('orientationchange', fitQuotationPageHeight);
    var layoutContent = document.getElementById('layoutSidenav_content');
    if (layoutContent && typeof ResizeObserver !== 'undefined') {
        (new ResizeObserver(function () { fitQuotationPageHeight(); })).observe(layoutContent);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            fitQuotationPageHeight();
            window.requestAnimationFrame(fitQuotationPageHeight);
        });
    } else {
        fitQuotationPageHeight();
        window.requestAnimationFrame(fitQuotationPageHeight);
    }

    var MAX_QUOTATION_LINES = 200;

    function renumberQuotationLines() {
        var tbody = document.getElementById('salesPortalLinesBody');
        if (!tbody) { return; }
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function (tr, i) {
            var c = tr.querySelector('.js-line-num');
            if (c) { c.textContent = String(i + 1); }
        });
        var n = rows.length;
        var lbl = document.getElementById('salesPortalLineCountLabel');
        var btn = document.getElementById('salesPortalAddQuotationLine');
        if (lbl) { lbl.textContent = n + ' linha(s) · máx. ' + MAX_QUOTATION_LINES; }
        if (btn) { btn.disabled = n >= MAX_QUOTATION_LINES; }
    }

    var addBtn = document.getElementById('salesPortalAddQuotationLine');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var tbody = document.getElementById('salesPortalLinesBody');
            var tpl = document.getElementById('salesPortalQuotationLineTemplate');
            if (!tbody || !tpl || !tpl.content) { return; }
            if (tbody.querySelectorAll('tr').length >= MAX_QUOTATION_LINES) { return; }
            tbody.appendChild(tpl.content.cloneNode(true));
            renumberQuotationLines();
            fitQuotationPageHeight();
        });
    }
    renumberQuotationLines();

    var docDate = document.getElementById('doc_date');
    var docDisp = document.getElementById('doc_date_display');
    if (docDate && docDisp) {
        function sync() { docDisp.value = docDate.value || ''; }
        docDate.addEventListener('change', sync);
        docDate.addEventListener('input', sync);
        sync();
    }
})();
</script>
<?php
$sapPickerConn = !empty($this->data['connection_id']) ? '&connection=' . (int) $this->data['connection_id'] : '';
$sapPickerSearchUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/sales-portal-search-business-partners';
include './app/adms/Views/salesPortal/partials/cardCodeSapPicker.php';
$sapItemPickerSearchUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/sales-portal-search-items';
$sapItemPickerConn = $sapPickerConn;
include './app/adms/Views/salesPortal/partials/itemSapPicker.php';
?>

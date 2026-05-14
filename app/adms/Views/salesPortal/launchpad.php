<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Painel</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Painel</li>
        </ol>
    </div>

    <p class="text-muted mb-4">Quem tem permissão neste módulo pode trabalhar com <strong>qualquer parceiro de negócio</strong> no SAP B1: o <code>CardCode</code> (e demais dados do documento) escolhe-se no fluxo de cada ecrã ou pedido à Service Layer — <strong>não</strong> há vínculo fixo entre utilizador do administrativo e um único cliente no ERP.</p>

    <?php
    $perms = $this->data['buttonPermission'] ?? [];
    $canListQuotations = in_array('SalesPortalListQuotations', $perms, true);
    $canCreateQuotation = in_array('SalesPortalCreateQuotation', $perms, true);
    $canListOrders = in_array('SalesPortalListOrders', $perms, true);
    $canCreateOrder = in_array('SalesPortalCreateOrder', $perms, true);
    $canListInvoices = in_array('SalesPortalListInvoices', $perms, true);
    $showQuotationsBlock = $canListQuotations || $canCreateQuotation;
    $showOrdersBlock = $canListOrders || $canCreateOrder;
    $showInvoicesBlock = $canListInvoices;
    $blockCount = (int) $showQuotationsBlock + (int) $showOrdersBlock + (int) $showInvoicesBlock;
    $blockColClass = $blockCount >= 3 ? 'col-12 col-lg-4' : ($blockCount === 2 ? 'col-12 col-lg-6' : 'col-12');
    ?>

    <div class="row g-4 mb-4 align-items-stretch">
        <?php if ($showQuotationsBlock) { ?>
        <div class="<?= htmlspecialchars($blockColClass, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="border rounded-3 p-3 p-md-4 h-100 bg-white shadow-sm">
                <p class="text-uppercase small text-muted fw-semibold mb-3 mb-md-4" style="letter-spacing: .04em;">Cotações</p>
                <div class="row row-cols-1 row-cols-sm-2 g-3">
                    <?php if ($canListQuotations) { ?>
                    <div class="col">
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-quotations" class="card h-100 text-decoration-none shadow-sm border-0 bg-light">
                            <div class="card-body d-flex flex-column align-items-start gap-2">
                                <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-3"><i class="fa-solid fa-file-invoice fa-lg"></i></span>
                                <h5 class="card-title text-dark mb-0">Cotações</h5>
                                <p class="card-text small text-muted mb-0">Consultar cotações no SAP (Service Layer).</p>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                    <?php if ($canCreateQuotation) { ?>
                    <div class="col">
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-create-quotation" class="card h-100 text-decoration-none shadow-sm border-0 bg-light">
                            <div class="card-body d-flex flex-column align-items-start gap-2">
                                <span class="rounded-circle bg-success bg-opacity-10 text-success p-3"><i class="fa-solid fa-file-circle-plus fa-lg"></i></span>
                                <h5 class="card-title text-dark mb-0">Nova cotação</h5>
                                <p class="card-text small text-muted mb-0">Criar cotação no SAP (Service Layer).</p>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php if ($showOrdersBlock) { ?>
        <div class="<?= htmlspecialchars($blockColClass, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="border rounded-3 p-3 p-md-4 h-100 bg-white shadow-sm">
                <p class="text-uppercase small text-muted fw-semibold mb-3 mb-md-4" style="letter-spacing: .04em;">Pedidos</p>
                <div class="row row-cols-1 row-cols-sm-2 g-3">
                    <?php if ($canListOrders) { ?>
                    <div class="col">
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-orders" class="card h-100 text-decoration-none shadow-sm border-0 bg-light">
                            <div class="card-body d-flex flex-column align-items-start gap-2">
                                <span class="rounded-circle bg-info bg-opacity-10 text-info p-3"><i class="fa-solid fa-cart-shopping fa-lg"></i></span>
                                <h5 class="card-title text-dark mb-0">Pedidos</h5>
                                <p class="card-text small text-muted mb-0">Consultar pedidos de venda no SAP (Service Layer).</p>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                    <?php if ($canCreateOrder) { ?>
                    <div class="col">
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-create-order" class="card h-100 text-decoration-none shadow-sm border-0 bg-light">
                            <div class="card-body d-flex flex-column align-items-start gap-2">
                                <span class="rounded-circle bg-success bg-opacity-10 text-success p-3"><i class="fa-solid fa-cart-plus fa-lg"></i></span>
                                <h5 class="card-title text-dark mb-0">Novo pedido</h5>
                                <p class="card-text small text-muted mb-0">Criar pedido de venda no SAP (Service Layer).</p>
                            </div>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php if ($showInvoicesBlock) { ?>
        <div class="<?= htmlspecialchars($blockColClass, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="border rounded-3 p-3 p-md-4 h-100 bg-white shadow-sm">
                <p class="text-uppercase small text-muted fw-semibold mb-3 mb-md-4" style="letter-spacing: .04em;">Faturas</p>
                <div class="row row-cols-1 g-3">
                    <div class="col">
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>sales-portal-list-invoices" class="card h-100 text-decoration-none shadow-sm border-0 bg-light">
                            <div class="card-body d-flex flex-column align-items-start gap-2">
                                <span class="rounded-circle bg-warning bg-opacity-10 text-dark p-3"><i class="fa-solid fa-file-invoice-dollar fa-lg"></i></span>
                                <h5 class="card-title text-dark mb-0">Faturas</h5>
                                <p class="card-text small text-muted mb-0">Consultar faturas de cliente no SAP (Service Layer).</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="alert alert-light border shadow-sm mb-0">
        <p class="mb-2"><strong>Próximos passos</strong></p>
        <p class="small text-muted mb-0">Novos atalhos e fluxos do portal podem ser acrescentados aqui; a integração continua a usar o mesmo login e permissões do administrativo.</p>
    </div>
</div>

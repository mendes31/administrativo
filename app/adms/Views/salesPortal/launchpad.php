<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Portal de Vendas (SAP)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Portal de Vendas</li>
        </ol>
    </div>

    <p class="text-muted mb-4">Quem tem permissão neste módulo pode trabalhar com <strong>qualquer parceiro de negócio</strong> no SAP B1: o <code>CardCode</code> (e demais dados do documento) escolhe-se no fluxo de cada ecrã ou pedido à Service Layer — <strong>não</strong> há vínculo fixo entre utilizador do administrativo e um único cliente no ERP.</p>

    <div class="alert alert-light border shadow-sm mb-0">
        <p class="mb-2"><strong>Próximos passos</strong></p>
        <p class="small text-muted mb-0">As entradas do portal (cotações, pedidos, consultas) serão adicionadas aqui à medida que forem implementadas; a integração continua a usar o mesmo login e permissões do administrativo.</p>
    </div>
</div>

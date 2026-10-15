<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$registros = $this->data['registros'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$canCreate = in_array('FormacaoPrecoCreate', $perms, true);
$canView = in_array('FormacaoPrecoView', $perms, true);
$canUpdate = in_array('FormacaoPrecoUpdate', $perms, true);
$canPdf = in_array('FormacaoPrecoPdf', $perms, true);
?>
<style>
@media (max-width: 767.98px) {
    .fp-list-table thead { display: none; }
    .fp-list-table tbody tr {
        display: block;
        margin-bottom: .75rem;
        padding: .65rem .75rem;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        background: #fff;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.05);
    }
    .fp-list-table tbody td {
        display: block;
        width: 100% !important;
        border: 0;
        padding: .3rem 0;
        text-align: left !important;
    }
    .fp-list-table tbody td::before {
        content: attr(data-label);
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: .15rem;
    }
    .fp-list-table tbody td.fp-list-actions::before { content: none; }
    .fp-list-table tbody td.fp-list-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
    .fp-list-table tbody td.fp-list-actions .btn { flex: 1 1 auto; }
    .fp-list-new { width: 100%; }
}
</style>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Formação de Preço</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'dashboard', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Formação de Preço</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <span>Precificações nomeadas (três métodos comerciais)</span>
            <?php if ($canCreate): ?>
                <a href="<?= htmlspecialchars($url . 'formacao-preco-create', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm fp-list-new">
                    <i class="fa-regular fa-square-plus"></i> Nova precificação
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="small text-muted">
                Cada precificação é um cenário salvo (nome + produtos). Os cálculos seguem Markup sobre custo, margem sobre receita líquida e lucro sobre preço bruto — independentes do custeio fabril do Estoque.
            </p>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label mb-1">Busca</label>
                    <input type="text" name="q" id="q" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($this->data['filter_q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome ou observação">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <a href="<?= htmlspecialchars($url . 'formacao-preco', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpar</a>
                </div>
            </form>

            <?php if ($registros === []): ?>
                <p class="text-muted mb-0">Nenhuma precificação cadastrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle fp-list-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th class="text-center">Produtos</th>
                                <th>Responsável</th>
                                <th>Atualizado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($registros as $row):
                            $rid = (int) ($row['id'] ?? 0);
                            $destino = $canView
                                ? $url . 'formacao-preco-view/' . $rid
                                : ($canUpdate ? $url . 'formacao-preco-update/' . $rid : '');
                            ?>
                            <tr>
                                <td data-label="Nome">
                                    <?php if ($destino !== ''): ?>
                                        <a href="<?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                                            <?= htmlspecialchars((string) ($row['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string) ($row['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                    <?php if (!empty($row['observacao'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth((string) $row['observacao'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" data-label="Produtos"><?= (int) ($row['itens_count'] ?? 0) ?></td>
                                <td data-label="Responsável"><?= htmlspecialchars((string) ($row['created_by_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small" data-label="Atualizado"><?= htmlspecialchars((string) ($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end text-nowrap fp-list-actions">
                                    <?php if ($canView): ?>
                                        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($url . 'formacao-preco-view/' . $rid, ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
                                    <?php endif; ?>
                                    <?php if ($canPdf): ?>
                                        <a class="btn btn-outline-danger btn-sm" href="<?= htmlspecialchars($url . 'formacao-preco-pdf/' . $rid, ENT_QUOTES, 'UTF-8') ?>" target="_blank">PDF</a>
                                    <?php endif; ?>
                                    <?php if ($canUpdate): ?>
                                        <a class="btn btn-warning btn-sm" href="<?= htmlspecialchars($url . 'formacao-preco-update/' . $rid, ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

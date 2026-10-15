<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$rid = (int) ($this->data['registro_id'] ?? 0);
$perms = $this->data['buttonPermission'] ?? [];
$canUpdate = in_array('FormacaoPrecoUpdate', $perms, true);
$canDelete = in_array('FormacaoPrecoDelete', $perms, true);
$canPdf = in_array('FormacaoPrecoPdf', $perms, true);
$csrfDel = (string) ($this->data['csrf_delete'] ?? '');
$log = $this->data['log_resumo'] ?? null;
$reg = $this->data['registro'] ?? [];
?>
<style>
@media (max-width: 767.98px) {
    .fp-page-actions { width: 100%; }
    .fp-page-actions > .btn,
    .fp-page-actions > form { width: 100%; }
    .fp-page-actions form .btn { width: 100%; }
}
</style>
<div class="container-fluid px-2">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3"><?= htmlspecialchars((string) ($reg['nome'] ?? 'Formação de preço'), ENT_QUOTES, 'UTF-8') ?></h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'formacao-preco', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Formação de Preço</a></li>
            <li class="breadcrumb-item active">Consulta</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-3">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span>Cenário</span>
            <div class="d-flex flex-wrap gap-2 fp-page-actions">
                <?php if ($canPdf): ?>
                    <a href="<?= htmlspecialchars($url . 'formacao-preco-pdf/' . $rid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-danger btn-sm" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i> PDF da análise
                    </a>
                <?php endif; ?>
                <?php if ($canUpdate): ?>
                    <a href="<?= htmlspecialchars($url . 'formacao-preco-update/' . $rid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm">Editar</a>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                    <form method="post" action="<?= htmlspecialchars($url . 'formacao-preco-delete/' . $rid, ENT_QUOTES, 'UTF-8') ?>"
                          onsubmit="return confirm('Excluir esta precificação e todos os produtos?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfDel, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                    </form>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($url . 'formacao-preco', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row g-3">
                <?php include __DIR__ . '/_form_header.php'; ?>
            </div>
            <?php if (!empty($reg['created_by_name'])): ?>
                <p class="small text-muted mt-3 mb-0">Criado por <?= htmlspecialchars((string) $reg['created_by_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Produtos e cálculos</div>
        <div class="card-body p-2 p-md-3">
            <?php include __DIR__ . '/_workbench.php'; ?>
        </div>
    </div>

    <?php if (is_array($log) && !empty($log['has_logs'])): ?>
        <a class="btn btn-outline-secondary btn-sm mb-4" href="<?= htmlspecialchars((string) $log['list_url'], ENT_QUOTES, 'UTF-8') ?>">
            Log de alterações (<?= (int) ($log['count'] ?? 0) ?>)
        </a>
    <?php endif; ?>
</div>
<script src="<?= htmlspecialchars($url . 'public/adms/js/formacao-preco.js?v=20261015f', ENT_QUOTES, 'UTF-8') ?>"></script>

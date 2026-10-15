<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$csrf = CSRFHelper::generateCSRFToken('form_formacao_preco');
$rid = (int) ($this->data['registro_id'] ?? 0);
$canPdf = in_array('FormacaoPrecoPdf', $this->data['buttonPermission'] ?? [], true);
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
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Editar formação de preço</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'formacao-preco', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Formação de Preço</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <form method="post" id="fp-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card border-light shadow mb-3">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between">
                <span>Dados do cenário</span>
                <div class="d-flex flex-wrap gap-2 fp-page-actions">
                    <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                    <?php if ($canPdf): ?>
                        <a href="<?= htmlspecialchars($url . 'formacao-preco-pdf/' . $rid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-danger btn-sm" target="_blank">PDF da análise</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($url . 'formacao-preco-view/' . $rid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </div>
            <div class="card-body">
                <?php include './app/adms/Views/partials/alerts.php'; ?>
                <?php if (!empty($this->data['errors'])): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($this->data['errors'] as $err): ?>
                                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div class="row g-3">
                    <?php include __DIR__ . '/_form_header.php'; ?>
                </div>
            </div>
        </div>

        <div class="card border-light shadow mb-4">
            <div class="card-header">Produtos e cálculos</div>
            <div class="card-body p-2 p-md-3">
                <?php include __DIR__ . '/_workbench.php'; ?>
            </div>
        </div>
    </form>
</div>
<script src="<?= htmlspecialchars($url . 'public/adms/js/formacao-preco.js?v=20261015f', ENT_QUOTES, 'UTF-8') ?>"></script>

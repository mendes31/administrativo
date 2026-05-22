<?php

use App\adms\Helpers\FormatHelper;

$termo = $this->data['termo'] ?? null;
$hasTermo = !empty($this->data['has_termo']);
$titleHead = $this->data['title_head'] ?? 'Documento';
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-lg mt-4">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0 text-center font-weight-light">
                        <?= htmlspecialchars($termo['titulo'] ?? $titleHead) ?>
                    </h3>
                    <?php if ($hasTermo && !empty($termo['versao'])): ?>
                        <p class="text-center text-muted small mb-0 mt-1">
                            Versão <?= htmlspecialchars((string) $termo['versao']) ?>
                            <?php if (!empty($termo['data_inicio_vigencia'])): ?>
                                &middot; vigente desde <?= FormatHelper::formatDate($termo['data_inicio_vigencia'], 'd/m/Y') ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <?php if ($hasTermo): ?>
                        <div class="lgpd-term-content border rounded p-3 bg-light">
                            <?php echo $termo['conteudo']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <p class="mb-2"><strong>Conteúdo ainda não disponível.</strong></p>
                            <p class="mb-0 small"><?= $this->data['empty_hint'] ?? '' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao início
                    </a>
                    <?php if ($hasTermo && in_array('LgpdTermosView', $this->data['buttonPermission'] ?? [], true)): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-view/<?= (int) ($termo['id'] ?? 0) ?>"
                           class="btn btn-outline-primary btn-sm">
                            Abrir no módulo LGPD
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .lgpd-term-content p {
        margin-bottom: 0.35rem;
    }
    .lgpd-term-content ul,
    .lgpd-term-content ol {
        margin-bottom: 0.5rem;
    }
</style>

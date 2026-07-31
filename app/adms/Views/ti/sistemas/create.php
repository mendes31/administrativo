<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$form = $this->data['form'] ?? [];
$tipos = $this->data['tipos'] ?? [];
$filiais = $this->data['filiais'] ?? [];
$codigoPreview = (string) ($this->data['codigoPreview'] ?? '');
$csrf = CSRFHelper::generateCSRFToken('form_ti_sistema');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Sistema (TI)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>">Sistemas</a></li>
            <li class="breadcrumb-item active">Cadastrar</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Dados do sistema / equipamento</div>
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

            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <?php include __DIR__ . '/_form_fields.php'; ?>
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">Salvar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

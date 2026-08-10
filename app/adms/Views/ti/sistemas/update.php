<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$form = $this->data['form'] ?? [];
$tipos = $this->data['tipos'] ?? [];
$filiais = $this->data['filiais'] ?? [];
$sistemaId = (int) ($this->data['sistema_id'] ?? ($form['id'] ?? 0));
$csrf = CSRFHelper::generateCSRFToken('form_ti_sistema');
?>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Editar Sistema (TI)</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Sistemas</a></li>
            <li class="breadcrumb-item active">#<?= $sistemaId ?></li>
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
                <div class="col-12 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-warning btn-sm">Atualizar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas-view/' . $sistemaId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

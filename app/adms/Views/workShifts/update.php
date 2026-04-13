<?php

use App\adms\Helpers\CSRFHelper;

if (!isset($this->data['form']) || !is_array($this->data['form'])) {
    $this->data['form'] = [];
}
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Turnos de trabalho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>list-work-shifts" class="text-decoration-none">Turnos</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Editar turno</span>
            <span class="ms-auto">
                <?php if (in_array('ListWorkShifts', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>list-work-shifts" class="btn btn-info btn-sm"><i class="fa-solid fa-list"></i> Listar</a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_update_work_shift'); ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($this->data['form']['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <?php include __DIR__ . '/_form_fields.php'; ?>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

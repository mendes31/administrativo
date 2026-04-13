<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WorkShiftsRepository;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_work_shift');
$ws = $this->data['work_shift'] ?? [];
$fmt = static function (?string $t): string {
    if ($t === null || $t === '') {
        return '—';
    }

    return WorkShiftsRepository::timeInputValue($t) ?: '—';
};
$totalLabel = WorkShiftsRepository::formatMinutesLabel((int) ($ws['total_minutes'] ?? 0));
?>
<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Turno de trabalho</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>list-work-shifts" class="text-decoration-none">Turnos</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Detalhes</span>
            <span class="ms-sm-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('ListWorkShifts', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>list-work-shifts" class="btn btn-info btn-sm"><i class="fa-solid fa-list"></i> Listar</a>
                <?php }
                $wid = (int) ($ws['id'] ?? 0);
                if (in_array('UpdateWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>update-work-shift/<?= $wid ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                <?php }
                if (in_array('DeleteWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                    <form action="<?= $_ENV['URL_ADM']; ?>delete-work-shift" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                        <input type="hidden" name="id" value="<?= $wid; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="confirmDeletion(event, <?= $wid; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                    </form>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <dl class="row">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9"><?= (int) ($ws['id'] ?? 0); ?></dd>
                <dt class="col-sm-3">Descrição</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($ws['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt class="col-sm-3">Intervalo 1</dt>
                <dd class="col-sm-9"><?= $fmt($ws['entry_1'] ?? null); ?> — <?= $fmt($ws['exit_1'] ?? null); ?></dd>
                <dt class="col-sm-3">Intervalo 2</dt>
                <dd class="col-sm-9"><?= $fmt($ws['entry_2'] ?? null); ?> — <?= $fmt($ws['exit_2'] ?? null); ?></dd>
                <dt class="col-sm-3">Intervalo 3</dt>
                <dd class="col-sm-9"><?= $fmt($ws['entry_3'] ?? null); ?> — <?= $fmt($ws['exit_3'] ?? null); ?></dd>
                <dt class="col-sm-3">Tolerância extras</dt>
                <dd class="col-sm-9"><?= (int) ($ws['overtime_tolerance_minutes'] ?? 0); ?> min</dd>
                <dt class="col-sm-3">Tolerância faltas</dt>
                <dd class="col-sm-9"><?= (int) ($ws['absence_tolerance_minutes'] ?? 0); ?> min</dd>
                <dt class="col-sm-3">Carga horária líquida</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted small">(<?= (int) ($ws['total_minutes'] ?? 0); ?> min)</span></dd>
                <dt class="col-sm-3">Atualizado em</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($ws['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
            </dl>
        </div>
    </div>
</div>

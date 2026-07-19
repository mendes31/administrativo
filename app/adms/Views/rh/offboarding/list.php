<?php

declare(strict_types=1);

$planos = $this->data['planos'] ?? [];
$filters = $this->data['filters'] ?? [];
$tipos = $this->data['tipos'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Offboarding</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item active">RH</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Filtros</span>
            <?php if (!empty($this->data['buttonPermission']['RhOffboardingsCreate'])): ?>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-create', ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-plus me-1"></i>Iniciar offboarding
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label" for="adms_user_id">ID do usuário</label>
                    <input class="form-control form-control-sm" type="number" name="adms_user_id" id="adms_user_id"
                           value="<?= htmlspecialchars((string) ($filters['adms_user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select form-select-sm" name="status" id="status">
                        <option value="">Todos</option>
                        <?php foreach (['em_andamento', 'concluido', 'cancelado'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>>
                                <?= htmlspecialchars(str_replace('_', ' ', $st), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select class="form-select form-select-sm" name="tipo" id="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars(str_replace('_', ' ', $t), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-light shadow">
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Colaborador</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Prevista</th>
                        <th>Desligamento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($planos === []): ?>
                        <tr><td colspan="7" class="text-muted">Nenhum offboarding encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($planos as $p): ?>
                            <tr>
                                <td><?= (int) ($p['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($p['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', (string) ($p['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', (string) ($p['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($p['data_prevista'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($p['data_desligamento'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-view/' . (int) ($p['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if (!empty($this->data['paginator'])): ?>
                <div class="mt-2"><?= $this->data['paginator'] ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

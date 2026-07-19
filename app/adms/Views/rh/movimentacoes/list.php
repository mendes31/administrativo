<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

$movs = $this->data['movimentacoes'] ?? [];
$filters = $this->data['filters'] ?? [];
$tipos = $this->data['tipos'] ?? [];
$pagination = $this->data['pagination'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Movimentações</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item active">RH</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Filtros</span>
            <?php if (!empty($this->data['buttonPermission']['RhMovimentacoesCreate'])): ?>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-create', ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-plus me-1"></i>Nova movimentação
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label" for="adms_user_id">ID do usuário</label>
                    <input class="form-control form-control-sm" type="number" name="adms_user_id" id="adms_user_id"
                           value="<?= htmlspecialchars((string) ($filters['adms_user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                <div class="col-md-3 d-flex align-items-end">
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
                        <th>Vigência</th>
                        <th>Motivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($movs === []): ?>
                        <tr><td colspan="6" class="text-muted">Nenhuma movimentação encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movs as $m): ?>
                            <tr>
                                <td><?= (int) ($m['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($m['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', (string) ($m['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($m['data_vigencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($m['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-view/' . (int) ($m['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Ver</a>
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

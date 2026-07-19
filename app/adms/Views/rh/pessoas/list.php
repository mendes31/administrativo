<?php

declare(strict_types=1);

$pessoas = $this->data['pessoas'] ?? [];
$filters = $this->data['filters'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pessoas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item active">Identidade</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-3">
        <div class="card-header">Filtros</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="small text-muted">Somente leitura — dados sincronizados a partir de <code>adms_users</code> (Expand ADR-0006).</p>
            <form method="get" class="row g-2">
                <div class="col-md-6">
                    <label class="form-label" for="q">Busca</label>
                    <input class="form-control form-control-sm" type="text" name="q" id="q"
                           value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome, e-mail ou CPF">
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
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Conta</th>
                        <th>Vínculo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pessoas === []): ?>
                        <tr><td colspan="6" class="text-muted">Nenhuma pessoa encontrada. Rode a migration de identidade.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pessoas as $p): ?>
                            <tr>
                                <td><?= (int) ($p['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($p['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($p['cpf'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($p['adms_user_id']) ? '#' . (int) $p['adms_user_id'] : '—' ?></td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', (string) ($p['vinculo_status'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-pessoas-view/' . (int) ($p['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Ver</a>
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

<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;

$m = $this->data['movimentacao'] ?? [];
$id = (int) ($m['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Movimentação #<?= $id ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes', ENT_QUOTES, 'UTF-8') ?>">Movimentações</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-header">
            <?= htmlspecialchars((string) ($m['usuario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars(str_replace('_', ' ', (string) ($m['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p><strong>Vigência:</strong> <?= htmlspecialchars(FormatHelper::formatDate($m['data_vigencia'] ?? null), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Motivo:</strong> <?= htmlspecialchars((string) ($m['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($m['observacoes'])): ?>
                <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars((string) $m['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <h6>Antes</h6>
                    <ul class="mb-0">
                        <li>Departamento: <?= htmlspecialchars((string) ($m['dep_antes_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Cargo: <?= htmlspecialchars((string) ($m['cargo_antes_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Gestor: <?= htmlspecialchars((string) ($m['gestor_antes_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Depois</h6>
                    <ul class="mb-0">
                        <li>Departamento: <?= htmlspecialchars((string) ($m['dep_depois_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Cargo: <?= htmlspecialchars((string) ($m['cargo_depois_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Gestor: <?= htmlspecialchars((string) ($m['gestor_depois_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                </div>
            </div>

            <div class="mt-3">
                <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes', ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'view-user/' . (int) ($m['adms_user_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Ver usuário</a>
            </div>
        </div>
    </div>
</div>

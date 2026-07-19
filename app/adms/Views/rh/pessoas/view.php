<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;

$p = $this->data['pessoa'] ?? [];
$v = $this->data['vinculo'] ?? null;
$l = $this->data['lotacao'] ?? null;
$pessoaId = (int) ($p['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pessoa #<?= $pessoaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-pessoas', ENT_QUOTES, 'UTF-8') ?>">Pessoas</a></li>
            <li class="breadcrumb-item active">#<?= $pessoaId ?></li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Dados demográficos</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row">
                <div class="col-md-4"><strong>Nome</strong><br><?= htmlspecialchars((string) ($p['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4"><strong>CPF</strong><br><?= htmlspecialchars((string) ($p['cpf'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4"><strong>E-mail</strong><br><?= htmlspecialchars((string) ($p['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4"><strong>Nascimento</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_nascimento'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4"><strong>Sexo</strong><br><?= htmlspecialchars((string) ($p['sexo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4">
                    <strong>Conta</strong><br>
                    <?php if (!empty($p['adms_user_id'])): ?>
                        <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'view-user/' . (int) $p['adms_user_id'], ENT_QUOTES, 'UTF-8') ?>">
                            #<?= (int) $p['adms_user_id'] ?> — <?= htmlspecialchars((string) ($p['conta_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Vínculo</div>
        <div class="card-body">
            <?php if (!is_array($v)): ?>
                <p class="text-muted mb-0">Sem vínculo sincronizado.</p>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-3"><strong>Status</strong><br><?= htmlspecialchars((string) ($v['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-3"><strong>Início</strong><br><?= htmlspecialchars(FormatHelper::formatDate($v['data_inicio'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-3"><strong>Fim</strong><br><?= htmlspecialchars(FormatHelper::formatDate($v['data_fim'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-3"><strong>ID</strong><br>#<?= (int) ($v['id'] ?? 0) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Lotação vigente</div>
        <div class="card-body">
            <?php if (!is_array($l)): ?>
                <p class="text-muted mb-0">Sem lotação vigente.</p>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4"><strong>Departamento</strong><br><?= htmlspecialchars((string) ($l['departamento_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-4"><strong>Cargo</strong><br><?= htmlspecialchars((string) ($l['cargo_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-4"><strong>Gestor</strong><br><?= htmlspecialchars((string) ($l['gestor_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

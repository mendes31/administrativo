<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\RhOnboardingRepository;

$p = $this->data['plano'] ?? [];
$itens = $this->data['itens'] ?? [];
$canManage = !empty($this->data['can_manage']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$planoId = (int) ($p['id'] ?? 0);
$status = (string) ($p['status'] ?? '');
$statusClass = match ($status) {
    'em_andamento' => 'bg-warning text-dark',
    'concluido' => 'bg-success',
    'cancelado' => 'bg-dark',
    default => 'bg-secondary',
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Onboarding #<?= $planoId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <?php if (!empty($p['rh_candidato_id'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos-view/' . (int) $p['rh_candidato_id'], ENT_QUOTES, 'UTF-8') ?>">Candidato</a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active">Onboarding</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <?= htmlspecialchars((string) ($p['usuario_nome'] ?? $p['candidato_nome'] ?? 'Colaborador'), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($p['adms_user_id'])): ?>
                    · Usuário #<?= (int) $p['adms_user_id'] ?>
                <?php endif; ?>
            </span>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row mb-3">
                <div class="col-md-4"><strong>Início</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_inicio'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4"><strong>Limite</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_limite'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-4"><strong>E-mail</strong><br><?= htmlspecialchars((string) ($p['usuario_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <p class="small text-muted mb-0">O plano conclui automaticamente quando todos os itens obrigatórios estiverem concluídos ou dispensados.</p>

            <?php
            $experienciaLink = null;
            if (!empty($p['rh_conversao_id'])) {
                $experienciaLink = (new \App\adms\Models\Repository\RhPeriodosExperienciaRepository())
                    ->getByConversaoId((int) $p['rh_conversao_id']);
            }
            if (!empty($experienciaLink['id'])): ?>
                <p class="mt-2 mb-0">
                    <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-experiencia-view/' . (int) $experienciaLink['id'], ENT_QUOTES, 'UTF-8') ?>">
                        Ver período de experiência
                    </a>
                </p>
            <?php endif; ?>

            <?php if ($canManage && $status === RhOnboardingRepository::STATUS_EM_ANDAMENTO): ?>
                <form method="post" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="action" value="cancelar" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Cancelar este plano de onboarding?');">Cancelar plano</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header"><i class="fas fa-tasks me-2"></i>Checklist</div>
        <div class="card-body">
            <?php if ($itens === []): ?>
                <p class="text-muted mb-0">Nenhum item.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Obrigatório</th>
                                <th>Status</th>
                                <?php if ($canManage && $status !== RhOnboardingRepository::STATUS_CANCELADO): ?>
                                    <th>Atualizar</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= !empty($item['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                                    <td><?= htmlspecialchars(str_replace('_', ' ', (string) ($item['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php if ($canManage && $status !== RhOnboardingRepository::STATUS_CANCELADO): ?>
                                        <td>
                                            <form method="post" class="d-flex flex-wrap gap-1 align-items-center">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="item">
                                                <input type="hidden" name="item_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                                <select name="item_status" class="form-select form-select-sm" style="width:auto">
                                                    <?php foreach (['pendente', 'em_andamento', 'concluido', 'dispensado'] as $st): ?>
                                                        <option value="<?= $st ?>" <?= ($item['status'] ?? '') === $st ? 'selected' : '' ?>><?= str_replace('_', ' ', $st) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-outline-primary btn-sm">Salvar</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

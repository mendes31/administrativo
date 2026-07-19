<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\RhPeriodosExperienciaRepository;
use App\adms\Models\Services\RhExperienciaService;

$p = $this->data['periodo'] ?? [];
$canManage = !empty($this->data['can_manage']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$id = (int) ($p['id'] ?? 0);
$status = (string) ($p['status'] ?? '');
$statusClass = match ($status) {
    'em_andamento' => 'bg-warning text-dark',
    'prorrogado' => 'bg-info text-dark',
    'aprovado' => 'bg-success',
    'reprovado' => 'bg-danger',
    'cancelado' => 'bg-dark',
    default => 'bg-secondary',
};
$aberto = in_array($status, [
    RhPeriodosExperienciaRepository::STATUS_EM_ANDAMENTO,
    RhPeriodosExperienciaRepository::STATUS_PRORROGADO,
], true);
$jaProrrogou = (int) ($p['dias_prorrogacao'] ?? 0) > 0;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Período de experiência #<?= $id ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <?php if (!empty($p['rh_onboarding_plano_id'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-onboarding-view/' . (int) $p['rh_onboarding_plano_id'], ENT_QUOTES, 'UTF-8') ?>">Onboarding</a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active">Experiência</li>
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
                <div class="col-md-3"><strong>Início</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_inicio'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Fim previsto</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_fim_prevista'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Prorrogação</strong><br>
                    <?= $jaProrrogou ? ((int) $p['dias_prorrogacao'] . ' dias') : '—' ?>
                </div>
                <div class="col-md-3"><strong>Avaliado em</strong><br>
                    <?= htmlspecialchars(FormatHelper::formatDateTime($p['avaliado_em'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <?php if (!empty($p['observacoes'])): ?>
                <p class="mb-2"><strong>Observações:</strong><br><?= nl2br(htmlspecialchars((string) $p['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <p class="small text-muted mb-0">
                Padrão <?= RhExperienciaService::DIAS_PADRAO ?> dias; prorrogação única de até <?= RhExperienciaService::DIAS_PRORROGACAO_PADRAO ?> dias.
                A reprovação <strong>não</strong> desliga o usuário automaticamente neste incremento.
            </p>

            <?php if ($canManage && $aberto): ?>
                <hr>
                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="observacoes">Observações / motivo</label>
                        <input type="text" class="form-control" name="observacoes" id="observacoes" maxlength="500">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="dias_prorrogacao">Dias (prorrogar)</label>
                        <input type="number" class="form-control" name="dias_prorrogacao" id="dias_prorrogacao"
                               min="1" max="90" value="<?= RhExperienciaService::DIAS_PRORROGACAO_PADRAO ?>"
                            <?= $jaProrrogou ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="action" value="aprovar" class="btn btn-success btn-sm"
                                onclick="return confirm('Confirmar aprovação do período?');">Aprovar</button>
                        <button type="submit" name="action" value="reprovar" class="btn btn-danger btn-sm"
                                onclick="return confirm('Registrar reprovação?');">Reprovar</button>
                        <?php if (!$jaProrrogou): ?>
                            <button type="submit" name="action" value="prorrogar" class="btn btn-info btn-sm"
                                    onclick="return confirm('Prorrogar o período?');">Prorrogar</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="cancelar" class="btn btn-outline-dark btn-sm"
                                onclick="return confirm('Cancelar este período?');">Cancelar</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

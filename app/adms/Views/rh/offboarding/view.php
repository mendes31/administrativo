<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\RhOffboardingRepository;

$p = $this->data['plano'] ?? [];
$itens = $this->data['itens'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
$planoId = (int) ($p['id'] ?? 0);
$status = (string) ($p['status'] ?? '');
$pendentes = (int) ($this->data['obrigatorios_pendentes'] ?? 0);
$statusClass = match ($status) {
    'em_andamento' => 'bg-warning text-dark',
    'concluido' => 'bg-success',
    'cancelado' => 'bg-dark',
    default => 'bg-secondary',
};
$emAndamento = $status === RhOffboardingRepository::STATUS_EM_ANDAMENTO;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Offboarding #<?= $planoId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings', ENT_QUOTES, 'UTF-8') ?>">Offboarding</a></li>
            <li class="breadcrumb-item active">#<?= $planoId ?></li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <?= htmlspecialchars((string) ($p['usuario_nome'] ?? 'Colaborador'), ENT_QUOTES, 'UTF-8') ?>
                · Usuário #<?= (int) ($p['adms_user_id'] ?? 0) ?>
            </span>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="row mb-3">
                <div class="col-md-3"><strong>Tipo</strong><br><?= htmlspecialchars(str_replace('_', ' ', (string) ($p['tipo'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Prevista</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_prevista'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Desligamento</strong><br><?= htmlspecialchars(FormatHelper::formatDate($p['data_desligamento'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Classificação</strong><br><?= htmlspecialchars(UserFormHelper::tipoImpactoDesligamentoLabel($p['tipo_impacto'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <p class="mb-1"><strong>Motivo:</strong> <?= htmlspecialchars((string) ($p['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($p['observacoes'])): ?>
                <p class="text-muted mb-0"><?= nl2br(htmlspecialchars((string) $p['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <?php if ($emAndamento): ?>
                <div class="d-flex flex-wrap gap-2 mt-3 align-items-end">
                    <form method="post" class="d-flex flex-wrap gap-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="concluir">
                        <div>
                            <label class="form-label mb-0" for="data_desligamento">Data efetiva</label>
                            <input class="form-control form-control-sm" type="date" name="data_desligamento" id="data_desligamento"
                                   value="<?= htmlspecialchars((string) ($p['data_prevista'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm"
                                <?= $pendentes > 0 ? 'disabled title="Há itens obrigatórios pendentes"' : '' ?>
                                onclick="return confirm('Concluir offboarding e inativar o colaborador?');">
                            Concluir desligamento
                        </button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" name="action" value="cancelar" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Cancelar este offboarding sem desligar o colaborador?');">
                            Cancelar plano
                        </button>
                    </form>
                </div>
                <?php if ($pendentes > 0): ?>
                    <p class="small text-muted mt-2 mb-0"><?= $pendentes ?> item(ns) obrigatório(s) pendente(s).</p>
                <?php endif; ?>
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
                                <?php if ($emAndamento): ?>
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
                                    <?php if ($emAndamento): ?>
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

<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\RhOfertasRepository;

$o = $this->data['oferta'] ?? [];
$docs = $this->data['documentos'] ?? [];
$canManage = !empty($this->data['can_manage']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$ofertaId = (int) ($o['id'] ?? 0);
$status = (string) ($o['status'] ?? '');
$statusClass = match ($status) {
    'rascunho' => 'bg-secondary',
    'enviada' => 'bg-info text-dark',
    'aceita' => 'bg-success',
    'recusada' => 'bg-danger',
    'cancelada' => 'bg-dark',
    default => 'bg-secondary',
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Oferta #<?= $ofertaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos-view/' . (int) ($o['rh_candidato_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">Candidato</a>
            </li>
            <li class="breadcrumb-item active">Oferta</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <?= htmlspecialchars((string) ($o['candidato_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars((string) ($o['vaga_titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row mb-3">
                <div class="col-md-3"><strong>Salário</strong><br>
                    <?php if ($o['salario_oferecido'] !== null && $o['salario_oferecido'] !== ''): ?>
                        R$ <?= htmlspecialchars(number_format((float) $o['salario_oferecido'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-3"><strong>Contrato</strong><br><?= htmlspecialchars((string) ($o['tipo_contrato'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Início previsto</strong><br><?= htmlspecialchars(FormatHelper::formatDate($o['data_inicio_prevista'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-3"><strong>Validade</strong><br><?= htmlspecialchars(FormatHelper::formatDate($o['validade_ate'] ?? null) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <?php if (!empty($o['observacoes'])): ?>
                <p class="mb-2"><strong>Condições:</strong><br><?= nl2br(htmlspecialchars((string) $o['observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>
            <?php if (!empty($o['resposta_observacoes'])): ?>
                <p class="mb-2"><strong>Resposta:</strong><br><?= nl2br(htmlspecialchars((string) $o['resposta_observacoes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <p class="small text-muted mb-0">
                Aceite inicia pré-admissão (checklist). Não cria vínculo empregatício automaticamente.
            </p>

            <?php if (!empty($o['rh_conversao_id'])): ?>
                <div class="alert alert-success mt-3 mb-0">
                    Oferta convertida (conversão #<?= (int) $o['rh_conversao_id'] ?>).
                    <?php if (!empty($this->data['conversao']['adms_user_id'])): ?>
                        Usuário #
                        <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'view-user/' . (int) $this->data['conversao']['adms_user_id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= (int) $this->data['conversao']['adms_user_id'] ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($this->data['onboarding_plano']['id'])): ?>
                        ·
                        <a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-onboarding-view/' . (int) $this->data['onboarding_plano']['id'], ENT_QUOTES, 'UTF-8') ?>">
                            Abrir onboarding
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif ($canManage && $status === RhOfertasRepository::STATUS_ACEITA && !empty($this->data['buttonPermission']['RhOfertasConvert'])): ?>
                <div class="mt-3">
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-convert/' . $ofertaId, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-user-check me-1"></i>Converter em colaborador
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($canManage && in_array($status, [RhOfertasRepository::STATUS_RASCUNHO, RhOfertasRepository::STATUS_ENVIADA], true)): ?>
                <hr>
                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="observacoes">Observações da ação</label>
                        <input type="text" class="form-control" name="observacoes" id="observacoes" maxlength="500">
                    </div>
                    <div class="col-md-6 d-flex flex-wrap gap-2">
                        <?php if ($status === RhOfertasRepository::STATUS_RASCUNHO): ?>
                            <button type="submit" name="action" value="enviar" class="btn btn-info btn-sm">Marcar como enviada</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="aceitar" class="btn btn-success btn-sm"
                                onclick="return confirm('Registrar aceite e iniciar pré-admissão?');">Registrar aceite</button>
                        <button type="submit" name="action" value="recusar" class="btn btn-danger btn-sm"
                                onclick="return confirm('Registrar recusa da oferta?');">Registrar recusa</button>
                        <button type="submit" name="action" value="cancelar" class="btn btn-outline-dark btn-sm"
                                onclick="return confirm('Cancelar esta oferta?');">Cancelar oferta</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($status === RhOfertasRepository::STATUS_ACEITA): ?>
        <div class="card border-light shadow mb-4">
            <div class="card-header"><i class="fas fa-folder-open me-2"></i>Documentos de pré-admissão</div>
            <div class="card-body">
                <?php if ($docs === []): ?>
                    <p class="text-muted mb-0">Nenhum documento na checklist.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Obrigatório</th>
                                    <th>Status</th>
                                    <?php if ($canManage): ?><th>Atualizar</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($docs as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($doc['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= !empty($doc['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                                        <td><?= htmlspecialchars(ucfirst((string) ($doc['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php if ($canManage): ?>
                                            <td>
                                                <form method="post" class="d-flex flex-wrap gap-1 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="documento">
                                                    <input type="hidden" name="documento_id" value="<?= (int) ($doc['id'] ?? 0) ?>">
                                                    <select name="documento_status" class="form-select form-select-sm" style="width:auto">
                                                        <?php foreach (['pendente', 'recebido', 'aprovado', 'recusado'] as $st): ?>
                                                            <option value="<?= $st ?>" <?= ($doc['status'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
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
                    <p class="small text-muted mb-0">Upload de arquivos ainda não está disponível neste incremento.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

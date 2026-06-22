<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;

$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$context = $this->data['scan_context'] ?? [];
$equipamento = $context['equipamento'] ?? [];
$vistoria = $context['vistoria'] ?? null;
$code = (string) ($context['code'] ?? '');
$message = (string) ($context['message'] ?? '');
$perms = $this->data['buttonPermission'] ?? [];
$eqId = (int) ($equipamento['id'] ?? 0);
$vistoriaId = is_array($vistoria) ? (int) ($vistoria['id'] ?? 0) : 0;
$vistoriaStatus = is_array($vistoria) ? (string) ($vistoria['status'] ?? '') : '';
$vistoriaAberta = $vistoriaId > 0 && $vistoriaStatus !== '' && $vistoriaStatus !== 'Concluída';
$vistoriaConcluida = $vistoriaId > 0 && $vistoriaStatus === 'Concluída';
$podeExecutar = in_array('SstExecuteEquipamentoVistoria', $perms, true)
    && !in_array($code, ['inactive', 'forbidden'], true);
$csrfGerar = CSRFHelper::generateCSRFToken('sst_generate_equipamento_vistoria');
$competenciaAtual = date('Y-m');

$alertType = match ($code) {
    'ready' => 'success',
    'completed' => 'info',
    'no_open' => 'warning',
    'inactive', 'forbidden' => 'warning',
    default => 'secondary',
};
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0"><i class="fas fa-qrcode me-2"></i>Equipamento identificado</h2>
        <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>sst-scan-equipamento">Ler QR</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($equipamento['codigo'] ?? 'Equipamento') ?></li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($message !== ''): ?>
    <div class="alert alert-<?= $alertType ?> py-2 small"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <i class="fas fa-fire-extinguisher me-1"></i>
                    <?= htmlspecialchars($equipamento['codigo'] ?? 'Equipamento') ?>
                    <span class="badge bg-secondary ms-1"><?= htmlspecialchars($equipamento['status'] ?? '') ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Tipo</div>
                            <div class="fw-semibold"><?= htmlspecialchars($equipamento['tipo_nome'] ?? '—') ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Localização</div>
                            <div class="fw-semibold"><?= htmlspecialchars($equipamento['localizacao'] ?? '—') ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Departamento</div>
                            <div class="fw-semibold"><?= htmlspecialchars($equipamento['departamento_nome'] ?? '—') ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Responsável</div>
                            <div class="fw-semibold"><?= htmlspecialchars($equipamento['responsavel_nome'] ?? '— (fila geral)') ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Periodicidade</div>
                            <div class="fw-semibold"><?= htmlspecialchars(SstEquipamentoPeriodicidadeHelper::label((int) ($equipamento['periodicidade_meses'] ?? 1))) ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Dia previsto</div>
                            <div class="fw-semibold"><?php
                                if (!empty($equipamento['dia_previsto_vistoria'])) {
                                    echo 'Dia ' . (int) $equipamento['dia_previsto_vistoria'];
                                } else {
                                    echo 'Dia ' . (int) ($this->data['settings']['dia_previsto_padrao'] ?? 1);
                                }
                            ?></div>
                        </div>
                        <?php if (!empty($equipamento['capacidade'])): ?>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Capacidade</div>
                            <div class="fw-semibold"><?= htmlspecialchars($equipamento['capacidade']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($equipamento['data_proxima_recarga'])): ?>
                        <div class="col-6 col-md-4">
                            <div class="text-muted">Próx. recarga</div>
                            <div class="fw-semibold"><?= date('d/m/Y', strtotime((string) $equipamento['data_proxima_recarga'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array('SstViewEquipamento', $perms, true) && $eqId > 0): ?>
                    <div class="mt-3">
                        <a href="<?= htmlspecialchars($urlAdm) ?>sst-view-equipamento/<?= $eqId ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>Ver ficha completa
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><i class="fas fa-clipboard-check me-1"></i>Vistoria</div>
                <div class="card-body d-flex flex-column">
                    <?php if ($vistoriaAberta && $podeExecutar): ?>
                        <p class="small text-muted mb-3">Há uma vistoria aberta para este equipamento. Inicie o checklist no local da inspeção.</p>
                        <dl class="row small mb-3">
                            <dt class="col-5">Competência</dt>
                            <dd class="col-7"><?= htmlspecialchars($vistoria['competencia'] ?? '—') ?></dd>
                            <dt class="col-5">Prevista para</dt>
                            <dd class="col-7"><?= !empty($vistoria['data_prevista']) ? date('d/m/Y', strtotime((string) $vistoria['data_prevista'])) : '—' ?></dd>
                            <dt class="col-5">Status</dt>
                            <dd class="col-7">
                                <span class="badge <?= ($vistoria['status'] ?? '') === 'Vencida' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                    <?= htmlspecialchars($vistoria['status'] ?? '') ?>
                                </span>
                            </dd>
                        </dl>
                        <a href="<?= htmlspecialchars($urlAdm) ?>sst-execute-equipamento-vistoria/<?= $vistoriaId ?>?from=qr"
                           class="btn btn-success btn-lg w-100 mt-auto">
                            <i class="fas fa-play me-1"></i>
                            <?= ($vistoria['status'] ?? '') === 'Em andamento' ? 'Continuar vistoria' : 'Iniciar vistoria' ?>
                        </a>
                    <?php elseif ($vistoriaConcluida): ?>
                        <p class="small text-muted mb-3">Vistoria já concluída nesta competência.</p>
                        <dl class="row small mb-3">
                            <dt class="col-5">Competência</dt>
                            <dd class="col-7"><?= htmlspecialchars($vistoria['competencia'] ?? '—') ?></dd>
                            <dt class="col-5">Realizada em</dt>
                            <dd class="col-7"><?= !empty($vistoria['data_realizada']) ? date('d/m/Y H:i', strtotime((string) $vistoria['data_realizada'])) : '—' ?></dd>
                            <dt class="col-5">Resultado</dt>
                            <dd class="col-7"><?= htmlspecialchars($vistoria['resultado'] ?? '—') ?></dd>
                        </dl>
                        <?php if ($podeExecutar): ?>
                        <a href="<?= htmlspecialchars($urlAdm) ?>sst-execute-equipamento-vistoria/<?= $vistoriaId ?>?from=qr"
                           class="btn btn-outline-primary w-100 mt-auto">
                            <i class="fas fa-eye me-1"></i>Ver checklist registrado
                        </a>
                        <?php endif; ?>
                    <?php elseif ($code === 'no_open' && $podeExecutar && in_array('SstGenerateEquipamentoVistoria', $perms, true) && ($equipamento['status'] ?? '') === 'Ativo'): ?>
                        <p class="small text-muted mb-3">Não há vistoria aberta. Você pode gerar a vistoria da competência atual e em seguida iniciar a inspeção.</p>
                        <form method="POST" action="<?= htmlspecialchars($urlAdm) ?>sst-generate-equipamento-vistoria" class="mt-auto">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfGerar) ?>">
                            <input type="hidden" name="adms_sst_equipamento_id" value="<?= $eqId ?>">
                            <input type="hidden" name="competencia" value="<?= htmlspecialchars($competenciaAtual) ?>">
                            <input type="hidden" name="return_scan_token" value="<?= htmlspecialchars((string) ($this->data['scan_token'] ?? '')) ?>">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-1"></i>Gerar vistoria (<?= htmlspecialchars($competenciaAtual) ?>)
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="small text-muted mb-0 mt-auto">
                            <?php if (in_array($code, ['inactive', 'forbidden'], true)): ?>
                                Não é possível iniciar vistoria com este perfil ou status do equipamento.
                            <?php else: ?>
                                Nenhuma vistoria aberta no momento.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars($urlAdm) ?>sst-scan-equipamento" class="btn btn-outline-success btn-sm">
            <i class="fas fa-qrcode me-1"></i>Ler outro QR
        </a>
        <a href="<?= htmlspecialchars($urlAdm) ?>sst-minhas-equipamento-vistorias" class="btn btn-secondary btn-sm">
            <i class="fas fa-list me-1"></i>Minhas vistorias
        </a>
    </div>
</div>

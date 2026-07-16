<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserFormHelper;

$nc = $this->data['nc'] ?? [];
$acoes = $this->data['acoes'] ?? [];
$anexosPorAcao = $this->data['anexos_por_acao'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$ncId = (int) ($nc['id'] ?? 0);
$aberta = in_array($nc['status'] ?? '', ['Aberta', 'Em tratamento'], true);
$podeCriarAc = $aberta && in_array('SstCreateEquipamentoAcaoCorretiva', $perms, true);
$podeEncerrar = $aberta && in_array('SstEncerrarEquipamentoNaoConformidade', $perms, true);
$csrfEncerrar = CSRFHelper::generateCSRFToken('sst_encerrar_nc');
$acoesConcluidas = array_values(array_filter($acoes, static fn ($a) => ($a['status'] ?? '') === 'Concluído'));
$filial = UserFormHelper::empresaContratanteLabel($nc['empresa_contratante'] ?? null);
?>
<div class="container-fluid px-3 px-md-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            <?= htmlspecialchars($nc['codigo'] ?? 'NC') ?>
        </h2>
        <ol class="breadcrumb mb-0 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>sst-list-equipamento-nao-conformidades">Não conformidades</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($nc['codigo'] ?? '') ?></li>
        </ol>
    </div>

    <div class="alert alert-light border small">
        A vistoria de origem permanece com resultado <strong>Não conforme</strong> (histórico).
        Ao encerrar esta NC mediante ação corretiva, o registro fica:
        <em>NC encerrada pela AC-xxx</em> — sem reescrever a vistoria.
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-6 col-md-3">
                    <div class="text-muted">Status</div>
                    <div class="fw-semibold"><?= htmlspecialchars($nc['status'] ?? '') ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Equipamento</div>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($nc['equipamento_codigo'] ?? '') ?>
                        <?php if (($nc['equipamento_status'] ?? '') === 'Bloqueado'): ?>
                        <span class="badge bg-danger">Bloqueado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Grupo / Filial</div>
                    <div class="fw-semibold"><?= htmlspecialchars($nc['tipo_nome'] ?? '') ?> · <?= htmlspecialchars($filial) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Vistoria</div>
                    <div class="fw-semibold">
                        Comp. <?= htmlspecialchars($nc['competencia'] ?? '') ?>
                        · <?= htmlspecialchars($nc['vistoria_resultado'] ?? '') ?>
                    </div>
                    <?php if (!empty($nc['adms_sst_equipamento_vistoria_id'])): ?>
                    <a class="small" href="<?= htmlspecialchars($urlAdm) ?>sst-execute-equipamento-vistoria/<?= (int) $nc['adms_sst_equipamento_vistoria_id'] ?>">Ver vistoria</a>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <div class="text-muted">Item / desvio</div>
                    <div class="fw-semibold"><?= htmlspecialchars($nc['descricao'] ?? '') ?></div>
                    <?php if (!empty($nc['observacao'])): ?>
                    <div class="text-muted"><?= htmlspecialchars($nc['observacao']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (($nc['status'] ?? '') === 'Encerrada'): ?>
                <div class="col-12">
                    <div class="alert alert-success py-2 mb-0 small">
                        Encerrada em <?= !empty($nc['encerrada_em']) ? date('d/m/Y H:i', strtotime((string) $nc['encerrada_em'])) : '—' ?>
                        <?php if (!empty($nc['acao_encerramento_codigo'])): ?>
                        mediante ação corretiva <strong><?= htmlspecialchars($nc['acao_encerramento_codigo']) ?></strong>
                        <?php if (!empty($nc['acao_encerramento_titulo'])): ?>
                        (<?= htmlspecialchars($nc['acao_encerramento_titulo']) ?>)
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($nc['responsavel_encerramento_nome'])): ?>
                        · por <?= htmlspecialchars($nc['responsavel_encerramento_nome']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><i class="fas fa-tasks me-1"></i>Ações corretivas</span>
            <?php if ($podeCriarAc): ?>
            <a href="<?= htmlspecialchars($urlAdm) ?>sst-create-equipamento-acao-corretiva?nc_id=<?= $ncId ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Nova ação corretiva
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($acoes === []): ?>
            <p class="text-muted small p-3 mb-0">Nenhuma ação corretiva cadastrada. Crie uma ação com responsável e prazo para tratar o desvio.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Código</th><th>Título</th><th>Responsável</th><th>Prazo</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($acoes as $a): ?>
                    <?php $aid = (int) ($a['id'] ?? 0); ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($a['codigo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['titulo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['responsavel_nome'] ?? '—') ?></td>
                        <td><?= !empty($a['prazo']) ? date('d/m/Y', strtotime((string) $a['prazo'])) : '—' ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['status'] ?? '') ?></span></td>
                        <td>
                            <?php if (in_array('SstUpdateEquipamentoAcaoCorretiva', $perms, true)): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($urlAdm) ?>sst-update-equipamento-acao-corretiva/<?= $aid ?>">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                        $fotos = $anexosPorAcao[$aid] ?? [];
                        if ($fotos !== []):
                    ?>
                    <tr>
                        <td colspan="6" class="bg-light small py-2">
                            Evidências:
                            <?php foreach ($fotos as $f): ?>
                            <a href="<?= htmlspecialchars($urlAdm) ?>sst-view-anexo/<?= (int) $f['id'] ?>" target="_blank" class="me-2">
                                <?= htmlspecialchars($f['file_name'] ?? 'foto') ?>
                                (<?= !empty($f['created_at']) ? date('d/m/Y H:i', strtotime((string) $f['created_at'])) : '' ?>)
                            </a>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($podeEncerrar): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Encerrar NC</div>
        <div class="card-body">
            <?php if ($acoesConcluidas === []): ?>
            <p class="small text-muted mb-0">Conclua ao menos uma ação corretiva (status <strong>Concluído</strong>) para poder encerrar esta NC.</p>
            <?php else: ?>
            <form method="POST" action="<?= htmlspecialchars($urlAdm) ?>sst-encerrar-equipamento-nao-conformidade/<?= $ncId ?>" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfEncerrar) ?>">
                <div class="col-md-8">
                    <label class="form-label" for="acao_id">Ação corretiva que encerra a NC *</label>
                    <select name="acao_id" id="acao_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($acoesConcluidas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>">
                            <?= htmlspecialchars(($a['codigo'] ?? '') . ' — ' . ($a['titulo'] ?? '')) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('Encerrar a NC mediante a ação selecionada? A vistoria permanecerá Não conforme.');">
                        <i class="fas fa-check me-1"></i>Encerrar NC
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <a href="<?= htmlspecialchars($urlAdm) ?>sst-list-equipamento-nao-conformidades" class="btn btn-secondary">Voltar</a>
</div>

<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
$item = $this->data['item'] ?? [];
$historico = $this->data['historico'] ?? [];
$historicoRecargas = $this->data['historico_recargas'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrfGerarVistoria = CSRFHelper::generateCSRFToken('sst_generate_equipamento_vistoria');
$csrfRecarga = CSRFHelper::generateCSRFToken('sst_equipamento_recarga_form');
$competenciaAtual = date('Y-m');
$id = (int)($item['id'] ?? 0);
$qrScanUrl = (string)($this->data['qr_scan_url'] ?? '');
$controlaRecarga = !empty($item['controla_recarga']);
$recargaStatus = SstEquipamentoRecargaHelper::status(
    $item['data_proxima_recarga'] ?? null,
    $controlaRecarga
);
$validadeMeses = (int)($item['validade_recarga_meses'] ?? 12);
if ($validadeMeses <= 0) {
    $validadeMeses = 12;
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-fire-extinguisher me-2"></i><?= htmlspecialchars($item['codigo'] ?? 'Equipamento') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2">
            <span><?= htmlspecialchars($item['tipo_nome'] ?? '') ?></span>
            <span class="ms-auto">
                <?php if (in_array('SstUpdateEquipamento', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-equipamento/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i> Editar</a><?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Localização:</strong><br><?= htmlspecialchars($item['localizacao'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Departamento:</strong><br><?= htmlspecialchars($item['departamento_nome'] ?? '—') ?></div>
                <div class="col-md-3"><strong>Responsável:</strong><br><?= htmlspecialchars($item['responsavel_nome'] ?? '— (fila geral)') ?></div>
                <div class="col-md-3"><strong>Periodicidade:</strong><br><?= htmlspecialchars(SstEquipamentoPeriodicidadeHelper::label((int)($item['periodicidade_meses'] ?? 1))) ?></div>
                <div class="col-md-3"><strong>Dia previsto:</strong><br><?php
                    if (!empty($item['dia_previsto_vistoria'])) {
                        echo 'Dia ' . (int) $item['dia_previsto_vistoria'];
                    } else {
                        echo 'Padrão do módulo (dia ' . (int)($this->data['settings']['dia_previsto_padrao'] ?? 1) . ')';
                    }
                ?></div>
                <div class="col-md-3"><strong>Vistoria automática:</strong><br><?= !empty($item['vistoria_automatica']) ? 'Sim' : 'Não' ?></div>
                <div class="col-md-3 mt-2"><strong>Status:</strong> <?= htmlspecialchars($item['status'] ?? '') ?></div>
                <?php if (!empty($item['capacidade'])): ?><div class="col-md-3 mt-2"><strong>Capacidade:</strong> <?= htmlspecialchars($item['capacidade']) ?></div><?php endif; ?>
                <?php if ($controlaRecarga): ?>
                <div class="col-md-3 mt-2">
                    <strong>Última recarga:</strong><br>
                    <?= !empty($item['data_recarga']) ? date('d/m/Y', strtotime($item['data_recarga'])) : '—' ?>
                </div>
                <div class="col-md-3 mt-2">
                    <strong>Próx. recarga:</strong><br>
                    <?php if (!empty($item['data_proxima_recarga'])): ?>
                        <?= date('d/m/Y', strtotime($item['data_proxima_recarga'])) ?>
                        <span class="badge <?= SstEquipamentoRecargaHelper::statusBadgeClass($recargaStatus) ?>">
                            <?= htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($recargaStatus)) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Sem data</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($qrScanUrl !== ''): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-qrcode me-1"></i>QR Code — vistoria no celular</span>
            <?php if (in_array('SstExportEquipamentoQr', $perms, true)): ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-export-equipamento-qr/<?= $id ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank" rel="noopener">
                <i class="fas fa-download me-1"></i>Baixar PNG (etiqueta)
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-auto text-center">
                    <div id="sst-equipamento-qr" class="d-inline-block p-2 bg-white border rounded"></div>
                </div>
                <div class="col-md">
                    <p class="small text-muted mb-2">
                        Cole a etiqueta no equipamento. O responsável pode ler o QR pela câmera do celular
                        (<a href="<?= $_ENV['URL_ADM']; ?>sst-scan-equipamento">Ler QR dentro do sistema</a>)
                        ou pelo app de câmera — em ambos os casos abre o checklist da vistoria pendente.
                    </p>
                    <p class="small mb-0"><strong>Link:</strong> <code class="user-select-all"><?= htmlspecialchars($qrScanUrl) ?></code></p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    (function () {
        var el = document.getElementById('sst-equipamento-qr');
        if (!el || typeof QRCode === 'undefined') return;
        new QRCode(el, {
            text: <?= json_encode($qrScanUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            width: 200,
            height: 200,
            correctLevel: QRCode.CorrectLevel.M
        });
    })();
    </script>
    <?php endif; ?>

    <?php if ($controlaRecarga): ?>
    <div class="card mb-3 shadow-sm" id="recarga">
        <div class="card-header">
            <span><i class="fas fa-flask me-1"></i>Recargas / validade de carga</span>
        </div>
        <div class="card-body">
            <?php if (in_array('SstRegisterEquipamentoRecarga', $perms, true)): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-register-equipamento-recarga" class="row g-2 align-items-end border-bottom pb-3 mb-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfRecarga ?>">
                <input type="hidden" name="adms_sst_equipamento_id" value="<?= $id ?>">
                <div class="col-md-2">
                    <label class="form-label small" for="tipo_evento">Tipo</label>
                    <select name="tipo_evento" id="tipo_evento" class="form-select form-select-sm">
                        <?php foreach (SstEquipamentoRecargaHelper::tiposEvento() as $te): ?>
                        <option value="<?= htmlspecialchars($te) ?>" <?= $te === 'Recarga' ? 'selected' : '' ?>><?= htmlspecialchars($te) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small" for="data_recarga_reg">Data *</label>
                    <input type="date" name="data_recarga" id="data_recarga_reg" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small" for="data_proxima_recarga_reg">Próxima</label>
                    <input type="date" name="data_proxima_recarga" id="data_proxima_recarga_reg" class="form-control form-control-sm">
                    <div class="form-text">Vazio = +<?= $validadeMeses ?> meses</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small" for="empresa_recarga">Empresa</label>
                    <input type="text" name="empresa" id="empresa_recarga" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small" for="numero_documento_recarga">NF / doc.</label>
                    <input type="text" name="numero_documento" id="numero_documento_recarga" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Registrar</button>
                </div>
                <div class="col-12">
                    <label class="form-label small" for="observacao_recarga">Observação</label>
                    <input type="text" name="observacao" id="observacao_recarga" class="form-control form-control-sm">
                </div>
            </form>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Data</th><th>Tipo</th><th>Próxima</th><th>Empresa</th><th>Documento</th><th>Obs.</th><th>Registrado por</th></tr></thead>
                    <tbody>
                    <?php foreach ($historicoRecargas as $hr): ?>
                        <tr>
                            <td><?= !empty($hr['data_recarga']) ? date('d/m/Y', strtotime($hr['data_recarga'])) : '—' ?></td>
                            <td><?= htmlspecialchars($hr['tipo_evento'] ?? 'Recarga') ?></td>
                            <td><?= !empty($hr['data_proxima_recarga']) ? date('d/m/Y', strtotime($hr['data_proxima_recarga'])) : '—' ?></td>
                            <td><?= htmlspecialchars($hr['empresa'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($hr['numero_documento'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($hr['observacao'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($hr['created_by_nome'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($historicoRecargas === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma recarga registrada.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header hstack gap-2 flex-wrap">
            <span>Histórico de vistorias</span>
            <?php if (in_array('SstGenerateEquipamentoVistoria', $perms, true) && ($item['status'] ?? '') === 'Ativo'): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-generate-equipamento-vistoria" class="ms-auto d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrfGerarVistoria ?>">
                <input type="hidden" name="adms_sst_equipamento_id" value="<?= $id ?>">
                <input type="hidden" name="competencia" value="<?= htmlspecialchars($competenciaAtual) ?>">
                <button type="submit" class="btn btn-primary btn-sm" title="Competência <?= htmlspecialchars($competenciaAtual) ?>">
                    <i class="fas fa-plus-circle me-1"></i>Gerar vistoria (<?= htmlspecialchars($competenciaAtual) ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Competência</th><th>Prevista</th><th>Realizada</th><th>Status</th><th>Resultado</th><th>Executor</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($historico as $h): ?>
                        <tr class="<?= ($h['resultado'] ?? '') === 'Não conforme' ? 'table-danger' : '' ?>">
                            <td><?= htmlspecialchars($h['competencia'] ?? '') ?></td>
                            <td><?= !empty($h['data_prevista']) ? date('d/m/Y', strtotime($h['data_prevista'])) : '-' ?></td>
                            <td><?= !empty($h['data_realizada']) ? date('d/m/Y H:i', strtotime($h['data_realizada'])) : '-' ?></td>
                            <td><?= htmlspecialchars($h['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($h['resultado'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($h['executor_nome'] ?? '-') ?></td>
                            <td>
                                <?php if (in_array('SstExecuteEquipamentoVistoria', $perms, true)): ?>
                                <a href="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-primary"><?= ($h['status'] ?? '') === 'Concluída' ? 'Ver' : 'Executar' ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($historico === []): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhuma vistoria registrada.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

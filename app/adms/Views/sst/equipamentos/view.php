<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;

$item = $this->data['item'] ?? [];
$historico = $this->data['historico'] ?? [];
$historicoRecargas = $this->data['historico_recargas'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrfGerarVistoria = CSRFHelper::generateCSRFToken('sst_generate_equipamento_vistoria');
$csrfRecarga = CSRFHelper::generateCSRFToken('sst_equipamento_recarga_form');
$competenciaAtual = date('Y-m');
$id = (int) ($item['id'] ?? 0);
$qrScanUrl = (string) ($this->data['qr_scan_url'] ?? '');
$controlaRecarga = !empty($item['controla_recarga']);
$recargaStatus = SstEquipamentoRecargaHelper::status(
    $item['data_proxima_recarga'] ?? null,
    $controlaRecarga
);
$validadeMeses = (int) ($item['validade_recarga_meses'] ?? 12);
if ($validadeMeses <= 0) {
    $validadeMeses = 12;
}

$txt = static function (mixed $value): string {
    $t = trim((string) ($value ?? ''));

    return $t !== '' ? htmlspecialchars($t) : '—';
};
$dt = static function (mixed $value, bool $withTime = false): string {
    if ($value === null || trim((string) $value) === '') {
        return '—';
    }
    $ts = strtotime((string) $value);

    return $ts ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '—';
};
$field = static function (string $label, string $html, string $cols = 'col-6 col-md-4 col-xl-3'): void {
    echo '<div class="' . $cols . ' sst-eq-field"><div class="text-muted">'
        . htmlspecialchars($label)
        . '</div><div class="fw-semibold">'
        . $html
        . '</div></div>';
};

$diaPrevistoHtml = !empty($item['dia_previsto_vistoria'])
    ? 'Dia ' . (int) $item['dia_previsto_vistoria']
    : 'Padrão do módulo (dia ' . (int) ($this->data['settings']['dia_previsto_padrao'] ?? 1) . ')';

$proxRecargaHtml = '—';
if ($controlaRecarga) {
    if (!empty($item['data_proxima_recarga'])) {
        $proxRecargaHtml = $dt($item['data_proxima_recarga'])
            . ' <span class="badge ' . SstEquipamentoRecargaHelper::statusBadgeClass($recargaStatus) . '">'
            . htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($recargaStatus))
            . '</span>';
    } else {
        $proxRecargaHtml = '<span class="badge bg-secondary">Sem data</span>';
    }
}
?>
<div class="container-fluid px-3 px-md-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0"><i class="fas fa-fire-extinguisher me-2"></i><?= htmlspecialchars($item['codigo'] ?? 'Equipamento') ?></h2>
        <ol class="breadcrumb mb-0 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamentos">Equipamentos</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>

    <style>
        .sst-eq-field .text-muted { font-size: .7rem; letter-spacing: .02em; text-transform: uppercase; }
        .sst-eq-field .fw-semibold { font-size: .92rem; word-break: break-word; }
        .sst-eq-destaque { background: #f6faf7; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .5rem .65rem; height: 100%; }
    </style>
    <div class="card mb-3 shadow-sm">
        <div class="card-header d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center py-2">
            <span class="d-flex flex-wrap align-items-center gap-2">
                <?= htmlspecialchars($item['tipo_nome'] ?? '') ?>
                <?php if (!empty($item['status'])): ?>
                <span class="badge bg-secondary"><?= htmlspecialchars((string) $item['status']) ?></span>
                <?php endif; ?>
                <?php if ($controlaRecarga): ?>
                <span class="badge <?= SstEquipamentoRecargaHelper::statusBadgeClass($recargaStatus) ?>">
                    <?= htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($recargaStatus)) ?>
                </span>
                <?php endif; ?>
            </span>
            <div class="d-flex flex-wrap gap-1 ms-sm-auto">
                <?php if (in_array('SstUpdateEquipamento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-update-equipamento/<?= $id ?>" class="btn btn-warning btn-sm flex-fill flex-sm-grow-0"><i class="fa-regular fa-pen-to-square"></i> Editar</a>
                <?php endif; ?>
                <?php if (in_array('SstExportEquipamentoAuditoriaPdf', $perms, true)): ?>
                <button type="button" class="btn btn-outline-danger btn-sm flex-fill flex-sm-grow-0" data-bs-toggle="modal" data-bs-target="#modalAuditoriaPdf">
                    <i class="fas fa-file-pdf"></i> Relatório
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <div class="<?= $qrScanUrl !== '' ? 'col-lg-8' : 'col-12' ?>">
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <div class="sst-eq-destaque sst-eq-field">
                                <div class="text-muted">Nº série</div>
                                <div class="fw-semibold fs-5 mb-0"><?= $txt($item['numero_serie'] ?? null) ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="sst-eq-destaque sst-eq-field">
                                <div class="text-muted">Localização (sala / área)</div>
                                <div class="fw-semibold mb-0"><?= $txt($item['localizacao'] ?? null) ?></div>
                                <div class="small text-muted mt-1"><?= htmlspecialchars(SstEquipamentoSiteHelper::label($item['empresa_contratante'] ?? null)) ?></div>
                            </div>
                        </div>
                        <?php
                        $field('Patrimônio', $txt($item['patrimonio'] ?? null));
                        $field('Departamento', $txt($item['departamento_nome'] ?? null));
                        $field('Responsável', htmlspecialchars(trim((string) ($item['responsavel_nome'] ?? '')) !== '' ? (string) $item['responsavel_nome'] : '— (fila geral)'));
                        $field('Fabricante', $txt($item['fabricante'] ?? null));
                        $field('Tipo (agente)', $txt($item['modelo'] ?? null));
                        $field('Capacidade', $txt($item['capacidade'] ?? null));
                        $field('Data de fabricação', $dt($item['data_fabricacao'] ?? null));
                        if ($controlaRecarga) {
                            $field('Última recarga', $dt($item['data_recarga'] ?? null));
                            $field('Próxima recarga', $proxRecargaHtml);
                            $field('Validade do grupo', htmlspecialchars((string) $validadeMeses) . ' meses');
                        }
                        $field('Periodicidade', htmlspecialchars(SstEquipamentoPeriodicidadeHelper::label((int) ($item['periodicidade_meses'] ?? 1))));
                        $field('Dia previsto', htmlspecialchars($diaPrevistoHtml));
                        $field('1ª competência', $dt($item['data_referencia_inspecao'] ?? null));
                        $field('Vistoria automática', !empty($item['vistoria_automatica']) ? 'Sim' : 'Não');
                        ?>
                        <?php if (trim((string) ($item['observacoes'] ?? '')) !== ''): ?>
                        <div class="col-12 sst-eq-field">
                            <div class="text-muted">Observações</div>
                            <div class="fw-semibold" style="font-weight:500;white-space:pre-wrap;"><?= $txt($item['observacoes'] ?? null) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($qrScanUrl !== ''): ?>
                <div class="col-lg-4">
                    <button class="btn btn-outline-secondary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#sstEqQrBox" aria-expanded="false">
                        <i class="fas fa-qrcode me-1"></i>QR / etiqueta
                    </button>
                    <div id="sstEqQrBox" class="collapse d-lg-block">
                        <div class="border rounded p-2 text-center bg-light">
                            <div id="sst-equipamento-qr" class="d-inline-block p-1 bg-white border rounded"></div>
                            <div class="small text-muted mt-2 mb-2">Cole no equipamento. A câmera abre a vistoria pendente.</div>
                            <?php if (in_array('SstExportEquipamentoQr', $perms, true)): ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-export-equipamento-qr/<?= $id ?>" class="btn btn-outline-secondary btn-sm w-100" target="_blank" rel="noopener">
                                <i class="fas fa-download me-1"></i>Baixar PNG
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($qrScanUrl !== ''): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    (function () {
        var el = document.getElementById('sst-equipamento-qr');
        if (!el || typeof QRCode === 'undefined') return;
        new QRCode(el, {
            text: <?= json_encode($qrScanUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            width: 148,
            height: 148,
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
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small" for="tipo_evento">Tipo</label>
                    <select name="tipo_evento" id="tipo_evento" class="form-select form-select-sm">
                        <?php foreach (SstEquipamentoRecargaHelper::tiposEvento() as $te): ?>
                        <option value="<?= htmlspecialchars($te) ?>" <?= $te === 'Recarga' ? 'selected' : '' ?>><?= htmlspecialchars($te) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small" for="data_recarga_reg">Data *</label>
                    <input type="date" name="data_recarga" id="data_recarga_reg" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small" for="data_proxima_recarga_reg">Próxima</label>
                    <input type="date" name="data_proxima_recarga" id="data_proxima_recarga_reg" class="form-control form-control-sm">
                    <div class="form-text">Vazio = +<?= $validadeMeses ?> meses</div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small" for="empresa_recarga">Empresa</label>
                    <input type="text" name="empresa" id="empresa_recarga" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small" for="numero_documento_recarga">NF / doc.</label>
                    <input type="text" name="numero_documento" id="numero_documento_recarga" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-lg-2">
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Registrar</button>
                </div>
                <div class="col-12">
                    <label class="form-label small" for="observacao_recarga">Observação</label>
                    <input type="text" name="observacao" id="observacao_recarga" class="form-control form-control-sm">
                </div>
            </form>
            <?php endif; ?>

            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Data</th><th>Tipo</th><th>Próxima</th><th>Empresa</th><th>Documento</th><th>Obs.</th><th>Registrado por</th></tr></thead>
                        <tbody>
                        <?php foreach ($historicoRecargas as $hr): ?>
                            <tr>
                                <td><?= $dt($hr['data_recarga'] ?? null) ?></td>
                                <td><?= htmlspecialchars($hr['tipo_evento'] ?? 'Recarga') ?></td>
                                <td><?= $dt($hr['data_proxima_recarga'] ?? null) ?></td>
                                <td><?= $txt($hr['empresa'] ?? null) ?></td>
                                <td><?= $txt($hr['numero_documento'] ?? null) ?></td>
                                <td><?= $txt($hr['observacao'] ?? null) ?></td>
                                <td><?= $txt($hr['created_by_nome'] ?? null) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($historicoRecargas === []): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma recarga registrada.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-md-none list-mobile user-view-stack">
                <?php if ($historicoRecargas === []): ?>
                <p class="text-center text-muted small mb-0">Nenhuma recarga registrada.</p>
                <?php else: ?>
                    <?php foreach ($historicoRecargas as $hr): ?>
                    <article class="user-view-list-card">
                        <div class="user-view-list-card-title"><?= $dt($hr['data_recarga'] ?? null) ?></div>
                        <div class="user-view-list-card-meta">
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($hr['tipo_evento'] ?? 'Recarga') ?></span>
                        </div>
                        <dl class="user-view-list-card-dl mb-0">
                            <div><dt>Próxima</dt><dd><?= $dt($hr['data_proxima_recarga'] ?? null) ?></dd></div>
                            <div><dt>Empresa</dt><dd><?= $txt($hr['empresa'] ?? null) ?></dd></div>
                            <div><dt>Documento</dt><dd><?= $txt($hr['numero_documento'] ?? null) ?></dd></div>
                            <div><dt>Obs.</dt><dd><?= $txt($hr['observacao'] ?? null) ?></dd></div>
                            <div><dt>Registrado por</dt><dd><?= $txt($hr['created_by_nome'] ?? null) ?></dd></div>
                        </dl>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
            <span>Histórico de vistorias</span>
            <?php if (in_array('SstGenerateEquipamentoVistoria', $perms, true) && ($item['status'] ?? '') === 'Ativo'): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-generate-equipamento-vistoria" class="ms-md-auto">
                <input type="hidden" name="csrf_token" value="<?= $csrfGerarVistoria ?>">
                <input type="hidden" name="adms_sst_equipamento_id" value="<?= $id ?>">
                <input type="hidden" name="competencia" value="<?= htmlspecialchars($competenciaAtual) ?>">
                <button type="submit" class="btn btn-primary btn-sm w-100" title="Competência <?= htmlspecialchars($competenciaAtual) ?>">
                    <i class="fas fa-plus-circle me-1"></i>Gerar vistoria (<?= htmlspecialchars($competenciaAtual) ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Competência</th><th>Prevista</th><th>Realizada</th><th>Status</th><th>Resultado</th><th>Executor</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($historico as $h): ?>
                            <tr class="<?= ($h['resultado'] ?? '') === 'Não conforme' ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($h['competencia'] ?? '') ?></td>
                                <td><?= $dt($h['data_prevista'] ?? null) ?></td>
                                <td><?= $dt($h['data_realizada'] ?? null, true) ?></td>
                                <td><?= htmlspecialchars($h['status'] ?? '') ?></td>
                                <td><?= htmlspecialchars($h['resultado'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($h['executor_nome'] ?? '-') ?></td>
                                <td>
                                    <?php if (in_array('SstExecuteEquipamentoVistoria', $perms, true)): ?>
                                    <a href="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= (int) $h['id'] ?>" class="btn btn-sm btn-outline-primary"><?= ($h['status'] ?? '') === 'Concluída' ? 'Ver' : 'Executar' ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($historico === []): ?><tr><td colspan="7" class="text-center text-muted py-3">Nenhuma vistoria registrada.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-md-none list-mobile user-view-stack p-3">
                <?php if ($historico === []): ?>
                <p class="text-center text-muted small mb-0">Nenhuma vistoria registrada.</p>
                <?php else: ?>
                    <?php foreach ($historico as $h): ?>
                    <article class="user-view-list-card <?= ($h['resultado'] ?? '') === 'Não conforme' ? 'border-danger' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="user-view-list-card-title mb-0"><?= htmlspecialchars($h['competencia'] ?? '') ?></div>
                            <span class="badge bg-secondary flex-shrink-0"><?= htmlspecialchars($h['status'] ?? '') ?></span>
                        </div>
                        <div class="user-view-list-card-meta">
                            <?php if (!empty($h['resultado'])): ?>
                            <span class="badge <?= ($h['resultado'] ?? '') === 'Não conforme' ? 'bg-danger' : 'bg-light text-dark border' ?>"><?= htmlspecialchars((string) $h['resultado']) ?></span>
                            <?php endif; ?>
                        </div>
                        <dl class="user-view-list-card-dl">
                            <div><dt>Prevista</dt><dd><?= $dt($h['data_prevista'] ?? null) ?></dd></div>
                            <div><dt>Realizada</dt><dd><?= $dt($h['data_realizada'] ?? null, true) ?></dd></div>
                            <div><dt>Executor</dt><dd><?= htmlspecialchars($h['executor_nome'] ?? '—') ?></dd></div>
                        </dl>
                        <?php if (in_array('SstExecuteEquipamentoVistoria', $perms, true)): ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= (int) $h['id'] ?>" class="btn btn-sm btn-outline-primary w-100"><?= ($h['status'] ?? '') === 'Concluída' ? 'Ver' : 'Executar' ?></a>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (in_array('SstExportEquipamentoAuditoriaPdf', $perms, true)): ?>
<?php
$dePadrao = date('Y-01-01');
$atePadrao = date('Y-m-d');
?>
<div class="modal fade" id="modalAuditoriaPdf" tabindex="-1" aria-labelledby="modalAuditoriaPdfLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="<?= $_ENV['URL_ADM']; ?>sst-export-equipamento-auditoria-pdf/<?= $id ?>" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAuditoriaPdfLabel"><i class="fas fa-file-pdf me-2 text-danger"></i>Relatório para auditoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Gera PDF profissional (cabeçalho institucional) com <strong>todas as vistorias</strong> do período — checklist, fotos, NC e evidências — e <strong>recargas</strong> detalhadas.</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="aud_data_inicio">Data início *</label>
                            <input type="date" name="data_inicio" id="aud_data_inicio" class="form-control" required value="<?= htmlspecialchars($dePadrao) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="aud_data_fim">Data fim *</label>
                            <input type="date" name="data_fim" id="aud_data_fim" class="form-control" required value="<?= htmlspecialchars($atePadrao) ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-print me-1"></i>Gerar PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

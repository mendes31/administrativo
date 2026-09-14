<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstAsoStatusHelper;
use App\adms\Helpers\SstExameTipoHelper;

$item = $this->data['item'] ?? [];
$complementares = $this->data['complementares'] ?? [];
$medicos = $this->data['medicos'] ?? [];
$examesMeta = $this->data['examesMeta'] ?? [];
$csrf = CSRFHelper::generateCSRFToken('sst_registrar_resultados_aso');

$exigenciaLabels = [
    'obrigatorio' => 'Obrigatório',
    'recomendado' => 'Recomendado',
    'adicional' => 'Adicional',
];
$validadeSugerida = (string) ($item['data_validade'] ?? '');
if ($validadeSugerida === '') {
    $base = (string) ($item['data_realizacao'] ?? date('Y-m-d'));
    $real = \DateTimeImmutable::createFromFormat('Y-m-d', substr($base, 0, 10));
    if ($real instanceof \DateTimeImmutable) {
        $validadeSugerida = $real->modify('+12 months')->format('Y-m-d');
    }
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-file-medical me-2"></i>Registrar resultados — ASO #<?= (int) ($item['id'] ?? 0) ?></h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos">ASOs</a></li>
            <li class="breadcrumb-item active">Registrar resultados</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="alert alert-info py-2">
        <i class="fas fa-info-circle me-1"></i>
        Solicitação aberta para <strong><?= htmlspecialchars($item['colaborador_nome'] ?? '') ?></strong>
        — tipo <strong><?= htmlspecialchars($item['tipo'] ?? '') ?></strong>.
        Preencha data, <strong>Resultado ASO</strong> (Apto/Inapto) e a validade. A consulta clínica não exige resultado à parte.
        Hemogramas e outros complementares obrigatórios sim.
        <span class="badge bg-<?= SstAsoStatusHelper::badgeClass(SstAsoStatusHelper::AGUARDANDO_EXAMES) ?> ms-1">
            <?= htmlspecialchars(SstAsoStatusHelper::AGUARDANDO_EXAMES) ?>
        </span>
    </div>

    <form method="post" enctype="multipart/form-data" class="card border-light shadow">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="data_realizacao">Data realização <span class="text-danger">*</span></label>
                    <input type="date" name="data_realizacao" id="data_realizacao" class="form-control" required
                           value="<?= htmlspecialchars($item['data_realizacao'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="data_validade">Validade</label>
                    <input type="date" name="data_validade" id="data_validade" class="form-control"
                           value="<?= htmlspecialchars($validadeSugerida) ?>">
                    <div class="form-text">Se vazio, ao concluir será 12 meses após a realização.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="adms_sst_medico_id">Médico</label>
                    <select name="adms_sst_medico_id" id="adms_sst_medico_id" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($medicos as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= ((string) ($item['adms_sst_medico_id'] ?? '') === (string) $m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nome'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="resultado">Resultado ASO <span class="text-danger">*</span></label>
                    <select name="resultado" id="resultado" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach (['Apto', 'Inapto', 'Apto com restrição'] as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= ($item['resultado'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="clinica">Clínica</label>
                    <input type="text" name="clinica" id="clinica" class="form-control" value="<?= htmlspecialchars($item['clinica'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="restricoes">Restrições</label>
                    <input type="text" name="restricoes" id="restricoes" class="form-control" value="<?= htmlspecialchars($item['restricoes'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if ($complementares !== []): ?>
                <h6 class="mb-2">Exames complementares</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Exame</th>
                                <th style="width:110px">Exigência</th>
                                <th style="width:150px">Data</th>
                                <th style="width:180px">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($complementares as $i => $comp):
                            $eid = (int) ($comp['adms_sst_exame_id'] ?? 0);
                            $meta = $examesMeta[$eid] ?? ['resultados' => ['Normal' => 'Normal', 'Alterado' => 'Alterado'], 'tipo' => $comp['exame_tipo'] ?? null];
                            $exig = (string) ($comp['exigencia'] ?? '');
                            $isClinico = SstExameTipoHelper::isEventoClinicoAso(
                                (string) ($meta['tipo'] ?? $comp['exame_tipo'] ?? ''),
                                (string) ($comp['exame_nome'] ?? '')
                            );
                        ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($comp['exame_nome'] ?? '-') ?>
                                    <input type="hidden" name="complementares[<?= $i ?>][adms_sst_exame_id]" value="<?= $eid ?>">
                                    <input type="hidden" name="complementares[<?= $i ?>][exigencia]" value="<?= htmlspecialchars($exig) ?>">
                                    <?php if ($isClinico): ?>
                                        <div class="small text-muted">Coberto pelo Resultado ASO</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $exig === 'obrigatorio' ? 'danger' : ($exig === 'recomendado' ? 'info' : 'secondary') ?>"><?= htmlspecialchars($exigenciaLabels[$exig] ?? '-') ?></span></td>
                                <td>
                                    <?php if ($isClinico): ?>
                                        <span class="text-muted small">Igual à data de realização</span>
                                        <input type="hidden" name="complementares[<?= $i ?>][data_realizacao]" value="">
                                    <?php else: ?>
                                    <input type="date" name="complementares[<?= $i ?>][data_realizacao]" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($comp['data_realizacao'] ?? '') ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isClinico): ?>
                                        <span class="text-muted small">Apto / Inapto no campo acima</span>
                                        <input type="hidden" name="complementares[<?= $i ?>][resultado]" value="">
                                    <?php else: ?>
                                    <select name="complementares[<?= $i ?>][resultado]" class="form-select form-select-sm" <?= $exig === 'obrigatorio' ? 'required' : '' ?>>
                                        <option value="">—</option>
                                        <?php foreach ($meta['resultados'] as $val => $label): ?>
                                            <option value="<?= htmlspecialchars((string) $val) ?>" <?= ($comp['resultado'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars((string) $label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Concluir ASO</button>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-view-aso/<?= (int) ($item['id'] ?? 0) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</div>

<?php
use App\adms\Helpers\SstTreinamentoStatusHelper;

$item = $this->data['item'];
$aplicacoes = $this->data['aplicacoes'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$st = (string)($item['status'] ?? '');
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vínculo Treinamento SST</h2>
        <span class="ms-auto">
            <?php if (in_array('SstApplyTreinamento', $perms)): ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-apply-treinamento/<?= (int)$item['id'] ?>" class="btn btn-success btn-sm">Aplicar / agendar</a>
            <?php endif; ?>
            <?php if (in_array('SstExportTreinamentoCertificadoPdf', $perms) && !empty($item['data_realizacao'])): ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-export-treinamento-certificado-pdf/<?= (int)$item['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">Certificado PDF</a>
            <?php endif; ?>
            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-vinculos" class="btn btn-secondary btn-sm">Voltar</a>
        </span>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Situação atual</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Colaborador:</th><td><?= htmlspecialchars($item['colaborador_nome'] ?? '') ?></td></tr>
                    <tr><th>Treinamento:</th><td><?= htmlspecialchars($item['treinamento_nome'] ?? '') ?></td></tr>
                    <tr><th>Status:</th><td><span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span></td></tr>
                    <tr><th>Motivo:</th><td><?= htmlspecialchars($item['motivo'] ?? '-') ?></td></tr>
                    <tr><th>Agendado:</th><td><?= !empty($item['data_agendada']) ? date('d/m/Y', strtotime($item['data_agendada'])) : '-' ?></td></tr>
                    <tr><th>Realização:</th><td><?= !empty($item['data_realizacao']) ? date('d/m/Y', strtotime($item['data_realizacao'])) : '-' ?></td></tr>
                    <tr><th>Validade:</th><td><?= !empty($item['data_validade']) ? date('d/m/Y', strtotime($item['data_validade'])) : '-' ?></td></tr>
                    <tr><th>Limite 1º treinamento:</th><td><?= !empty($item['data_limite_primeiro']) ? date('d/m/Y', strtotime($item['data_limite_primeiro'])) : '-' ?></td></tr>
                    <tr><th>Nota:</th><td><?= htmlspecialchars((string)($item['nota'] ?? '-')) ?></td></tr>
                    <tr><th>Certificado:</th><td>
                        <?php if (!empty($item['certificado'])): ?>
                            <?= htmlspecialchars(basename((string)$item['certificado'])) ?>
                            <?php if (in_array('SstExportTreinamentoCertificadoPdf', $perms)): ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-export-treinamento-certificado-pdf/<?= (int)$item['id'] ?>" class="btn btn-link btn-sm p-0 ms-1" target="_blank">Abrir PDF</a>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td></tr>
                </table></div>
            </div>
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Histórico de aplicações</h5></div>
                <div class="card-body p-0">
                    <?php if ($aplicacoes === []): ?>
                        <div class="alert alert-info m-3 mb-0">Nenhuma aplicação registrada.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Data</th><th>Status</th><th>Instrutor</th><th>Nota</th></tr></thead>
                            <tbody>
                            <?php foreach ($aplicacoes as $a): ?>
                                <tr>
                                    <td><?= !empty($a['data_realizacao']) ? date('d/m/Y', strtotime($a['data_realizacao'])) : (!empty($a['data_agendada']) ? date('d/m/Y', strtotime($a['data_agendada'])) : '-') ?></td>
                                    <td><?= htmlspecialchars($a['status'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($a['instrutor_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars((string)($a['nota'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>

<?php
$item = $this->data['item'] ?? [];
$payload = $this->data['payload'] ?? [];
$trab = $payload['trabalhador'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$id = (int)($item['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php $sstRascunhoKind = 'ppp'; include __DIR__ . '/../partials/sst_rascunho_oficial_alert.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-file-alt me-2"></i>PPP — <?= htmlspecialchars($trab['nome'] ?? $item['colaborador_nome'] ?? '') ?></h2>
        <span class="badge text-bg-warning">Não oficial</span>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-ppp">PPP</a></li>
            <li class="breadcrumb-item active">Versão <?= (int)($item['versao'] ?? 1) ?></li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Resumo</span>
            <span class="ms-auto">
                <?php if (in_array('SstExportPppPdf', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-export-ppp-pdf/<?= $id ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
                <?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-employee-profile/<?= (int)($item['adms_user_id'] ?? 0) ?>" class="btn btn-outline-primary btn-sm">Perfil SST</a>
            </span>
        </div>
        <div class="card-body">
            <div class="alert alert-warning small"><?= htmlspecialchars($payload['observacao_legal'] ?? \App\adms\Models\Services\SstEsocialPolicy::observacaoPpp()) ?></div>
            <div class="row mb-3">
                <div class="col-md-3"><strong>CPF:</strong> <?= htmlspecialchars($trab['cpf'] ?? $item['colaborador_cpf'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Cargo:</strong> <?= htmlspecialchars($trab['cargo_atual'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Departamento:</strong> <?= htmlspecialchars($trab['departamento_atual'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Gerado:</strong> <?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></div>
            </div>
            <h6>Registros ambientais (riscos)</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Período</th><th>Agente</th><th>Tipo</th><th>Intensidade</th></tr></thead>
                    <tbody>
                    <?php foreach ($payload['registros_ambientais'] ?? [] as $ra): ?>
                        <tr>
                            <td><?= htmlspecialchars($ra['periodo'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ra['agente_nocivo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ra['tipo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ra['intensidade_concentracao'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payload['registros_ambientais'])): ?><tr><td colspan="4" class="text-muted">Nenhum risco vinculado.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <h6>Monitoração biológica (ASOs)</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Data</th><th>Tipo</th><th>Exame</th><th>Resultado</th><th>Validade</th></tr></thead>
                    <tbody>
                    <?php foreach ($payload['monitoracao_biologica'] ?? [] as $m): ?>
                        <tr>
                            <td><?= !empty($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '-' ?></td>
                            <td><?= htmlspecialchars($m['tipo_exame'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['exame'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['resultado'] ?? '') ?></td>
                            <td><?= !empty($m['validade']) ? date('d/m/Y', strtotime($m['validade'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <h6>Entregas de EPI</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Data</th><th>EPI</th><th>Qtd</th><th>Termo</th></tr></thead>
                    <tbody>
                    <?php foreach ($payload['entregas_epi'] ?? [] as $e): ?>
                        <tr>
                            <td><?= !empty($e['data']) ? date('d/m/Y', strtotime($e['data'])) : '-' ?></td>
                            <td><?= htmlspecialchars($e['epi'] ?? '') ?></td>
                            <td><?= (int)($e['quantidade'] ?? 0) ?></td>
                            <td><?= !empty($e['termo_assinado']) ? 'Sim' : 'Não' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

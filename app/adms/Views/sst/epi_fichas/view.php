<?php
$item = $this->data['item'] ?? [];
$itens = $this->data['itens'] ?? [];
$episEntregues = $this->data['epis_entregues'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$fichaId = (int)($item['id'] ?? 0);
$uid = (int)($item['adms_user_id'] ?? 0);
$status = (string)($item['status_assinatura'] ?? '');
$hash = (string)($item['pdf_hash_sha256'] ?? '');
$hashShort = $hash !== '' ? substr($hash, 0, 12) . '…' : '—';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-file-signature me-2"></i>Ficha de entrega #<?= $fichaId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-fichas">Fichas de EPI</a></li>
            <li class="breadcrumb-item active">#<?= $fichaId ?></li>
        </ol>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <span class="badge bg-<?= $status === 'Assinado' ? 'success' : ($status === 'Pendente' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($status) ?></span>
                <?php if ($status === 'Pendente'): ?>
                <span class="text-muted small ms-2">Aguardando confirmação do colaborador no portal</span>
                <?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if (in_array('SstExportEpiFichaPdf', $perms, true) && !empty($item['pdf_storage_path'])): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-export-epi-ficha-pdf/<?= $fichaId ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                <?php endif; ?>
                <?php if ($status === 'Pendente' && in_array('SstViewEpiFicha', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-view-epi-ficha/<?= $fichaId ?>?action=resend_push" class="btn btn-outline-primary btn-sm"><i class="fas fa-bell"></i> Reenviar notificação</a>
                <?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-employee-profile/<?= $uid ?>" class="btn btn-outline-info btn-sm"><i class="fas fa-user"></i> Perfil SST</a>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-fichas" class="btn btn-secondary btn-sm">Voltar</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4" data-adms-help-section="aba-dados-ficha">
                <div class="card-header"><h5 class="mb-0">Dados da ficha</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th width="35%">Colaborador</th><td><?= htmlspecialchars($item['colaborador_nome'] ?? '') ?></td></tr>
                        <tr><th>CPF</th><td><?= htmlspecialchars($item['colaborador_cpf'] ?? '-') ?></td></tr>
                        <tr><th>Cargo / Depto</th><td><?= htmlspecialchars(($item['cargo_nome'] ?? '-') . ' / ' . ($item['departamento_nome'] ?? '-')) ?></td></tr>
                        <tr><th>Data entrega</th><td><?= !empty($item['data_entrega']) ? date('d/m/Y', strtotime($item['data_entrega'])) : '-' ?></td></tr>
                        <tr><th>Responsável</th><td><?= htmlspecialchars($item['entregue_por_nome'] ?? '-') ?></td></tr>
                        <tr><th>Hash PDF</th><td><code class="small"><?= htmlspecialchars($hashShort) ?></code></td></tr>
                        <?php if ($status === 'Assinado'): ?>
                        <tr><th>Assinado em</th><td><?= !empty($item['signed_at']) ? date('d/m/Y H:i', strtotime($item['signed_at'])) : '-' ?></td></tr>
                        <tr><th>IP / método</th><td><?= htmlspecialchars(($item['signed_ip'] ?? '-') . ' · ' . ($item['signed_auth_method'] ?? '-')) ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($item['observacoes'])): ?>
                        <tr><th>Observações</th><td><?= nl2br(htmlspecialchars($item['observacoes'])) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm mb-4" data-adms-help-section="aba-itens">
                <div class="card-header"><h5 class="mb-0">Itens desta ficha</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>EPI</th><th>Tam.</th><th>CA</th><th>Qtde</th><th>Prev. troca</th></tr></thead>
                        <tbody>
                        <?php foreach ($itens as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['epi_nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['tamanho'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($i['ca_utilizado'] ?? $i['epi_ca_catalogo'] ?? '-') ?></td>
                            <td><?= (int)($i['quantidade'] ?? 1) ?></td>
                            <td><?= !empty($i['data_prevista_troca']) ? date('d/m/Y', strtotime($i['data_prevista_troca'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-4" data-adms-help-section="aba-historico-epi">
                <div class="card-header"><h5 class="mb-0">Todos os EPIs entregues ao colaborador</h5></div>
                <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                    <?php if ($episEntregues === []): ?>
                    <p class="text-muted p-3 mb-0 small">Nenhum EPI entregue registrado.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Data</th><th>EPI</th><th>Tam.</th><th>CA</th><th>Qtde</th></tr></thead>
                        <tbody>
                        <?php foreach ($episEntregues as $e): ?>
                        <tr>
                            <td class="small"><?= !empty($e['data_entrega']) ? date('d/m/Y', strtotime($e['data_entrega'])) : '-' ?></td>
                            <td><?= htmlspecialchars($e['epi_nome'] ?? '') ?></td>
                            <td class="small"><?= htmlspecialchars($e['tamanho'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($e['ca'] ?? '-') ?></td>
                            <td><?= (int)($e['quantidade'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
use App\adms\Helpers\SstTreinamentoStatusHelper;

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$vinculos = $this->data['vinculos'] ?? [];
$pendentes = $this->data['pendentes'] ?? [];
$pendenciasSst = $this->data['pendencias_sst'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$canPdf = in_array('ViewSstTreinamentoCertificadoPdf', $perms, true);
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-graduation-cap me-2"></i>Meus treinamentos SST</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Meus treinamentos SST</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($pendentes !== []): ?>
    <div class="alert alert-warning">
        <strong><?= count($pendentes) ?> treinamento(s) com pendência.</strong>
        Verifique agendamentos, vencimentos ou treinamentos ainda não realizados.
    </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 class="mb-0">Treinamentos obrigatórios</h5></div>
        <div class="card-body p-0">
            <?php if ($vinculos === []): ?>
            <p class="text-muted p-3 mb-0">Nenhum treinamento SST vinculado ao seu cadastro.</p>
            <?php else: ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Treinamento</th>
                            <th>NR</th>
                            <th>Status</th>
                            <th>Agendado</th>
                            <th>Realização</th>
                            <th>Validade</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($vinculos as $v):
                        $id = (int)($v['id'] ?? 0);
                        $st = (string)($v['status'] ?? '');
                        $temCertificado = !empty($v['certificado']) || !empty($v['data_realizacao']);
                    ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($v['treinamento_nome'] ?? '') ?></div>
                                <?php if (!empty($v['treinamento_codigo'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($v['treinamento_codigo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($v['nr_referencia'] ?? '-') ?></td>
                            <td><span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span></td>
                            <td><?= !empty($v['data_agendada']) ? date('d/m/Y', strtotime($v['data_agendada'])) : '-' ?></td>
                            <td><?= !empty($v['data_realizacao']) ? date('d/m/Y', strtotime($v['data_realizacao'])) : '-' ?></td>
                            <td><?= !empty($v['data_validade']) ? date('d/m/Y', strtotime($v['data_validade'])) : '-' ?></td>
                            <td class="text-nowrap">
                                <?php if ($canPdf && $temCertificado): ?>
                                <a href="<?= htmlspecialchars($urlAdm) ?>view-sst-treinamento-certificado-pdf/<?= $id ?>" class="btn btn-outline-primary btn-sm" target="_blank">Certificado PDF</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-block d-md-none p-2">
                <?php foreach ($vinculos as $v):
                    $id = (int)($v['id'] ?? 0);
                    $st = (string)($v['status'] ?? '');
                    $temCertificado = !empty($v['certificado']) || !empty($v['data_realizacao']);
                ?>
                <div class="card mb-2 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="fw-bold small"><?= htmlspecialchars($v['treinamento_nome'] ?? '') ?></div>
                        <?php if (!empty($v['nr_referencia'])): ?>
                        <div class="small text-muted">NR: <?= htmlspecialchars($v['nr_referencia']) ?></div>
                        <?php endif; ?>
                        <span class="badge <?= SstTreinamentoStatusHelper::badgeClass($st) ?>"><?= htmlspecialchars(SstTreinamentoStatusHelper::label($st)) ?></span>
                        <div class="small mt-1">
                            <?php if (!empty($v['data_validade'])): ?>
                            Validade: <?= date('d/m/Y', strtotime($v['data_validade'])) ?>
                            <?php elseif (!empty($v['data_agendada'])): ?>
                            Agendado: <?= date('d/m/Y', strtotime($v['data_agendada'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($canPdf && $temCertificado): ?>
                        <a href="<?= htmlspecialchars($urlAdm) ?>view-sst-treinamento-certificado-pdf/<?= $id ?>" class="btn btn-outline-primary btn-sm mt-2" target="_blank">Certificado PDF</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($pendenciasSst !== []): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 class="mb-0">Pendências identificadas pela matriz SST</h5></div>
        <div class="card-body p-0">
            <?php foreach ($pendenciasSst as $p): ?>
            <div class="px-3 py-2 border-bottom small">
                <?= htmlspecialchars($p['treinamento_nome'] ?? $p['nome'] ?? '') ?>
                <span class="badge bg-<?= htmlspecialchars($p['situacao_badge'] ?? 'warning') ?> ms-1"><?= htmlspecialchars($p['situacao_label'] ?? 'Pendente') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

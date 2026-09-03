<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-file-alt me-2"></i>PPP — Perfil Profissiográfico</h2>
        <span class="badge text-bg-warning">Não oficial</span>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">PPP</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php $sstRascunhoKind = 'ppp'; include __DIR__ . '/../partials/sst_rascunho_oficial_alert.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header">Documentos gerados</div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4"><label class="form-label small">Colaborador</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>" placeholder="Nome"></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Colaborador</th><th>Versão</th><th>Gerado em</th><th>Por</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                            <td>v<?= (int)($r['versao'] ?? 1) ?></td>
                            <td><?= !empty($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-' ?></td>
                            <td><?= htmlspecialchars($r['gerado_por_nome'] ?? '-') ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewPpp', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-ppp/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstExportPppPdf', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-export-ppp-pdf/<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i></a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <p class="text-muted small mt-2 mb-0">Para gerar um novo PPP, acesse o perfil SST do colaborador e use o botão &quot;Gerar PPP&quot;.</p>
        </div>
    </div>
</div>

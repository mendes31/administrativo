<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$rows = isset($this->data['rows']) && is_array($this->data['rows']) ? $this->data['rows'] : [];
$labels = $this->data['type_labels'] ?? [];
if (!is_array($labels)) {
    $labels = [];
}
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">Pendências de ciência (folha RH)</h2>
        <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="text-decoration-none">Documentos RH</a></li>
            <li class="breadcrumb-item active">Pendências</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-2 px-3">
            <span class="fw-semibold small text-uppercase" style="letter-spacing: .04em;">Colaboradores sem confirmação</span>
        </div>
        <div class="card-body p-0">
            <?php if ($rows === []): ?>
                <div class="p-4 text-center text-muted small">Nenhuma pendência no momento.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr class="small">
                                <th class="ps-3">Colaborador</th>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Publicado</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $id = (int)($r['id'] ?? 0);
                                $dt = (string)($r['document_type'] ?? '');
                                $typeLabel = $labels[$dt] ?? $dt;
                                $pub = (string)($r['published_at'] ?? '');
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold small"><?= htmlspecialchars((string)($r['user_name'] ?? '')) ?></div>
                                        <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars((string)($r['user_email'] ?? '')) ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars((string)($r['title'] ?? '')) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($typeLabel) ?></span></td>
                                    <td class="small text-muted text-nowrap"><?= htmlspecialchars($pub !== '' ? $pub : '—') ?></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

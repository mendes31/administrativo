<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$docs = isset($this->data['documents']) && is_array($this->data['documents']) ? $this->data['documents'] : [];
$f = $this->data['filters'] ?? [];
$typeLabels = $this->data['type_labels'] ?? [];
$curYear = (int)date('Y');
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">Meus documentos</h2>
        <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item d-none d-sm-inline"><a href="<?= htmlspecialchars($urlAdm) ?>employee-portal" class="text-decoration-none">Portal do Colaborador</a></li>
            <li class="breadcrumb-item d-inline d-sm-none"><a href="<?= htmlspecialchars($urlAdm) ?>employee-portal" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item active">Folha</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow mb-3">
        <div class="card-body py-3">
            <form method="get" action="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-0" for="f_type">Tipo</label>
                    <select name="document_type" id="f_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($typeLabels as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= (($f['document_type'] ?? '') === $code) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-0" for="f_year">Ano</label>
                    <select name="year" id="f_year" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php for ($y = $curYear; $y >= $curYear - 15; $y--): ?>
                            <option value="<?= $y ?>" <?= ((string)($f['year'] ?? '') === (string)$y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-0" for="f_month">Mês</label>
                    <select name="month" id="f_month" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ((string)($f['month'] ?? '') === (string)$m) ? 'selected' : '' ?>><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <div class="d-grid d-sm-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Filtrar</button>
                        <a href="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="btn btn-outline-secondary btn-sm">Limpar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-light shadow overflow-hidden">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <span><i class="fas fa-folder-open me-2"></i>Documentos disponíveis</span>
        </div>
        <div class="card-body p-0">
            <?php if ($docs === []): ?>
                <div class="p-4 text-center text-muted">Nenhum documento encontrado para os filtros selecionados.</div>
            <?php else: ?>
                <!-- Mobile: cartões (sem scroll horizontal) -->
                <div class="d-md-none list-group list-group-flush">
                    <?php foreach ($docs as $d): ?>
                        <?php
                        $id = (int)($d['id'] ?? 0);
                        $dt = (string)($d['document_type'] ?? '');
                        $label = $typeLabels[$dt] ?? $dt;
                        $y = (int)($d['reference_year'] ?? 0);
                        $mo = $d['reference_month'] ?? null;
                        $ref = $mo !== null && $mo !== '' ? str_pad((string)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
                        $title = (string)($d['title'] ?? '');
                        ?>
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-break"><?= htmlspecialchars($title) ?></div>
                                    <div class="small text-muted mt-1">Ref. <?= htmlspecialchars($ref) ?></div>
                                </div>
                                <span class="badge bg-secondary flex-shrink-0"><?= htmlspecialchars($label) ?></span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                    <i class="fas fa-eye me-1"></i> Visualizar
                                </a>
                                <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop: tabela -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Referência</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($docs as $d): ?>
                                    <?php
                                    $id = (int)($d['id'] ?? 0);
                                    $dt = (string)($d['document_type'] ?? '');
                                    $label = $typeLabels[$dt] ?? $dt;
                                    $y = (int)($d['reference_year'] ?? 0);
                                    $mo = $d['reference_month'] ?? null;
                                    $ref = $mo !== null && $mo !== '' ? str_pad((string)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($d['title'] ?? '')) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($label) ?></span></td>
                                        <td><?= htmlspecialchars($ref) ?></td>
                                        <td class="text-end text-nowrap">
                                            <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Visualizar</a>
                                            <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-sm btn-outline-secondary">Download</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3 mb-4">
        <a href="<?= htmlspecialchars($urlAdm) ?>employee-portal" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar ao portal</a>
    </div>
</div>

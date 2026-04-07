<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$csrf = $this->data['csrf_token'] ?? '';
$batches = $this->data['import_batches'] ?? [];
$typeLabels = [
    'payroll' => 'Folha de pagamento',
    'vacation_receipt' => 'Recibo de férias',
    'ir_statement' => 'Informe de IR',
    'time_bank' => 'Banco de horas',
    'other' => 'Outros',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Importar documentos de folha (PDF)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item active">Importar folhas</li>
        </ol>
    </div>

    <div class="alert alert-info border-0 shadow-sm">
        <strong>Como funciona:</strong> envie o PDF único recebido do escritório. O sistema lê o texto de cada página,
        identifica o <strong>CPF</strong> e gera um PDF por colaborador, associando ao cadastro de usuário ativo com o mesmo CPF.
        Páginas sem texto selecionável (scan sem OCR) podem não ser identificadas.
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><span><i class="fas fa-file-pdf me-2 text-danger"></i>Upload e parâmetros</span></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <div class="col-md-6">
                    <label class="form-label" for="pdf_file">Arquivo PDF <span class="text-danger">*</span></label>
                    <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf,.pdf" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="document_type">Tipo de documento</label>
                    <select name="document_type" id="document_type" class="form-select">
                        <option value="payroll">Folha de pagamento</option>
                        <option value="vacation_receipt">Recibo de férias</option>
                        <option value="ir_statement">Informe de IR</option>
                        <option value="time_bank">Banco de horas</option>
                        <option value="other">Outros</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="reference_year">Ano de referência</label>
                    <input type="number" name="reference_year" id="reference_year" class="form-control" min="2000" max="2100"
                           value="<?= (int)date('Y') ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="reference_month">Mês (opcional)</label>
                    <select name="reference_month" id="reference_month" class="form-select">
                        <option value="">— todos / anual —</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <div class="form-text">Informe de IR anual pode ficar em branco.</div>
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="title_prefix">Prefixo do título exibido ao colaborador</label>
                    <input type="text" name="title_prefix" id="title_prefix" class="form-control" maxlength="120"
                           placeholder="Ex.: Folha de pagamento (padrão conforme o tipo)">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt me-1"></i> Processar PDF</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex align-items-center gap-2">
            <span><i class="fas fa-history me-2 text-secondary"></i>Importações recentes</span>
            <span class="badge bg-secondary ms-auto"><?= count($batches) ?> lote(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($batches === []) { ?>
                <p class="text-muted mb-0 p-3">Nenhuma importação registada ainda.</p>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Ficheiro</th>
                                <th>Tipo</th>
                                <th>Ref.</th>
                                <th class="text-center">Pág.</th>
                                <th class="text-center">Docs</th>
                                <th>Por</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $row) {
                                $id = (int)($row['id'] ?? 0);
                                $fn = (string)($row['original_filename'] ?? '');
                                $dt = (string)($row['document_type'] ?? '');
                                $y = (int)($row['reference_year'] ?? 0);
                                $mo = $row['reference_month'];
                                $ref = $mo !== null && $mo !== '' ? str_pad((string)(int)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
                                $pt = (int)($row['pages_total'] ?? 0);
                                $pm = (int)($row['pages_matched'] ?? 0);
                                $pu = (int)($row['pages_unmatched'] ?? 0);
                                $dc = (int)($row['documents_count'] ?? 0);
                                $by = (string)($row['created_by_name'] ?? '—');
                                $ca = (string)($row['created_at'] ?? '');
                                ?>
                                <tr>
                                    <td><?= $id ?></td>
                                    <td class="text-nowrap small"><?= htmlspecialchars($ca !== '' ? $ca : '—') ?></td>
                                    <td class="small" title="<?= htmlspecialchars($fn) ?>"><?= htmlspecialchars(mb_strlen($fn) > 48 ? mb_substr($fn, 0, 45) . '…' : $fn) ?></td>
                                    <td class="small"><?= htmlspecialchars($typeLabels[$dt] ?? $dt) ?></td>
                                    <td class="small"><?= htmlspecialchars($ref) ?></td>
                                    <td class="text-center small text-nowrap" title="total / associadas / não id."><?= $pt ?> / <span class="text-success"><?= $pm ?></span> / <span class="text-warning"><?= $pu ?></span></td>
                                    <td class="text-center"><?= $dc ?></td>
                                    <td class="small"><?= htmlspecialchars($by) ?></td>
                                    <td class="text-end pe-2">
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Remover este lote e todos os documentos entregues aos colaboradores desta importação?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                            <input type="hidden" name="action" value="delete_batch">
                                            <input type="hidden" name="delete_batch_id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

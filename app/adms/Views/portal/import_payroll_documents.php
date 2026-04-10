<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$csrf = $this->data['csrf_token'] ?? '';
$importNonce = (string)($this->data['import_nonce'] ?? '');
$batches = $this->data['import_batches'] ?? [];
$typeRows = $this->data['payroll_document_types'] ?? [];
$typeLabels = $this->data['type_labels'] ?? [];
if (!is_array($typeLabels)) {
    $typeLabels = [];
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Importar documentos de RH (PDF)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item active">Importar RH</li>
        </ol>
    </div>

    <div class="alert alert-info border-0 shadow-sm">
        <strong>Como funciona:</strong> envie o PDF único recebido do escritório. O sistema lê o texto de cada página,
        identifica o <strong>CPF</strong> e gera um PDF por colaborador que exista no cadastro como <strong>utilizador ativo</strong> com o mesmo CPF;
        CPFs sem correspondência são ignorados (os demais são processados).
        Páginas sem texto selecionável (scan sem OCR) podem não ser identificadas.
        <span class="d-block mt-2"><strong>Mesmo período, vários ficheiros:</strong> com <strong>nome diferente</strong> ou <strong>referência (ano/mês) diferente</strong>,
        os documentos <strong>acumulam</strong> (ex.: quinzenal e mensal no mesmo mês).
        A <strong>substituição</strong> só ocorre ao reenviar o <strong>mesmo nome de ficheiro</strong> para o <strong>mesmo tipo, ano e mês de referência</strong> (correção do mesmo PDF naquele período).</span>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><span><i class="fas fa-file-pdf me-2 text-danger"></i>Upload e parâmetros</span></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form id="formImportPayrollPdf" action="" method="post" enctype="multipart/form-data" class="row g-3 position-relative">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="import_nonce" value="<?= htmlspecialchars($importNonce) ?>">

                <div id="importPayrollOverlay" class="d-none position-absolute top-0 start-0 w-100 h-100 rounded-3 bg-white bg-opacity-90 flex-column align-items-center justify-content-center p-4" style="z-index: 10; min-height: 220px;">
                    <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                    <p class="fw-semibold text-center mb-2">A processar o PDF…</p>
                    <p class="small text-muted text-center mb-3">Não feche esta página nem clique novamente. PDFs grandes (ex.: 100+ páginas) podem demorar vários minutos.</p>
                    <div class="progress w-100" style="max-width: 420px;" role="progressbar" aria-label="Processamento em curso">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="pdf_file">Arquivo PDF <span class="text-danger">*</span></label>
                    <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf,.pdf" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="document_type">Tipo de documento</label>
                    <select name="document_type" id="document_type" class="form-select">
                        <?php foreach ($typeRows as $trow) {
                            $tc = (string)($trow['code'] ?? '');
                            if ($tc === '') {
                                continue;
                            }
                            $tname = (string)($trow['name'] ?? $typeLabels[$tc] ?? $tc);
                            ?>
                            <option value="<?= htmlspecialchars($tc) ?>"><?= htmlspecialchars($tname) ?></option>
                        <?php } ?>
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
                    <button type="submit" id="btnProcessarPayrollPdf" class="btn btn-primary">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Processar PDF
                    </button>
                </div>
            </form>
            <script>
            (function () {
                var form = document.getElementById('formImportPayrollPdf');
                var btn = document.getElementById('btnProcessarPayrollPdf');
                var overlay = document.getElementById('importPayrollOverlay');
                var fileInput = document.getElementById('pdf_file');
                if (!form || !btn || !overlay) return;
                form.addEventListener('submit', function () {
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        return;
                    }
                    btn.disabled = true;
                    btn.setAttribute('aria-busy', 'true');
                    overlay.classList.remove('d-none');
                    overlay.classList.add('d-flex');
                });
            })();
            </script>
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
                                <th class="text-center">Mesclados FLS</th>
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
                                $logRaw = (string)($row['log_json'] ?? '');
                                $mergedByFls = 0;
                                if ($logRaw !== '') {
                                    $logData = json_decode($logRaw, true);
                                    if (is_array($logData)) {
                                        $mergedByFls = (int)($logData['merged_by_fls_documents'] ?? 0);
                                    }
                                }
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
                                    <td class="text-center"><?= $mergedByFls ?></td>
                                    <td class="small"><?= htmlspecialchars($by) ?></td>
                                    <td class="text-end pe-2 text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Ações do lote">
                                            <a href="<?= htmlspecialchars($urlAdm) ?>payroll-import-batch-report/<?= $id ?>"
                                               class="btn btn-outline-primary" title="Relatório (ciência, visualização, notificação)"><i class="fas fa-chart-bar"></i></a>
                                            <a href="<?= htmlspecialchars($urlAdm) ?>payroll-import-batch-audit/<?= $id ?>"
                                               class="btn btn-outline-secondary" title="Trilha de auditoria (eventos + acessos PDF)"><i class="fas fa-list-alt"></i></a>
                                        </div>
                                        <form method="post" action="" class="d-inline ms-1" onsubmit="return confirm('Remover este lote e todos os documentos entregues aos colaboradores desta importação?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                            <input type="hidden" name="action" value="delete_batch">
                                            <input type="hidden" name="delete_batch_id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar lote"><i class="fas fa-trash-alt"></i></button>
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

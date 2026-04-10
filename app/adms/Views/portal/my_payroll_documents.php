<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';

$docs = isset($this->data['documents']) && is_array($this->data['documents']) ? $this->data['documents'] : [];

$f = $this->data['filters'] ?? [];

$typeLabels = $this->data['type_labels'] ?? [];

$curYear = (int)date('Y');

/** Classes Bootstrap 5 por código de tipo (badges coloridos). */
$payrollDocBadgeClass = [
    'payroll' => 'text-bg-primary',
    'vacation_receipt' => 'text-bg-warning',
    'ir_statement' => 'text-bg-info',
    'time_bank' => 'text-bg-success',
    'other' => 'text-bg-secondary',
];

?>

<div class="container-fluid px-3 px-md-4">

    <div class="row justify-content-center">

        <div class="col-12 col-lg-10 col-xl-8 col-xxl-7">



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

            <div class="alert alert-info border-0 shadow-sm small mb-3 rounded-3" role="note">
                <div class="d-flex gap-2">
                    <span class="text-info flex-shrink-0" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
                    <div>
                        <strong class="d-block mb-1">Documentos de RH e proteção de dados (LGPD)</strong>
                        <p class="mb-2 mb-md-1">
                            Esta área destina-se à <strong>disponibilização dos seus documentos trabalhistas e fiscais</strong>
                            (por exemplo holerites, recibos e informes) pela empresa, em cumprimento de obrigações legais e contratuais.
                            O tratamento dos dados observa a Lei nº 13.709/2018 (LGPD), em especial as bases legais de
                            <strong>execução de contrato</strong> e de <strong>cumprimento de obrigação legal ou regulatória</strong>,
                            conforme o tipo de documento.
                        </p>
                        <p class="mb-2 mb-md-1">
                            Os ficheiros são entregues de forma <strong>privada</strong> (sem endereço público direto ao ficheiro).
                            As <strong>visualizações e transferências</strong> podem ser <strong>registadas</strong> (por exemplo data e origem do acesso)
                            para fins de segurança da informação, prevenção a incidentes e demonstração de disponibilização, quando aplicável.
                        </p>
                        <p class="mb-0">
                            Para exercer direitos do titular (confirmação de tratamento, acesso, correção de dados cadastrais, entre outros previstos na LGPD),
                            contacte o <strong>RH</strong> ou o <strong>encarregado de proteção de dados (DPO)</strong> da organização, nos canais oficiais.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3 rounded-3">

                <div class="card-body py-3 px-3 px-md-4">

                    <form method="get" action="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="row g-2 g-md-3 align-items-end">

                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">

                            <label class="form-label small text-muted mb-0" for="f_type">Tipo</label>

                            <select name="document_type" id="f_type" class="form-select form-select-sm">

                                <option value="">Todos</option>

                                <?php foreach ($typeLabels as $code => $label): ?>

                                    <option value="<?= htmlspecialchars($code) ?>" <?= (($f['document_type'] ?? '') === $code) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-6 col-sm-3 col-md-2">

                            <label class="form-label small text-muted mb-0" for="f_year">Ano</label>

                            <select name="year" id="f_year" class="form-select form-select-sm">

                                <option value="">Todos</option>

                                <?php for ($y = $curYear; $y >= $curYear - 15; $y--): ?>

                                    <option value="<?= $y ?>" <?= ((string)($f['year'] ?? '') === (string)$y) ? 'selected' : '' ?>><?= $y ?></option>

                                <?php endfor; ?>

                            </select>

                        </div>

                        <div class="col-6 col-sm-3 col-md-2">

                            <label class="form-label small text-muted mb-0" for="f_month">Mês</label>

                            <select name="month" id="f_month" class="form-select form-select-sm">

                                <option value="">Todos</option>

                                <?php for ($m = 1; $m <= 12; $m++): ?>

                                    <option value="<?= $m ?>" <?= ((string)($f['month'] ?? '') === (string)$m) ? 'selected' : '' ?>><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>

                                <?php endfor; ?>

                            </select>

                        </div>

                        <div class="col-12 col-md-auto ms-md-auto">

                            <div class="d-flex flex-wrap gap-2 justify-content-md-end">

                                <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-search me-1"></i> Filtrar</button>

                                <a href="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="btn btn-outline-secondary btn-sm px-3">Limpar</a>

                            </div>

                        </div>

                    </form>

                </div>

            </div>



            <div class="card border-0 shadow-sm overflow-hidden rounded-3">

                <div class="card-header bg-white border-bottom py-2 px-3 px-md-4 d-flex align-items-center gap-2">

                    <span class="text-primary"><i class="fas fa-folder-open"></i></span>

                    <span class="fw-semibold small text-uppercase" style="letter-spacing: .04em;">Documentos disponíveis</span>

                </div>

                <div class="card-body p-0">

                    <?php if ($docs === []): ?>

                        <div class="p-4 text-center text-muted small">Nenhum documento encontrado para os filtros selecionados.</div>

                    <?php else: ?>

                        <!-- Mobile: cartões compactos -->

                        <div class="d-md-none">

                            <?php foreach ($docs as $d): ?>

                                <?php

                                $id = (int)($d['id'] ?? 0);

                                $dt = (string)($d['document_type'] ?? '');

                                $label = $typeLabels[$dt] ?? $dt;

                                $y = (int)($d['reference_year'] ?? 0);

                                $mo = $d['reference_month'] ?? null;

                                $ref = $mo !== null && $mo !== '' ? str_pad((string)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;

                                $title = (string)($d['title'] ?? '');
                                $netAmount = isset($d['net_amount']) && $d['net_amount'] !== null && $d['net_amount'] !== ''
                                    ? (float)$d['net_amount']
                                    : null;

                                $badgeClass = $payrollDocBadgeClass[$dt] ?? 'text-bg-light text-secondary border';

                                ?>

                                <div class="border-bottom px-3 py-2">

                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">

                                        <div class="min-w-0 flex-grow-1">

                                            <div class="fw-semibold small text-break lh-sm"><?= htmlspecialchars($title) ?></div>

                                            <div class="text-muted mt-1" style="font-size: .72rem;">Ref. <?= htmlspecialchars($ref) ?></div>
                                            <?php if ($netAmount !== null): ?>
                                                <div class="text-success mt-1 fw-semibold" style="font-size: .76rem;">
                                                    Valor líquido: R$ <?= htmlspecialchars(number_format($netAmount, 2, ',', '.')) ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <span class="badge rounded-pill <?= htmlspecialchars($badgeClass) ?> flex-shrink-0" style="font-size: .65rem; font-weight: 500;"><?= htmlspecialchars($label) ?></span>

                                    </div>

                                    <div class="d-flex gap-2">

                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-sm btn-outline-primary flex-fill py-1" target="_blank" rel="noopener">

                                            <i class="fas fa-eye me-1"></i> Visualizar

                                        </a>

                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-sm btn-outline-secondary flex-fill py-1">

                                            <i class="fas fa-download me-1"></i> Download

                                        </a>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>



                        <!-- Desktop: tabela compacta -->

                        <div class="d-none d-md-block px-0">

                            <div class="table-responsive">

                                <table class="table table-sm table-hover mb-0 align-middle">

                                    <thead class="table-light">

                                        <tr class="small">

                                            <th class="ps-4 py-2">Título</th>

                                            <th class="py-2">Tipo</th>

                                            <th class="py-2">Referência</th>
                                            <th class="py-2">Valor líquido</th>

                                            <th class="text-end pe-4 py-2" style="width: 1%;">Ações</th>

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
                                            $netAmount = isset($d['net_amount']) && $d['net_amount'] !== null && $d['net_amount'] !== ''
                                                ? (float)$d['net_amount']
                                                : null;

                                            $badgeClass = $payrollDocBadgeClass[$dt] ?? 'text-bg-light text-secondary border';

                                            ?>

                                            <tr>

                                                <td class="ps-4"><?= htmlspecialchars((string)($d['title'] ?? '')) ?></td>

                                                <td><span class="badge rounded-pill <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($label) ?></span></td>

                                                <td class="text-muted small"><?= htmlspecialchars($ref) ?></td>
                                                <td class="text-success small fw-semibold">
                                                    <?= $netAmount !== null ? 'R$ ' . htmlspecialchars(number_format($netAmount, 2, ',', '.')) : '—' ?>
                                                </td>

                                                <td class="text-end pe-4 text-nowrap">

                                                    <div class="btn-group btn-group-sm" role="group">

                                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">Visualizar</a>

                                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-outline-secondary">Download</a>

                                                    </div>

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

    </div>

</div>



<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';

$docs = isset($this->data['documents']) && is_array($this->data['documents']) ? $this->data['documents'] : [];

$f = $this->data['filters'] ?? [];

$typeLabels = $this->data['type_labels'] ?? [];

$curYear = (int)date('Y');

$filtersActive = (($f['document_type'] ?? '') !== '')
    || (($f['year'] ?? '') !== '')
    || (($f['month'] ?? '') !== '');

$isInstitutionalPayrollUser = !empty($this->data['is_institutional_user']);

/** Classes Bootstrap 5 por código de tipo (badges coloridos). */
$payrollDocBadgeClass = [
    'payroll' => 'text-bg-primary',
    'vacation_receipt' => 'text-bg-warning',
    'ir_statement' => 'text-bg-info',
    'time_bank' => 'text-bg-success',
    'other' => 'text-bg-secondary',
];

/** Ícones Font Awesome por código de tipo (fallback: documento genérico). */
$payrollDocTypeIcon = [
    'payroll' => 'fa-file-invoice-dollar',
    'vacation_receipt' => 'fa-umbrella-beach',
    'ir_statement' => 'fa-file-invoice',
    'time_bank' => 'fa-business-time',
    'other' => 'fa-file-alt',
];

?>

<style>
.payroll-page-payrolldocs .payroll-title-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.payroll-page-payrolldocs .payroll-mobile-actions .btn {
    min-width: 2.35rem;
    padding: 0.2rem 0.45rem;
    line-height: 1.2;
}
@media (min-width: 400px) {
    .payroll-page-payrolldocs .payroll-mobile-actions .btn.payroll-btn-with-label {
        min-width: auto;
        padding: 0.25rem 0.5rem;
    }
    .payroll-page-payrolldocs .payroll-mobile-actions .btn.payroll-btn-with-label .payroll-btn-short {
        display: inline !important;
        font-size: 0.7rem;
        margin-left: 0.2rem;
    }
}
.payroll-page-payrolldocs .payroll-mobile-actions .btn .payroll-btn-short {
    display: none;
}
.payroll-page-payrolldocs .payroll-lgpd-summary {
    font-size: 0.8125rem;
    line-height: 1.35;
}
.payroll-page-payrolldocs .payroll-net-wrap .payroll-net-value {
    font-variant-numeric: tabular-nums;
    min-width: 5.5rem;
    display: inline-block;
}
.payroll-page-payrolldocs .payroll-net-toggle {
    color: var(--bs-secondary);
    line-height: 1;
    vertical-align: middle;
}
.payroll-page-payrolldocs .payroll-net-toggle:hover {
    color: var(--bs-primary);
}
</style>

<div class="container-fluid px-3 px-md-4 payroll-page-payrolldocs">

    <div class="row justify-content-center">

        <div class="col-12">



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

            <div class="alert alert-info border-0 shadow-sm py-2 px-2 px-md-3 mb-2 mb-md-3 rounded-3" role="note">
                <div class="d-flex gap-2 align-items-start">
                    <span class="text-info flex-shrink-0 mt-1" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
                    <div class="min-w-0 flex-grow-1">
                        <strong class="d-block small mb-1">RH e proteção de dados (LGPD)</strong>
                        <p class="mb-1 payroll-lgpd-summary text-body-secondary">
                            Os seus documentos são disponibilizados de forma <strong>privada</strong>; acessos podem ser <strong>registados</strong> por segurança e comprovação.
                            Tratamento conforme a <strong>LGPD</strong> (bases legais de contrato e obrigação legal).
                        </p>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none payroll-lgpd-toggle" data-bs-toggle="collapse" data-bs-target="#lgpdPayrollFullNote" aria-expanded="false" aria-controls="lgpdPayrollFullNote">
                            <span class="when-collapsed">Ler mais</span><span class="when-expanded d-none">Ler menos</span>
                        </button>
                        <div class="collapse small mt-2" id="lgpdPayrollFullNote">
                            <p class="mb-2">
                                Esta área destina-se à <strong>disponibilização dos seus documentos trabalhistas e fiscais</strong>
                                (por exemplo holerites, recibos e informes) pela empresa, em cumprimento de obrigações legais e contratuais.
                                O tratamento dos dados observa a Lei nº 13.709/2018 (LGPD), em especial as bases legais de
                                <strong>execução de contrato</strong> e de <strong>cumprimento de obrigação legal ou regulatória</strong>,
                                conforme o tipo de documento.
                            </p>
                            <p class="mb-2">
                                Os ficheiros são entregues sem endereço público direto ao ficheiro.
                                As <strong>visualizações e transferências</strong> podem ser <strong>registadas</strong> (data e origem do acesso)
                                para segurança da informação, prevenção de incidentes e demonstração de disponibilização, quando aplicável.
                            </p>
                            <p class="mb-0">
                                Para exercer direitos do titular (confirmação de tratamento, acesso, correção cadastral, entre outros),
                                contacte o <strong>RH</strong> ou o <strong>encarregado de proteção de dados (DPO)</strong>, nos canais oficiais.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-2 mb-md-3 rounded-3">
                <div class="card-body py-2 px-2 px-md-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm d-none d-md-inline-flex align-items-center gap-1" data-bs-toggle="collapse" data-bs-target="#payrollFiltersPanel" aria-expanded="<?= $filtersActive ? 'true' : 'false' ?>" aria-controls="payrollFiltersPanel" id="btnPayrollFiltersToggleDesktop">
                            <i class="fas fa-filter" aria-hidden="true"></i>
                            <span>Filtros</span>
                            <?php if ($filtersActive): ?>
                                <span class="badge text-bg-primary rounded-pill ms-1" style="font-size: 0.65rem;">ativo</span>
                            <?php endif; ?>
                        </button>
                        <button type="button" class="adm-filter-mobile-trigger adm-filter-mobile-trigger--compact d-md-none" data-bs-toggle="collapse" data-bs-target="#payrollFiltersPanel" aria-expanded="<?= $filtersActive ? 'true' : 'false' ?>" aria-controls="payrollFiltersPanel" id="btnPayrollFiltersToggle">
                            <span class="adm-filter-mobile-trigger__leading">
                                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                                <span>Filtros</span>
                                <?php if ($filtersActive): ?>
                                    <span class="badge rounded-pill ms-1" style="font-size: 0.65rem; background: #e5e7eb; color: #374151;">ativo</span>
                                <?php endif; ?>
                            </span>
                            <span class="adm-filter-mobile-trigger__chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <?php if ($filtersActive): ?>
                            <span class="small text-muted text-truncate" style="max-width: 100%;">
                                <?php
                                $bits = [];
                                if (($f['document_type'] ?? '') !== '') {
                                    $bits[] = $typeLabels[$f['document_type']] ?? (string)$f['document_type'];
                                }
                                if (($f['year'] ?? '') !== '') {
                                    $bits[] = (string)$f['year'];
                                }
                                if (($f['month'] ?? '') !== '') {
                                    $bits[] = 'mês ' . str_pad((string)$f['month'], 2, '0', STR_PAD_LEFT);
                                }
                                echo htmlspecialchars(implode(' · ', $bits));
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="collapse <?= $filtersActive ? 'show' : '' ?> mt-2 pt-2 border-top border-light" id="payrollFiltersPanel">
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
                                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-search me-1"></i> Aplicar</button>
                                    <a href="<?= htmlspecialchars($urlAdm) ?>my-payroll-documents" class="btn btn-outline-secondary btn-sm px-3">Limpar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



            <div class="card border-0 shadow-sm overflow-hidden rounded-3">

                <div class="card-header bg-white border-bottom py-2 px-2 px-md-3 d-flex align-items-center gap-2">

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
                                $typeIcon = $payrollDocTypeIcon[$dt] ?? 'fa-file-alt';

                                $ver = (int)($d['document_version'] ?? 1);
                                $h = (string)($d['file_hash_sha256'] ?? '');
                                $hashShort = $h !== '' ? substr($h, 0, 10) . '…' : '—';
                                $sigSt = (string)($d['signature_status'] ?? 'not_required');
                                $reqSig = (int)($d['requires_signature_snapshot'] ?? 0) === 1;
                                $needSign = !$isInstitutionalPayrollUser && $sigSt === 'pending' && $reqSig;
                                $signed = $sigSt === 'signed';
                                if (!$reqSig) {
                                    $cienciaLabel = 'N/A';
                                    $cienciaClass = 'text-bg-secondary';
                                } elseif ($isInstitutionalPayrollUser && $reqSig && !$signed) {
                                    $cienciaLabel = 'Isento';
                                    $cienciaClass = 'text-bg-light text-muted border';
                                } elseif ($signed) {
                                    $cienciaLabel = 'Confirmada';
                                    $cienciaClass = 'text-bg-success';
                                } elseif ($needSign) {
                                    $cienciaLabel = 'Pendente';
                                    $cienciaClass = 'text-bg-warning text-dark';
                                } else {
                                    $cienciaLabel = $sigSt;
                                    $cienciaClass = 'text-bg-light text-secondary border';
                                }

                                ?>

                                <div class="border-bottom px-3 py-2">

                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">

                                        <div class="min-w-0 flex-grow-1">

                                            <div class="fw-semibold small text-break lh-sm"><?= htmlspecialchars($title) ?></div>

                                            <div class="text-muted mt-1" style="font-size: .72rem;">Ref. <?= htmlspecialchars($ref) ?> · v.<?= $ver ?> · <code class="small"><?= htmlspecialchars($hashShort) ?></code></div>
                                            <div class="mt-1"><span class="badge rounded-pill <?= htmlspecialchars($cienciaClass) ?>" style="font-size: .65rem;"><?= htmlspecialchars($cienciaLabel) ?></span></div>
                                            <?php if ($netAmount !== null): ?>
                                                <?php
                                                $netFormatted = 'R$ ' . number_format($netAmount, 2, ',', '.');
                                                $netMasked = 'R$ ••••••';
                                                ?>
                                                <div class="d-inline-flex align-items-center flex-wrap gap-1 mt-1 payroll-net-wrap">
                                                    <span class="text-muted" style="font-size: .68rem;">Líquido</span>
                                                    <span class="payroll-net-value text-success fw-semibold" style="font-size: .76rem;" data-hidden-text="<?= htmlspecialchars($netMasked, ENT_QUOTES, 'UTF-8') ?>" data-visible-text="<?= htmlspecialchars($netFormatted, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($netMasked, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <button type="button" class="btn btn-link payroll-net-toggle p-0 border-0 align-middle" style="font-size: .85rem;" aria-pressed="false" aria-label="Mostrar valor líquido" title="Mostrar valor">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <span class="badge rounded-pill <?= htmlspecialchars($badgeClass) ?> flex-shrink-0" style="font-size: .65rem; font-weight: 500;"><i class="fas <?= htmlspecialchars($typeIcon) ?> me-1" aria-hidden="true"></i><?= htmlspecialchars($label) ?></span>

                                    </div>

                                    <div class="d-flex flex-wrap gap-2">

                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-sm btn-outline-primary flex-grow-1 py-1" style="min-width:44%;" target="_blank" rel="noopener">

                                            <i class="fas fa-eye me-1"></i> Visualizar

                                        </a>

                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-sm btn-outline-secondary flex-grow-1 py-1" style="min-width:44%;">

                                            <i class="fas fa-download me-1"></i> Download

                                        </a>
                                        <?php if ($needSign): ?>
                                            <a href="<?= htmlspecialchars($urlAdm) ?>sign-payroll-document/<?= $id ?>" class="btn btn-sm btn-primary flex-grow-1 py-1" style="min-width:44%;"><i class="fas fa-signature me-1"></i> Assinar</a>
                                        <?php endif; ?>
                                        <?php if ($signed): ?>
                                            <a href="<?= htmlspecialchars($urlAdm) ?>payroll-signature-receipt/<?= $id ?>" class="btn btn-sm btn-outline-success flex-grow-1 py-1" style="min-width:44%;" target="_blank" rel="noopener"><i class="fas fa-file-pdf me-1"></i> Comprovante</a>
                                        <?php endif; ?>
                                        <?php if ($reqSig && ($needSign || $signed)): ?>
                                            <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-signed-bundle/<?= $id ?>" class="btn btn-sm btn-outline-danger flex-grow-1 py-1" style="min-width:44%;" title="Original + trilha (atualiza ao visualizar, descarregar e assinar)"><i class="fas fa-file-contract me-1"></i> PDF com trilha</a>
                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>



                        <!-- Desktop: tabela compacta -->

                        <div class="d-none d-md-block px-0">

                            <div class="table-responsive-md">

                                <table class="table table-sm table-hover mb-0 align-middle payroll-docs-table">

                                    <thead class="table-light">

                                        <tr class="small">

                                            <th class="ps-4 py-2">Título</th>

                                            <th class="py-2">Tipo</th>

                                            <th class="py-2">Referência</th>
                                            <th class="py-2 text-center">V.</th>
                                            <th class="py-2">Hash</th>
                                            <th class="py-2">Ciência</th>
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
                                            $typeIcon = $payrollDocTypeIcon[$dt] ?? 'fa-file-alt';

                                            $ver = (int)($d['document_version'] ?? 1);
                                            $h = (string)($d['file_hash_sha256'] ?? '');
                                            $hashShort = $h !== '' ? substr($h, 0, 10) . '…' : '—';
                                            $sigSt = (string)($d['signature_status'] ?? 'not_required');
                                            $reqSig = (int)($d['requires_signature_snapshot'] ?? 0) === 1;
                                            $needSign = !$isInstitutionalPayrollUser && $sigSt === 'pending' && $reqSig;
                                            $signed = $sigSt === 'signed';
                                            if (!$reqSig) {
                                                $cienciaLabel = 'N/A';
                                                $cienciaClass = 'text-bg-secondary';
                                            } elseif ($isInstitutionalPayrollUser && $reqSig && !$signed) {
                                                $cienciaLabel = 'Isento';
                                                $cienciaClass = 'text-bg-light text-muted border';
                                            } elseif ($signed) {
                                                $cienciaLabel = 'OK';
                                                $cienciaClass = 'text-bg-success';
                                            } elseif ($needSign) {
                                                $cienciaLabel = 'Pend.';
                                                $cienciaClass = 'text-bg-warning text-dark';
                                            } else {
                                                $cienciaLabel = $sigSt;
                                                $cienciaClass = 'text-bg-light text-secondary border';
                                            }

                                            ?>

                                            <tr>

                                                <td class="ps-4 text-break"><?= htmlspecialchars((string)($d['title'] ?? '')) ?></td>

                                                <td class="text-nowrap"><span class="badge rounded-pill <?= htmlspecialchars($badgeClass) ?>"><i class="fas <?= htmlspecialchars($typeIcon) ?> me-1" aria-hidden="true"></i><?= htmlspecialchars($label) ?></span></td>

                                                <td class="text-muted small"><?= htmlspecialchars($ref) ?></td>
                                                <td class="text-center small text-muted"><?= $ver ?></td>
                                                <td class="small"><code class="small"><?= htmlspecialchars($hashShort) ?></code></td>
                                                <td><span class="badge rounded-pill <?= htmlspecialchars($cienciaClass) ?>" style="font-size:.7rem;"><?= htmlspecialchars($cienciaLabel) ?></span></td>
                                                <td class="small">
                                                    <?php if ($netAmount !== null): ?>
                                                        <?php
                                                        $netFormatted = 'R$ ' . number_format($netAmount, 2, ',', '.');
                                                        $netMasked = 'R$ ••••••';
                                                        ?>
                                                        <div class="d-inline-flex align-items-center gap-1 payroll-net-wrap">
                                                            <span class="payroll-net-value text-success fw-semibold" data-hidden-text="<?= htmlspecialchars($netMasked, ENT_QUOTES, 'UTF-8') ?>" data-visible-text="<?= htmlspecialchars($netFormatted, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($netMasked, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <button type="button" class="btn btn-link payroll-net-toggle p-0 border-0" style="font-size: .8rem;" aria-pressed="false" aria-label="Mostrar valor líquido" title="Mostrar valor">
                                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="text-end pe-4 text-nowrap">

                                                    <div class="btn-group btn-group-sm" role="group">

                                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">Visualizar</a>

                                                        <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-document/<?= $id ?>?inline=0" class="btn btn-outline-secondary">Download</a>
                                                        <?php if ($needSign): ?>
                                                            <a href="<?= htmlspecialchars($urlAdm) ?>sign-payroll-document/<?= $id ?>" class="btn btn-primary">Assinar</a>
                                                        <?php endif; ?>
                                                        <?php if ($signed): ?>
                                                            <a href="<?= htmlspecialchars($urlAdm) ?>payroll-signature-receipt/<?= $id ?>" class="btn btn-outline-success" target="_blank" rel="noopener" title="Só comprovante">Comp.</a>
                                                        <?php endif; ?>
                                                        <?php if ($reqSig && ($needSign || $signed)): ?>
                                                            <a href="<?= htmlspecialchars($urlAdm) ?>view-payroll-signed-bundle/<?= $id ?>" class="btn btn-outline-danger" title="Original + trilha (evolui até à ciência)"><i class="fas fa-file-pdf"></i></a>
                                                        <?php endif; ?>

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

<script>
(function () {
    var root = document.querySelector('.payroll-page-payrolldocs');
    if (!root) return;
    root.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.payroll-net-toggle');
        if (!btn || !root.contains(btn)) return;
        ev.preventDefault();
        var wrap = btn.closest('.payroll-net-wrap');
        if (!wrap) return;
        var valEl = wrap.querySelector('.payroll-net-value');
        var icon = btn.querySelector('i');
        if (!valEl || !icon) return;
        var hidden = valEl.getAttribute('data-hidden-text') || '';
        var visible = valEl.getAttribute('data-visible-text') || '';
        var isShown = btn.getAttribute('aria-pressed') === 'true';
        if (isShown) {
            valEl.textContent = hidden;
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-label', 'Mostrar valor líquido');
            btn.setAttribute('title', 'Mostrar valor');
            icon.className = 'fas fa-eye';
        } else {
            valEl.textContent = visible;
            btn.setAttribute('aria-pressed', 'true');
            btn.setAttribute('aria-label', 'Ocultar valor líquido');
            btn.setAttribute('title', 'Ocultar valor');
            icon.className = 'fas fa-eye-slash';
        }
    });
})();
</script>


<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$batch = $this->data['batch'] ?? [];
$batchId = (int)($this->data['batch_id'] ?? 0);
$timeline = $this->data['timeline'] ?? [];
$docMap = $this->data['doc_map'] ?? [];
$reportUrl = (string)($this->data['report_url'] ?? '');
$fn = (string)($batch['original_filename'] ?? '');

$labelEvent = static function (string $type, string $source): string {
    if ($source === 'acesso_pdf') {
        return $type === 'access_attachment' ? 'PDF baixado (attachment)' : 'PDF visualizado (inline)';
    }

    return match ($type) {
        'document_published' => 'Disponibilizado (publicação)',
        'document_viewed' => 'Visualizado (evento)',
        'document_downloaded' => 'Baixado (evento)',
        'document_signed' => 'Ciência confirmada',
        'otp_requested' => 'OTP pedido',
        'otp_sent_whatsapp' => 'OTP enviado (WhatsApp)',
        'otp_sent_email_fallback' => 'OTP enviado (e-mail)',
        'otp_send_fail' => 'Falha envio OTP',
        'otp_validate_success' => 'OTP validado',
        'otp_validate_fail' => 'Falha validação OTP',
        'otp_rate_limited' => 'OTP limitado (taxa)',
        'password_sign_fail' => 'Falha senha (ciência)',
        default => $type,
    };
};

$fmt = static function (?string $mysql): string {
    if ($mysql === null || $mysql === '') {
        return '—';
    }
    $t = strtotime($mysql);

    return $t !== false ? date('d/m/Y H:i:s', $t) : htmlspecialchars($mysql);
};
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">Trilha de auditoria</h2>
        <ol class="breadcrumb mb-0 mt-2 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="text-decoration-none">Importar RH</a></li>
            <li class="breadcrumb-item active">Auditoria lote #<?= $batchId ?></li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-0 shadow-sm mb-3 mt-3 rounded-3">
        <div class="card-header bg-secondary text-white d-flex flex-wrap align-items-center gap-2 py-2">
            <span class="fw-semibold"><i class="fas fa-shield-alt me-2"></i>Lote #<?= $batchId ?></span>
            <span class="small opacity-90 text-truncate" style="max-width:420px;"><?= htmlspecialchars($fn) ?></span>
            <div class="ms-md-auto d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($reportUrl) ?>" class="btn btn-light btn-sm"><i class="fas fa-chart-bar me-1"></i> Relatório resumido</a>
                <a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            </div>
        </div>
        <div class="card-body small text-muted">
            Linhas da tabela <code>adms_payroll_document_events</code> e <code>adms_payroll_document_access_logs</code>, ordenadas no tempo.
            Metadados JSON aparecem abreviados; o hash do ficheiro pode constar no evento de publicação ou na ciência.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-2">
            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.04em;">Cronologia (<?= count($timeline) ?> registos)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small">
                            <th class="ps-3">Quando</th>
                            <th>Origem</th>
                            <th>Ação</th>
                            <th>Documento / titular</th>
                            <th>User ID</th>
                            <th>IP</th>
                            <th class="pe-3">User-Agent / meta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeline as $row) {
                            $did = (int)($row['document_id'] ?? 0);
                            $doc = $docMap[$did] ?? null;
                            $title = $doc ? (string)($doc['title'] ?? '') : '';
                            $owner = $doc ? (string)($doc['owner_name'] ?? '') : '';
                            $meta = $row['meta_json'] ?? null;
                            $metaShort = '';
                            if ($meta !== null && $meta !== '') {
                                $metaShort = mb_strlen((string)$meta) > 120 ? mb_substr((string)$meta, 0, 117) . '…' : (string)$meta;
                            }
                            $ua = (string)($row['user_agent'] ?? '');
                            $uaShort = $ua !== '' ? (mb_strlen($ua) > 80 ? mb_substr($ua, 0, 77) . '…' : $ua) : '—';
                            ?>
                            <tr>
                                <td class="ps-3 text-nowrap small"><?= $fmt((string)($row['created_at'] ?? '')) ?></td>
                                <td class="small"><span class="badge text-bg-light text-secondary border"><?= htmlspecialchars((string)($row['source'] ?? '')) ?></span></td>
                                <td class="small"><?= htmlspecialchars($labelEvent((string)($row['event_type'] ?? ''), (string)($row['source'] ?? ''))) ?></td>
                                <td class="small">
                                    <?php if ($did > 0) { ?>
                                        <span class="fw-semibold">#<?= $did ?></span>
                                        <?php if ($title !== '') { ?><div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($title) ?></div><?php } ?>
                                        <?php if ($owner !== '') { ?><div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($owner) ?></div><?php } ?>
                                    <?php } else { ?>
                                        —
                                    <?php } ?>
                                </td>
                                <td class="small"><?= $row['user_id'] !== null ? (int)$row['user_id'] : '—' ?></td>
                                <td class="small text-break"><code class="small"><?= htmlspecialchars((string)($row['ip'] ?? '—')) ?></code></td>
                                <td class="pe-3 small">
                                    <div class="text-break" title="<?= htmlspecialchars($ua) ?>"><code style="font-size:.65rem;"><?= htmlspecialchars($uaShort) ?></code></div>
                                    <?php if ($metaShort !== '') { ?>
                                        <div class="mt-1"><code style="font-size:.65rem;"><?= htmlspecialchars($metaShort) ?></code></div>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

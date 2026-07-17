<?php
$package = is_array($this->data['package'] ?? null) ? $this->data['package'] : [];
$protocol = (string) ($this->data['protocol'] ?? '');
$csrf = (string) ($this->data['csrf_token'] ?? '');
$excluded = is_array($this->data['excluded_content'] ?? null) ? $this->data['excluded_content'] : [];
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

$label = static fn (string $key): string => ucfirst(str_replace('_', ' ', $key));
$display = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    if (is_bool($value)) {
        return $value ? 'Sim' : 'Não';
    }
    return (string) $value;
};
$renderAssoc = static function (array $values) use ($label, $display): void {
    if ($values === []) {
        echo '<p class="text-muted small mb-0">Sem dados.</p>';
        return;
    }
    echo '<div class="table-responsive"><table class="table table-sm table-striped mb-0"><tbody>';
    foreach ($values as $key => $value) {
        echo '<tr><th style="width:42%">' . htmlspecialchars($label((string) $key), ENT_QUOTES, 'UTF-8') . '</th>';
        echo '<td>' . htmlspecialchars($display($value), ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    echo '</tbody></table></div>';
};
$renderRows = static function (array $rows) use ($label, $display): void {
    if ($rows === []) {
        echo '<p class="text-muted small mb-0">Sem registros.</p>';
        return;
    }
    $headers = array_keys($rows[0]);
    echo '<div class="table-responsive"><table class="table table-sm table-striped table-hover mb-0"><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($label((string) $header), ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<td>' . htmlspecialchars($display($row[$header] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-file-shield me-2"></i>Evidências auditáveis</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $urlAdm ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item">Evidências</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php if (!empty($this->data['error'])): ?>
        <div class="alert alert-warning"><?= htmlspecialchars((string) $this->data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="alert alert-success">
        <div class="fw-semibold mb-1"><i class="fas fa-shield-alt me-1"></i>Exportação sanitizada</div>
        <div class="small">
            O pacote comprova estrutura, criptografia, segregação, auditoria, retenção e operação sem descriptografar conteúdos.
            Use preferencialmente um protocolo criado exclusivamente para demonstração ou auditoria.
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card border-light shadow-sm h-100">
                <div class="card-header fw-semibold">Gerar prévia</div>
                <div class="card-body">
                    <form method="get" class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label for="evidence-protocol" class="form-label small">Protocolo de referência (opcional)</label>
                            <input type="text" name="protocol" id="evidence-protocol" class="form-control"
                                maxlength="32" autocomplete="off"
                                value="<?= htmlspecialchars($protocol, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Ex.: CD-2026-TESTE">
                            <div class="form-text">Em branco: controles globais. Com protocolo: acrescenta metadados e evidências agregadas daquela denúncia.</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Atualizar prévia</button>
                        </div>
                    </form>

                    <hr>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (['pdf' => ['PDF', 'fa-file-pdf', 'danger'], 'excel' => ['Excel', 'fa-file-excel', 'success']] as $format => [$text, $icon, $color]): ?>
                        <form method="post" action="<?= htmlspecialchars($urlAdm . 'whistleblowing-audit-evidence', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="format" value="<?= $format ?>">
                            <input type="hidden" name="protocol" value="<?= htmlspecialchars($protocol, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-outline-<?= $color ?>">
                                <i class="fas <?= $icon ?> me-1"></i>Baixar <?= $text ?>
                            </button>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-light shadow-sm h-100">
                <div class="card-header fw-semibold">Conteúdo sempre excluído</div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($excluded as $item): ?>
                        <li class="list-group-item"><i class="fas fa-ban text-danger me-1"></i><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-light shadow-sm mb-3">
        <div class="card-body py-2 small">
            <strong>Gerado em:</strong> <?= htmlspecialchars($display($package['generated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <span class="mx-2">|</span>
            <strong>Ambiente:</strong> <?= htmlspecialchars($display($package['environment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <div class="font-monospace text-break mt-1">
                <strong>Fingerprint SHA-256:</strong> <?= htmlspecialchars((string) ($package['fingerprint_sha256'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>

    <div class="accordion mb-4" id="evidence-sections">
        <?php
        $sections = [
            ['report', 'Denúncia de referência', 'assoc'],
            ['cryptography', 'Evidência de criptografia', 'assoc'],
            ['operational_summary', 'Resumo operacional', 'assoc'],
            ['policies', 'Políticas seguras', 'assoc'],
            ['retention_summary', 'Resumo de retenção', 'assoc'],
            ['access_summary', 'Acessos agregados', 'rows'],
            ['status_timeline', 'Linha do tempo de status', 'rows'],
            ['messages_summary', 'Mensagens agregadas', 'rows'],
            ['attachments_summary', 'Anexos agregados', 'rows'],
            ['committees', 'Comitês e segregação', 'rows'],
            ['committee_categories', 'Classificações por comitê', 'rows'],
            ['retention_runs', 'Execuções de retenção', 'rows'],
            ['schema', 'Estrutura técnica', 'rows'],
        ];
        foreach ($sections as $index => [$key, $title, $type]):
            $value = is_array($package[$key] ?? null) ? $package[$key] : [];
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#evidence-<?= $index ?>">
                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge bg-secondary ms-2"><?= count($value) ?></span>
                </button>
            </h2>
            <div id="evidence-<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                data-bs-parent="#evidence-sections">
                <div class="accordion-body p-2">
                    <?php $type === 'assoc' ? $renderAssoc($value) : $renderRows($value); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-light shadow-sm mb-4">
        <div class="card-header fw-semibold">Últimas gerações</div>
        <div class="card-body p-0">
            <?php $renderRows(is_array($this->data['recent_exports'] ?? null) ? $this->data['recent_exports'] : []); ?>
        </div>
        <div class="card-footer small text-muted">A identificação do usuário responsável permanece registrada no banco para auditoria interna, mas não é incluída no pacote exportado.</div>
    </div>
</div>

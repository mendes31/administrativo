<?php
$settings = $this->data['log_settings'] ?? [];
$profiles = $this->data['slow_request_profiles'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? '';
$downloadSessionToken = $this->data['csrf_download_session_logs'] ?? '';
$downloadSlowToken = $this->data['csrf_download_slow_profiles'] ?? '';
$buttonPermission = $this->data['buttonPermission'] ?? [];
$sessionDebugEnabled = (int)($settings['session_debug_logs'] ?? 0) === 1;
$slowProfilerEnabled = (int)($settings['slow_request_profiler_enabled'] ?? 0) === 1;
$frontendDebugEnabled = (int)($settings['frontend_debug_logs'] ?? 0) === 1;
$slowThresholdMs = (int)($settings['slow_request_threshold_ms'] ?? 700);
$slowRetentionDays = (int)($settings['slow_request_retention_days'] ?? 7);
?>

<div class="container-fluid px-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-sliders-h me-2"></i>Configurações de Logs
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Administração</li>
            <li class="breadcrumb-item">Logs</li>
            <li class="breadcrumb-item active">Configurações</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Logs de Diagnóstico</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-log-settings">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="session_debug_logs" name="session_debug_logs" value="1"
                                   <?= $sessionDebugEnabled ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="session_debug_logs">
                                Ativar logs de diagnóstico de sessão
                            </label>
                        </div>

                        <p class="text-muted small mb-4">
                            Use apenas durante investigação. Quando ativado, o sistema grava logs de sessão
                            detalhados em arquivo, o que pode aumentar I/O de disco e impactar performance.
                        </p>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="frontend_debug_logs" name="frontend_debug_logs" value="1"
                                   <?= $frontendDebugEnabled ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="frontend_debug_logs">
                                Ativar logs de debug no front-end (console)
                            </label>
                            <div class="form-text">
                                Mantém o console mais silencioso em produção. Ative apenas para investigação pontual.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="slow_request_profiler_enabled" name="slow_request_profiler_enabled" value="1"
                                   <?= $slowProfilerEnabled ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="slow_request_profiler_enabled">
                                Ativar micro-profiler de requisições lentas
                            </label>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="slow_request_threshold_ms">
                                    Limite para considerar lenta (ms)
                                </label>
                                <input type="number"
                                       min="10"
                                       max="30000"
                                       step="10"
                                       class="form-control"
                                       id="slow_request_threshold_ms"
                                       name="slow_request_threshold_ms"
                                       value="<?= htmlspecialchars((string)$slowThresholdMs, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">
                                    Para investigação curta use valores menores (ex.: 10–100ms).
                                    Em produção estável, 700–1200ms costuma ser um bom limite.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="slow_request_retention_days">
                                    Retenção dos registros (dias)
                                </label>
                                <input type="number"
                                       min="1"
                                       max="60"
                                       step="1"
                                       class="form-control"
                                       id="slow_request_retention_days"
                                       name="slow_request_retention_days"
                                       value="<?= htmlspecialchars((string)$slowRetentionDays, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">Limpeza automática executada em segundo plano.</div>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            Quando ativo, apenas requisições acima do limite serão salvas em banco,
                            com método, rota, duração e uso de memória.
                        </p>

                        <button type="submit" class="btn btn-primary mb-3">
                            <i class="fas fa-save me-2"></i>Salvar Configurações
                        </button>
                    </form>

                    <hr class="my-4">

                    <h6 class="fw-semibold mb-3">
                        <i class="fas fa-filter me-2"></i>Filtro para exportar micro-profiler
                    </h6>
                    <form class="row g-3 mb-3" id="slow-profiler-filter-form">
                        <div class="col-md-3">
                            <label for="slow_export_days" class="form-label small fw-semibold">Últimos N dias</label>
                            <input type="number" min="0" max="30" step="1" class="form-control form-control-sm"
                                   id="slow_export_days" name="days" placeholder="0 = ilimitado">
                        </div>
                        <div class="col-md-3">
                            <label for="slow_export_from" class="form-label small fw-semibold">Data início</label>
                            <input type="date" class="form-control form-control-sm"
                                   id="slow_export_from" name="from">
                        </div>
                        <div class="col-md-3">
                            <label for="slow_export_to" class="form-label small fw-semibold">Data fim</label>
                            <input type="date" class="form-control form-control-sm"
                                   id="slow_export_to" name="to">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="slow-export-quick-24h">
                                24h
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="slow-export-quick-7d">
                                7 dias
                            </button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if (in_array('DownloadSessionDiagnosticLogs', $buttonPermission, true)): ?>
                            <a href="<?= $_ENV['URL_ADM'] ?>download-session-diagnostic-logs?token=<?= urlencode((string)$downloadSessionToken); ?>"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-file-archive me-2"></i>Baixar logs de sessão
                            </a>
                        <?php endif; ?>
                        <?php if (in_array('ExportSlowRequestProfilesCsv', $buttonPermission, true)): ?>
                            <button type="button"
                                    id="btn-export-slow-profiler"
                                    class="btn btn-outline-success">
                                <i class="fas fa-file-csv me-2"></i>Baixar micro-profiler (CSV)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-stopwatch me-2"></i>Últimas requisições lentas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($profiles)): ?>
                        <p class="text-muted mb-0">Nenhum registro encontrado até o momento.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Método</th>
                                        <th>Rota</th>
                                        <th>Duração</th>
                                        <th>Memória</th>
                                        <th>Usuário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profiles as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)($item['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($item['request_method'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars((string)($item['route_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars((string)($item['request_uri'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td><?= (int)($item['duration_ms'] ?? 0); ?> ms</td>
                                            <td><?= number_format((float)($item['memory_mb'] ?? 0), 2, ',', '.'); ?> MB</td>
                                            <td><?= isset($item['user_id']) ? (int)$item['user_id'] : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if (in_array('ExportSlowRequestProfilesCsv', $buttonPermission, true)): ?>
        <script>
            (function () {
                const baseUrl = "<?= $_ENV['URL_ADM'] ?>export-slow-request-profiles-csv?token=<?= urlencode((string)$downloadSlowToken); ?>";
                const btn = document.getElementById('btn-export-slow-profiler');
                const form = document.getElementById('slow-profiler-filter-form');
                if (!btn || !form) {
                    return;
                }

                function buildUrl() {
                    const params = new URLSearchParams();
                    const days = form.querySelector('#slow_export_days').value;
                    const from = form.querySelector('#slow_export_from').value;
                    const to = form.querySelector('#slow_export_to').value;

                    if (days && parseInt(days, 10) > 0) {
                        params.set('days', String(parseInt(days, 10)));
                    } else {
                        if (from) {
                            params.set('from', from);
                        }
                        if (to) {
                            params.set('to', to);
                        }
                    }

                    const query = params.toString();
                    return query ? baseUrl + '&' + query : baseUrl;
                }

                btn.addEventListener('click', function () {
                    window.location.href = buildUrl();
                });

                const quick24 = document.getElementById('slow-export-quick-24h');
                const quick7 = document.getElementById('slow-export-quick-7d');
                if (quick24) {
                    quick24.addEventListener('click', function (e) {
                        e.preventDefault();
                        form.querySelector('#slow_export_days').value = '1';
                        form.querySelector('#slow_export_from').value = '';
                        form.querySelector('#slow_export_to').value = '';
                    });
                }
                if (quick7) {
                    quick7.addEventListener('click', function (e) {
                        e.preventDefault();
                        form.querySelector('#slow_export_days').value = '7';
                        form.querySelector('#slow_export_from').value = '';
                        form.querySelector('#slow_export_to').value = '';
                    });
                }
            })();
        </script>
    <?php endif; ?>
</div>

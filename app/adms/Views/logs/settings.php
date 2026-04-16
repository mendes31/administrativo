<?php
$settings = $this->data['log_settings'] ?? [];
$csrfToken = $this->data['csrf_token'] ?? '';
$sessionDebugEnabled = (int)($settings['session_debug_logs'] ?? 0) === 1;
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

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Configurações
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


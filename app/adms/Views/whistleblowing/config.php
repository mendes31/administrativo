<?php

$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$tokenConfigured = !empty($this->data['token_configured']);
$keyConfigured = !empty($this->data['key_configured']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$updated = (string) ($this->data['config_row']['updated_at'] ?? '');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-cog me-2"></i>Configuração — Canal de Denúncias</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item active">Configuração</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-2">
        <?php
        $log_resumo = $this->data['log_resumo'] ?? [];
        $log_btn_class = 'btn btn-outline-secondary btn-sm';
        include __DIR__ . '/../partials/button_log_alteracoes.php';
        ?>
    </div>

    <div class="alert alert-info small">
        <strong>Não é obrigatório usar o arquivo <code>.env</code>.</strong> Token do cron e chave de criptografia podem ser definidos aqui, como no cron de lembretes de folha (RH).
        Se existir valor no banco, ele tem prioridade sobre o <code>.env</code>.
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-key me-1"></i>Token do cron (retenção LGPD)</div>
                <div class="card-body">
                    <p class="small text-muted">
                        Estado:
                        <?= $tokenConfigured ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-warning text-dark">Não configurado</span>' ?>
                        <?php if ($updated !== ''): ?> — atualizado em <?= htmlspecialchars($updated) ?><?php endif; ?>
                    </p>
                    <pre class="bg-light p-2 rounded small text-break"><?= htmlspecialchars($urlAdm) ?>whistleblowing-retention-cron?token=SEU_TOKEN</pre>
                    <p class="small text-muted">Arquiva denúncias após 5 anos e exclui após 10 anos (conforme cadastro).</p>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="save_token">
                        <div class="col-12">
                            <label class="form-label small"><?= $tokenConfigured ? 'Novo token' : 'Token secreto' ?></label>
                            <input type="password" name="http_cron_token" class="form-control font-monospace" autocomplete="new-password" minlength="16" placeholder="Mínimo 16 caracteres">
                        </div>
                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar token</button>
                        </div>
                    </form>
                    <?php if ($tokenConfigured): ?>
                    <form method="post" class="mt-2" onsubmit="return confirm('Remover o token do cron?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="clear_token">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Limpar token</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-lock me-1"></i>Chave de criptografia (AES-256)</div>
                <div class="card-body">
                    <p class="small text-muted">
                        Estado:
                        <?= $keyConfigured ? '<span class="badge bg-success">Configurada</span>' : '<span class="badge bg-warning text-dark">Usando fallback (.env ou padrão interno)</span>' ?>
                    </p>
                    <p class="small">Protege relatos e mensagens no banco. <strong>Não altere</strong> após denúncias em produção, salvo migração planejada.</p>
                    <p class="small text-muted">Gere com: <code>php -r "echo bin2hex(random_bytes(32));"</code></p>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="save_key">
                        <div class="col-12">
                            <label class="form-label small"><?= $keyConfigured ? 'Nova chave (substitui a atual)' : 'Chave secreta' ?></label>
                            <input type="password" name="encryption_key" class="form-control font-monospace" autocomplete="new-password" minlength="32" placeholder="Mínimo 32 caracteres">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Guardar chave</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

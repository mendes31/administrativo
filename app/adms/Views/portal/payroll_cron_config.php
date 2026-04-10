<?php

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$configured = !empty($this->data['token_configured']);
$csrf = (string)($this->data['csrf_token'] ?? '');
$updated = isset($this->data['cron_row']['updated_at']) ? (string)$this->data['cron_row']['updated_at'] : '';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">Cron — lembretes de folha (RH)</h2>
        <ol class="breadcrumb mb-3 mt-2 mt-md-3 ms-md-auto small mb-md-3">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="text-decoration-none">Documentos RH</a></li>
            <li class="breadcrumb-item active">Cron lembretes</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="alert alert-info border-0 shadow-sm small mb-3 rounded-3">
        <strong>O que faz:</strong> envia <strong>notificações internas</strong> (sino do portal) aos colaboradores que ainda não deram ciência a documentos de folha, segundo a <strong>régua D+X</strong> definida em cada <a href="<?= htmlspecialchars($urlAdm) ?>list-payroll-document-types">tipo de documento</a>.
        Por defeito o sistema corre isto <strong>no máximo uma vez por 24 horas</strong>, no primeiro <strong>login</strong> ou acesso ao <strong>dashboard</strong> (igual à retenção de currículos / outros serviços em <code>storage/cache/system/</code>).
        A <strong>URL com token</strong> abaixo é <strong>opcional</strong> — só para forçar uma execução imediata a partir de um agendador externo, sem depender de alguém entrar na aplicação.
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <span class="fw-semibold"><i class="fas fa-key me-2 text-primary"></i>Token HTTP</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <p class="small text-muted mb-3">
                        Estado:
                        <?php if ($configured): ?>
                            <span class="badge bg-success">Configurado</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Não configurado</span> — o endpoint HTTP recusará chamadas até guardar um token.
                        <?php endif; ?>
                        <?php if ($updated !== ''): ?>
                            <span class="text-muted ms-1">Última atualização: <?= htmlspecialchars($updated) ?></span>
                        <?php endif; ?>
                    </p>

                    <p class="small mb-2"><strong>URL para o agendador</strong> (exemplo; substitua <code>SEU_TOKEN</code>):</p>
                    <pre class="bg-light p-2 rounded small text-break mb-4"><?= htmlspecialchars($urlAdm) ?>payroll-reminders-cron?token=SEU_TOKEN</pre>

                    <form method="post" action="<?= htmlspecialchars($urlAdm) ?>payroll-cron-config" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <div class="col-12">
                            <label for="http_cron_token" class="form-label"><?= $configured ? 'Novo token (substitui o atual)' : 'Token secreto' ?></label>
                            <input type="password" name="http_cron_token" id="http_cron_token" class="form-control font-monospace" autocomplete="new-password"
                                   minlength="16" placeholder="Mínimo 16 caracteres — use um gerador aleatório">
                            <small class="form-text text-muted">Em produção, guarde este valor só no agendador e nesta página. Não reexibimos o token após guardar.</small>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar token</button>
                            <button type="submit" name="clear_token" value="1" class="btn btn-outline-danger" onclick="return confirm('Remover o token? O cron HTTP deixará de aceitar chamadas até configurar de novo.');">
                                <i class="fas fa-eraser me-1"></i> Limpar token
                            </button>
                            <a href="<?= htmlspecialchars($urlAdm) ?>list-payroll-document-types" class="btn btn-outline-secondary">Tipos de documento (régua D+X)</a>
                        </div>
                    </form>

                    <hr class="my-4">
                    <h6 class="small text-uppercase text-muted mb-2">Alternativa — linha de comandos</h6>
                    <p class="small text-muted mb-0"><strong>CLI (forçar já):</strong> <code class="small">php scripts/payroll_reminders_cron.php</code> — ignora o intervalo de 24 h; não requer token.</p>
                </div>
            </div>
        </div>
    </div>
</div>

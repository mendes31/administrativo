<?php



$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

$tokenConfigured = !empty($this->data['token_configured']);

$keyConfigured = !empty($this->data['key_configured']);

$keyWrapped = !empty($this->data['key_wrapped']);

$wrapSecretConfigured = !empty($this->data['wrap_secret_configured']);

$channelPublicOk = !empty($this->data['channel_public_ok']);

$csrf = (string) ($this->data['csrf_token'] ?? '');

$updated = (string) ($this->data['config_row']['updated_at'] ?? '');

$lastRun = $this->data['last_retention_run'] ?? null;

$archiveYears = (int) ($this->data['retention_archive_years'] ?? 5);

$deleteYears = (int) ($this->data['retention_delete_years'] ?? 10);

$cronEnabled = !empty($this->data['cron_enabled']);

$cronTime = (string) ($this->data['cron_time'] ?? '02:00');

$rateMax = (int) ($this->data['rate_limit_max_attempts'] ?? 5);

$rateWindow = (int) ($this->data['rate_limit_window_minutes'] ?? 15);

$slaDefault = (int) ($this->data['sla_first_response_hours'] ?? 72);

$slaCritico = (int) ($this->data['sla_hours_critico'] ?? 24);

$slaAlto = (int) ($this->data['sla_hours_alto'] ?? 48);

$slaMedio = (int) ($this->data['sla_hours_medio'] ?? 72);

$slaBaixo = (int) ($this->data['sla_hours_baixo'] ?? 120);

$slaClosureEnabled = !empty($this->data['sla_closure_enabled']);

$slaClosureDefault = (int) ($this->data['sla_closure_hours'] ?? 720);

$slaClosureCritico = (int) ($this->data['sla_closure_hours_critico'] ?? 168);

$slaClosureAlto = (int) ($this->data['sla_closure_hours_alto'] ?? 360);

$slaClosureMedio = (int) ($this->data['sla_closure_hours_medio'] ?? 720);

$slaClosureBaixo = (int) ($this->data['sla_closure_hours_baixo'] ?? 1080);

$notifyReply = !empty($this->data['notify_committee_on_reply']);

$notifyStatus = !empty($this->data['notify_committee_on_status_change']);

$notifySla = !empty($this->data['notify_committee_on_sla_breach']);

$notifyClosureSla = !empty($this->data['notify_committee_on_sla_closure_breach']);

$notifyReporter = !empty($this->data['notify_reporter_on_reply']);

$reporterInactivityEnabled = !empty($this->data['reporter_inactivity_enabled']);

$reporterInactivityDays = (int) ($this->data['reporter_inactivity_days'] ?? 15);

$captchaEnabled = !empty($this->data['captcha_enabled']);

$captchaProvider = (string) ($this->data['captcha_provider'] ?? 'hcaptcha');

$captchaSiteKey = (string) ($this->data['captcha_site_key'] ?? '');

$captchaSecretKey = (string) ($this->data['captcha_secret_key'] ?? '');

$cronLine = (string) ($this->data['cron_line'] ?? '');

$publicUrl = (string) ($this->data['public_channel_url'] ?? '');

$logoUrl = $urlAdm . 'public/adms/image/logo/Logo-Tiaraju.png';

$rotationCounts = $this->data['rotation_counts'] ?? ['reports' => 0, 'messages' => 0, 'attachments' => 0, 'legacy_files' => 0];

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-wrap align-items-center gap-2">

        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-cog me-2"></i>Configuração — Canal de Denúncias</h2>

        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">

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
        <a href="<?= htmlspecialchars($urlAdm) ?>whistleblowing-audit-evidence" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-shield me-1"></i>Pacote de evidências
        </a>

    </div>



    <div class="row g-3 mb-3">

        <div class="col-lg-4">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold"><i class="fas fa-globe me-1"></i> Canal público</div>

                <div class="card-body small">

                    <p class="mb-2">

                        URL: <a href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($publicUrl) ?></a>

                    </p>

                    <p class="mb-2">

                        Estado:

                        <?php if ($channelPublicOk): ?>

                            <span class="badge bg-success">Ativo</span> — chave forte configurada

                        <?php else: ?>

                            <span class="badge bg-danger">Bloqueado</span> — defina chave com mín. 32 caracteres abaixo

                        <?php endif; ?>

                    </p>

                    <p class="mb-0 text-muted">Sem chave forte, registro e acompanhamento públicos ficam indisponíveis.</p>

                </div>

            </div>

        </div>

        <div class="col-lg-8">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold"><i class="fas fa-shield-alt me-1"></i> Última retenção LGPD</div>

                <div class="card-body small">

                    <?php if ($lastRun): ?>

                        <p class="mb-1">

                            <strong>Execução:</strong>

                            <?= htmlspecialchars((string)($lastRun['finished_at'] ?: $lastRun['started_at']), ENT_QUOTES, 'UTF-8'); ?>

                            — status <?= htmlspecialchars((string)$lastRun['status'], ENT_QUOTES, 'UTF-8'); ?>

                        </p>

                        <p class="mb-0">

                            Arquivadas: <?= (int)($lastRun['archived_count'] ?? 0); ?> —

                            Excluídas: <?= (int)($lastRun['deleted_count'] ?? 0); ?> —

                            Anexos removidos: <?= (int)($lastRun['attachments_deleted'] ?? 0); ?>

                            <a href="<?= htmlspecialchars($urlAdm) ?>whistleblowing-governance" class="ms-2">Ver governança</a>

                        </p>

                    <?php else: ?>

                        <p class="mb-0 text-muted">Nenhuma execução registrada. Configure token e cron abaixo ou execute em Governança LGPD.</p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>



    <div class="card border-light shadow-sm mb-3">
        <div class="card-header fw-semibold">
            <i class="fas fa-qrcode me-1"></i> QR Code e cartaz do canal
        </div>
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-auto text-center">
                    <div id="whistleblowing-public-qr" class="d-inline-block p-2 bg-white border rounded"></div>
                </div>
                <div class="col-md">
                    <p class="small mb-2">
                        O QR Code direciona para o canal público:
                        <code class="user-select-all"><?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?></code>
                    </p>
                    <p class="small text-muted mb-3">
                        Baixe um cartaz institucional em PNG, pronto para impressão e divulgação. O material não contém endereço de e-mail.
                    </p>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="download-whistleblowing-poster">
                        <i class="fas fa-download me-1"></i>Baixar cartaz com QR Code
                    </button>
                    <div class="small text-danger mt-2 d-none" id="whistleblowing-qr-error" role="alert"></div>
                </div>
            </div>
        </div>
    </div>



    <div class="card border-light shadow-sm mb-3">

        <div class="card-header fw-semibold"><i class="fas fa-balance-scale me-1"></i> Políticas LGPD e segurança pública</div>

        <div class="card-body">

            <form method="post" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <input type="hidden" name="action" value="save_policies">

                <div class="col-md-3">

                    <label class="form-label small">Arquivar após (anos)</label>

                    <input type="number" name="retention_archive_years" class="form-control form-control-sm" min="1" max="30" value="<?= $archiveYears ?>" required>

                </div>

                <div class="col-md-3">

                    <label class="form-label small">Excluir após (anos)</label>

                    <input type="number" name="retention_delete_years" class="form-control form-control-sm" min="2" max="50" value="<?= $deleteYears ?>" required>

                </div>

                <div class="col-md-2">

                    <label class="form-label small">Hora de referência</label>

                    <input type="time" name="cron_time" class="form-control form-control-sm" value="<?= htmlspecialchars($cronTime) ?>" title="Opcional — apenas para agendamento externo em servidor Linux">

                </div>

                <div class="col-md-2 d-flex align-items-end">

                    <div class="form-check">

                        <input type="checkbox" name="cron_enabled" value="1" class="form-check-input" id="cron_enabled" <?= $cronEnabled ? 'checked' : '' ?>>

                        <label class="form-check-label small" for="cron_enabled">Retenção automática</label>
                        <div class="form-text">Controla só arquivamento/exclusão LGPD. Alertas de SLA e inatividade do denunciante continuam ativos pelo cron/login.</div>

                    </div>

                </div>

                <div class="col-md-3">

                    <label class="form-label small">Tentativas canal público (máx.)</label>

                    <input type="number" name="rate_limit_max_attempts" class="form-control form-control-sm" min="3" max="20" value="<?= $rateMax ?>">

                    <div class="form-text">Registro, acompanhamento e respostas do denunciante.</div>
                </div>

                <div class="col-md-3">

                    <label class="form-label small">Janela bloqueio (minutos)</label>

                    <input type="number" name="rate_limit_window_minutes" class="form-control form-control-sm" min="5" max="120" value="<?= $rateWindow ?>">

                </div>

                <div class="col-12"><hr class="my-1"><h6 class="small fw-semibold mb-0">SLA — primeira resposta (horas)</h6></div>

                <div class="col-md-2">
                    <label class="form-label small">Padrão global</label>
                    <input type="number" name="sla_first_response_hours" class="form-control form-control-sm" min="1" max="720" value="<?= $slaDefault ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Crítico</label>
                    <input type="number" name="sla_hours_critico" class="form-control form-control-sm" min="1" max="720" value="<?= $slaCritico ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Alto</label>
                    <input type="number" name="sla_hours_alto" class="form-control form-control-sm" min="1" max="720" value="<?= $slaAlto ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Médio</label>
                    <input type="number" name="sla_hours_medio" class="form-control form-control-sm" min="1" max="720" value="<?= $slaMedio ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Baixo</label>
                    <input type="number" name="sla_hours_baixo" class="form-control form-control-sm" min="1" max="720" value="<?= $slaBaixo ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <p class="small text-muted mb-2">Prioridade: SLA da classificação (se definido) → risco → padrão global.</p>
                </div>

                <div class="col-12">
                    <hr class="my-1">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="small fw-semibold mb-0">SLA — encerramento (horas)</h6>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" name="sla_closure_enabled" value="1" class="form-check-input"
                                id="sla_closure_enabled" <?= $slaClosureEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="sla_closure_enabled">Ativar SLA de encerramento</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 closure-sla-field">
                    <label class="form-label small">Padrão global</label>
                    <input type="number" name="sla_closure_hours" class="form-control form-control-sm" min="1" max="8760" value="<?= $slaClosureDefault ?>">
                </div>
                <div class="col-md-2 closure-sla-field">
                    <label class="form-label small">Crítico</label>
                    <input type="number" name="sla_closure_hours_critico" class="form-control form-control-sm" min="1" max="8760" value="<?= $slaClosureCritico ?>">
                </div>
                <div class="col-md-2 closure-sla-field">
                    <label class="form-label small">Alto</label>
                    <input type="number" name="sla_closure_hours_alto" class="form-control form-control-sm" min="1" max="8760" value="<?= $slaClosureAlto ?>">
                </div>
                <div class="col-md-2 closure-sla-field">
                    <label class="form-label small">Médio</label>
                    <input type="number" name="sla_closure_hours_medio" class="form-control form-control-sm" min="1" max="8760" value="<?= $slaClosureMedio ?>">
                </div>
                <div class="col-md-2 closure-sla-field">
                    <label class="form-label small">Baixo</label>
                    <input type="number" name="sla_closure_hours_baixo" class="form-control form-control-sm" min="1" max="8760" value="<?= $slaClosureBaixo ?>">
                </div>
                <div class="col-md-2 closure-sla-field d-flex align-items-end">
                    <p class="small text-muted mb-2">Conta desde o registro até o encerramento formal.</p>
                </div>

                <div class="col-12">
                    <hr class="my-1">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="small fw-semibold mb-0">Retorno do denunciante</h6>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" name="reporter_inactivity_enabled" value="1" class="form-check-input"
                                id="reporter_inactivity_enabled" <?= $reporterInactivityEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="reporter_inactivity_enabled">Ativar prazo de retorno</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="reporter_inactivity_days">Prazo após resposta pública (dias)</label>
                    <input type="number" name="reporter_inactivity_days" id="reporter_inactivity_days"
                        class="form-control form-control-sm" min="1" max="365" value="<?= $reporterInactivityDays ?>">
                </div>
                <div class="col-md-9 d-flex align-items-end">
                    <p class="small text-muted mb-2">
                        Cada resposta pública do comitê reinicia o prazo. Uma resposta ou anexo do denunciante encerra a pendência.
                        Ao vencer, o comitê recebe um alerta e decide manualmente; o sistema nunca encerra a denúncia automaticamente.
                    </p>
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="small fw-semibold mb-0">Notificações</h6></div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="notify_committee_on_reply" value="1" class="form-check-input" id="notify_reply" <?= $notifyReply ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_reply">Comitê — resposta do denunciante</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="notify_committee_on_status_change" value="1" class="form-check-input" id="notify_status" <?= $notifyStatus ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_status">Comitê — mudança de status</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="notify_committee_on_sla_breach" value="1" class="form-check-input" id="notify_sla" <?= $notifySla ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_sla">Comitê — SLA da 1ª resposta estourado</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="notify_reporter_on_reply" value="1" class="form-check-input" id="notify_reporter" <?= $notifyReporter ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_reporter">Denunciante — nova resposta (e-mail voluntário)</label>
                    </div>
                </div>
                <div class="col-md-3 closure-sla-field">
                    <div class="form-check">
                        <input type="checkbox" name="notify_committee_on_sla_closure_breach" value="1" class="form-check-input"
                            id="notify_closure_sla" <?= $notifyClosureSla ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_closure_sla">Comitê — SLA de encerramento estourado</label>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="small fw-semibold mb-0">CAPTCHA no canal público</h6></div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="captcha_enabled" value="1" class="form-check-input" id="captcha_enabled" <?= $captchaEnabled ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="captcha_enabled">Ativar</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Provedor</label>
                    <select name="captcha_provider" class="form-select form-select-sm">
                        <option value="hcaptcha" <?= $captchaProvider === 'hcaptcha' ? 'selected' : '' ?>>hCaptcha</option>
                        <option value="recaptcha" <?= $captchaProvider === 'recaptcha' ? 'selected' : '' ?>>reCAPTCHA</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Site key</label>
                    <input type="text" name="captcha_site_key" class="form-control form-control-sm" value="<?= htmlspecialchars($captchaSiteKey, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Secret key</label>
                    <input type="password" name="captcha_secret_key" class="form-control form-control-sm" value="<?= htmlspecialchars($captchaSecretKey, ENT_QUOTES, 'UTF-8') ?>" autocomplete="new-password">
                </div>

                <div class="col-12">

                    <p class="small text-muted mb-2">
                        A retenção LGPD roda automaticamente no <strong>primeiro login do dia</strong> (máx. 1× a cada 24 h), como treinamentos e currículos — sem Agendador de Tarefas no Windows.
                        Os prazos de arquivamento e exclusão contam a partir da <strong>data de encerramento</strong> da denúncia (status Encerrada).
                        Denúncias em andamento não entram na fila de retenção até serem encerradas.
                    </p>

                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Salvar políticas</button>

                </div>

            </form>

        </div>

    </div>



    <div class="row g-3">

        <div class="col-lg-6">

            <div class="card border-light shadow">

                <div class="card-header fw-semibold"><i class="fas fa-server me-1"></i> Agendamento externo (opcional)</div>

                <div class="card-body">

                    <p class="small text-muted mb-2">
                        Em produção Linux, pode agendar via crontab. No ambiente Windows do Tiaraju, a rotina já dispara no login — este token é <strong>opcional</strong>.
                    </p>

                    <p class="small text-muted mb-2">

                        Estado do token:

                        <?= $tokenConfigured ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-secondary">Não configurado</span>' ?>

                        <?php if ($updated !== ''): ?> — config atualizada em <?= htmlspecialchars($updated) ?><?php endif; ?>

                    </p>

                    <?php if ($cronLine !== ''): ?>

                    <p class="small fw-semibold mb-1">Linha sugerida para crontab (Linux):</p>

                    <pre class="bg-light p-2 rounded small user-select-all wb-cron-line"><?= htmlspecialchars($cronLine) ?></pre>

                    <?php endif; ?>

                    <form method="post" class="row g-2">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                        <input type="hidden" name="action" value="save_token">

                        <div class="col-12">

                            <label class="form-label small"><?= $tokenConfigured ? 'Novo token' : 'Token secreto' ?></label>

                            <input type="password" name="http_cron_token" class="form-control font-monospace" autocomplete="new-password" minlength="16" placeholder="Mínimo 16 caracteres">

                        </div>

                        <div class="col-12">

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

                <div class="card-header fw-semibold"><i class="fas fa-lock me-1"></i> Chave de criptografia (AES-256)</div>

                <div class="card-body">

                    <p class="small text-muted mb-2">

                        Estado:

                        <?= $keyConfigured ? '<span class="badge bg-success">Configurada (canal liberado)</span>' : '<span class="badge bg-danger">Obrigatória — canal bloqueado</span>' ?>

                        <?php if ($keyConfigured): ?>
                            <?= $keyWrapped
                                ? ' <span class="badge bg-primary">Envelopada no banco</span>'
                                : ' <span class="badge bg-warning text-dark">Em claro no banco</span>' ?>
                        <?php endif; ?>

                        <?= $wrapSecretConfigured
                            ? ' <span class="badge bg-secondary">KEK .env OK</span>'
                            : ' <span class="badge bg-danger">Falta WHISTLEBLOWING_KEY_WRAP_SECRET no .env</span>' ?>

                    </p>

                    <p class="small">Protege relatos, mensagens e anexos. A chave digitada aqui (DEK) é <strong>envelopada</strong> com o segredo do <code>.env</code> antes de gravar no banco — dump sozinho não abre as denúncias. Na <strong>primeira configuração</strong>, salve abaixo. Para trocar depois, use <strong>Rotação de chave</strong> (não precisa alterar o .env).</p>

                    <p class="small text-muted mb-2">Gere a DEK com: <code>php -r "echo bin2hex(random_bytes(32));"</code>. Guarde uma cópia offline (cofre) — se perder a DEK e os backups, não há recuperação.</p>

                    <?php if (!$wrapSecretConfigured): ?>
                        <div class="alert alert-warning small py-2">
                            Antes de guardar a DEK, adicione ao <code>.env</code> (mín. 32 caracteres) e reinicie o PHP se necessário:<br>
                            <code>WHISTLEBLOWING_KEY_WRAP_SECRET=</code> + valor gerado com
                            <code>php -r "echo bin2hex(random_bytes(32));"</code>
                            <br><span class="text-muted">Este segredo quase nunca muda. Guarde backup do <code>.env</code> separado do dump do banco. Sem ele, a chave envelopada no banco não abre.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$keyConfigured): ?>

                    <form method="post" class="row g-2">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                        <input type="hidden" name="action" value="save_key">

                        <div class="col-12">

                            <label class="form-label small">Chave secreta (DEK)</label>

                            <input type="password" name="encryption_key" class="form-control font-monospace" autocomplete="new-password" minlength="32" placeholder="Mínimo 32 caracteres" <?= $wrapSecretConfigured ? '' : 'disabled' ?>>

                        </div>

                        <div class="col-12">

                            <button type="submit" class="btn btn-primary btn-sm" <?= $wrapSecretConfigured ? '' : 'disabled' ?>><i class="fas fa-save"></i> Guardar chave</button>

                        </div>

                    </form>

                    <?php else: ?>

                    <p class="small text-muted mb-0">Chave já configurada<?= $keyWrapped ? ' e envelopada' : '' ?>. Use o card <strong>Rotação de chave</strong> abaixo para alterá-la com recriptografia automática.</p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>



    <?php if ($keyConfigured): ?>

    <div class="card border-warning shadow-sm mb-3">

        <div class="card-header fw-semibold bg-warning-subtle"><i class="fas fa-sync-alt me-1"></i> Rotação de chave (recriptografia)</div>

        <div class="card-body small">

            <p class="mb-2">

                Recriptografa <strong><?= (int)($rotationCounts['reports'] ?? 0) ?> denúncia(s)</strong>,

                <strong><?= (int)($rotationCounts['messages'] ?? 0) ?> mensagem(ns)</strong> e

                <strong><?= (int)($rotationCounts['attachments'] ?? 0) ?> anexo(s)</strong>

                <?php if ((int)($rotationCounts['legacy_files'] ?? 0) > 0): ?>

                    (<?= (int)$rotationCounts['legacy_files'] ?> arquivo(s) legado(s) serão migrados para <code>.enc</code>)

                <?php endif; ?>.

            </p>

            <p class="text-muted mb-3">A chave atual é recuperada automaticamente do banco (envelopada com o segredo do <code>.env</code>). Informe apenas a <strong>nova chave</strong> e confirme.</p>

            <form method="post" class="row g-2" onsubmit="return confirm('Confirmar rotação da chave? A operação recriptografa todos os dados do canal.');">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <input type="hidden" name="action" value="rotate_key">

                <div class="col-md-6">

                    <label class="form-label small">Nova chave (mín. 32)</label>

                    <input type="password" name="new_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="new-password" minlength="32" required>

                </div>

                <div class="col-md-6">

                    <label class="form-label small">Confirmar nova chave</label>

                    <input type="password" name="confirm_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="new-password" minlength="32" required>

                </div>

                <div class="col-12">

                    <a class="small text-decoration-none" data-bs-toggle="collapse" href="#rotationAdvanced" role="button" aria-expanded="false" aria-controls="rotationAdvanced">
                        <i class="fas fa-caret-right me-1"></i>Opções avançadas de recuperação
                    </a>

                    <div class="collapse mt-2" id="rotationAdvanced">

                        <div class="border rounded p-2 bg-light">

                            <div class="mb-2">

                                <label class="form-label small mb-1">Chave anterior manual</label>

                                <input type="password" name="current_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="off" placeholder="Preencha somente se o sistema não conseguir ler os dados antigos">

                                <div class="form-text">Use apenas em recuperação/migração (ex.: dados cifrados com chave externa). No uso normal, deixe em branco.</div>

                            </div>

                            <div class="form-check">

                                <input type="checkbox" name="try_legacy_key" value="1" class="form-check-input" id="try_legacy_key" checked>

                                <label class="form-check-label" for="try_legacy_key">Tentar também chave legada do sistema (denúncias muito antigas)</label>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-12">

                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-sync-alt me-1"></i>Rotacionar e recriptografar</button>

                </div>

            </form>

        </div>

    </div>

    <?php endif; ?>

</div>

<script>
(function () {
    const toggle = document.getElementById('sla_closure_enabled');
    const fields = document.querySelectorAll('.closure-sla-field');
    if (!toggle) return;

    function syncClosureSlaFields() {
        fields.forEach(function (field) {
            field.classList.toggle('opacity-50', !toggle.checked);
        });
    }

    toggle.addEventListener('change', syncClosureSlaFields);
    syncClosureSlaFields();
})();
</script>

<style>
.wb-cron-line {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
}
@media (max-width: 767.98px) {
    .container-fluid.px-4 { padding-left: .75rem !important; padding-right: .75rem !important; }
    .form-control-sm,
    .form-select-sm,
    button.btn-sm,
    a.btn-sm {
        min-height: 42px;
        font-size: 1rem;
    }
    .card-body > form .btn { width: 100%; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function () {
    const publicUrl = <?= json_encode($publicUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const logoUrl = <?= json_encode($logoUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const qrContainer = document.getElementById('whistleblowing-public-qr');
    const downloadButton = document.getElementById('download-whistleblowing-poster');
    const errorBox = document.getElementById('whistleblowing-qr-error');

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    }

    if (!qrContainer || !downloadButton || publicUrl === '') {
        return;
    }
    if (typeof QRCode === 'undefined') {
        downloadButton.disabled = true;
        showError('Não foi possível carregar o gerador de QR Code. Verifique a conexão e atualize a página.');
        return;
    }

    new QRCode(qrContainer, {
        text: publicUrl,
        width: 190,
        height: 190,
        colorDark: '#102f31',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    function roundedRect(ctx, x, y, width, height, radius) {
        const r = Math.min(radius, width / 2, height / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + width - r, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + r);
        ctx.lineTo(x + width, y + height - r);
        ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
        ctx.lineTo(x + r, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawWrappedText(ctx, text, centerX, startY, maxWidth, lineHeight) {
        const words = text.split(/\s+/);
        const lines = [];
        let line = '';
        words.forEach(function (word) {
            const candidate = line === '' ? word : line + ' ' + word;
            if (ctx.measureText(candidate).width > maxWidth && line !== '') {
                lines.push(line);
                line = word;
            } else {
                line = candidate;
            }
        });
        if (line !== '') lines.push(line);
        lines.forEach(function (item, index) {
            ctx.fillText(item, centerX, startY + (index * lineHeight));
        });

        return startY + (lines.length * lineHeight);
    }

    function loadImage(src) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            image.onload = function () { resolve(image); };
            image.onerror = reject;
            image.src = src;
        });
    }

    function qrSource() {
        return qrContainer.querySelector('canvas') || qrContainer.querySelector('img');
    }

    async function createPoster() {
        const qr = qrSource();
        if (!qr) {
            throw new Error('QR Code ainda não está pronto.');
        }

        const canvas = document.createElement('canvas');
        canvas.width = 1240;
        canvas.height = 1754;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            throw new Error('Seu navegador não suporta a geração do cartaz.');
        }

        const gradient = ctx.createLinearGradient(0, 0, 1240, 1754);
        gradient.addColorStop(0, '#173b3e');
        gradient.addColorStop(0.55, '#244f50');
        gradient.addColorStop(1, '#102f31');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.strokeStyle = 'rgba(255, 255, 255, .18)';
        ctx.lineWidth = 4;
        ctx.strokeRect(34, 34, canvas.width - 68, canvas.height - 68);

        ctx.textAlign = 'center';
        ctx.fillStyle = '#f5f2e9';
        ctx.font = '900 104px Arial, sans-serif';
        ctx.fillText('NOVO CANAL', 620, 190);
        ctx.fillText('DE DENÚNCIAS', 620, 310);

        ctx.fillStyle = '#f2f3ef';
        ctx.font = '500 35px Arial, sans-serif';
        const descriptionEnd = drawWrappedText(
            ctx,
            'O Grupo Tiaraju disponibiliza um canal específico, seguro e confidencial para o recebimento de denúncias e relatos relacionados à ética, conduta e segurança de alimentos, garantindo respeito, integridade e o compromisso com a qualidade e segurança em todas as nossas atividades.',
            620,
            430,
            1020,
            48
        );

        ctx.fillStyle = '#78c5ae';
        ctx.font = '700 39px Arial, sans-serif';
        drawWrappedText(
            ctx,
            'Escaneie o QR Code e registre sua denúncia com facilidade',
            620,
            descriptionEnd + 65,
            760,
            48
        );

        const qrBoxSize = 500;
        const qrBoxX = (canvas.width - qrBoxSize) / 2;
        const qrBoxY = 860;
        ctx.fillStyle = '#ffffff';
        roundedRect(ctx, qrBoxX, qrBoxY, qrBoxSize, qrBoxSize, 42);
        ctx.fill();
        ctx.drawImage(qr, qrBoxX + 48, qrBoxY + 48, qrBoxSize - 96, qrBoxSize - 96);

        ctx.fillStyle = '#f2f3ef';
        ctx.font = '500 30px Arial, sans-serif';
        drawWrappedText(
            ctx,
            'A denúncia pode ser realizada de forma anônima ou identificada, conforme a preferência do denunciante.',
            620,
            1435,
            900,
            40
        );

        try {
            const logo = await loadImage(logoUrl);
            const logoBoxX = 360;
            const logoBoxY = 1550;
            const logoBoxWidth = 520;
            const logoBoxHeight = 125;
            ctx.fillStyle = 'rgba(255, 255, 255, .94)';
            roundedRect(ctx, logoBoxX, logoBoxY, logoBoxWidth, logoBoxHeight, 22);
            ctx.fill();
            const scale = Math.min((logoBoxWidth - 70) / logo.width, (logoBoxHeight - 35) / logo.height);
            const logoWidth = logo.width * scale;
            const logoHeight = logo.height * scale;
            ctx.drawImage(
                logo,
                logoBoxX + ((logoBoxWidth - logoWidth) / 2),
                logoBoxY + ((logoBoxHeight - logoHeight) / 2),
                logoWidth,
                logoHeight
            );
        } catch (e) {
            ctx.fillStyle = '#f2f3ef';
            ctx.font = '900 48px Arial, sans-serif';
            ctx.fillText('GRUPO TIARAJU', 620, 1625);
        }

        return canvas;
    }

    downloadButton.addEventListener('click', async function () {
        downloadButton.disabled = true;
        const originalHtml = downloadButton.innerHTML;
        downloadButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando cartaz...';
        errorBox?.classList.add('d-none');

        try {
            const poster = await createPoster();
            const link = document.createElement('a');
            link.download = 'cartaz-canal-de-denuncias.png';
            link.href = poster.toDataURL('image/png');
            link.click();
        } catch (error) {
            showError(error instanceof Error ? error.message : 'Não foi possível gerar o cartaz.');
        } finally {
            downloadButton.disabled = false;
            downloadButton.innerHTML = originalHtml;
        }
    });
})();
</script>



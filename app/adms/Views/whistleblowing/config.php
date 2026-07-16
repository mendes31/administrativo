<?php



$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

$tokenConfigured = !empty($this->data['token_configured']);

$keyConfigured = !empty($this->data['key_configured']);

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

$notifyReply = !empty($this->data['notify_committee_on_reply']);

$notifyStatus = !empty($this->data['notify_committee_on_status_change']);

$notifySla = !empty($this->data['notify_committee_on_sla_breach']);

$notifyReporter = !empty($this->data['notify_reporter_on_reply']);

$captchaEnabled = !empty($this->data['captcha_enabled']);

$captchaProvider = (string) ($this->data['captcha_provider'] ?? 'hcaptcha');

$captchaSiteKey = (string) ($this->data['captcha_site_key'] ?? '');

$captchaSecretKey = (string) ($this->data['captcha_secret_key'] ?? '');

$cronLine = (string) ($this->data['cron_line'] ?? '');

$publicUrl = (string) ($this->data['public_channel_url'] ?? '');

$rotationCounts = $this->data['rotation_counts'] ?? ['reports' => 0, 'messages' => 0, 'attachments' => 0, 'legacy_files' => 0];

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

                    </div>

                </div>

                <div class="col-md-3">

                    <label class="form-label small">Tentativas acompanhamento (máx.)</label>

                    <input type="number" name="rate_limit_max_attempts" class="form-control form-control-sm" min="3" max="20" value="<?= $rateMax ?>">

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
                        <label class="form-check-label small" for="notify_sla">Comitê — SLA estourado</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" name="notify_reporter_on_reply" value="1" class="form-check-input" id="notify_reporter" <?= $notifyReporter ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notify_reporter">Denunciante — nova resposta (e-mail voluntário)</label>
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

                    <pre class="bg-light p-2 rounded small text-break user-select-all"><?= htmlspecialchars($cronLine) ?></pre>

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

                    </p>

                    <p class="small">Protege relatos, mensagens e anexos. Na <strong>primeira configuração</strong>, salve a chave abaixo. Para trocar depois, use <strong>Rotação de chave</strong>.</p>

                    <p class="small text-muted">Gere com: <code>php -r "echo bin2hex(random_bytes(32));"</code></p>

                    <?php if (!$keyConfigured): ?>

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

                    <?php else: ?>

                    <p class="small text-muted mb-0">Chave já configurada. Use o card <strong>Rotação de chave</strong> abaixo para alterá-la com recriptografia automática.</p>

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

            <p class="text-muted mb-3">Deixe <strong>Chave atual</strong> em branco se houver denúncias antigas (chave legada). O sistema testa automaticamente chave informada, chave do banco e chave legada.</p>

            <form method="post" class="row g-2" onsubmit="return confirm('Confirmar rotação da chave? A operação recriptografa todos os dados do canal.');">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <input type="hidden" name="action" value="rotate_key">

                <div class="col-md-4">

                    <label class="form-label small">Chave atual</label>

                    <input type="password" name="current_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="off" placeholder="Chave em uso hoje">

                </div>

                <div class="col-md-4">

                    <label class="form-label small">Nova chave (mín. 32)</label>

                    <input type="password" name="new_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="new-password" minlength="32" required>

                </div>

                <div class="col-md-4">

                    <label class="form-label small">Confirmar nova chave</label>

                    <input type="password" name="confirm_encryption_key" class="form-control form-control-sm font-monospace" autocomplete="new-password" minlength="32" required>

                </div>

                <div class="col-12">

                    <div class="form-check">

                        <input type="checkbox" name="try_legacy_key" value="1" class="form-check-input" id="try_legacy_key" checked>

                        <label class="form-check-label" for="try_legacy_key">Tentar também chave legada do sistema (denúncias muito antigas)</label>

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



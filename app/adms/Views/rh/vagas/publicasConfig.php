<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $this->data
 */
$captchaEnabled = !empty($this->data['captcha_enabled']);
$captchaProvider = (string) ($this->data['captcha_provider'] ?? 'hcaptcha');
$captchaSiteKey = (string) ($this->data['captcha_site_key'] ?? '');
$secretConfigured = !empty($this->data['captcha_secret_configured']);
$portalUrl = (string) ($this->data['portal_url'] ?? '');
$csrf = (string) ($this->data['csrf_token'] ?? '');
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Portal de Vagas — CAPTCHA</h4>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <p class="text-muted small">
                        Configuração exclusiva da candidatura em
                        <a href="<?= htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') ?>
                        </a>.
                        Independente do CAPTCHA do Canal de Denúncias.
                    </p>

                    <form method="post" action="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-vagas-publicas-config', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="captcha_enabled" value="1" class="form-check-input" id="captcha_enabled"
                                        <?= $captchaEnabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="captcha_enabled">Ativar CAPTCHA</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="captcha_provider">Provedor</label>
                                <select name="captcha_provider" id="captcha_provider" class="form-select">
                                    <option value="hcaptcha" <?= $captchaProvider === 'hcaptcha' ? 'selected' : '' ?>>hCaptcha</option>
                                    <option value="recaptcha" <?= $captchaProvider === 'recaptcha' ? 'selected' : '' ?>>reCAPTCHA v2</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="captcha_site_key">Site key</label>
                                <input type="text" name="captcha_site_key" id="captcha_site_key" class="form-control"
                                       value="<?= htmlspecialchars($captchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                       autocomplete="off">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="captcha_secret_key">Secret key</label>
                                <input type="password" name="captcha_secret_key" id="captcha_secret_key" class="form-control"
                                       value="" autocomplete="new-password"
                                       placeholder="<?= $secretConfigured ? 'Deixe em branco para manter a chave atual' : 'Informe a secret key' ?>">
                                <?php if ($secretConfigured): ?>
                                    <div class="form-text">Já há uma secret key salva. Preencha apenas para substituí-la.</div>
                                <?php else: ?>
                                    <div class="form-text">Obrigatória para o CAPTCHA funcionar quando ativado.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-3 small">
                            Com <strong>Ativar</strong> marcado, o formulário público só envia se site key e secret key estiverem preenchidas.
                            Sem ativar, a candidatura segue só com CSRF, honeypot e rate limit.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-vagas', ENT_QUOTES, 'UTF-8') ?>">
                            Voltar às vagas
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\PositionDisplayHelper;

/**
 * @var array<string, mixed> $vaga
 * @var array<string, mixed> $form
 * @var string $base_url
 * @var string|null $flash_error
 * @var string|null $flash_success
 * @var string $csrf_token
 * @var array<string, mixed>|null $lgpd_termo
 * @var bool $captcha_enabled
 * @var string $captcha_site_key
 * @var string $captcha_provider
 * @var bool $candidatura_habilitada
 */
$v = $vaga ?? [];
$form = is_array($form ?? null) ? $form : [];
$base_url = rtrim((string) ($base_url ?? ''), '/');
$vagaId = (int) ($v['id'] ?? 0);
$mostrarSalario = !empty($v['mostrar_salario']);
$candidaturaHabilitada = !empty($candidatura_habilitada);
$termo = is_array($lgpd_termo ?? null) ? $lgpd_termo : null;
$captchaEnabled = !empty($captcha_enabled);
$captchaSiteKey = (string) ($captcha_site_key ?? '');
$captchaProvider = (string) ($captcha_provider ?? 'hcaptcha');
?>
<article class="vp-card">
    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
        <h2 class="mb-0"><?= htmlspecialchars((string) ($v['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($v['tipo_contrato'])): ?>
            <span class="vp-badge"><?= htmlspecialchars((string) $v['tipo_contrato'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <p class="vp-meta mb-3">
        <?= htmlspecialchars((string) ($v['area_nome'] ?? 'Área a definir'), ENT_QUOTES, 'UTF-8') ?>
        <?php
        $cargo = PositionDisplayHelper::formatForDisplay((string) ($v['cargo_nome'] ?? ''));
        if ($cargo !== ''): ?>
            · <?= htmlspecialchars($cargo, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>

    <dl class="row mb-3">
        <?php if (!empty($v['local_trabalho'])): ?>
            <dt class="col-sm-4">Local</dt>
            <dd class="col-sm-8"><?= htmlspecialchars((string) $v['local_trabalho'], ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <?php if (!empty($v['jornada_trabalho'])): ?>
            <dt class="col-sm-4">Jornada</dt>
            <dd class="col-sm-8"><?= htmlspecialchars((string) $v['jornada_trabalho'], ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <dt class="col-sm-4">Quantidade</dt>
        <dd class="col-sm-8"><?= (int) ($v['quantidade_vagas'] ?? 1) ?></dd>
        <?php if ($mostrarSalario && (!empty($v['salario_min']) || !empty($v['salario_max']))): ?>
            <dt class="col-sm-4">Faixa salarial</dt>
            <dd class="col-sm-8">
                <?php
                $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float) $v['salario_min'], 2, ',', '.') : '';
                $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float) $v['salario_max'], 2, ',', '.') : '';
                if ($salMin !== '' && $salMax !== '') {
                    echo htmlspecialchars($salMin . ' — ' . $salMax, ENT_QUOTES, 'UTF-8');
                } elseif ($salMin !== '') {
                    echo 'A partir de ' . htmlspecialchars($salMin, ENT_QUOTES, 'UTF-8');
                } else {
                    echo 'Até ' . htmlspecialchars($salMax, ENT_QUOTES, 'UTF-8');
                }
                ?>
            </dd>
        <?php endif; ?>
        <?php if (!empty($v['data_limite_inscricao'])): ?>
            <dt class="col-sm-4">Inscrições até</dt>
            <dd class="col-sm-8"><?= htmlspecialchars(FormatHelper::formatDateTime($v['data_limite_inscricao']), ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
    </dl>

    <?php if (!empty($v['descricao'])): ?>
        <h3 class="h6 text-uppercase text-muted">Descrição</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['descricao'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>
    <?php if (!empty($v['requisitos'])): ?>
        <h3 class="h6 text-uppercase text-muted">Requisitos</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['requisitos'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>
    <?php if (!empty($v['beneficios'])): ?>
        <h3 class="h6 text-uppercase text-muted">Benefícios</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['beneficios'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>
</article>

<section class="vp-card" id="candidatar">
    <h2 class="h5 mb-3">Candidatar-se</h2>

    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $flash_success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $flash_error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$candidaturaHabilitada): ?>
        <p class="vp-meta mb-0">Candidatura temporariamente indisponível. Entre em contato com o RH pelos canais oficiais.</p>
    <?php else: ?>
        <form method="post" action="<?= htmlspecialchars($base_url . '/' . $vagaId, ENT_QUOTES, 'UTF-8') ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nome">Nome completo *</label>
                    <input class="form-control" type="text" name="form[nome]" id="nome" required maxlength="150"
                           value="<?= htmlspecialchars((string) ($form['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">E-mail *</label>
                    <input class="form-control" type="email" name="form[email]" id="email" required maxlength="190"
                           value="<?= htmlspecialchars((string) ($form['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="telefone">Telefone *</label>
                    <input class="form-control" type="text" name="form[telefone]" id="telefone" required maxlength="40"
                           value="<?= htmlspecialchars((string) ($form['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cidade">Cidade</label>
                    <input class="form-control" type="text" name="form[cidade]" id="cidade" maxlength="100"
                           value="<?= htmlspecialchars((string) ($form['cidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="estado">UF</label>
                    <input class="form-control" type="text" name="form[estado]" id="estado" maxlength="2"
                           value="<?= htmlspecialchars((string) ($form['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="area_interesse">Área de interesse *</label>
                    <input class="form-control" type="text" name="form[area_interesse]" id="area_interesse" required maxlength="150"
                           value="<?= htmlspecialchars((string) ($form['area_interesse'] ?? ($v['area_nome'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mensagem">Mensagem / experiência (opcional)</label>
                    <textarea class="form-control" name="form[mensagem]" id="mensagem" rows="3" maxlength="2000"><?= htmlspecialchars((string) ($form['mensagem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <?php if ($termo !== null): ?>
                <div class="mt-3 p-3 border rounded bg-light">
                    <p class="small mb-2"><strong>Termo LGPD</strong> (v<?= htmlspecialchars((string) ($termo['versao'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>)</p>
                    <div class="small" style="max-height:140px;overflow:auto;white-space:pre-wrap;"><?= htmlspecialchars((string) ($termo['conteudo'] ?? $termo['texto'] ?? $termo['descricao'] ?? 'Consentimento para tratamento de dados pessoais no recrutamento.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" name="lgpd_consent" id="lgpd_consent" required
                            <?= !empty($form['lgpd_consent']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lgpd_consent">
                            Li e concordo com o tratamento dos meus dados pessoais para fins de recrutamento *
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($captchaEnabled && $captchaSiteKey !== ''): ?>
                <div class="mt-3">
                    <?php if ($captchaProvider === 'recaptcha'): ?>
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($captchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"></div>
                    <?php else: ?>
                        <div class="h-captcha" data-sitekey="<?= htmlspecialchars($captchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" style="background:var(--vp-accent);border-color:var(--vp-accent);">
                    Enviar candidatura
                </button>
            </div>
            <p class="vp-meta mt-2 mb-0">Envio de currículo em arquivo ainda não está disponível neste formulário.</p>
        </form>
    <?php endif; ?>
</section>

<p class="mt-3 mb-0">
    <a class="vp-link" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">&larr; Voltar às vagas</a>
</p>

<?php if ($captchaEnabled && $captchaSiteKey !== ''): ?>
    <?php if ($captchaProvider === 'recaptcha'): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php else: ?>
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <?php endif; ?>
<?php endif; ?>

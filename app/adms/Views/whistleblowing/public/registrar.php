<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string|null $error */
/** @var string $csrf_token */
/** @var list<string> $categories */
/** @var list<string> $risk_levels */
/** @var array<string, mixed> $old */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-file-alt me-2"></i>Registrar denúncia</h1>
        <p class="text-muted mb-0">Preencha o formulário abaixo. A identificação é opcional — por padrão a denúncia permanece anônima.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="anon-badge anon-badge--alert mb-3">
        <strong><i class="fas fa-exclamation-triangle me-1"></i> ATENÇÃO!</strong>
        <ul class="mb-0 small">
            <li>Guarde o <strong>protocolo</strong> e a <strong>senha</strong> ao final — eles não serão exibidos novamente.</li>
            <li>Perdeu protocolo ou senha? Registre uma <strong>nova denúncia</strong> e informe isso no relato.</li>
            <li>Retornos do comitê aparecem somente na consulta por protocolo.</li>
        </ul>
    </div>

    <form class="canal-form" method="post" action="<?php echo htmlspecialchars($base_url . 'enviar', ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="canal-field">
            <label class="form-label fw-semibold">Classificação <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo (($old['category'] ?? '') === $cat) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="canal-field">
            <label class="form-label fw-semibold">Grau de risco</label>
            <select name="risk_level" class="form-select">
                <?php foreach ($risk_levels as $risk): ?>
                    <option value="<?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo (($old['risk_level'] ?? 'Médio') === $risk) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="canal-field">
            <label class="form-label fw-semibold">Relato da denúncia <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="5" required
                placeholder="Descreva o ocorrido (datas, locais, contexto)..."><?php echo htmlspecialchars((string)($old['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="canal-field">
            <label class="form-label fw-semibold">Envolvidos (opcional)</label>
            <textarea name="involved" class="form-control" rows="3"
                placeholder="Nome, cargo, setor ou empresa terceirizada, se souber..."><?php echo htmlspecialchars((string)($old['involved'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="canal-field">
            <label class="form-label fw-semibold">Anexos (opcional)</label>
            <input type="file" name="attachments[]" class="form-control" multiple
                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.mp4,.doc,.docx">
            <div class="form-text">Fotos, PDF, áudio ou vídeo. Máximo 10 MB por arquivo.</div>
        </div>

        <div class="canal-field border rounded p-3 bg-light">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="identify_reporter" value="1" id="identify_reporter"
                    <?php echo !empty($old['identify_reporter']) ? 'checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="identify_reporter">
                    Desejo me identificar voluntariamente
                </label>
            </div>
            <p class="small text-muted mb-2">
                Opcional. Seu contato será cifrado e visível apenas ao comitê responsável.
                <strong>Os retornos do comitê não são enviados por e-mail ou telefone</strong> — consulte sempre por protocolo e senha.
            </p>
            <div id="reporter-fields" class="<?php echo !empty($old['identify_reporter']) ? '' : 'd-none'; ?>">
                <div class="canal-field mb-2">
                    <label class="form-label small" for="reporter_name">Nome</label>
                    <input type="text" name="reporter_name" id="reporter_name" class="form-control"
                        autocomplete="name"
                        value="<?php echo htmlspecialchars((string)($old['reporter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label small" for="reporter_email">E-mail</label>
                        <input type="email" name="reporter_email" id="reporter_email" class="form-control"
                            autocomplete="email" inputmode="email"
                            value="<?php echo htmlspecialchars((string)($old['reporter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small" for="reporter_phone">Telefone</label>
                        <input type="tel" name="reporter_phone" id="reporter_phone" class="form-control"
                            autocomplete="tel" inputmode="tel"
                            value="<?php echo htmlspecialchars((string)($old['reporter_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-text">Nenhum campo é obrigatório isoladamente: informe pelo menos um (nome, e-mail ou telefone). Dados tratados conforme a LGPD (Lei 13.709/2018).</div>
                <div id="reporter-identify-error" class="invalid-feedback d-block d-none mt-2" role="alert"></div>
            </div>
        </div>

        <div class="canal-form-actions">
            <button type="submit" class="btn btn-canal-primary">
                <i class="fas fa-paper-plane me-1"></i>Enviar denúncia
            </button>
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>
<script>
(function () {
    var identifyCheckbox = document.getElementById('identify_reporter');
    var reporterFields = document.getElementById('reporter-fields');
    var identifyError = document.getElementById('reporter-identify-error');
    var form = document.querySelector('.canal-form');
    var identifyErrorMessage = 'Informe pelo menos um campo (nome, e-mail ou telefone) ou desmarque a opção de se identificar.';

    function clearIdentifyError() {
        if (!identifyError) {
            return;
        }
        identifyError.textContent = '';
        identifyError.classList.add('d-none');
    }

    function showIdentifyError() {
        if (!identifyError) {
            return;
        }
        identifyError.textContent = identifyErrorMessage;
        identifyError.classList.remove('d-none');
    }

    function hasAnyIdentifyField() {
        var name = (document.getElementById('reporter_name')?.value || '').trim();
        var email = (document.getElementById('reporter_email')?.value || '').trim();
        var phone = (document.getElementById('reporter_phone')?.value || '').trim();
        return name !== '' || email !== '' || phone !== '';
    }

    identifyCheckbox?.addEventListener('change', function () {
        reporterFields?.classList.toggle('d-none', !this.checked);
        clearIdentifyError();
    });

    ['reporter_name', 'reporter_email', 'reporter_phone'].forEach(function (id) {
        document.getElementById(id)?.addEventListener('input', clearIdentifyError);
    });

    form?.addEventListener('submit', function (event) {
        if (!identifyCheckbox?.checked) {
            clearIdentifyError();
            return;
        }

        if (!hasAnyIdentifyField()) {
            event.preventDefault();
            showIdentifyError();
            document.getElementById('reporter_name')?.focus();
        }
    });
})();
</script>

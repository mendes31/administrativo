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
        <p class="text-muted">Preencha o formulário abaixo. Seus dados de identificação não serão solicitados.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($base_url . 'enviar', ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="mb-3">
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

        <div class="mb-3">
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

        <div class="mb-3">
            <label class="form-label fw-semibold">Relato da denúncia <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="6" required
                placeholder="Descreva o ocorrido com o máximo de detalhes possível (datas, locais, contexto)..."><?php echo htmlspecialchars((string)($old['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Envolvidos (opcional)</label>
            <textarea name="involved" class="form-control" rows="3"
                placeholder="Nome, cargo, setor ou empresa terceirizada, se souber..."><?php echo htmlspecialchars((string)($old['involved'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Anexos (opcional)</label>
            <input type="file" name="attachments[]" class="form-control" multiple
                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.mp4,.doc,.docx">
            <div class="form-text">Fotos, PDF, áudio ou vídeo. Máximo 10 MB por arquivo.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-canal-primary">
                <i class="fas fa-paper-plane me-1"></i>Enviar denúncia
            </button>
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>

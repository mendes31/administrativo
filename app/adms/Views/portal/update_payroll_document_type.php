<?php
use App\adms\Helpers\CSRFHelper;

$type = $this->data['type'] ?? null;
if (!is_array($type)) {
    $type = [];
}
$rj = isset($type['rules_json']) && $type['rules_json'] !== null ? (string)$type['rules_json'] : '';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar tipo de documento (RH)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-payroll-document-types" class="text-decoration-none">Tipos (RH)</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i><?= htmlspecialchars((string)($type['name'] ?? '')) ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_payroll_document_type'); ?>">

                <div class="col-md-4">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars((string)($type['code'] ?? '')) ?>" readonly disabled>
                    <small class="form-text text-muted">O código não pode ser alterado (histórico de documentos).</small>
                </div>

                <div class="col-md-8">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required maxlength="255"
                           value="<?= htmlspecialchars((string)($type['name'] ?? '')) ?>">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="2"><?= htmlspecialchars((string)($type['description'] ?? '')) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label for="default_title_prefix" class="form-label">Prefixo padrão do título (colaborador)</label>
                    <input type="text" name="default_title_prefix" id="default_title_prefix" class="form-control" maxlength="200"
                           value="<?= htmlspecialchars((string)($type['default_title_prefix'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label for="icon" class="form-label">Ícone (opcional)</label>
                    <input type="text" name="icon" id="icon" class="form-control" maxlength="100"
                           value="<?= htmlspecialchars((string)($type['icon'] ?? '')) ?>"
                           placeholder="ex.: fa-file-invoice-dollar">
                    <small class="form-text text-muted">Classe após <code>fas</code>.</small>
                </div>

                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Ordem</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" min="0"
                           value="<?= (int)($type['sort_order'] ?? 0) ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                            <?= !empty($type['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Ativo</label>
                    </div>
                </div>

                <div class="col-12"><hr><h6 class="mb-2">Regras</h6></div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_signature" id="requires_signature" value="1"
                            <?= !empty($type['requires_signature']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requires_signature">Exigir ciência / assinatura formal</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="signature_auth" class="form-label">Autenticação para assinatura</label>
                    <?php $sa = (string)($type['signature_auth'] ?? 'none'); ?>
                    <select name="signature_auth" id="signature_auth" class="form-select">
                        <option value="none" <?= $sa === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                        <option value="password" <?= $sa === 'password' ? 'selected' : '' ?>>Palavra-passe da sessão</option>
                        <option value="otp_whatsapp" <?= $sa === 'otp_whatsapp' ? 'selected' : '' ?>>OTP WhatsApp</option>
                        <option value="otp_email" <?= $sa === 'otp_email' ? 'selected' : '' ?>>OTP e-mail</option>
                        <option value="otp_whatsapp_fallback_email" <?= $sa === 'otp_whatsapp_fallback_email' ? 'selected' : '' ?>>OTP WhatsApp com fallback e-mail</option>
                    </select>
                </div>

                <?php
                $sreOn = !empty($type['signature_reminders_enabled']);
                $rd1 = (int)($type['signature_reminder_day_1'] ?? 1);
                $rd2 = (int)($type['signature_reminder_day_2'] ?? 3);
                $rd3 = (int)($type['signature_reminder_day_3'] ?? 7);
                ?>
                <div class="col-12"><hr class="my-2"><h6 class="mb-2">Lembretes automáticos (cron)</h6>
                    <p class="small text-muted mb-2">Notificações internas enquanto a ciência estiver pendente. Tipos sem esta opção não entram na régua.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="signature_reminders_enabled" id="signature_reminders_enabled" value="1"
                            <?= $sreOn ? 'checked' : '' ?>>
                        <label class="form-check-label" for="signature_reminders_enabled">Ativar lembretes D+X após publicação</label>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_1">D+ (1.º)</label>
                    <input type="number" name="signature_reminder_day_1" id="signature_reminder_day_1" class="form-control form-control-sm" min="0" max="365" value="<?= $rd1 ?>">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_2">D+ (2.º)</label>
                    <input type="number" name="signature_reminder_day_2" id="signature_reminder_day_2" class="form-control form-control-sm" min="0" max="365" value="<?= $rd2 ?>">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_3">D+ (3.º)</label>
                    <input type="number" name="signature_reminder_day_3" id="signature_reminder_day_3" class="form-control form-control-sm" min="0" max="365" value="<?= $rd3 ?>">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_auth_download" id="require_auth_download" value="1"
                            <?= !empty($type['require_auth_download']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_auth_download">Exigir palavra-passe antes do download do PDF (visualizar continua sem pedir senha)</label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="rules_json" class="form-label">Regras adicionais (JSON opcional)</label>
                    <textarea name="rules_json" id="rules_json" class="form-control font-monospace small" rows="5"><?= htmlspecialchars($rj) ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Atualizar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-payroll-document-types" class="btn btn-outline-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Novo tipo de documento (RH)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-payroll-document-types" class="text-decoration-none">Tipos (RH)</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-plus-circle me-2"></i>Cadastro</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_payroll_document_type'); ?>">

                <div class="col-md-4">
                    <label for="code" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control" required
                           pattern="[a-z][a-z0-9_]*"
                           maxlength="63"
                           placeholder="ex.: payroll_extra">
                    <small class="form-text text-muted">Identificador fixo (não muda depois). Letras minúsculas, números e underscore.</small>
                </div>

                <div class="col-md-8">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required maxlength="255"
                           placeholder="Nome exibido nos ecrãs">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-md-6">
                    <label for="default_title_prefix" class="form-label">Prefixo padrão do título (colaborador)</label>
                    <input type="text" name="default_title_prefix" id="default_title_prefix" class="form-control" maxlength="200"
                           placeholder="Usado quando o import deixa o prefixo em branco">
                </div>

                <div class="col-md-6">
                    <label for="icon" class="form-label">Ícone (opcional)</label>
                    <input type="text" name="icon" id="icon" class="form-control" maxlength="100"
                           placeholder="ex.: fa-file-invoice-dollar">
                    <small class="form-text text-muted">Classe Font Awesome após <code>fas</code> (ex.: <code>fa-file-pdf</code>).</small>
                </div>

                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Ordem</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="0" min="0">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Ativo</label>
                    </div>
                </div>

                <div class="col-12"><hr><h6 class="mb-2">Regras (futuro / extensível)</h6></div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_signature" id="requires_signature" value="1">
                        <label class="form-check-label" for="requires_signature">Exigir ciência / assinatura formal</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="signature_auth" class="form-label">Autenticação para assinatura</label>
                    <select name="signature_auth" id="signature_auth" class="form-select">
                        <option value="none">Nenhuma</option>
                        <option value="password">Palavra-passe da sessão</option>
                        <option value="otp_whatsapp">OTP WhatsApp</option>
                        <option value="otp_email">OTP e-mail</option>
                        <option value="otp_whatsapp_fallback_email">OTP WhatsApp com fallback e-mail</option>
                    </select>
                </div>

                <div class="col-12"><hr class="my-2"><h6 class="mb-2">Lembretes automáticos (cron)</h6>
                    <p class="small text-muted mb-2">Notificações internas ao colaborador enquanto a ciência estiver pendente. Desative para tipos que não precisam de régua (ex.: apenas informativos).</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="signature_reminders_enabled" id="signature_reminders_enabled" value="1">
                        <label class="form-check-label" for="signature_reminders_enabled">Ativar lembretes D+X após publicação</label>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_1">D+ (1.º)</label>
                    <input type="number" name="signature_reminder_day_1" id="signature_reminder_day_1" class="form-control form-control-sm" min="0" max="365" value="1">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_2">D+ (2.º)</label>
                    <input type="number" name="signature_reminder_day_2" id="signature_reminder_day_2" class="form-control form-control-sm" min="0" max="365" value="3">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small" for="signature_reminder_day_3">D+ (3.º)</label>
                    <input type="number" name="signature_reminder_day_3" id="signature_reminder_day_3" class="form-control form-control-sm" min="0" max="365" value="7">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_auth_download" id="require_auth_download" value="1">
                        <label class="form-check-label" for="require_auth_download">Exigir reautenticação para download (futuro)</label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="rules_json" class="form-label">Regras adicionais (JSON opcional)</label>
                    <textarea name="rules_json" id="rules_json" class="form-control font-monospace small" rows="5"
                              placeholder='{"exemplo_chave": true}'></textarea>
                    <small class="form-text text-muted">JSON válido ou vazio.</small>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-payroll-document-types" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$config = $this->data['email_config'] ?? [];
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Configuração de Servidor de E-mail (SMTP)</h4>
                </div>
                <div class="card-body">
                    <!-- Dicas de configuração -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Dicas para Gmail:</h6>
                        <ul class="mb-0">
                            <li>Use <strong>smtp.gmail.com</strong> como servidor</li>
                            <li>Porta: <strong>587</strong> com criptografia <strong>TLS</strong></li>
                            <li>Use uma <strong>senha de app</strong> (não sua senha normal)</li>
                            <li>Ative a verificação em duas etapas no Google</li>
                        </ul>
                    </div>

                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>create-email-config">
                        <div class="mb-3">
                            <label for="MAIL_HOST" class="form-label">Servidor SMTP (Host)</label>
                            <input type="text" class="form-control" id="MAIL_HOST" name="MAIL_HOST" 
                                   value="<?= htmlspecialchars($config['host'] ?? 'smtp.gmail.com') ?>" 
                                   placeholder="smtp.gmail.com" required>
                            <div class="form-text">Para Gmail: smtp.gmail.com</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="MAIL_USERNAME" class="form-label">Usuário SMTP (E-mail)</label>
                            <input type="email" class="form-control" id="MAIL_USERNAME" name="MAIL_USERNAME" 
                                   value="<?= htmlspecialchars($config['username'] ?? '') ?>" 
                                   placeholder="seu-email@gmail.com" required>
                            <div class="form-text">Seu endereço de e-mail completo</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="MAIL_PASSWORD" class="form-label">Senha SMTP</label>
                            <input type="password" class="form-control" id="MAIL_PASSWORD" name="MAIL_PASSWORD" 
                                   value="<?= htmlspecialchars($config['password'] ?? '') ?>" 
                                   placeholder="Sua senha de app" required autocomplete="off">
                            <div class="form-text">
                                <strong>IMPORTANTE:</strong> Use uma senha de app do Google, não sua senha normal!
                                <br><a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-info">
                                    <i class="fas fa-external-link-alt"></i> Gerar senha de app
                                </a>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="MAIL_PORT" class="form-label">Porta</label>
                                    <input type="number" class="form-control" id="MAIL_PORT" name="MAIL_PORT" 
                                           value="<?= htmlspecialchars($config['port'] ?? '587') ?>" 
                                           placeholder="587" required>
                                    <div class="form-text">Para Gmail: 587</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="MAIL_ENCRYPTI" class="form-label">Criptografia</label>
                                    <select class="form-select" id="MAIL_ENCRYPTI" name="MAIL_ENCRYPTI" required>
                                        <option value="TLS" <?= ($config['encryption'] ?? 'TLS') == 'TLS' ? 'selected' : '' ?>>TLS</option>
                                        <option value="SSL" <?= ($config['encryption'] ?? '') == 'SSL' ? 'selected' : '' ?>>SSL</option>
                                        <option value="" <?= ($config['encryption'] ?? '') == '' ? 'selected' : '' ?>>Nenhuma</option>
                                    </select>
                                    <div class="form-text">Para Gmail: TLS</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="EMAIL_TI" class="form-label">E-mail Remetente (From)</label>
                            <input type="email" class="form-control" id="EMAIL_TI" name="EMAIL_TI" 
                                   value="<?= htmlspecialchars($config['from_email'] ?? '') ?>" 
                                   placeholder="seu-email@gmail.com" required>
                            <div class="form-text">E-mail que aparecerá como remetente</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="NAME_EMAIL_TI" class="form-label">Nome Remetente</label>
                            <input type="text" class="form-control" id="NAME_EMAIL_TI" name="NAME_EMAIL_TI" 
                                   value="<?= htmlspecialchars($config['from_name'] ?? '') ?>" 
                                   placeholder="Nome da Empresa" required>
                            <div class="form-text">Nome que aparecerá como remetente</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                            <button type="button" class="btn btn-info" onclick="testarEmail()">
                                <i class="fas fa-paper-plane"></i> Testar Configuração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testarEmail() {
    if (confirm('Deseja testar a configuração de e-mail? Será enviado um e-mail de teste para o endereço configurado.')) {
        // Criar um formulário temporário para enviar o POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $_ENV['URL_ADM'] ?>test-email-config';
        // Adicionar ao DOM e enviar
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 
<?php

use App\adms\Helpers\CSRFHelper;

// Tratamento de erro para evitar página em branco
try {
    $config = $this->data['whatsapp_config'] ?? [];
    $csrf_token = CSRFHelper::generateCSRFToken('form_whatsapp_config');
} catch (\Throwable $e) {
    error_log("Erro na view whatsappConfig: " . $e->getMessage());
    $config = [];
    $csrf_token = '';
}
?>

<div class="container-fluid px-4">
    
    <?php 
    // Usar caminho absoluto para evitar problemas em produção
    $alertsPath = realpath(__DIR__ . '/../partials/alerts.php');
    if ($alertsPath) {
        include $alertsPath;
    } else {
        // Fallback para caminho relativo se realpath falhar
        include __DIR__ . '/../partials/alerts.php';
    }
    ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fab fa-whatsapp me-2"></i>Configuração de WhatsApp
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Configuração WhatsApp</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Formulário de Configuração -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Dados de Conexão</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-whatsapp-config">
                        
                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Provedor da API *</label>
                            <select name="api_provider" class="form-select" required>
                                <option value="Evolution" <?= ($config['api_provider'] ?? 'Evolution') === 'Evolution' ? 'selected' : '' ?>>
                                    Evolution API (Recomendado - Open Source)
                                </option>
                                <option value="Twilio" <?= ($config['api_provider'] ?? '') === 'Twilio' ? 'selected' : '' ?>>
                                    Twilio WhatsApp API
                                </option>
                                <option value="Meta" <?= ($config['api_provider'] ?? '') === 'Meta' ? 'selected' : '' ?>>
                                    Meta WhatsApp Business API (Oficial)
                                </option>
                            </select>
                            <div class="form-text">
                                <strong>Evolution API:</strong> Grátis, open source, fácil de hospedar<br>
                                <strong>Twilio:</strong> Pago, confiável, internacional<br>
                                <strong>Meta:</strong> Oficial, requer aprovação do Facebook
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL da API *</label>
                            <input type="url" name="api_url" class="form-control" required
                                   placeholder="https://api.evolution.com.br"
                                   value="<?= htmlspecialchars($config['api_url'] ?? '') ?>">
                            <div class="form-text">URL base da API (sem barra no final)</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">API Key *</label>
                                <input type="text" name="api_key" class="form-control" required
                                       placeholder="sua-api-key-aqui"
                                       value="<?= htmlspecialchars($config['api_key'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">API Token</label>
                                <input type="text" name="api_token" class="form-control"
                                       placeholder="token-opcional"
                                       value="<?= htmlspecialchars($config['api_token'] ?? '') ?>">
                                <div class="form-text">Apenas para Twilio e Meta</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome da Instância</label>
                                <input type="text" name="instance_name" class="form-control"
                                       placeholder="tiaraju-crm"
                                       value="<?= htmlspecialchars($config['instance_name'] ?? '') ?>">
                                <div class="form-text">Nome configurado na API</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número WhatsApp</label>
                                <input type="text" name="phone_number" class="form-control"
                                       placeholder="5541999887766"
                                       value="<?= htmlspecialchars($config['phone_number'] ?? '') ?>">
                                <div class="form-text">Número com DDI (sem espaços)</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Webhook URL (Opcional)</label>
                            <input type="url" name="webhook_url" class="form-control"
                                   placeholder="https://seusite.com.br/webhook/whatsapp"
                                   value="<?= htmlspecialchars($config['webhook_url'] ?? '') ?>">
                            <div class="form-text">URL para receber respostas e notificações</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                   value="1" <?= ($config['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <strong>Ativar integração WhatsApp</strong>
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Salvar Configuração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar com Teste -->
        <div class="col-md-4">
            <!-- Teste de Conexão -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Testar Configuração</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>test-whatsapp-config">
                        <div class="mb-3">
                            <label class="form-label">Número de Teste</label>
                            <input type="text" name="test_number" class="form-control" required
                                   placeholder="5541999887766"
                                   value="<?= htmlspecialchars($config['phone_number'] ?? '') ?>">
                            <div class="form-text">Digite o número para receber a mensagem de teste</div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Teste
                        </button>
                    </form>
                </div>
            </div>

            <!-- Casos de Uso -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Onde Usar</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>Esta configuração será usada em:</strong></p>
                    <ul class="small">
                        <li>✅ <strong>Recuperar Senha</strong> (enviar código via WhatsApp)</li>
                        <li>✅ <strong>CRM</strong> (contatar parceiros/clientes)</li>
                        <li>✅ <strong>Notificações</strong> (alertas automáticos)</li>
                        <li>✅ <strong>Lembretes</strong> (atividades, vencimentos)</li>
                        <li>✅ <strong>Confirmações</strong> (pedidos, agendamentos)</li>
                    </ul>
                </div>
            </div>

            <!-- Documentação -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-book me-2"></i>Documentação</h6>
                </div>
                <div class="card-body">
                    <p class="small"><strong>Evolution API</strong> (Recomendado 🌟):</p>
                    <ul class="small">
                        <li>GitHub: <code>EvolutionAPI/evolution-api</code></li>
                        <li>Docker: <code>atendai/evolution-api</code></li>
                        <li>✅ <strong>Grátis</strong> e open source</li>
                        <li>✅ Hospedagem própria</li>
                        <li>✅ Sem limites de mensagens</li>
                    </ul>
                    
                    <p class="small mt-3"><strong>Twilio:</strong></p>
                    <ul class="small">
                        <li>Site: <code>twilio.com/whatsapp</code></li>
                        <li>Account SID = API Key</li>
                        <li>Auth Token = API Token</li>
                        <li>💰 Pago por mensagem</li>
                    </ul>

                    <p class="small mt-3"><strong>Meta/Facebook:</strong></p>
                    <ul class="small">
                        <li>Site: <code>developers.facebook.com</code></li>
                        <li>Requer App Business verificado</li>
                        <li>Phone Number ID necessário</li>
                        <li>⚠️ Processo de aprovação longo</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>


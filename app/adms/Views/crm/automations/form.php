<?php
$users = $this->data['users'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-robot text-primary me-2"></i>
            Nova Automação
        </h1>
        <a href="<?= $_ENV['URL_ADM'] ?>crm-list-automations" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <form method="POST" id="formAutomation">
        
        <!-- Informações Básicas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>1. Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nome da Automação *</label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="Ex: Notificar quando oportunidade > R$ 50mil">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prioridade</label>
                        <input type="number" name="priority" class="form-control" value="0" min="0">
                        <small class="form-text">Ordem de execução (0 = primeira)</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Descreva o objetivo desta automação..."></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                    <label class="form-check-label" for="is_active">
                        <i class="fas fa-power-off me-1"></i>Automação Ativa
                    </label>
                </div>
            </div>
        </div>

        <!-- Gatilho -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>2. Quando Executar (Gatilho)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Entidade *</label>
                        <select name="entity_type" class="form-select" required>
                            <option value="partner">Parceiro</option>
                            <option value="opportunity" selected>Oportunidade</option>
                            <option value="activity">Atividade</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Evento *</label>
                        <select name="trigger_event" class="form-select" required>
                            <option value="created">➕ Quando for criado</option>
                            <option value="updated">✏️ Quando for atualizado</option>
                            <option value="stage_changed">🔄 Quando mudar de etapa</option>
                            <option value="status_changed">📊 Quando mudar status</option>
                        </select>
                    </div>
                </div>
                
                <!-- Condições (Opcional) -->
                <div class="border-top pt-3 mt-2">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-filter me-2"></i>Condições (Opcional - Deixe vazio para sempre executar)
                    </h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Campo</label>
                            <input type="text" name="condition_field" class="form-control"
                                   placeholder="Ex: value, segment, stage_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Operador</label>
                            <select name="condition_operator" class="form-select">
                                <option value="=">=  (igual)</option>
                                <option value="!=">!= (diferente)</option>
                                <option value=">"> >  (maior)</option>
                                <option value=">=">>= (maior ou igual)</option>
                                <option value="<"> <  (menor)</option>
                                <option value="<="><= (menor ou igual)</option>
                                <option value="contains">Contém</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor</label>
                            <input type="text" name="condition_value" class="form-control"
                                   placeholder="Ex: 50000, Ambos, Negociação">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ação -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-magic me-2"></i>3. O Que Fazer (Ação)</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Tipo de Ação *</label>
                    <select name="action_type" id="action_type" class="form-select" required>
                        <option value="send_notification">🔔 Enviar Notificação Interna</option>
                        <option value="send_email">📧 Enviar E-mail</option>
                        <option value="send_whatsapp">💬 Enviar WhatsApp</option>
                        <option value="create_activity">✅ Criar Atividade</option>
                        <option value="create_note">📝 Criar Nota</option>
                    </select>
                </div>

                <!-- Configurações de cada ação (mostradas dinamicamente) -->
                
                <!-- Notificação -->
                <div class="action-config" id="config-send_notification">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Notificar Usuário</label>
                            <select name="action_notification_user" class="form-select">
                                <option value="">Responsável pela entidade</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="action_notification_message" class="form-control" rows="3"
                                      placeholder="Use variáveis: {partner_name}, {value}, {title}, {responsible_name}">Nova oportunidade criada: {title} - Valor: R$ {value}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="action-config" id="config-send_email" style="display: none;">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Para (E-mail)</label>
                            <input type="email" name="action_email_to" class="form-control"
                                   placeholder="Use {partner_email} ou digite email fixo">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Assunto</label>
                            <input type="text" name="action_email_subject" class="form-control"
                                   placeholder="Assunto do email">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="action_email_body" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="action-config" id="config-send_whatsapp" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="action_whatsapp_phone" class="form-control"
                                   placeholder="Use {partner_phone} ou fixo">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="action_whatsapp_message" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Criar Atividade -->
                <div class="action-config" id="config-create_activity" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="action_activity_type" class="form-select">
                                <option value="Ligação">Ligação</option>
                                <option value="Reunião">Reunião</option>
                                <option value="E-mail">E-mail</option>
                                <option value="Tarefa" selected>Tarefa</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prioridade</label>
                            <select name="action_activity_priority" class="form-select">
                                <option value="Baixa">Baixa</option>
                                <option value="Média" selected>Média</option>
                                <option value="Alta">Alta</option>
                                <option value="Urgente">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" name="action_activity_title" class="form-control"
                                   placeholder="Ex: Follow-up com {partner_name}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="action_activity_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsável</label>
                            <select name="action_activity_responsible" class="form-select">
                                <option value="">Responsável pela entidade</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Criar Nota -->
                <div class="action-config" id="config-create_note" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Conteúdo da Nota</label>
                        <textarea name="action_note_content" class="form-control" rows="4"
                                  placeholder="Use variáveis: {partner_name}, {value}, {title}"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="action_note_important" id="note_important">
                        <label class="form-check-label" for="note_important">
                            ⭐ Marcar como importante
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-robot me-2"></i>Criar Automação
            </button>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-list-automations" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionTypeSelect = document.getElementById('action_type');
    const actionConfigs = document.querySelectorAll('.action-config');
    
    function showActionConfig() {
        const selectedAction = actionTypeSelect.value;
        
        // Esconder todos
        actionConfigs.forEach(config => {
            config.style.display = 'none';
        });
        
        // Mostrar o selecionado
        const selectedConfig = document.getElementById('config-' + selectedAction);
        if (selectedConfig) {
            selectedConfig.style.display = 'block';
        }
    }
    
    actionTypeSelect.addEventListener('change', showActionConfig);
    showActionConfig(); // Executar ao carregar
});
</script>


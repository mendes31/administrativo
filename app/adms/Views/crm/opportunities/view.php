<?php
use App\adms\Helpers\CrmCustomFieldsHelper;

$opportunity = $this->data['opportunity'] ?? [];
$stageHistory = $this->data['stage_history'] ?? [];
$activities = $this->data['activities'] ?? [];
$notes = $this->data['notes'] ?? [];
$documents = $this->data['documents'] ?? [];
?>

<div class="container-fluid px-4">
            
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h1 class="mt-2">
                    <i class="fas fa-bullseye text-primary me-2"></i>
                    Detalhes da Oportunidade
                </h1>
                <div>
                    <?php if (!empty($opportunity['partner_phone']) || !empty($opportunity['partner_mobile'])): ?>
                    <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalWhatsApp">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </button>
                    <?php endif; ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-generate-proposal-pdf/<?= $opportunity['id'] ?>" 
                       class="btn btn-info btn-sm me-1" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Proposta (PDF)
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-update-opportunity/<?= $opportunity['id'] ?>" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-opportunity/<?= $opportunity['id'] ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Tem certeza que deseja excluir esta oportunidade?')">
                        <i class="fas fa-trash"></i> Excluir
                    </a>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-kanban-pipeline" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <!-- Informações Principais -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header" style="background-color: <?= htmlspecialchars($opportunity['stage_color'] ?? '#6c757d') ?>; color: white;">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= htmlspecialchars($opportunity['title'] ?? 'N/A') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Código:</strong> <?= htmlspecialchars($opportunity['code'] ?? 'N/A') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Status:</strong>
                                    <span class="badge bg-<?= $opportunity['status'] === 'Aberta' ? 'success' : ($opportunity['status'] === 'Ganha' ? 'primary' : 'danger') ?>">
                                        <?= htmlspecialchars($opportunity['status'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Etapa:</strong>
                                    <span class="badge" style="background-color: <?= htmlspecialchars($opportunity['stage_color'] ?? '#6c757d') ?>">
                                        <?= htmlspecialchars($opportunity['stage_name'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Parceiro:</strong> <?= htmlspecialchars($opportunity['partner_name'] ?? 'N/A') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Responsável:</strong> <?= htmlspecialchars($opportunity['responsible_name'] ?? 'N/A') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Valor:</strong> R$ <?= number_format($opportunity['value'] ?? 0, 2, ',', '.') ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Probabilidade:</strong> <?= htmlspecialchars($opportunity['probability'] ?? '0') ?>%
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Previsão de Fechamento:</strong>
                                    <?= $opportunity['expected_close_date'] ? date('d/m/Y', strtotime($opportunity['expected_close_date'])) : 'N/A' ?>
                                </div>
                                <?php if (!empty($opportunity['description'])): ?>
                                <div class="col-12 mb-3">
                                    <strong>Descrição:</strong>
                                    <p class="mt-2"><?= nl2br(htmlspecialchars($opportunity['description'])) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($opportunity['next_action'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong><i class="fas fa-tasks me-2"></i>Próxima Ação:</strong>
                                        <?= htmlspecialchars($opportunity['next_action']) ?>
                                        <?php if (!empty($opportunity['next_action_date'])): ?>
                                            <br><small>Data: <?= date('d/m/Y', strtotime($opportunity['next_action_date'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Campos Customizáveis -->
                            <?php if (!empty($this->data['custom_fields'])): ?>
                                <div class="mt-4 pt-3 border-top">
                                    <?php
                                    $customFieldValues = $this->data['custom_field_values'] ?? [];
                                    echo CrmCustomFieldsHelper::displayFields($this->data['custom_fields'], $customFieldValues);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar com Resumo -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Resumo</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Criado em</small>
                                <div><?= date('d/m/Y H:i', strtotime($opportunity['created_at'])) ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Última atualização</small>
                                <div><?= $opportunity['updated_at'] ? date('d/m/Y H:i', strtotime($opportunity['updated_at'])) : 'N/A' ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Tempo na etapa atual</small>
                                <div>
                                    <?php
                                    if ($opportunity['stage_entered_at']) {
                                        $days = (new DateTime())->diff(new DateTime($opportunity['stage_entered_at']))->days;
                                        echo $days . ' dia(s)';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (!empty($opportunity['source'])): ?>
                            <div>
                                <small class="text-muted">Origem</small>
                                <div><?= htmlspecialchars($opportunity['source']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="opportunityTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#history-tab" type="button">
                        <i class="fas fa-history me-1"></i> Histórico de Etapas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activities-tab" type="button">
                        <i class="fas fa-tasks me-1"></i> Atividades (<?= count($activities) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-tab" type="button">
                        <i class="fas fa-sticky-note me-1"></i> Notas (<?= count($notes) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents-tab" type="button">
                        <i class="fas fa-file-alt me-1"></i> Documentos (<?= count($documents) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-3" id="opportunityTabsContent">
                <!-- Histórico de Etapas -->
                <div class="tab-pane fade show active" id="history-tab" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($stageHistory)): ?>
                                <div class="timeline">
                                    <?php foreach ($stageHistory as $history): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <div class="rounded-circle p-2" style="background-color: <?= htmlspecialchars($history['to_stage_color'] ?? '#6c757d') ?>; width: 40px; height: 40px;">
                                                        <i class="fas fa-arrow-right text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <strong>
                                                                <?php if ($history['from_stage_name']): ?>
                                                                    <span class="badge" style="background-color: <?= htmlspecialchars($history['from_stage_color']) ?>">
                                                                        <?= htmlspecialchars($history['from_stage_name']) ?>
                                                                    </span>
                                                                    →
                                                                <?php endif; ?>
                                                                <span class="badge" style="background-color: <?= htmlspecialchars($history['to_stage_color']) ?>">
                                                                    <?= htmlspecialchars($history['to_stage_name']) ?>
                                                                </span>
                                                            </strong>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y H:i', strtotime($history['moved_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <div class="mt-1">
                                                        <small>
                                                            Por: <?= htmlspecialchars($history['moved_by_name']) ?>
                                                            <?php if ($history['days_in_stage'] > 0): ?>
                                                                | Permaneceu <?= $history['days_in_stage'] ?> dia(s) na etapa anterior
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Nenhum histórico de movimentação encontrado.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Atividades -->
                <div class="tab-pane fade" id="activities-tab" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-tasks me-2"></i>Atividades</h6>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewActivity">
                                <i class="fas fa-plus me-1"></i>Nova Atividade
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($activities)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-green">
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Título</th>
                                                <th>Agendado para</th>
                                                <th>Status</th>
                                                <th>Responsável</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-<?= $activity['type'] === 'Ligação' ? 'phone' : ($activity['type'] === 'Reunião' ? 'users' : ($activity['type'] === 'E-mail' ? 'envelope' : 'tasks')) ?> me-1"></i>
                                                        <?= htmlspecialchars($activity['type']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['title']) ?></td>
                                                    <td><?= $activity['scheduled_date'] ? date('d/m/Y H:i', strtotime($activity['scheduled_date'])) : 'N/A' ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $activity['status'] === 'Concluída' ? 'success' : ($activity['status'] === 'Pendente' ? 'warning' : 'secondary') ?>">
                                                            <?= htmlspecialchars($activity['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['responsible_name']) ?></td>
                                                    <td class="text-center">
                                                        <?php if ($activity['status'] === 'Pendente'): ?>
                                                            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-complete-activity/<?= $activity['id'] ?>" style="display:inline;">
                                                                <button type="submit" class="btn btn-sm btn-success" title="Marcar como concluída">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-activity/<?= $activity['id'] ?>" 
                                                           class="btn btn-sm btn-danger" title="Excluir"
                                                           onclick="return confirm('Tem certeza que deseja excluir esta atividade?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Nenhuma atividade encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="tab-pane fade" id="notes-tab" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notas</h6>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewNote">
                                <i class="fas fa-plus me-1"></i>Nova Nota
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($notes)): ?>
                                <?php foreach ($notes as $note): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?= htmlspecialchars($note['created_by_name'] ?? 'Usuário') ?></strong>
                                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></small>
                                                    </div>
                                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                                </div>
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-note/<?= $note['id'] ?>" 
                                                   class="btn btn-sm btn-danger ms-2" title="Excluir"
                                                   onclick="return confirm('Tem certeza que deseja excluir esta nota?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">Nenhuma nota encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Documentos -->
                <div class="tab-pane fade" id="documents-tab" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Documentos</h6>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalUploadDocument">
                                <i class="fas fa-upload me-1"></i>Upload Documento
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($documents)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-green">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Tipo</th>
                                                <th>Tamanho</th>
                                                <th>Enviado em</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td><i class="fas fa-file me-1"></i> <?= htmlspecialchars($doc['file_name']) ?></td>
                                                    <td><?= htmlspecialchars($doc['file_type'] ?? 'N/A') ?></td>
                                                    <td><?= isset($doc['file_size']) ? round($doc['file_size'] / 1024, 2) . ' KB' : 'N/A' ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($doc['uploaded_at'])) ?></td>
                                                    <td class="text-center">
                                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-download-document/<?= $doc['id'] ?>" 
                                                           class="btn btn-sm btn-primary" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-document/<?= $doc['id'] ?>" 
                                                           class="btn btn-sm btn-danger" title="Excluir"
                                                           onclick="return confirm('Tem certeza que deseja excluir este documento?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Nenhum documento encontrado.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

<?php
// Incluir modais
include('./app/adms/Views/crm/opportunities/modals.php');
?>

<!-- Modal de Confirmação de Conflito de Horário -->
<?php if (isset($_SESSION['schedule_conflict'])): ?>
    <?php 
    $conflictData = $_SESSION['schedule_conflict'];
    $conflicts = $conflictData['conflicts'];
    $pendingData = $conflictData['pending_data'];
    unset($_SESSION['schedule_conflict']); // Limpar após exibir
    ?>
    <div class="modal fade show" id="modalScheduleConflict" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Conflito de Horário Detectado!
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeConflictModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <strong>⚠️ Atenção!</strong> Já existe(m) atividade(s) agendada(s) para 
                        <strong><?= htmlspecialchars($conflicts[0]['responsible_name'] ?? 'este usuário') ?></strong> 
                        neste horário:
                    </div>
                    
                    <div class="list-group mb-3">
                        <?php foreach ($conflicts as $conflict): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <i class="fas fa-calendar-check text-danger me-1"></i>
                                            <?= htmlspecialchars($conflict['title']) ?>
                                        </h6>
                                        <div class="mt-1">
                                            <?php if (!empty($conflict['partner_name'])): ?>
                                                <small class="text-muted me-2">
                                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($conflict['partner_name']) ?>
                                                </small>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($conflict['responsible_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger">
                                            <i class="fas fa-clock me-1"></i><?= $conflict['start'] ?> - <?= $conflict['end'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>O que deseja fazer?</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Cancelar:</strong> Não criar a atividade</li>
                            <li><strong>Continuar:</strong> Agendar mesmo com conflito</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeConflictModal()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-create-activity" style="display: inline;">
                        <?php foreach ($pendingData as $key => $value): ?>
                            <?php if (is_array($value)): ?>
                                <?php foreach ($value as $subKey => $subValue): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key) ?>[<?= htmlspecialchars($subKey) ?>]" value="<?= htmlspecialchars($subValue ?? '') ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value ?? '') ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="force_schedule" value="1">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-check me-1"></i>Continuar Mesmo Assim
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function closeConflictModal() {
        document.getElementById('modalScheduleConflict').style.display = 'none';
    }
    
    // Auto-mostrar modal ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalScheduleConflict');
        if (modal) {
            modal.style.display = 'block';
        }
    });
    </script>
<?php endif; ?>

<!-- Modal WhatsApp -->
<?php 
    $partnerMobile = $opportunity['partner_mobile'] ?? '';
    $partnerPhone = $opportunity['partner_phone'] ?? '';
?>
<div class="modal fade" id="modalWhatsApp" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-send-whatsapp">
                <div class="modal-header" style="background-color: #25D366; color: white;">
                    <h5 class="modal-title"><i class="fab fa-whatsapp me-2"></i>Enviar WhatsApp</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="redirect_to" value="crm-view-opportunity/<?= $opportunity['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Número</label>
                        <select name="phone_number" class="form-select" required>
                            <?php if (!empty($partnerMobile)): ?>
                                <option value="<?= htmlspecialchars($partnerMobile) ?>">📱 <?= htmlspecialchars($partnerMobile) ?> (Celular)</option>
                            <?php endif; ?>
                            <?php if (!empty($partnerPhone)): ?>
                                <option value="<?= htmlspecialchars($partnerPhone) ?>">☎️ <?= htmlspecialchars($partnerPhone) ?> (Telefone)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensagem *</label>
                        <textarea name="message" class="form-control" rows="6" required
                                  placeholder="Digite sua mensagem...">Olá <?= htmlspecialchars($opportunity['partner_name'] ?? '') ?>!

Tenho uma atualização sobre a oportunidade "<?= htmlspecialchars($opportunity['title'] ?? '') ?>".
                        </textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" style="background-color: #25D366; color: white;">
                        <i class="fab fa-whatsapp me-1"></i>Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


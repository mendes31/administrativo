<?php
// Modais para Atividades, Notas e Documentos
$opportunityId = $this->data['opportunity']['id'] ?? null;
$partnerId = $this->data['opportunity']['partner_id'] ?? null;
?>

<!-- Modal: Nova Atividade -->
<div class="modal fade" id="modalNewActivity" tabindex="-1" aria-labelledby="modalNewActivityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-create-activity">
                <div class="modal-header" style="background-color: #2E9263; color: white;">
                    <h5 class="modal-title" id="modalNewActivityLabel">
                        <i class="fas fa-tasks me-2"></i>Nova Atividade
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="opportunity_id" value="<?= $opportunityId ?>">
                    <input type="hidden" name="partner_id" value="<?= $partnerId ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Atividade *</label>
                            <select name="type" class="form-select" required>
                                <option value="Ligação">📞 Ligação</option>
                                <option value="Reunião">👥 Reunião</option>
                                <option value="E-mail">✉️ E-mail</option>
                                <option value="Tarefa" selected>✅ Tarefa</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prioridade</label>
                            <select name="priority" class="form-select">
                                <option value="Baixa">Baixa</option>
                                <option value="Média" selected>Média</option>
                                <option value="Alta">Alta</option>
                                <option value="Urgente">Urgente</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="Ex: Ligar para confirmar reunião">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Detalhes adicionais..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Data e Hora Agendada</label>
                            <input type="datetime-local" name="scheduled_date" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duração (minutos)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="1" 
                                   placeholder="30" value="30">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lembrete (Data/Hora)</label>
                        <input type="datetime-local" name="reminder_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Salvar Atividade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nova Nota -->
<div class="modal fade" id="modalNewNote" tabindex="-1" aria-labelledby="modalNewNoteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-create-note" id="formNewNote">
                <div class="modal-header" style="background-color: #2E9263; color: white;">
                    <h5 class="modal-title" id="modalNewNoteLabel">
                        <i class="fas fa-sticky-note me-2"></i>Nova Nota
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="opportunity_id" value="<?= $opportunityId ?>">
                    <input type="hidden" name="partner_id" value="<?= $partnerId ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Conteúdo da Nota *</label>
                        <textarea name="content" class="form-control" rows="5" required 
                                  placeholder="Digite sua nota aqui..."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_important" id="is_important" value="1">
                        <label class="form-check-label" for="is_important">
                            ⭐ Marcar como importante
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Salvar Nota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Upload Documento -->
<div class="modal fade" id="modalUploadDocument" tabindex="-1" aria-labelledby="modalUploadDocumentLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-upload-document" enctype="multipart/form-data">
                <div class="modal-header" style="background-color: #2E9263; color: white;">
                    <h5 class="modal-title" id="modalUploadDocumentLabel">
                        <i class="fas fa-upload me-2"></i>Upload de Documento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="opportunity_id" value="<?= $opportunityId ?>">
                    <input type="hidden" name="partner_id" value="<?= $partnerId ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Selecionar Arquivo *</label>
                        <input type="file" name="document" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                        <div class="form-text">
                            Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, RAR<br>
                            Tamanho máximo: 10MB
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Descreva o documento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Fazer Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


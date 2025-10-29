<?php
$partner = $this->data['partner'];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($partner['name']); ?>
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners">Parceiros</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <!-- Header do Parceiro -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-3">
                        <?php echo htmlspecialchars($partner['name']); ?>
                        <span class="badge bg-<?php echo $partner['partner_type'] == 'Cliente' ? 'success' : ($partner['partner_type'] == 'Lead' ? 'primary' : 'secondary'); ?>">
                            <?php echo $partner['partner_type']; ?>
                        </span>
                    </h4>
                    <p class="text-muted mb-2">
                        <i class="fas fa-tag me-2"></i><?php echo $partner['segment']; ?> • 
                        <i class="fas fa-code me-2"></i><?php echo $partner['code']; ?>
                    </p>
                    <?php if ($partner['trading_name']): ?>
                        <p class="mb-2"><strong>Nome Fantasia:</strong> <?php echo htmlspecialchars($partner['trading_name']); ?></p>
                    <?php endif; ?>
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($partner['email'] ?? '-'); ?> •
                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($partner['phone'] ?? $partner['mobile'] ?? '-'); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (!empty($partner['mobile']) || !empty($partner['phone'])): ?>
                        <button type="button" class="btn btn-success mb-2 me-2" data-bs-toggle="modal" data-bs-target="#modalWhatsApp">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-update-partner/<?php echo $partner['id']; ?>" 
                       class="btn btn-warning mb-2 me-2">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners" class="btn btn-secondary mb-2">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Informações -->
    <ul class="nav nav-tabs mb-3" id="partnerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button">
                <i class="fas fa-info-circle me-1"></i>Dados
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="oportunidades-tab" data-bs-toggle="tab" data-bs-target="#oportunidades" type="button">
                <i class="fas fa-handshake me-1"></i>Oportunidades 
                <span class="badge bg-primary"><?php echo count($this->data['opportunities'] ?? []); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="atividades-tab" data-bs-toggle="tab" data-bs-target="#atividades" type="button">
                <i class="fas fa-tasks me-1"></i>Atividades
                <span class="badge bg-success"><?php echo count($this->data['activities'] ?? []); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notas-tab" data-bs-toggle="tab" data-bs-target="#notas" type="button">
                <i class="fas fa-sticky-note me-1"></i>Notas
                <span class="badge bg-warning"><?php echo count($this->data['notes'] ?? []); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button">
                <i class="fas fa-file me-1"></i>Documentos
                <span class="badge bg-info"><?php echo count($this->data['documents'] ?? []); ?></span>
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Tabs -->
    <div class="tab-content" id="partnerTabsContent">
        
        <!-- Tab: Dados -->
        <div class="tab-pane fade show active" id="dados" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Informações Básicas</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Código:</th>
                                    <td><?php echo $partner['code']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nome/Razão Social:</th>
                                    <td><?php echo htmlspecialchars($partner['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Nome Fantasia:</th>
                                    <td><?php echo htmlspecialchars($partner['trading_name'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td><?php echo $partner['type_person'] == 'PJ' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?></td>
                                </tr>
                                <tr>
                                    <th>CPF/CNPJ:</th>
                                    <td><?php echo htmlspecialchars($partner['document'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($partner['email'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Telefone:</th>
                                    <td><?php echo htmlspecialchars($partner['phone'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Celular:</th>
                                    <td><?php echo htmlspecialchars($partner['mobile'] ?? '-'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Classificação</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Segmento:</th>
                                    <td><span class="badge bg-info"><?php echo $partner['segment']; ?></span></td>
                                </tr>
                                <tr>
                                    <th>Tipo de Parceiro:</th>
                                    <td><?php echo $partner['partner_type']; ?></td>
                                </tr>
                                <tr>
                                    <th>Prioridade:</th>
                                    <td><?php echo $partner['priority'] ?? '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="badge bg-success"><?php echo $partner['status']; ?></span></td>
                                </tr>
                                <tr>
                                    <th>Responsável:</th>
                                    <td><?php echo htmlspecialchars($partner['responsible_name'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Departamento:</th>
                                    <td><?php echo htmlspecialchars($partner['department_name'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Receita Estimada:</th>
                                    <td class="text-success fw-bold">R$ <?php echo number_format($partner['estimated_revenue'] ?? 0, 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <th>Cadastrado em:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($partner['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Oportunidades -->
        <div class="tab-pane fade" id="oportunidades" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Oportunidades</h5>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-create-opportunity?partner_id=<?php echo $partner['id']; ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Nova Oportunidade
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['opportunities'])): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Nenhuma oportunidade cadastrada
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                            <thead class="thead-green">
                                <tr>
                                    <th>Código</th>
                                    <th>Título</th>
                                    <th>Etapa</th>
                                    <th>Valor</th>
                                    <th>Probabilidade</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                                <tbody>
                                    <?php foreach ($this->data['opportunities'] as $opp): ?>
                                        <tr>
                                            <td><code><?php echo $opp['code']; ?></code></td>
                                            <td><?php echo htmlspecialchars($opp['title']); ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $opp['stage_color']; ?>;">
                                                    <?php echo $opp['stage_name']; ?>
                                                </span>
                                            </td>
                                            <td class="text-success fw-bold">R$ <?php echo number_format($opp['value'], 2, ',', '.'); ?></td>
                                            <td><?php echo $opp['probability']; ?>%</td>
                                            <td><?php echo $opp['status']; ?></td>
                                            <td class="text-center">
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-view-opportunity/<?= $opp['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-update-opportunity/<?= $opp['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Atividades -->
        <div class="tab-pane fade" id="atividades" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Atividades</h5>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewActivity">
                        <i class="fas fa-plus me-1"></i>Nova Atividade
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['activities'])): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-clipboard fa-3x mb-3 d-block"></i>
                            Nenhuma atividade registrada
                        </p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($this->data['activities'] as $activity): ?>
                                <div class="timeline-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex">
                                        <div class="timeline-marker me-3">
                                            <i class="fas fa-<?php echo $activity['type'] == 'Ligação' ? 'phone' : ($activity['type'] == 'Email' ? 'envelope' : 'calendar'); ?> text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                            <p class="text-muted small mb-1">
                                                <?php echo htmlspecialchars($activity['description'] ?? ''); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo $activity['responsible_name']; ?> •
                                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($activity['scheduled_date'])); ?> •
                                                <span class="badge bg-<?php echo $activity['status'] == 'Concluída' ? 'success' : 'warning'; ?>">
                                                    <?php echo $activity['status']; ?>
                                                </span>
                                            </small>
                                        </div>
                                        <div class="ms-2">
                                            <?php if ($activity['status'] === 'Pendente'): ?>
                                                <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-complete-activity/<?= $activity['id'] ?>" style="display:inline;">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Concluir">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-activity/<?= $activity['id'] ?>" 
                                               class="btn btn-sm btn-danger" title="Excluir"
                                               onclick="return confirm('Tem certeza que deseja excluir esta atividade?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Notas -->
        <div class="tab-pane fade" id="notas" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notas</h5>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewNote">
                        <i class="fas fa-plus me-1"></i>Nova Nota
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['notes'])): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-sticky-note fa-3x mb-3 d-block"></i>
                            Nenhuma nota cadastrada
                        </p>
                    <?php else: ?>
                        <?php foreach ($this->data['notes'] as $note): ?>
                            <div class="alert alert-light border mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <?php if ($note['is_pinned']): ?>
                                            <i class="fas fa-thumbtack text-warning me-2"></i>
                                        <?php endif; ?>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo $note['created_by_name']; ?> •
                                            <i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?>
                                        </small>
                                    </div>
                                    <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-note/<?= $note['id'] ?>" 
                                       class="btn btn-sm btn-danger" title="Excluir"
                                       onclick="return confirm('Tem certeza que deseja excluir esta nota?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Documentos -->
        <div class="tab-pane fade" id="documentos" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Documentos</h5>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalUploadDocument">
                        <i class="fas fa-upload me-1"></i>Upload
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['documents'])): ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-file fa-3x mb-3 d-block"></i>
                            Nenhum documento cadastrado
                        </p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($this->data['documents'] as $doc): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-<?php echo strpos($doc['file_type'] ?? '', 'pdf') !== false ? 'pdf' : 'alt'; ?> me-2"></i>
                                        <strong><?php echo htmlspecialchars($doc['file_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Enviado por <?php echo $doc['uploaded_by_name']; ?> em <?php echo date('d/m/Y', strtotime($doc['uploaded_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-download-document/<?= $doc['id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-document/<?= $doc['id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir este documento?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
// Incluir modais (mesmos de oportunidades, pois compartilham a estrutura)
$this->data['opportunity'] = ['id' => null, 'partner_id' => $partner['id']];
include('./app/adms/Views/crm/opportunities/modals.php');
?>

<!-- Modal WhatsApp -->
<div class="modal fade" id="modalWhatsApp" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-send-whatsapp">
                <div class="modal-header" style="background-color: #25D366; color: white;">
                    <h5 class="modal-title"><i class="fab fa-whatsapp me-2"></i>Enviar WhatsApp</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="redirect_to" value="crm-view-partner/<?= $partner['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Número</label>
                        <select name="phone_number" class="form-select" required>
                            <?php if (!empty($partner['mobile'])): ?>
                                <option value="<?= htmlspecialchars($partner['mobile']) ?>">
                                    📱 <?= htmlspecialchars($partner['mobile']) ?> (Celular)
                                </option>
                            <?php endif; ?>
                            <?php if (!empty($partner['phone'])): ?>
                                <option value="<?= htmlspecialchars($partner['phone']) ?>">
                                    ☎️ <?= htmlspecialchars($partner['phone']) ?> (Telefone)
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mensagem *</label>
                        <textarea name="message" class="form-control" rows="6" required 
                                  placeholder="Digite sua mensagem...">Olá <?= htmlspecialchars($partner['name']) ?>!

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


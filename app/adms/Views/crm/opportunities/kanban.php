<?php
use App\adms\Helpers\FormatHelper;
?>

<link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/crm/kanban.css">

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Header com título e filtros -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-chart-line me-2"></i>Pipeline de Vendas
            </h2>
            <p class="text-muted mb-0">
                Valor total do pipeline: <strong class="text-success">R$ <?php echo number_format($this->data['total_pipeline_value'], 2, ',', '.'); ?></strong>
            </p>
        </div>
        
        <?php if (in_array('CrmCreateOpportunity', $this->data['buttonPermission'] ?? [])): ?>
        <a href="<?php echo $_ENV['URL_ADM']; ?>crm-create-opportunity" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Nova Oportunidade
        </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>crm-kanban-pipeline" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pesquisar</label>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Nome, parceiro..."
                           value="<?php echo htmlspecialchars($this->data['filters']['search'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Responsável</label>
                    <select name="responsible_user_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['users'] as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo ($this->data['filters']['responsible_user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-kanban-pipeline" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Pipeline Kanban -->
    <div class="kanban-container">
        <div class="kanban-board">
            
            <?php foreach ($this->data['stages'] as $stage): ?>
            <div class="kanban-column" data-stage-id="<?php echo $stage['id']; ?>">
                
                <!-- Header da Coluna -->
                <div class="kanban-column-header" style="background-color: <?php echo $stage['color']; ?>;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <?php echo htmlspecialchars($stage['name']); ?>
                        </h5>
                        <span class="badge bg-white text-dark">
                            <?php echo $stage['count']; ?>
                        </span>
                    </div>
                    <div class="text-white-50 small mt-1">
                        R$ <?php echo number_format($stage['total_value'], 2, ',', '.'); ?>
                    </div>
                </div>

                <!-- Cards da Coluna -->
                <div class="kanban-column-body" data-stage-id="<?php echo $stage['id']; ?>">
                    
                    <?php if (empty($stage['opportunities'])): ?>
                        <div class="kanban-empty-state">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">Nenhuma oportunidade</p>
                        </div>
                    <?php else: ?>
                        
                        <?php foreach ($stage['opportunities'] as $opp): ?>
                        <div class="kanban-card" 
                             data-opportunity-id="<?php echo $opp['id']; ?>"
                             onclick="window.location.href='<?= $_ENV['URL_ADM'] ?>crm-view-opportunity/<?= $opp['id'] ?>'"
                             style="cursor: pointer;"
                             title="Clique para visualizar detalhes">
                            
                            <!-- Header do Card -->
                            <div class="kanban-card-header">
                                <span class="kanban-card-partner">
                                    <?php echo htmlspecialchars($opp['partner_name']); ?>
                                </span>
                                <span class="kanban-card-probability">
                                    <?php echo $opp['probability']; ?>%
                                </span>
                            </div>

                            <!-- Corpo do Card -->
                            <div class="kanban-card-body">
                                <h6 class="kanban-card-title">
                                    <?php echo htmlspecialchars($opp['title']); ?>
                                </h6>
                                
                                <div class="kanban-card-value">
                                    R$ <?php echo number_format($opp['value'], 2, ',', '.'); ?>
                                </div>
                                
                                <div class="kanban-card-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($opp['responsible_name']); ?>
                                    </small>
                                </div>
                                
                                <?php if ($opp['days_in_stage'] > 0): ?>
                                <div class="kanban-card-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i><?php echo $opp['days_in_stage']; ?> dias nesta etapa
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($opp['next_action']): ?>
                                <div class="kanban-card-action">
                                    <i class="fas fa-tasks me-1"></i><?php echo htmlspecialchars($opp['next_action']); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Footer do Card -->
                            <div class="kanban-card-footer">
                                <span class="badge" style="background-color: <?php echo $stage['color']; ?>;">
                                    <?php echo htmlspecialchars($opp['segment'] ?? 'Sem segmento'); ?>
                                </span>
                            </div>
                            
                        </div>
                        <?php endforeach; ?>
                        
                    <?php endif; ?>
                    
                </div>

                <!-- Footer da Coluna -->
                <div class="kanban-column-footer">
                    <button class="btn btn-sm btn-light w-100" 
                            onclick="window.location.href='<?php echo $_ENV['URL_ADM']; ?>crm-create-opportunity'">
                        <i class="fas fa-plus me-1"></i>Adicionar oportunidade
                    </button>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
    </div>

</div>

<!-- JavaScript do Kanban -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/crm/kanban.js"></script>


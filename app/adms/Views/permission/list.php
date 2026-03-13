<?php
// Não precisa definir $urlAdm, usar diretamente $_ENV['URL_ADM']
?>

<!-- CSS separado para permissões -->
<link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>css/permission-list.css?v=<?php echo time(); ?>">

<div class="container-fluid px-4">
    
    <!-- Cabeçalho da página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-0 me-3">Permissões</h2>
        </div>
        
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-access-levels" class="text-decoration-none">Níveis de Acesso</a>
                    </li>
                    <li class="breadcrumb-item active">Permissões</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Badge do nível de acesso -->
    <div class="mb-3">
        <span class="badge bg-primary fs-6">
            <i class="fas fa-shield-alt"></i> 
            <?php echo htmlspecialchars($this->data['accessLevel']['name'] ?? 'Nível de Acesso'); ?>
        </span>
    </div>

    <!-- Barra de controles -->




        <!-- PAINEL DE CONTROLE - DENTRO da área de conteúdo -->
    <div class="control-panel mb-4">
        <div class="control-panel-content">
            <!-- Desktop Layout -->
            <div class="d-none d-md-block">
                <div class="row g-3 align-items-center">
                    <!-- Busca -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0"
                                   id="searchGroup"
                                   placeholder="Buscar por grupo..."
                                   onkeyup="filterGroups(this.value)">
                        </div>
                    </div>

                    <!-- Botões de controle -->
                    <div class="col-md-4 text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-success btn-sm btn-group-action" id="expandAllBtn" onclick="expandAllGroups()">
                                <i class="fas fa-expand-alt"></i> Expandir Todos
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm btn-group-action" id="collapseAllBtn" onclick="collapseAllGroups()">
                                <i class="fas fa-compress-alt"></i> Colapsar Todos
                            </button>
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a target="_blank" href="<?php echo $_ENV['URL_ADM']; ?>export-access-level-permissions-pdf/<?php echo $this->data['accessLevel']['id'] ?? 0; ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                            </a>
                            <button type="button" class="btn btn-primary btn-sm" id="savePermissionsBtn">
                                <i class="fas fa-save me-1"></i> Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Layout -->
            <div class="d-block d-md-none">
                <div class="row g-2">
                    <!-- Busca Mobile -->
                    <div class="col-12 mb-2">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0"
                                   id="searchGroupMobile"
                                   placeholder="Buscar por grupo..."
                                   onkeyup="filterGroups(this.value)">
                        </div>
                    </div>

                    <!-- Botões de controle Mobile -->
                    <div class="col-6">
                        <button type="button" class="btn btn-success btn-sm w-100 btn-group-action" id="expandAllBtnMobile" onclick="expandAllGroups()">
                            <i class="fas fa-expand-alt"></i> Expandir
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-secondary btn-sm w-100 btn-group-action" id="collapseAllBtnMobile" onclick="collapseAllGroups()">
                            <i class="fas fa-compress-alt"></i> Colapsar
                        </button>
                    </div>

                    <!-- Botões de ação Mobile -->
                    <div class="col-6">
                        <a target="_blank" href="<?php echo $_ENV['URL_ADM']; ?>export-access-level-permissions-pdf/<?php echo $this->data['accessLevel']['id'] ?? 0; ?>" class="btn btn-outline-success btn-sm w-100">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="savePermissionsBtnMobile">
                            <i class="fas fa-save me-1"></i> Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de permissões -->
    <form id="permissionsForm" method="POST" action="<?php echo $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . ($this->data['accessLevel']['id'] ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $this->data['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="adms_access_level_id" value="<?php echo ($this->data['accessLevel']['id'] ?? ''); ?>">
        
        <?php
        if (!empty($this->data['pages'] ?? [])) {
            // Agrupar páginas por grupo
            $groupedPages = [];
            foreach ($this->data['pages'] as $page) {
                $groupId = $page['agp_name'] ?? 'Sem Grupo';
                if (!isset($groupedPages[$groupId])) {
                    $groupedPages[$groupId] = [];
                }
                $groupedPages[$groupId][] = $page;
            }
            
            // Ordenar grupos alfabeticamente
            ksort($groupedPages);
            
            // Debug: Verificar se há duplicação nos dados
            foreach ($groupedPages as $groupId => $pages) {
                $pageIds = array_column($pages, 'id');
                $uniqueIds = array_unique($pageIds);
                $duplicateIds = array_diff_assoc($pageIds, array_unique($pageIds));
                
                if (!empty($duplicateIds)) {
                    echo "<!-- DEBUG DUPLICADOS: Grupo '$groupId' tem IDs duplicados: " . implode(', ', $duplicateIds) . " -->";
                }
                
                echo "<!-- DEBUG GRUPO: '$groupId' - Total: " . count($pages) . ", Únicos: " . count($uniqueIds) . " -->";
            }
        ?>
        
        <!-- Botões de ação fixos abaixo do cabeçalho da página (dentro do formulário) -->
        <!-- Removido bloco duplicado de ações para evitar 2 botões Exportar PDF -->

        <!-- DESKTOP: Tabela responsiva -->
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover mb-4 table-permissions-desktop">
                                            <thead class="table-light">
                            <tr>
                                <th style="width: 20%;">Status</th>
                                <th style="width: 15%;">ID</th>
                                <th style="width: 30%;">Nome da Página</th>
                                <th style="width: 20%;">Observação</th>
                                <th style="width: 15%;">Tipo</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php
                        foreach ($groupedPages as $groupId => $pages) {
                            // Calcular contadores do grupo
                            $totalCount = count($pages);
                            $authorizedCount = 0;
                            
                            foreach ($pages as $page) {
                                if (isset(($this->data['accessLevelsPages'] ?? [])[$page['id']])) {
                                    $authorizedCount++;
                                }
                            }
                            $revokedCount = $totalCount - $authorizedCount;
                            
                            // Cabeçalho do grupo
                            echo '<tr class="group-header" data-group="' . htmlspecialchars($groupId) . '">';
                            echo '<td colspan="5" class="group-header-cell">';
                            echo '<div class="d-flex align-items-center justify-content-between">';
                            echo '<div class="d-flex align-items-center" onclick="toggleGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-chevron-right toggle-icon me-3 text-primary"></i>';
                            echo '<h6 class="mb-0 fw-bold text-primary group-name">' . htmlspecialchars($groupId) . '</h6>';
                            echo '</div>';
                            echo '<div class="d-flex align-items-center gap-3">';
                            echo '<span class="badge bg-info">Grupo</span>';
                            echo '<div class="group-counters">';
                            echo '<span class="badge bg-secondary me-2">Total: ' . $totalCount . '</span>';
                            echo '<span class="badge bg-success me-2">Autorizadas: ' . $authorizedCount . '</span>';
                            echo '<span class="badge bg-danger">Revogadas: ' . $revokedCount . '</span>';
                            echo '</div>';
                            echo '<div class="group-actions">';
                            echo '<button type="button" class="btn btn-success btn-sm me-2 btn-group-action" onclick="event.stopPropagation(); authorizeGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-check-double"></i> Autorizar Grupo';
                            echo '</button>';
                            echo '<button type="button" class="btn btn-danger btn-sm btn-group-action" onclick="event.stopPropagation(); revokeGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-times"></i> Revogar Grupo';
                            echo '</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                            
                            // Linhas de permissões do grupo (inicialmente ocultas)
                            foreach ($pages as $page) {
                                $isAllowed = isset(($this->data['accessLevelsPages'] ?? [])[$page['id']]);
                                $isPrivate = ($page['public_page'] ?? 0) == 0;
                                
                                echo '<tr class="group-content-row" data-group-content="' . htmlspecialchars($groupId) . '" style="display: none;">';
                                echo '<td class="align-middle">';
                                echo '<div class="form-check form-switch d-flex justify-content-center">';
                                echo '<input class="form-check-input permission-toggle" type="checkbox" ';
                                echo 'name="permissions[' . $page['id'] . ']" value="1" ';
                                echo 'id="permission_' . $page['id'] . '" ';
                                echo 'data-page-id="' . $page['id'] . '" ';
                                echo 'data-group="' . htmlspecialchars($groupId) . '"';
                                if ($isAllowed) echo ' checked';
                                echo ' onchange="updateGroupCounters(\'' . htmlspecialchars($groupId) . '\')">';
                                echo '</div>';
                                echo '</td>';
                                echo '<td class="align-middle text-center">';
                                echo '<code class="text-muted">' . $page['id'] . '</code>';
                                echo '</td>';
                                echo '<td class="align-middle">';
                                echo '<strong>' . htmlspecialchars($page['name'] ?? '') . '</strong>';
                                echo '</td>';
                                echo '<td class="align-middle">';
                                if (!empty($page['obs'])) {
                                    echo '<span class="text-muted">' . htmlspecialchars($page['obs']) . '</span>';
                                } else {
                                    echo '<span class="text-muted fst-italic">Sem observação</span>';
                                }
                                echo '</td>';
                                echo '<td class="align-middle text-center">';
                                if ($isPrivate) {
                                    echo '<span class="badge bg-danger"><i class="fas fa-lock"></i> Privada</span>';
                                } else {
                                    echo '<span class="badge bg-success"><i class="fas fa-unlock"></i> Pública</span>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MOBILE: Cards responsivos -->
        <div class="d-block d-md-none">
            <?php
            foreach ($groupedPages as $groupId => $pages) {
                // Debug: Verificar execução do loop mobile
                echo "<!-- DEBUG LOOP MOBILE: Executando loop para grupo '$groupId' -->";
                
                // Calcular contadores do grupo
                $totalCount = count($pages);
                $authorizedCount = 0;
                
                foreach ($pages as $page) {
                    if (isset(($this->data['accessLevelsPages'] ?? [])[$page['id']])) {
                        $authorizedCount++;
                    }
                }
                
                $revokedCount = $totalCount - $authorizedCount;
            ?>
                <div class="card mb-3 shadow-sm group-card" data-group="<?= htmlspecialchars($groupId) ?>">
                    <div class="card-header bg-light">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center" onclick="toggleGroupMobile('<?= htmlspecialchars($groupId) ?>')">
                                <i class="fas fa-chevron-right toggle-icon-mobile me-2 text-primary"></i>
                                <h6 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($groupId) ?></h6>
                            </div>
                            <span class="badge bg-info">Grupo</span>
                        </div>
                        
                        <div class="mt-2">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-secondary">Total: <?= $totalCount ?></span>
                                <span class="badge bg-success">Autorizadas: <?= $authorizedCount ?></span>
                                <span class="badge bg-danger">Revogadas: <?= $revokedCount ?></span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm btn-group-action" onclick="authorizeGroup('<?= htmlspecialchars($groupId) ?>')">
                                    <i class="fas fa-check-double"></i> Autorizar Grupo
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-group-action" onclick="revokeGroup('<?= htmlspecialchars($groupId) ?>')">
                                    <i class="fas fa-times"></i> Revogar Grupo
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body group-content-mobile" data-group-content="<?= htmlspecialchars($groupId) ?>" style="display: none;">
                        <div class="row">
                            <?php foreach ($pages as $page) { 
                                $isAllowed = isset(($this->data['accessLevelsPages'] ?? [])[$page['id']]);
                                $isPrivate = ($page['public_page'] ?? 0) == 0;
                            ?>
                                <div class="col-12 mb-3">
                                    <div class="card border-light shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($page['name'] ?? '') ?></h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input permission-toggle" type="checkbox" 
                                                           name="permissions[<?= $page['id'] ?>]" value="1" 
                                                           id="permission_mobile_<?= $page['id'] ?>" 
                                                           data-page-id="<?= $page['id'] ?>" 
                                                           data-group="<?= htmlspecialchars($groupId) ?>"
                                                           <?= $isAllowed ? 'checked' : '' ?>
                                                           onchange="updateGroupCounters('<?= htmlspecialchars($groupId) ?>')">
                                                </div>
                                            </div>
                                            
                                            <div class="row text-muted small">
                                                <div class="col-6">
                                                    <strong>ID:</strong> <?= $page['id'] ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <?php if ($isPrivate): ?>
                                                        <span class="badge bg-danger"><i class="fas fa-lock"></i> Privada</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><i class="fas fa-unlock"></i> Pública</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($page['obs'])): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted"><?= htmlspecialchars($page['obs']) ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-2">
                                                <span class="text-muted small">Ações não disponíveis</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php } else { ?>
            <div class="text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                    Nenhuma página encontrada para configurar permissões.
                </div>
            </div>
        <?php } ?>
        
        <!-- Espaçamento para o cabeçalho fixo -->
        <div class="py-4"></div>
    </form>
</div>

<!-- CSS para suavizar os botões -->
<style>
/* ===== BOTÕES SUAVIZADOS ===== */

/* Botões de grupo - Desktop */
.group-actions .btn-group-action {
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    font-weight: 500 !important;
    letter-spacing: 0.025em !important;
    position: relative !important;
    overflow: hidden !important;
}

.group-actions .btn-group-action:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
}

.group-actions .btn-group-action:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
}

/* Botão Autorizar Grupo - Desktop */
.group-actions .btn-success.btn-group-action {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white !important;
}

.group-actions .btn-success.btn-group-action:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
}

.group-actions .btn-success.btn-group-action:active {
    background: linear-gradient(135deg, #1e7e34 0%, #1a7a6b 100%) !important;
}

/* Botão Revogar Grupo - Desktop */
.group-actions .btn-danger.btn-group-action {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%) !important;
    color: white !important;
}

.group-actions .btn-danger.btn-group-action:hover {
    background: linear-gradient(135deg, #c82333 0%, #e8590c 100%) !important;
}

.group-actions .btn-danger.btn-group-action:active {
    background: linear-gradient(135deg, #bd2130 0%, #d63384 100%) !important;
}

/* Botões de controle (Expandir/Colapsar Todos) */
.btn-group-action.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white !important;
    position: relative !important;
    z-index: 10 !important;
}

.btn-group-action.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
}

.btn-group-action.btn-success:active {
    background: linear-gradient(135deg, #1e7e34 0%, #1a7a6b 100%) !important;
}

.btn-group-action.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
    color: white !important;
    position: relative !important;
    z-index: 10 !important;
}

.btn-group-action.btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268 0%, #343a40 100%) !important;
}

.btn-group-action.btn-secondary:active {
    background: linear-gradient(135deg, #495057 0%, #212529 100%) !important;
}

/* CSS simplificado para botões de controle */
.btn-group-action {
    position: relative !important;
    z-index: 10 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
    user-select: none !important;
}

/* CSS específico para botões de controle */
#expandAllBtn, #expandAllBtnMobile {
    position: relative !important;
    z-index: 30 !important;
}

#collapseAllBtn, #collapseAllBtnMobile {
    position: relative !important;
    z-index: 30 !important;
}

/* Garantir que os botões não se sobreponham */
.btn-group-action + .btn-group-action {
    margin-left: 0.5rem !important;
}

/* Cabeçalho fixo */
.sticky-header {
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    transition: all 0.3s ease !important;
}

.sticky-header:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
}

/* CSS SIMPLIFICADO - APENAS O ESSENCIAL */
.btn-group-action {
    pointer-events: auto !important;
    cursor: pointer !important;
    user-select: none !important;
    position: relative !important;
    z-index: 1 !important;
}

.btn-group-action:focus {
    outline: none !important;
}

.btn-group-action:active {
    transform: none !important;
}

/* PAINEL DE CONTROLE - Simples e funcional */
.control-panel {
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 5px !important;
    padding: 1.5rem !important;
    margin-bottom: 2rem !important;
}

.control-panel-content {
    max-width: 100% !important;
}

/* Botões de grupo - Mobile - SIMPLIFICADO */
.group-card .btn-group-action {
    border-radius: 8px !important;
    border: none !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    transition: none !important;
    font-weight: 500 !important;
    position: relative !important;
    padding: 8px 16px !important;
    z-index: 1 !important;
}

.group-card .btn-group-action:hover {
    transform: none !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
}

.group-card .btn-group-action:active {
    transform: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}

/* Botão Autorizar Grupo - Mobile */
.group-card .btn-success.btn-group-action {
    background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%) !important;
    color: white !important;
}

.group-card .btn-success.btn-group-action:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 50%, #138496 100%) !important;
}

.group-card .btn-success.btn-group-action:active {
    background: linear-gradient(135deg, #1e7e34 0%, #1a7a6b 50%, #117a8b 100%) !important;
}

/* Botão Revogar Grupo - Mobile */
.group-card .btn-danger.btn-group-action {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 50%, #e83e8c 100%) !important;
    color: white !important;
}

.group-card .btn-danger.btn-group-action:hover {
    background: linear-gradient(135deg, #c82333 0%, #e8590c 50%, #d63384 100%) !important;
}

.group-card .btn-danger.btn-group-action:active {
    background: linear-gradient(135deg, #bd2130 0%, #d63384 50%, #c73e6b 100%) !important;
}

/* EFEITO DE BRILHO REMOVIDO - PODE INTERFERIR COM CLIQUES */

/* Responsividade para tablets */
@media (min-width: 768px) and (max-width: 1199px) {
    .group-actions .btn-group-action {
        padding: 8px 16px !important;
        font-size: 0.9rem !important;
    }
    
    .group-card .btn-group-action {
        padding: 8px 16px !important;
        font-size: 0.9rem !important;
    }
    
    /* Botões de controle */
    .btn-group-action.btn-success,
    .btn-group-action.btn-secondary {
        padding: 8px 16px !important;
        font-size: 0.9rem !important;
    }
}

/* Responsividade para mobile pequeno */
@media (max-width: 575.98px) {
    .group-card .btn-group-action {
        padding: 12px 18px !important;
        font-size: 0.85rem !important;
        width: 100% !important;
        margin-bottom: 8px !important;
    }
    
    .group-card .d-flex.gap-2 {
        flex-direction: column !important;
    }
}

/* Melhorias adicionais para suavidade */
.btn-group-action {
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
}

.btn-group-action i {
    transition: transform 0.2s ease !important;
}

.btn-group-action:hover i {
    transform: scale(1.1) !important;
}

/* ANIMAÇÕES REMOVIDAS - PODEM INTERFERIR COM CLIQUES */

/* Efeito de profundidade nos botões */
.btn-group-action {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}

/* Melhorias para botões desktop */
.group-counters .btn-group-action {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}

/* Melhorias para botões mobile */
.group-card .btn-group-action {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
}
</style>

<!-- Alertas de feedback -->
<div id="successAlert" class="alert alert-success alert-dismissible fade position-fixed" 
     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; display: none;">
    <i class="fas fa-check-circle me-2"></i>
    <span id="successMessage"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<div id="errorAlert" class="alert alert-danger alert-dismissible fade position-fixed" 
     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; display: none;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <span id="errorMessage"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<style>
/* Estilos específicos para a nova estrutura responsiva */
.table-permissions-desktop {
    font-size: 0.9rem;
}

.table-permissions-desktop th,
.table-permissions-desktop td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

/* Cards mobile */
.group-card .card-header {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.group-card .card-header:hover {
    background-color: #e9ecef !important;
}

.toggle-icon-mobile {
    transition: transform 0.3s ease;
}

.group-card.expanded .toggle-icon-mobile {
    transform: rotate(90deg);
}

/* Responsividade */
@media (max-width: 767.98px) {
    .group-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .group-actions .btn {
        width: 100%;
    }
}
</style>
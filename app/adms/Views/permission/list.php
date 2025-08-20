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
    <div class="row align-items-center mb-4">
        <!-- Filtro de busca -->
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
            <button type="button" class="btn btn-success btn-sm me-2" onclick="expandAllGroups()">
                <i class="fas fa-expand-alt"></i> Expandir Todos
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="collapseAllGroups()">
                <i class="fas fa-compress-alt"></i> Colapsar Todos
            </button>
        </div>
        
        
    </div>

    <!-- Formulário de permissões -->
    <form method="POST" action="<?php echo $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . ($this->data['accessLevel']['id'] ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $this->data['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="adms_access_level_id" value="<?php echo ($this->data['accessLevel']['id'] ?? ''); ?>">
        
        <!-- Tabela de permissões -->
        <div class="table-responsive">
            <table class="table table-hover mb-4">
                <thead class="table-light" style="display: none;">
                    <tr>
                        <th style="width: 20%;">Status</th>
                        <th style="width: 20%;">ID</th>
                        <th style="width: 20%;">Nome da Página</th>
                        <th style="width: 20%;">Observação</th>
                        <th style="width: 20%;">Tipo</th>
                    </tr>
                </thead>
                <tbody>
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
                            echo '<td class="group-header-cell">';
                            echo '<div class="d-flex align-items-center" onclick="toggleGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-chevron-right toggle-icon me-3 text-primary"></i>';
                            echo '<h6 class="mb-0 fw-bold text-primary group-name">' . htmlspecialchars($groupId) . '</h6>';
                            echo '</div>';
                            echo '</td>';
                            echo '<td class="group-header-cell text-center">';
                            echo '<span class="badge bg-info">Grupo</span>';
                            echo '</td>';
                            echo '<td class="group-header-cell">';
                            echo '<div class="d-flex align-items-center justify-content-between">';
                            echo '<div class="group-counters me-3">';
                            echo '<span class="badge bg-secondary me-2">Total: ' . $totalCount . '</span>';
                            echo '<span class="badge bg-success me-2">Autorizadas: ' . $authorizedCount . '</span>';
                            echo '<span class="badge bg-danger">Revogadas: ' . $revokedCount . '</span>';
                            echo '</div>';
                            echo '<div class="group-actions">';
                            echo '<button type="button" class="btn btn-success btn-sm me-2" onclick="event.stopPropagation(); authorizeGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-check-double"></i> Autorizar Grupo';
                            echo '</button>';
                            echo '<button type="button" class="btn btn-danger btn-sm" onclick="event.stopPropagation(); revokeGroup(\'' . htmlspecialchars($groupId) . '\')">';
                            echo '<i class="fas fa-times"></i> Revogar Grupo';
                            echo '</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</td>';
                            echo '<td class="group-header-cell">';
                            echo '<span class="text-muted">-</span>';
                            echo '</td>';
                            echo '<td class="group-header-cell text-center">';
                            echo '<span class="text-muted">-</span>';
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
                    } else {
                        echo '<tr><td colspan="5" class="text-center py-5">';
                        echo '<div class="text-muted">';
                        echo '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>';
                        echo 'Nenhuma página encontrada para configurar permissões.';
                        echo '</div>';
                        echo '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Botão de salvar -->
        <div class="text-center py-4">
            <button type="button" class="btn btn-primary btn-lg px-5" id="savePermissionsBtn">
                <i class="fas fa-save me-2"></i> Salvar Permissões
            </button>
        </div>
    </form>
</div>

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
<?php
// Não precisa definir $urlAdm, usar diretamente $_ENV['URL_ADM']
?>

<div class="container-fluid px-4">
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Permissões</h2>
      
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Permissões</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?php echo $this->data['accessLevel']['name'] ?? 'Nível de Acesso'; ?></span>
        </div>
        
        <div class="card-body">
            <!-- Container fixo superior - MOVIDO PARA DENTRO DO CARD -->
            <div class="sticky-top-container mb-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchGroup" placeholder="Buscar por grupo..." onkeyup="filterByGroup(this.value)">
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <button type="button" class="btn btn-success btn-sm me-2" onclick="expandAllGroups()">
                            <i class="fas fa-expand-alt"></i> Expandir Todos
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm me-2" onclick="collapseAllGroups()">
                            <i class="fas fa-compress-alt"></i> Colapsar Todos
                        </button>
                        
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?php echo $_ENV['URL_ADM'] . 'list-access-levels'; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Grupos começam colapsados. Clique no cabeçalho para expandir.
            </div>
                        
                        <!-- Visualização Desktop (Tabela) -->
                        <div class="d-none d-md-block">
                            <form method="POST" action="<?php echo $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . ($this->data['accessLevel']['id'] ?? ''); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $this->data['csrf_token'] ?? ''; ?>">
                                <input type="hidden" name="adms_access_level_id" value="<?php echo ($this->data['accessLevel']['id'] ?? ''); ?>">
                                
                                <table class="table table-striped table-hover">
                                    <tbody>
                                        <?php
                                        // Debug: verificar dados recebidos
                                        if (isset($this->data['pages']) && !empty($this->data['pages'])) {
                                            echo "<!-- Debug: " . count($this->data['pages']) . " páginas encontradas -->";
                                        }
                                        if (isset($this->data['accessLevelsPages']) && !empty($this->data['accessLevelsPages'])) {
                                            echo "<!-- Debug: " . count($this->data['accessLevelsPages']) . " permissões encontradas -->";
                                        }
                                        
                                        if (!empty($this->data['pages'] ?? [])) {
                                            // Agrupar páginas por grupo e ordenar alfabeticamente
                                            $groupedPages = [];
                                            foreach (($this->data['pages'] ?? []) as $page) {
                                                $groupId = $page['agp_name'] ?? 'Sem Grupo';
                                                if (!isset($groupedPages[$groupId])) {
                                                    $groupedPages[$groupId] = [];
                                                }
                                                $groupedPages[$groupId][] = $page;
                                            }
                                            
                                            // Ordenar grupos alfabeticamente
                                            ksort($groupedPages);
                                            
                                            foreach ($groupedPages as $groupId => $pages) {
                                                // Contar permissões do grupo
                                                $totalPermissions = count($pages);
                                                $allowedPermissions = 0;
                                                
                                                foreach ($pages as $page) {
                                                    if (isset(($this->data['accessLevelsPages'] ?? [])[$page['id']])) {
                                                        $allowedPermissions++;
                                                    }
                                                }
                                                
                                                $deniedPermissions = $totalPermissions - $allowedPermissions;
                                                
                                                // Cabeçalho do grupo usando linha inteira
                                                echo '<tr class="group-header" data-group="' . htmlspecialchars($groupId) . '" onclick="toggleGroup(this, \'' . $groupId . '\')">';
                                                echo '<td class="text-start" colspan="5">';
                                                echo '<i class="fas fa-chevron-right toggle-icon"></i>';
                                                echo '<span class="group-name">' . htmlspecialchars($groupId) . '</span>';
                                                echo '<span class="group-counter total">' . $totalPermissions . ' total</span>';
                                                echo '<span class="group-counter allowed">' . $allowedPermissions . '</span>';
                                                echo '<span class="group-counter denied">' . $deniedPermissions . '</span>';
                                                echo '<button type="button" class="btn btn-success btn-sm ms-2" onclick="event.stopPropagation(); authorizeGroup(\'' . htmlspecialchars($groupId) . '\')">Autorizar</button>';
                                                echo '<button type="button" class="btn btn-danger btn-sm ms-1" onclick="event.stopPropagation(); revokeGroup(\'' . htmlspecialchars($groupId) . '\')">Revogar</button>';
                                                echo '</td>';
                                                echo '</tr>';
                                                
                                                // Cabeçalho da tabela para este grupo
                                                echo '<tr class="group-table-header" data-group="' . htmlspecialchars($groupId) . '">';
                                                echo '<th>Liberado</th>';
                                                echo '<th>Página</th>';
                                                echo '<th>Nome</th>';
                                                echo '<th>Observação</th>';
                                                echo '<th>Pública/Privada</th>';
                                                echo '</tr>';
                                                
                                                // Linhas das permissões do grupo
                                                foreach ($pages as $page) {
                                                    $isAllowed = isset(($this->data['accessLevelsPages'] ?? [])[$page['id']]);
                                                    $isPrivate = ($page['public_page'] ?? 0) == 0;
                                                    
                                                    echo '<tr class="group-content-row" data-group="' . htmlspecialchars($groupId) . '">';
                                                    echo '<td class="text-start">';
                                                    echo '<div class="form-check form-switch d-flex justify-content-start">';
                                                    echo '<input class="form-check-input permission-toggle" type="checkbox" name="permissions[' . $page['id'] . ']" value="1" id="permission_' . $page['id'] . '" data-page-id="' . $page['id'] . '" data-group="' . htmlspecialchars($groupId) . '"';
                                                    if ($isAllowed) echo ' checked';
                                                    echo ' onchange="togglePermission(this, ' . $page['id'] . ', \'' . htmlspecialchars($groupId) . '\')">';
                                                    echo '<label class="form-check-label ms-2" for="permission_' . $page['id'] . '">Liberado</label>';
                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '<td class="text-start">' . $page['id'] . '</td>';
                                                    echo '<td class="text-start">' . htmlspecialchars($page['name'] ?? '') . '</td>';
                                                    echo '<td class="text-start">' . htmlspecialchars($page['obs'] ?? '') . '</td>';
                                                    echo '<td class="text-start">';
                                                    if ($isPrivate) {
                                                        echo '<span class="badge bg-danger">Privada</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">Pública</span>';
                                                    }
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center">Nenhuma página encontrada para configurar permissões.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                
                                <!-- Botão Salvar dentro do formulário desktop -->
                                <div class="text-center mt-3 mb-3">
                                    <button type="button" class="btn btn-primary btn-lg" id="savePermissionsBtn">
                                        <i class="fas fa-save"></i> Salvar Permissões
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm ms-2" onclick="alert('Teste JavaScript funcionando!')">
                                        <i class="fas fa-test"></i> Teste JS
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Visualização Mobile (Cards) -->
                        <div class="d-md-none">
                            <form method="POST" action="<?php echo $_ENV['URL_ADM'] . 'list-access-levels-permissions/' . ($this->data['accessLevel']['id'] ?? ''); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $this->data['csrf_token'] ?? ''; ?>">
                                <input type="hidden" name="adms_access_level_id" value="<?php echo ($this->data['accessLevel']['id'] ?? ''); ?>">
                                
                                <?php
                                if (!empty($this->data['pages'] ?? [])) {
                                    // Agrupar páginas por grupo e ordenar alfabeticamente
                                    $groupedPages = [];
                                    foreach (($this->data['pages'] ?? []) as $page) {
                                        $groupId = $page['agp_name'] ?? 'Sem Grupo';
                                        if (!isset($groupedPages[$groupId])) {
                                            $groupedPages[$groupId] = [];
                                        }
                                        $groupedPages[$groupId][] = $page;
                                    }
                                    
                                    // Ordenar grupos alfabeticamente
                                    ksort($groupedPages);
                                    
                                    foreach ($groupedPages as $groupId => $pages) {
                                        // Contar permissões do grupo
                                        $totalPermissions = count($pages);
                                        $allowedPermissions = 0;
                                        
                                        foreach ($pages as $page) {
                                            if (isset(($this->data['accessLevelsPages'] ?? [])[$page['id']])) {
                                                $allowedPermissions++;
                                            }
                                        }
                                        
                                        $deniedPermissions = $totalPermissions - $allowedPermissions;
                                        
                                        echo '<div class="card mb-3 group-card" data-group="' . htmlspecialchars($groupId) . '">';
                                        echo '<div class="card-header group-card-header" onclick="toggleMobileGroup(this, \'' . $groupId . '\')">';
                                        echo '<div class="d-flex justify-content-between align-items-center">';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<i class="fas fa-chevron-right toggle-icon me-2"></i>';
                                        echo '<h6 class="mb-0 group-name">' . htmlspecialchars($groupId) . '</h6>';
                                        echo '</div>';
                                        echo '<div class="d-flex align-items-center gap-2">';
                                        echo '<span class="badge bg-secondary">' . $totalPermissions . ' total</span>';
                                        echo '<span class="badge bg-success">' . $allowedPermissions . '</span>';
                                        echo '<span class="badge bg-danger">' . $deniedPermissions . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '<div class="mt-2">';
                                        echo '<button type="button" class="btn btn-success btn-sm me-2" onclick="event.stopPropagation(); authorizeMobileGroup(\'' . htmlspecialchars($groupId) . '\')">Autorizar Grupo</button>';
                                        echo '<button type="button" class="btn btn-danger btn-sm" onclick="event.stopPropagation(); revokeMobileGroup(\'' . htmlspecialchars($groupId) . '\')">Revogar Grupo</button>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<div class="card-body group-card-content" style="display: none;">';
                                        foreach ($pages as $page) {
                                            $isAllowed = isset(($this->data['accessLevelsPages'] ?? [])[$page['id']]);
                                            $isPrivate = ($page['public_page'] ?? 0) == 0;
                                            
                                            echo '<div class="permission-item mb-3 p-3 border rounded">';
                                            echo '<div class="d-flex justify-content-between align-items-start mb-2">';
                                            echo '<div class="form-check form-switch">';
                                            echo '<input class="form-check-input permission-toggle" type="checkbox" name="permissions[' . $page['id'] . ']" value="1" id="mobile_permission_' . $page['id'] . '" data-page-id="' . $page['id'] . '" data-group="' . htmlspecialchars($groupId) . '"';
                                            if ($isAllowed) echo ' checked';
                                            echo ' onchange="togglePermission(this, ' . $page['id'] . ', \'' . htmlspecialchars($groupId) . '\')">';
                                            echo '<label class="form-check-label ms-2" for="mobile_permission_' . $page['id'] . '">Liberado</label>';
                                            echo '</div>';
                                            echo '<span class="badge ' . ($isPrivate ? 'bg-danger' : 'bg-success') . '">' . ($isPrivate ? 'Privada' : 'Pública') . '</span>';
                                            echo '</div>';
                                            echo '<div class="permission-details">';
                                            echo '<div class="mb-1"><strong>Página:</strong> ' . $page['id'] . '</div>';
                                            echo '<div class="mb-1"><strong>Nome:</strong> ' . htmlspecialchars($page['name'] ?? '') . '</div>';
                                            if (!empty($page['obs'])) {
                                                echo '<div><strong>Observação:</strong> ' . htmlspecialchars($page['obs']) . '</div>';
                                            }
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-warning">Nenhuma página encontrada para configurar permissões.</div>';
                                }
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de sucesso -->
<div id="successAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display: none; position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
    <i class="fas fa-check-circle me-2"></i>
    <span id="successMessage"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Alerta de erro -->
<div id="errorAlert" class="alert alert-danger alert-dismissible fade" role="alert" style="display: none; position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <span id="errorMessage"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
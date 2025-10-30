<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->data['title_head'] ?? 'Organograma'; ?></title>
    <?php echo $this->data['css'] ?? ''; ?>
    <style>
        /* Organograma CSS */
        .org-chart-container {
            overflow-x: auto;
            overflow-y: auto;
            padding: 25px 15px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 10px;
            min-height: 500px;
        }
        
        .org-chart {
            display: flex;
            justify-content: center;
            margin: 0 auto;
        }
        
        .org-chart ul {
            padding-top: 15px;
            position: relative;
            display: flex;
            justify-content: center;
        }
        
        .org-chart li {
            float: left;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 15px 4px 0 4px;
        }
        
        .org-chart li::before,
        .org-chart li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 2px solid #2E9263;
            width: 50%;
            height: 15px;
        }
        
        .org-chart li::after {
            right: auto;
            left: 50%;
            border-left: 2px solid #2E9263;
        }
        
        .org-chart li:only-child::after,
        .org-chart li:only-child::before {
            display: none;
        }
        
        .org-chart li:only-child {
            padding-top: 0;
        }
        
        .org-chart li:first-child::before,
        .org-chart li:last-child::after {
            border: 0 none;
        }
        
        .org-chart li:last-child::before {
            border-right: 2px solid #2E9263;
            border-radius: 0 5px 0 0;
        }
        
        .org-chart li:first-child::after {
            border-radius: 5px 0 0 0;
        }
        
        .org-chart ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 2px solid #2E9263;
            width: 0;
            height: 15px;
        }
        
        .org-chart-node {
            border: 2px solid #2E9263;
            padding: 10px;
            text-align: center;
            display: inline-block;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            min-width: 140px;
            max-width: 160px;
            cursor: pointer;
        }
        
        .org-chart-node:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(46, 146, 99, 0.3);
            z-index: 10;
        }
        
        .org-chart-node.is-manager {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fff 0%, #fff9e6 100%);
        }
        
        .org-chart-node.is-ceo {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff 0%, #ffe6e6 100%);
        }
        
        .org-chart-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin: 0 auto 8px;
            border: 2px solid #2E9263;
            object-fit: cover;
            background: #e9ecef;
        }
        
        .org-chart-node.is-manager .org-chart-avatar {
            border-color: #ffc107;
        }
        
        .org-chart-node.is-ceo .org-chart-avatar {
            border-color: #dc3545;
        }
        
        .org-chart-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 12px;
            line-height: 1.2;
        }
        
        .org-chart-position {
            color: #6c757d;
            font-size: 10px;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .org-chart-department {
            background: #2E9263;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            display: inline-block;
            margin-top: 4px;
        }
        
        .org-chart-badge {
            background: #ffc107;
            color: #000;
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 4px;
            display: inline-block;
        }
        
        .org-chart-stats {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, #2E9263 0%, #25764f 100%);
            color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.3;
        }
        
        .legend {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 13px;
        }
        
        .legend-color {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 5px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .org-chart-container {
                background: white;
            }
        }
    </style>
</head>
<body>
    <?php echo $this->data['navbar'] ?? ''; ?>
    <?php echo $this->data['sidebar'] ?? ''; ?>
    
    <div class="content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-sitemap me-2"></i>Organograma da Empresa
                </h1>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-users" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-value"><?= $this->data['stats']['total_users'] ?></div>
                        <div class="stat-label"><i class="fas fa-users me-1"></i>Total de Colaboradores</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                        <div class="stat-value"><?= $this->data['stats']['total_managers'] ?></div>
                        <div class="stat-label"><i class="fas fa-user-tie me-1"></i>Gerentes/Supervisores</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                        <div class="stat-value"><?= $this->data['stats']['total_levels'] ?></div>
                        <div class="stat-label"><i class="fas fa-layer-group me-1"></i>Níveis Hierárquicos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">
                        <div class="stat-value"><?= $this->data['stats']['largest_team']['count'] ?></div>
                        <div class="stat-label">
                            <i class="fas fa-crown me-1"></i>Maior Equipe
                            <?php if ($this->data['stats']['largest_team']['manager']): ?>
                                <br><small><?= htmlspecialchars($this->data['stats']['largest_team']['manager']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Legenda -->
            <div class="legend no-print">
                <strong><i class="fas fa-info-circle me-2"></i>Legenda:</strong>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #fff 0%, #ffe6e6 100%); border: 2px solid #dc3545;"></span>
                    CEO/Direção (sem supervisor)
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #fff 0%, #fff9e6 100%); border: 2px solid #ffc107;"></span>
                    Gerente/Supervisor (tem subordinados)
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: white; border: 2px solid #2E9263;"></span>
                    Colaborador (sem subordinados)
                </div>
            </div>
            
            <!-- Organograma -->
            <div class="org-chart-container">
                <div class="org-chart">
                    <?php echo renderHierarchyTree($this->data['hierarchy']); ?>
                </div>
            </div>
            
            <!-- Instruções -->
            <div class="alert alert-info mt-4 no-print">
                <h6><i class="fas fa-lightbulb me-2"></i>Como Usar:</h6>
                <ul class="mb-0">
                    <li>👆 <strong>Passe o mouse</strong> sobre um colaborador para destacá-lo</li>
                    <li>🖨️ Use o botão <strong>"Imprimir"</strong> para gerar PDF do organograma</li>
                    <li>🔗 As <strong>linhas verdes</strong> mostram as relações de subordinação</li>
                    <li>👑 <strong>Bordas douradas</strong> indicam gerentes com equipe</li>
                    <li>🔴 <strong>Bordas vermelhas</strong> indicam cargos de direção (topo da hierarquia)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php echo $this->data['footer'] ?? ''; ?>
    <?php echo $this->data['js'] ?? ''; ?>
    
    <script>
        // Tooltip para mostrar informações ao clicar
        document.querySelectorAll('.org-chart-node').forEach(node => {
            node.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Remover destaque anterior
                document.querySelectorAll('.org-chart-node').forEach(n => n.style.outline = '');
                
                // Destacar este nó
                this.style.outline = '3px solid #2E9263';
                this.style.outlineOffset = '3px';
            });
        });
        
        // Remover destaque ao clicar fora
        document.addEventListener('click', function() {
            document.querySelectorAll('.org-chart-node').forEach(n => n.style.outline = '');
        });
    </script>
</body>
</html>

<?php
/**
 * Renderizar árvore hierárquica recursivamente
 */
function renderHierarchyTree(array $hierarchy): string
{
    if (empty($hierarchy)) {
        return '';
    }
    
    $html = '<ul>';
    
    foreach ($hierarchy as $node) {
        $user = $node['user'];
        $children = $node['children'];
        $childrenCount = $node['children_count'];
        $totalSubordinates = $node['total_subordinates'];
        
        // Determinar classe do nó
        $nodeClass = 'org-chart-node';
        if ($user['immediate_supervisor_id'] === null) {
            $nodeClass .= ' is-ceo'; // CEO/Direção
        } elseif ($childrenCount > 0) {
            $nodeClass .= ' is-manager'; // Gerente/Supervisor
        }
        
        // Avatar
        $avatar = !empty($user['image']) 
            ? $_ENV['URL_ADM'] . 'public/adms/uploads/' . $user['image']
            : $_ENV['URL_ADM'] . 'public/adms/image/icon_user.png';
        
        $html .= '<li>';
        $html .= '<div class="' . $nodeClass . '" title="Clique para destacar">';
        
        // Avatar
        $html .= '<img src="' . htmlspecialchars($avatar) . '" class="org-chart-avatar" alt="' . htmlspecialchars($user['name']) . '">';
        
        // Nome
        $html .= '<div class="org-chart-name">' . htmlspecialchars($user['name']) . '</div>';
        
        // Cargo
        $html .= '<div class="org-chart-position">' . htmlspecialchars($user['position_name']) . '</div>';
        
        // Departamento
        $html .= '<div class="org-chart-department">' . htmlspecialchars($user['department_name']) . '</div>';
        
        // Badge de subordinados
        if ($childrenCount > 0) {
            $html .= '<div class="org-chart-badge">';
            $html .= '<i class="fas fa-users me-1"></i>';
            $html .= $childrenCount . ' direto' . ($childrenCount > 1 ? 's' : '');
            if ($totalSubordinates > $childrenCount) {
                $html .= ' | ' . $totalSubordinates . ' total';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Renderizar filhos recursivamente
        if (!empty($children)) {
            $html .= renderHierarchyTree($children);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}
?>


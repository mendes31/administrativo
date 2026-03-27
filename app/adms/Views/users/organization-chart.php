<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->data['title_head'] ?? 'Organograma'; ?></title>
    <?php echo $this->data['css'] ?? ''; ?>
    <style>
        /* Organograma CSS - Layout Horizontal */
        .org-chart-container {
            overflow-x: auto;
            overflow-y: auto;
            padding: 30px 20px;
            background: #faf9f6;
            border-radius: 10px;
            min-height: 400px;
            width: 100%;
            position: relative;
        }
        
        .org-chart {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: flex-start;
            min-width: fit-content;
            padding: 20px 0;
            width: max-content;
        }
        
        .org-chart ul {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: flex-start;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 25px;
            position: relative;
            flex-wrap: wrap;
        }
        
        .org-chart li {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-shrink: 0;
        }
        
        /* Quando expandido, os filhos aparecem abaixo */
        .org-chart li.has-children:not(.collapsed) {
            flex-direction: column;
        }
        
        .org-chart li.has-children:not(.collapsed) > ul {
            display: flex;
            flex-direction: row;
            margin-top: 30px;
            position: relative;
        }
        
        /* Remover TODAS as linhas por padrão */
        .org-chart li::before,
        .org-chart li::after,
        .org-chart-node::after,
        .org-chart-node-wrapper::after {
            display: none;
        }
        
        /* Linhas horizontais entre irmãos no nível superior apenas (não conectar com "Sem Hierarquia Definida") */
        .org-chart > ul > li:not(:first-child):not(.is-orphan-branch)::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -30px;
            width: 30px;
            height: 2px;
            background: #ef4444;
            z-index: 0;
            display: block;
        }
        
        /* Não mostrar linha horizontal antes de "Sem Hierarquia Definida" */
        .org-chart > ul > li.is-orphan-branch::before {
            display: none !important;
        }
        
        /* Container e espaçamento quando expandido */
        .org-chart li.has-children:not(.collapsed) {
            position: relative;
            margin-bottom: 50px;
        }
        
        /* Container para subordinados quando expandido */
        .org-chart li.has-children:not(.collapsed) > ul {
            position: relative;
            margin-top: 30px;
            padding-top: 0;
        }
        
        /* Linha horizontal acima dos subordinados - calculada dinamicamente via JS */
        .org-chart li.has-children:not(.collapsed) > ul.has-visible-items::before {
            content: '';
            position: absolute;
            top: -30px;
            left: var(--line-left, 50%);
            width: var(--line-width, 0px);
            transform: translateX(0);
            height: 2px;
            background: #ef4444;
            z-index: 1;
            display: block;
        }
        
        /* Linha vertical saindo do supervisor (APENAS quando expandido E tem subordinados visíveis) */
        /* Termina 2px antes da linha horizontal para não ultrapassar */
        .org-chart li.has-children:not(.collapsed).has-visible-children > .org-chart-node > .org-chart-node-wrapper::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 28px;
            background: #ef4444;
            z-index: 0;
            display: block !important;
        }
        
        /* Linha vertical conectando cada subordinado à linha horizontal (apenas quando há linha horizontal) */
        /* Começa exatamente na linha horizontal */
        .org-chart li.has-children:not(.collapsed) > ul.has-visible-items > li::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 30px;
            background: #ef4444;
            z-index: 0;
            display: block;
        }
        
        /* Garantir que a linha horizontal esteja sobre as verticais para fazer a conexão visual */
        .org-chart li.has-children:not(.collapsed) > ul.has-visible-items::before {
            z-index: 2;
        }
        
        /* Garantir que nós SEM filhos NUNCA tenham linhas verticais */
        .org-chart li:not(.has-children)::after,
        .org-chart li:not(.has-children) > .org-chart-node::after,
        .org-chart li:not(.has-children) > .org-chart-node > .org-chart-node-wrapper::after {
            display: none !important;
        }
        
        /* Garantir que nós colapsados NUNCA tenham linhas verticais */
        .org-chart li.has-children.collapsed::after,
        .org-chart li.has-children.collapsed > .org-chart-node::after,
        .org-chart li.has-children.collapsed > .org-chart-node > .org-chart-node-wrapper::after {
            display: none !important;
        }
        
        /* Garantir que nós sem filhos visíveis não tenham linhas verticais */
        .org-chart li.has-children:not(.has-visible-children)::after,
        .org-chart li.has-children:not(.has-visible-children) > .org-chart-node::after,
        .org-chart li.has-children:not(.has-visible-children) > .org-chart-node > .org-chart-node-wrapper::after {
            display: none !important;
        }
        
        .org-chart-node {
            border: none;
            padding: 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            transition: all 0.3s ease;
            min-width: 140px;
            max-width: 160px;
            cursor: pointer;
            position: relative;
            margin-bottom: 20px;
        }
        
        .org-chart-node-wrapper {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            border-radius: 12px;
            padding: 12px 10px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            position: relative;
            width: 100%;
            z-index: 1;
        }
        
        .org-chart-node:hover .org-chart-node-wrapper {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(20, 184, 166, 0.3);
        }
        
        .org-chart-node.is-ceo .org-chart-node-wrapper {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .org-chart-node.is-manager .org-chart-node-wrapper {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .org-chart-node.is-orphan .org-chart-node-wrapper {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        /* Botão de expandir/colapsar */
        .org-chart-toggle {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffff;
            color: #14b8a6;
            border: 2px solid #14b8a6;
            cursor: pointer;
            display: flex !important;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            line-height: 1;
            padding: 0;
            margin: 0;
        }
        
        .org-chart-toggle:hover {
            background: #14b8a6;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        }
        
        .org-chart-node.is-ceo .org-chart-toggle {
            border-color: #dc2626;
            color: #dc2626;
        }
        
        .org-chart-node.is-ceo .org-chart-toggle:hover {
            background: #dc2626;
            color: white;
        }
        
        .org-chart-node.is-manager .org-chart-toggle {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        
        .org-chart-node.is-manager .org-chart-toggle:hover {
            background: #f59e0b;
            color: white;
        }
        
        .org-chart-node.is-orphan .org-chart-toggle {
            border-color: #8b5cf6;
            color: #8b5cf6;
        }
        
        .org-chart-node.is-orphan .org-chart-toggle:hover {
            background: #8b5cf6;
            color: white;
        }
        
        /* Estado colapsado */
        .org-chart-node.has-children {
            position: relative;
        }
        
        /* Animação suave */
        .org-chart ul {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .org-chart li.collapsed > ul {
            display: none;
            opacity: 0;
            transform: translateY(-10px);
        }
        
        .org-chart-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 8px;
            object-fit: cover;
            display: block;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .org-chart-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 8px;
            background: #ececec;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            color: #6c757d;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .org-chart-name {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            font-size: 12px;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .org-chart-position {
            color: rgba(255, 255, 255, 0.9);
            font-size: 10px;
            margin-bottom: 6px;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .org-chart-department {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 9px;
            display: inline-block;
            margin-top: 4px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .org-chart-badge {
            background: rgba(255, 255, 255, 0.9);
            color: #14b8a6;
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 4px;
            display: inline-block;
        }
        
        .org-chart-node.is-ceo .org-chart-badge {
            color: #dc2626;
        }
        
        .org-chart-node.is-manager .org-chart-badge {
            color: #f59e0b;
        }
        
        .org-chart-node.is-orphan .org-chart-badge {
            color: #8b5cf6;
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
        
        .org-ranking-section .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .org-ranking-section .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #495057;
        }
        .org-ranking-section .rank-pos {
            font-weight: 700;
            color: #2E9263;
            width: 2.5rem;
        }

        /* Painel único com rolagem vertical — não empurra o organograma para baixo */
        .org-ranking-panel {
            max-height: clamp(220px, 36vh, 380px);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.75rem 0.85rem 0.65rem;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            -webkit-overflow-scrolling: touch;
        }
        .org-ranking-panel .org-ranking-section {
            margin-bottom: 0 !important;
        }
        .org-ranking-panel .card {
            box-shadow: none;
            border: 1px solid #eef1f4;
        }

        @media print {
            .no-print {
                display: none;
            }
            
            .org-chart-container {
                background: white;
            }

            .org-ranking-panel {
                max-height: none !important;
                overflow: visible !important;
                box-shadow: none;
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
                        <div class="stat-label"><i class="fas fa-user-tie me-1"></i>Gerentes / Supervisores</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                        <div class="stat-value"><?= $this->data['stats']['total_coordinators'] ?? 0 ?></div>
                        <div class="stat-label"><i class="fas fa-user-friends me-1"></i>Coordenadores</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">
                        <div class="stat-value"><?= $this->data['stats']['total_levels'] ?></div>
                        <div class="stat-label"><i class="fas fa-layer-group me-1"></i>Níveis Hierárquicos</div>
                    </div>
                </div>
            </div>

            <?php
            $teamRank = $this->data['team_rankings'] ?? ['by_department' => [], 'by_level' => []];
            $rankDept = $teamRank['by_department'] ?? [];
            $rankLevel = $teamRank['by_level'] ?? [];
            ?>
            <!-- Rankings: equipe por departamento e por nível hierárquico (área com scroll) -->
            <div class="org-ranking-panel mb-4">
            <div class="row org-ranking-section">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-1"><i class="fas fa-building me-2 text-success"></i>Ranking por departamento</h2>
                            <p class="small text-muted mb-0">Colaboradores ativos por departamento (do maior para o menor).</p>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($rankDept)): ?>
                                <p class="text-muted small p-3 mb-0">Nenhum dado para exibir.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="border-0">#</th>
                                                <th scope="col" class="border-0">Departamento</th>
                                                <th scope="col" class="border-0 text-end">Colaboradores</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rankDept as $i => $row): ?>
                                                <tr>
                                                    <td class="rank-pos"><?= $i + 1 ?></td>
                                                    <td><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-end fw-semibold"><?= (int)($row['count'] ?? 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-1"><i class="fas fa-layer-group me-2 text-primary"></i>Por nível hierárquico</h2>
                            <p class="small text-muted mb-0">Nível 1 = topo (ex.: direção); níveis seguintes = distância na cadeia até o topo.</p>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($rankLevel)): ?>
                                <p class="text-muted small p-3 mb-0">Nenhum dado para exibir.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="border-0">Nível</th>
                                                <th scope="col" class="border-0 text-end">Colaboradores</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rankLevel as $row): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                            Nível <?= (int)($row['level'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-semibold"><?= (int)($row['count'] ?? 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- Legenda -->
            <div class="legend no-print">
                <strong><i class="fas fa-info-circle me-2"></i>Legenda:</strong>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border: 2px solid #dc2626;"></span>
                    CEO/Direção (sem supervisor)
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: 2px solid #f59e0b;"></span>
                    Gerente/Supervisor (tem subordinados)
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); border: 2px solid #14b8a6;"></span>
                    Colaborador (sem subordinados)
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: 2px solid #8b5cf6;"></span>
                    Sem Hierarquia Definida
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #ef4444; border: 2px solid #ef4444;"></span>
                    Linhas de conexão hierárquica
                </div>
            </div>
            
            <!-- Controles de Expansão -->
            <div class="alert alert-light border mb-3 no-print">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Controles:</strong> Clique nos botões <span class="badge bg-success">−</span> ou <span class="badge bg-success">+</span> em cada card para expandir/colapsar ramificações
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="expandAll">
                            <i class="fas fa-expand-arrows-alt me-1"></i>Expandir Tudo
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll">
                            <i class="fas fa-compress-arrows-alt me-1"></i>Colapsar Tudo
                        </button>
                    </div>
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
                    <li>➕ <strong>Clique no botão</strong> circular em cada card para expandir/colapsar subordinados</li>
                    <li>🔒 <strong>Galhos começam fechados</strong> - apenas o nível superior é exibido inicialmente</li>
                    <li>↔️ <strong>Layout horizontal</strong> - expanda conforme necessário, a página aumenta automaticamente</li>
                    <li>👆 <strong>Passe o mouse</strong> sobre um colaborador para destacá-lo</li>
                    <li>🖨️ Use o botão <strong>"Imprimir"</strong> para gerar PDF do organograma</li>
                    <li>🔗 As <strong>linhas vermelhas</strong> mostram as relações de subordinação</li>
                    <li>🔵 <strong>Cards teal/azul</strong> indicam colaboradores sem subordinados</li>
                    <li>🟠 <strong>Cards laranjas</strong> indicam gerentes com equipe</li>
                    <li>🔴 <strong>Cards vermelhos</strong> indicam CEO/Direção (topo da hierarquia)</li>
                    <li>🟣 <strong>Cards roxos</strong> indicam usuários sem hierarquia definida</li>
                    <li>📷 <strong>Fotos</strong> ou <strong>iniciais</strong> são exibidas automaticamente</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php echo $this->data['footer'] ?? ''; ?>
    <?php echo $this->data['js'] ?? ''; ?>
    
    <script>
        // Função para calcular e ajustar linhas horizontais e verticais
        function updateHorizontalLines() {
            // Remover todas as classes de controle primeiro
            document.querySelectorAll('.org-chart li').forEach(li => {
                li.classList.remove('has-visible-children');
            });
            document.querySelectorAll('.org-chart ul').forEach(ul => {
                ul.classList.remove('has-visible-items');
            });
            
            // Processar apenas nós expandidos
            document.querySelectorAll('.org-chart li.has-children:not(.collapsed)').forEach(li => {
                const ul = Array.from(li.children).find(child => child.tagName === 'UL');
                if (!ul) return;
                
                const children = Array.from(ul.children).filter(child => child.tagName === 'LI');
                
                if (children.length > 0) {
                    // Adicionar classe para indicar que tem filhos visíveis
                    li.classList.add('has-visible-children');
                    ul.classList.add('has-visible-items');
                    
                    // Calcular linha horizontal apenas se houver filhos visíveis
                    const firstChild = children[0];
                    const lastChild = children[children.length - 1];
                    
                    // Verificar se os elementos estão visíveis
                    if (firstChild.offsetParent !== null && lastChild.offsetParent !== null) {
                        // Obter posições relativas ao ul
                        const ulRect = ul.getBoundingClientRect();
                        const firstRect = firstChild.getBoundingClientRect();
                        const lastRect = lastChild.getBoundingClientRect();
                        
                        // Calcular posição e largura da linha horizontal (do centro do primeiro ao centro do último)
                        const firstCenter = firstRect.left - ulRect.left + (firstRect.width / 2);
                        const lastCenter = lastRect.right - ulRect.left - (lastRect.width / 2);
                        const width = Math.max(0, lastCenter - firstCenter);
                        
                        // Aplicar estilos à linha horizontal
                        ul.style.setProperty('--line-left', firstCenter + 'px');
                        ul.style.setProperty('--line-width', width + 'px');
                    }
                } else {
                    // Sem filhos visíveis, ocultar linha
                    ul.style.setProperty('--line-width', '0px');
                }
            });
        }
        
        // Listener para redimensionamento da janela
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                updateHorizontalLines();
            }, 150);
        });
        
        // Aguardar carregamento do DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar: todos os nós começam COLAPSADOS (apenas nível superior visível)
            const allLis = document.querySelectorAll('.org-chart li.has-children');
            allLis.forEach(li => {
                li.classList.add('collapsed');
                // Buscar o primeiro ul filho direto
                const ul = Array.from(li.children).find(child => child.tagName === 'UL');
                if (ul) {
                    ul.style.display = 'none';
                }
                const toggle = li.querySelector('.org-chart-toggle');
                if (toggle) {
                    toggle.setAttribute('data-symbol', '+');
                    toggle.textContent = '+';
                }
            });
            
            // Adicionar event listeners aos botões de toggle
            document.querySelectorAll('.org-chart-toggle').forEach(button => {
                // Definir símbolo inicial baseado no estado
                const li = button.closest('li.has-children');
                if (li && li.classList.contains('collapsed')) {
                    button.setAttribute('data-symbol', '+');
                    button.textContent = '+';
                } else {
                    button.setAttribute('data-symbol', '−');
                    button.textContent = '−';
                }
                
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const li = this.closest('li.has-children');
                    if (li) {
                        const isCollapsed = li.classList.contains('collapsed');
                        li.classList.toggle('collapsed');
                        
                        // Atualizar texto do botão
                        if (li.classList.contains('collapsed')) {
                            this.setAttribute('data-symbol', '+');
                            this.textContent = '+';
                        } else {
                            this.setAttribute('data-symbol', '−');
                            this.textContent = '−';
                        }
                        
                        // Animação suave
                        const ul = Array.from(li.children).find(child => child.tagName === 'UL');
                        if (ul) {
                            if (li.classList.contains('collapsed')) {
                                ul.style.display = 'none';
                                ul.style.opacity = '0';
                            } else {
                                ul.style.display = 'flex';
                                ul.style.flexDirection = 'row';
                                setTimeout(() => {
                                    ul.style.opacity = '1';
                                    ul.style.transform = 'translateY(0)';
                                    // Atualizar linhas horizontais após animação
                                    setTimeout(() => updateHorizontalLines(), 50);
                                }, 10);
                            }
                        }
                        // Atualizar linhas horizontais sempre que houver mudança
                        setTimeout(() => updateHorizontalLines(), 100);
                    }
                });
            });
            
            // Atualizar linhas horizontais inicialmente
            setTimeout(() => updateHorizontalLines(), 200);
            
            // Botão Expandir Tudo
            document.getElementById('expandAll')?.addEventListener('click', function() {
                document.querySelectorAll('.org-chart li.has-children.collapsed').forEach(li => {
                    li.classList.remove('collapsed');
                    const toggle = li.querySelector('.org-chart-toggle');
                    if (toggle) {
                        toggle.setAttribute('data-symbol', '−');
                        toggle.textContent = '−';
                    }
                    const ul = Array.from(li.children).find(child => child.tagName === 'UL');
                    if (ul) {
                        ul.style.display = 'flex';
                        ul.style.flexDirection = 'row';
                        setTimeout(() => {
                            ul.style.opacity = '1';
                            ul.style.transform = 'translateY(0)';
                        }, 10);
                    }
                });
                // Atualizar linhas horizontais após expandir tudo
                setTimeout(() => updateHorizontalLines(), 300);
            });
            
            // Botão Colapsar Tudo
            document.getElementById('collapseAll')?.addEventListener('click', function() {
                document.querySelectorAll('.org-chart li.has-children:not(.collapsed)').forEach(li => {
                    li.classList.add('collapsed');
                    const toggle = li.querySelector('.org-chart-toggle');
                    if (toggle) {
                        toggle.setAttribute('data-symbol', '+');
                        toggle.textContent = '+';
                    }
                    const ul = Array.from(li.children).find(child => child.tagName === 'UL');
                    if (ul) {
                        ul.style.display = 'none';
                        ul.style.opacity = '0';
                    }
                });
                // Linhas horizontais serão automaticamente removidas quando colapsado
            });
            
            // Tooltip para destacar ao clicar no card
            document.querySelectorAll('.org-chart-node').forEach(node => {
                node.addEventListener('click', function(e) {
                    // Não destacar se clicar no botão de toggle
                    if (e.target.classList.contains('org-chart-toggle')) {
                        return;
                    }
                    
                    e.stopPropagation();
                    
                    // Remover destaque anterior
                    document.querySelectorAll('.org-chart-node').forEach(n => n.style.outline = '');
                    
                    // Destacar este nó
                    this.style.outline = '3px solid #2E9263';
                    this.style.outlineOffset = '3px';
                });
            });
            
            // Remover destaque ao clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.org-chart-node')) {
                    document.querySelectorAll('.org-chart-node').forEach(n => n.style.outline = '');
                }
            });
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
        $isOrphan = ($user['immediate_supervisor_id'] === null && $childrenCount == 0);
        
        if ($user['id'] == 0) {
            // Nó especial "Sem Hierarquia Definida"
            $nodeClass .= ' is-orphan';
        } elseif ($user['immediate_supervisor_id'] === null) {
            $nodeClass .= ' is-ceo'; // CEO/Direção
        } elseif ($childrenCount > 0) {
            $nodeClass .= ' is-manager'; // Gerente/Supervisor
        } elseif ($isOrphan) {
            $nodeClass .= ' is-orphan'; // Órfão
        }
        
        $hasChildren = !empty($children);
        
        // Verificar se é nó especial "Sem Hierarquia Definida"
        $isSpecialNode = ($user['id'] == 0);
        
        // Construir classes do li
        $liClasses = [];
        if ($hasChildren) {
            $liClasses[] = 'has-children';
            $liClasses[] = 'collapsed';
        }
        if ($isSpecialNode) {
            $liClasses[] = 'is-orphan-branch';
        }
        $liClassAttr = !empty($liClasses) ? ' class="' . implode(' ', $liClasses) . '"' : '';
        $html .= '<li' . $liClassAttr . '>';
        
        $nodeClassWithChildren = $nodeClass . ($hasChildren ? ' has-children' : '');
        $html .= '<div class="' . $nodeClassWithChildren . '" title="Clique para destacar">';
        
        // Wrapper interno com background
        $html .= '<div class="org-chart-node-wrapper">';
        
        // Botão de expandir/colapsar (se tiver filhos)
        if ($hasChildren) {
            $html .= '<button type="button" class="org-chart-toggle" data-symbol="+" aria-label="Expandir/Colapsar" title="Clique para expandir/comprimir">+</button>';
        }
        
        if ($isSpecialNode) {
            // Nó especial - mostrar apenas título
            $html .= '<div class="org-chart-avatar-placeholder" style="font-size: 14px; width: 40px; height: 40px;">?</div>';
            $html .= '<div class="org-chart-name" style="font-size: 11px;">' . htmlspecialchars($user['name']) . '</div>';
            $html .= '<div class="org-chart-position" style="font-size: 9px;">' . htmlspecialchars($user['position_name']) . '</div>';
        } else {
            // Avatar - usar sistema de iniciais quando não tiver foto
            $hasImage = !empty($user['image']);
            
            // Gerar iniciais do nome (sempre gerar para fallback)
            $userName = trim($user['name'] ?? '');
            $initials = '??';
            if (!empty($userName)) {
                $nameParts = array_filter(explode(' ', $userName), function($part) {
                    return !empty(trim($part));
                });
                if (count($nameParts) >= 2) {
                    $first = mb_substr($nameParts[0], 0, 1, 'UTF-8');
                    $last = mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8');
                    $initials = mb_strtoupper($first . $last, 'UTF-8');
                } elseif (count($nameParts) == 1) {
                    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 2, 'UTF-8'), 'UTF-8');
                }
            }
            
            if ($hasImage) {
                $avatarSrc = $_ENV['URL_ADM'] . 'serve-file?path=' . urlencode($user['image']);
                $html .= '<img src="' . htmlspecialchars($avatarSrc) . '" class="org-chart-avatar" alt="' . htmlspecialchars($user['name']) . '" onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
                $html .= '<div class="org-chart-avatar-placeholder" style="display: none;">' . htmlspecialchars($initials) . '</div>';
            } else {
                $html .= '<div class="org-chart-avatar-placeholder" style="display: flex;">' . htmlspecialchars($initials) . '</div>';
            }
            
            // Nome
            $html .= '<div class="org-chart-name">' . htmlspecialchars($user['name']) . '</div>';
            
            // Cargo
            $html .= '<div class="org-chart-position">' . htmlspecialchars($user['position_name']) . '</div>';
            
            // Departamento
            $html .= '<div class="org-chart-department">' . htmlspecialchars($user['department_name']) . '</div>';
        }
        
        // Badge de subordinados (só mostrar se tiver filhos)
        if ($childrenCount > 0) {
            $html .= '<div class="org-chart-badge">';
            $html .= '<i class="fas fa-users me-1"></i>';
            $html .= $childrenCount . ' direto' . ($childrenCount > 1 ? 's' : '');
            if ($totalSubordinates > $childrenCount) {
                $html .= ' | ' . $totalSubordinates . ' total';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Fecha wrapper
        $html .= '</div>'; // Fecha node
        
        // Renderizar filhos recursivamente
        if ($hasChildren) {
            $html .= renderHierarchyTree($children);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}
?>


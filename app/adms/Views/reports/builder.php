<?php
use App\adms\Helpers\CSRFHelper;

$report = $this->data['report'] ?? null;
$availableTables = $this->data['availableTables'] ?? [];
// Prioriza flag enviada pelo controller; mantém fallback pela query string
$isSapScope = $this->data['is_sap_scope'] ?? (!empty($_GET['source']) && $_GET['source'] === 'sap');
$defaultQueryMode = $report['query_mode'] ?? 'builder';
$shouldOpenSqlTab = $isSapScope || $defaultQueryMode === 'custom_sql';
$usersForShare = $this->data['users_for_share'] ?? [];
$sharedUserIds = $this->data['shared_user_ids'] ?? [];
$sharedUserIdsFlipped = array_fill_keys(array_map('intval', $sharedUserIds), true);

// Gerar token CSRF para o formulário de relatórios
$csrfToken = CSRFHelper::generateCSRFToken('form_dynamic_report');
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">
                <i class="fas fa-chart-bar"></i>
                <?= $report ? 'Editar Relatório' : 'Construtor de Relatórios Dinâmicos' ?>
            </h3>
        </div>
        <div class="card-body">
            <!-- Abas de Modo -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="builder-tab" data-bs-toggle="tab" data-bs-target="#builder-mode" 
                            type="button" role="tab">
                        <i class="fas fa-cubes"></i> Modo Construtor Visual (Tabelas Locais)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="sql-tab" data-bs-toggle="tab" data-bs-target="#sql-mode" 
                            type="button" role="tab">
                        <i class="fas fa-code"></i> SQL Personalizado (Local + SAP B1)
                    </button>
                </li>
            </ul>

            <form id="reportBuilderForm" method="POST" action="<?= $_ENV['URL_ADM'] ?>save-dynamic-report">
                <input type="hidden" name="id" value="<?= $report['id'] ?? '' ?>">
                <input type="hidden" name="query_mode" id="queryMode" value="<?= htmlspecialchars($defaultQueryMode) ?>">
                
                <!-- Informações Básicas (sempre visível) -->
                <div class="row mb-4">
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Nome do Relatório *</label>
                        <input type="text" name="name" id="reportName" class="form-control" 
                               value="<?= htmlspecialchars($report['name'] ?? '') ?>" 
                               placeholder="Ex: Vendas do Mês" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Categoria</label>
                        <input type="text" name="category" class="form-control" 
                               value="<?= htmlspecialchars($report['category'] ?? '') ?>"
                               placeholder="Ex: Vendas">
                    </div>
                </div>

                <div class="card border mb-4">
                    <div class="card-header py-2">
                        <strong><i class="fas fa-users"></i> Quem pode ver este relatório</strong>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublic" value="1"
                                   <?= !empty($report['is_public']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isPublic">
                                <strong>Público</strong> — qualquer utilizador com acesso ao menu «Relatórios Locais» vê e executa este relatório.
                            </label>
                        </div>
                        <label class="form-label fw-bold mb-1" for="sharedUserIds">
                            Ou partilhar apenas com utilizadores específicos (mantém privado para os restantes)
                        </label>
                        <select name="shared_user_ids[]" id="sharedUserIds" class="form-select" multiple size="8"
                                aria-describedby="sharedUserHelp">
                            <?php foreach ($usersForShare as $u): ?>
                                <?php
                                $uid = (int) ($u['id'] ?? 0);
                                if ($uid < 1) {
                                    continue;
                                }
                                $sel = !empty($sharedUserIdsFlipped[$uid]) ? ' selected' : '';
                                ?>
                                <option value="<?= $uid ?>"<?= $sel ?>>
                                    <?= htmlspecialchars(trim((string)($u['name'] ?? ''))) ?>
                                    <?php if (!empty($u['email'])): ?>
                                        — <?= htmlspecialchars((string) $u['email']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="sharedUserHelp" class="form-text">
                            Utilize <kbd>Ctrl</kbd> / <kbd>Cmd</kbd> para selecionar vários. Quem estiver na lista pode <strong>visualizar e executar</strong>;
                            só o criador (ou administrador com acesso total) pode <strong>editar ou apagar</strong>.
                        </div>
                    </div>
                </div>

                <!-- Conteúdo das Abas -->
                <div class="tab-content">
                    <!-- ABA 1: MODO CONSTRUTOR -->
                    <div class="tab-pane fade show active" id="builder-mode" role="tabpanel">
                        <div class="row">
                            <!-- Coluna Esquerda: Tabelas e Campos -->
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-database"></i> Tabelas e Campos</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <!-- Busca de Tabelas -->
                                        <input type="text" id="tableSearch" class="form-control form-control-sm mb-2" 
                                               placeholder="🔍 Buscar tabela...">
                                        
                                        <!-- Lista de Tabelas -->
                                        <div id="tablesList" style="max-height: 500px; overflow-y: auto;">
                                            <?php foreach ($availableTables as $tableName => $tableInfo): ?>
                                                <div class="table-item p-2 border-bottom" data-table="<?= $tableName ?>" 
                                                     style="cursor: pointer;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-table text-primary"></i>
                                                            <strong><?= htmlspecialchars($tableName) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($tableInfo['label']) ?></small>
                                                        </div>
                                                        <span class="badge bg-<?= ($tableInfo['connection'] ?? 'local') === 'sap_b1' ? 'warning' : 'success' ?>">
                                                            <?= ($tableInfo['connection'] ?? 'local') === 'sap_b1' ? 'SAP B1' : 'Local' ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Campos da Tabela (ocultos inicialmente) -->
                                                    <div class="fields-list mt-2" style="display: none;">
                                                        <div class="list-group list-group-flush">
                                                            <?php foreach ($tableInfo['fields'] as $fieldName => $fieldLabel): ?>
                                                                <div class="list-group-item list-group-item-action p-1 field-item" 
                                                                     data-field="<?= $fieldName ?>"
                                                                     style="cursor: pointer; font-size: 0.85rem;">
                                                                    <i class="fas fa-columns"></i>
                                                                    <?= htmlspecialchars($fieldName) ?>
                                                                    <br>
                                                                    <small class="text-muted"><?= htmlspecialchars($fieldLabel) ?></small>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Coluna Direita: Query Builder -->
                            <div class="col-md-8">
                                <input type="hidden" name="data_source" id="dataSource" value="<?= $report['data_source'] ?? '' ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tabela Selecionada:</label>
                                    <div class="input-group">
                                        <input type="text" id="selectedTableDisplay" class="form-control" readonly 
                                               value="<?= $report['data_source'] ?? 'Nenhuma tabela selecionada' ?>">
                                        <button type="button" class="btn btn-outline-secondary" id="clearTableBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Campos Selecionados:</label>
                                    <div id="selectedFieldsContainer" class="border rounded p-3 min-height-100" 
                                         style="min-height: 150px; background: #f8f9fa;">
                                        <p class="text-muted text-center my-5">
                                            <i class="fas fa-hand-point-left"></i> 
                                            Clique nos campos à esquerda para adicionar
                                            <br>
                                            <small>Ou deixe vazio para SELECT * FROM tabela</small>
                                        </p>
                                    </div>
                                </div>

                                <!-- FILTROS (WHERE) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-filter"></i> Filtros (WHERE):
                                    </label>
                                    <div id="filtersContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noFilters">
                                            <small><i class="fas fa-info-circle"></i> Nenhum filtro adicionado</small>
                                        </p>
                                        <div id="filtersList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addFilterBtn">
                                        <i class="fas fa-plus"></i> Adicionar Filtro
                                    </button>
                                </div>

                                <!-- ORDENAÇÃO (ORDER BY) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-sort"></i> Ordenar Por (ORDER BY):
                                    </label>
                                    <div id="orderByContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noOrderBy">
                                            <small><i class="fas fa-info-circle"></i> Nenhuma ordenação adicionada</small>
                                        </p>
                                        <div id="orderByList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOrderByBtn">
                                        <i class="fas fa-plus"></i> Adicionar Ordenação
                                    </button>
                                </div>

                                <!-- AGRUPAMENTO (GROUP BY) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-layer-group"></i> Agrupar Por (GROUP BY):
                                    </label>
                                    <div id="groupByContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noGroupBy">
                                            <small><i class="fas fa-info-circle"></i> Nenhum agrupamento adicionado</small>
                                        </p>
                                        <div id="groupByList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addGroupByBtn">
                                        <i class="fas fa-plus"></i> Adicionar Agrupamento
                                    </button>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tipo de Visualização:</label>
                                        <select name="visualization_type" class="form-select" id="visualizationType">
                                            <option value="table" selected>📊 Tabela</option>
                                            <option value="bar_chart">📈 Gráfico de Barras</option>
                                            <option value="line_chart">📉 Gráfico de Linhas</option>
                                            <option value="pie_chart">🥧 Gráfico de Pizza</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA 2: MODO SQL PERSONALIZADO -->
                    <div class="tab-pane fade" id="sql-mode" role="tabpanel">
                        <div class="alert alert-primary border-primary">
                            <h5><i class="fas fa-code"></i> Modo SQL Personalizado</h5>
                            <p class="mb-2">
                                <strong>Escreva sua query SQL diretamente.</strong> 
                                O sistema detecta automaticamente se é <span class="badge bg-success">Local</span> ou <span class="badge bg-warning">SAP B1</span>.
                            </p>
                            <hr>
                            <p class="mb-0 small">
                                <i class="fas fa-lightbulb text-warning"></i> 
                                <strong>Exemplos:</strong>
                            </p>
                            <ul class="small mb-0">
                                <li><code>SELECT * FROM adms_users</code> → Todos os usuários (Local)</li>
                                <li><code>SELECT name, email FROM adms_users WHERE status = 'Ativo'</code> → Usuários ativos (Local)</li>
                                <li><code>SELECT * FROM OITM</code> → Todos os itens (SAP B1) 🔷</li>
                                <li><code>SELECT CardCode, CardName, Balance FROM OCRD WHERE CardType = 'C'</code> → Clientes (SAP B1) 🔷</li>
                                <li><code>SELECT DocNum, DocDate, DocTotal FROM OINV WHERE MONTH(DocDate) = MONTH(CURRENT_DATE)</code> → NFs do mês (SAP B1) 🔷</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">
                                    <i class="fas fa-edit"></i> Query SQL:
                                </label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="decreaseEditorBtn" title="Diminuir editor">
                                        <i class="fas fa-compress-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="increaseEditorBtn" title="Aumentar editor">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="fullscreenEditorBtn" title="Tela cheia">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="sqlEditorWrapper" style="position: relative;">
                                <div id="sqlEditorContainer" style="height: 250px; border: 2px solid #0d6efd; border-radius: 5px;"></div>
                            </div>
                            <textarea name="custom_sql" id="customSql" style="display: none;"><?= htmlspecialchars($report['custom_sql'] ?? '') ?></textarea>
                            <div class="form-text mt-2">
                                <i class="fas fa-shield-alt text-success"></i> 
                                Apenas queries <strong>SELECT</strong> são permitidas por segurança.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de Visualização:</label>
                                <select name="visualization_type_sql" class="form-select">
                                    <option value="table">📊 Tabela</option>
                                    <option value="bar_chart">📈 Gráfico de Barras</option>
                                    <option value="line_chart">📉 Gráfico de Linhas</option>
                                    <option value="pie_chart">🥧 Gráfico de Pizza</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0 mt-4">
                                    <div class="card-body p-2">
                                        <small class="text-muted">
                                            <i class="fas fa-magic"></i> <strong>Auto-detecção:</strong>
                                            <br>
                                            Tabelas SAP B1: OCRD, OINV, ORDR, OITM, etc → <span class="badge bg-warning">SAP B1</span>
                                            <br>
                                            Outras tabelas → <span class="badge bg-success">Local</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" name="fields" id="fieldsJson" value="[]">
                <input type="hidden" name="filters" id="filtersJson" value="[]">
                <input type="hidden" name="groupby" id="groupbyJson" value="[]">
                <input type="hidden" name="orderby" id="orderbyJson" value="[]">
                <input type="hidden" name="chart_config" id="chartConfigJson" value="{}">

                <hr class="my-4">

                <!-- Botões de Ação -->
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-info btn-lg" id="previewBtn">
                            <i class="fas fa-eye"></i> Visualizar Prévia em Tempo Real
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning btn-lg dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="refreshPreviewBtn">
                                <i class="fas fa-sync"></i> Atualizar Consulta
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" id="refreshFullBtn">
                                        <i class="fas fa-sync-alt"></i> Atualização Completa
                                        <small class="d-block text-muted">Busca todos os dados novamente</small>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" id="refreshIncrementalBtn">
                                        <i class="fas fa-plus-circle"></i> Busca Incremental
                                        <small class="d-block text-muted">Busca apenas novos registros (mais rápido)</small>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Salvar Relatório
                        </button>
                        <a href="<?= $_ENV['URL_ADM'] ?>list-dynamic-reports" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card de Prévia -->
    <div class="card mt-4" id="previewCard" style="display: none;">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-eye"></i> Prévia do Relatório</h5>
        </div>
        <div class="card-body">
            <div id="previewContent"></div>
        </div>
    </div>
</div>

<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script>
const shouldOpenSqlTab = <?= $shouldOpenSqlTab ? 'true' : 'false' ?>;
let reportState = {
    dataSource: '<?= $report['data_source'] ?? '' ?>',
    availableFields: {},
    selectedFields: [],
    filters: [],
    orderBy: [],
    groupBy: [],
    connectionType: 'local',
    queryMode: '<?= $defaultQueryMode ?>'
};

let sqlEditor = null;
let monacoLoaded = false;
let editorInitialized = false;
let loadingMonaco = false; // Flag para evitar carregamentos simultâneos

// Função para carregar Monaco Editor dinamicamente
function loadMonacoEditor() {
    return new Promise((resolve, reject) => {
        if (monacoLoaded) {
            resolve();
            return;
        }
        
        // Se já está carregando, aguardar
        if (loadingMonaco) {
            console.log('⏳ Já está carregando Monaco, aguardando...');
            const checkInterval = setInterval(() => {
                if (monacoLoaded) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
            return;
        }
        
        loadingMonaco = true;
        console.log('📦 Carregando Monaco Editor...');
        
        // Verificar se o loader já foi adicionado
        if (document.querySelector('script[src*="monaco-editor"]')) {
            console.log('⚠️ Script Monaco já existe no DOM');
            monacoLoaded = true;
            loadingMonaco = false;
            resolve();
            return;
        }
        
        // Carregar loader do Monaco
        const loaderScript = document.createElement('script');
        loaderScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js';
        loaderScript.onload = () => {
            // Configurar require do Monaco
            window.require.config({ 
                paths: { 
                    vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' 
                }
            });
            
            window.require(['vs/editor/editor.main'], () => {
                monacoLoaded = true;
                loadingMonaco = false;
                console.log('✅ Monaco Editor biblioteca carregada!');
                resolve();
            });
        };
        loaderScript.onerror = () => {
            loadingMonaco = false;
            reject(new Error('Falha ao carregar Monaco Editor'));
        };
        document.head.appendChild(loaderScript);
    });
}

// Função para inicializar o Monaco Editor
async function initMonacoEditor() {
    // Verificar se já existe um editor criado
    if (sqlEditor !== null) {
        console.log('✅ Editor já existe, apenas ajustando layout');
        sqlEditor.layout();
        return;
    }
    
    if (editorInitialized) {
        console.log('⚠️ Editor marcado como inicializado mas sqlEditor é null, reinicializando...');
        editorInitialized = false;
    }
    
    try {
        console.log('🚀 Iniciando Monaco Editor...');
        await loadMonacoEditor();
        createEditor();
    } catch (error) {
        console.error('❌ Erro ao carregar Monaco Editor:', error);
    }
}

function createEditor() {
    const container = document.getElementById('sqlEditorContainer');
    if (!container) {
        console.error('❌ Container não encontrado!');
        return;
    }
    
    // Verificar se o container já tem um editor (Monaco cria divs internas)
    if (container.children.length > 0) {
        console.log('⚠️ Container já tem editor, pulando criação');
        return;
    }
    
    const initialValue = document.getElementById('customSql').value || `-- Digite sua query SQL aqui
-- Exemplo:
SELECT * FROM OITM

-- Ou com filtros:
-- SELECT ItemCode, ItemName, OnHand 
-- FROM OITM
-- WHERE OnHand > 0
-- ORDER BY ItemName`;

    console.log('🎨 Criando editor Monaco...');
    sqlEditor = monaco.editor.create(container, {
        value: initialValue,
        language: 'sql',
        theme: 'vs',
        automaticLayout: true,
        fontSize: 14,
        minimap: { enabled: true },
        scrollBeyondLastLine: false,
        wordWrap: 'on',
        lineNumbers: 'on',
        renderWhitespace: 'selection',
        bracketPairColorization: {enabled: true},
        suggest: {
            showKeywords: true,
            showSnippets: true
        }
    });

    // Sincronizar com o textarea oculto
    sqlEditor.onDidChangeModelContent(() => {
        document.getElementById('customSql').value = sqlEditor.getValue();
    });
    
    editorInitialized = true;
    console.log('✅ Monaco Editor inicializado com sucesso!');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM carregado!');

    if (shouldOpenSqlTab) {
        setTimeout(() => {
            const sqlTabBtn = document.getElementById('sql-tab');
            if (sqlTabBtn) {
                sqlTabBtn.click();
            }
        }, 250);
    }
    
    // Busca de tabelas
    document.getElementById('tableSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.table-item').forEach(item => {
            const tableName = item.dataset.table.toLowerCase();
            const tableLabel = item.querySelector('small').textContent.toLowerCase();
            item.style.display = (tableName.includes(search) || tableLabel.includes(search)) ? 'block' : 'none';
        });
    });

    // Clique em tabela
    document.querySelectorAll('.table-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.classList.contains('field-item') || e.target.closest('.field-item')) return;
            
            const tableName = this.dataset.table;
            const fieldsDiv = this.querySelector('.fields-list');
            const fieldItems = fieldsDiv ? fieldsDiv.querySelectorAll('.field-item') : [];
            
            console.log(`📋 Clicou na tabela: ${tableName}`);
            console.log(`  Campos encontrados: ${fieldItems.length}`);
            
            if (fieldsDiv) {
                const isVisible = fieldsDiv.style.display !== 'none';
                fieldsDiv.style.display = isVisible ? 'none' : 'block';
                console.log(`  Status: ${isVisible ? 'Fechando' : 'Abrindo'} lista de campos`);
            } else {
                console.warn(`  ❌ Div .fields-list não encontrada!`);
            }
            
            // Selecionar tabela
            reportState.dataSource = tableName;
            document.getElementById('dataSource').value = tableName;
            document.getElementById('selectedTableDisplay').value = tableName;
        });
    });

    // Clique em campo
    document.querySelectorAll('.field-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const fieldName = this.dataset.field;
            const fieldLabel = this.querySelector('small') ? this.querySelector('small').textContent : fieldName;
            addSelectedField({field: fieldName, label: fieldLabel});
        });
    });

    // Limpar tabela
    document.getElementById('clearTableBtn').addEventListener('click', function() {
        reportState.dataSource = '';
        reportState.selectedFields = [];
        reportState.filters = [];
        reportState.orderBy = [];
        reportState.groupBy = [];
        document.getElementById('dataSource').value = '';
        document.getElementById('selectedTableDisplay').value = 'Nenhuma tabela selecionada';
        document.getElementById('selectedFieldsContainer').innerHTML = `
            <p class="text-muted text-center my-5">
                <i class="fas fa-hand-point-left"></i> Clique nos campos à esquerda para adicionar
            </p>
        `;
        updateFiltersDisplay();
        updateOrderByDisplay();
        updateGroupByDisplay();
    });

    // Botões de adicionar
    document.getElementById('addFilterBtn').addEventListener('click', addFilter);
    document.getElementById('addOrderByBtn').addEventListener('click', addOrderBy);
    document.getElementById('addGroupByBtn').addEventListener('click', addGroupBy);

    // Troca de aba
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        console.log('🔍 Aba encontrada:', tab.id, tab);
        
        tab.addEventListener('shown.bs.tab', function(e) {
            console.log('🔄 Aba trocada para:', e.target.id);
            reportState.queryMode = e.target.id === 'sql-tab' ? 'custom_sql' : 'builder';
            document.getElementById('queryMode').value = reportState.queryMode;
            
            // Inicializar Monaco Editor quando a aba SQL for aberta
            if (e.target.id === 'sql-tab') {
                console.log('📝 Iniciando Monaco Editor...');
                setTimeout(() => {
                    initMonacoEditor();
                }, 100);
            }
        });
        
        // Adicionar evento de clique também
        tab.addEventListener('click', function(e) {
            console.log('👆 Clique na aba:', e.target.id);
        });
    });
    
    // Listener direto no botão SQL tab (fallback)
    const sqlTabBtn = document.getElementById('sql-tab');
    if (sqlTabBtn) {
        console.log('✅ Botão SQL-tab encontrado, adicionando listener direto');
        sqlTabBtn.addEventListener('click', function() {
            console.log('🎯 Clique direto no SQL-tab!');
            
            // Aguardar a transição do Bootstrap (geralmente 150ms)
            setTimeout(() => {
                console.log('🔍 Verificando se aba SQL está visível...');
                const sqlModeDiv = document.getElementById('sql-mode');
                const isVisible = sqlModeDiv && (
                    sqlModeDiv.classList.contains('show') || 
                    sqlModeDiv.classList.contains('active') ||
                    sqlModeDiv.style.display !== 'none'
                );
                
                console.log('📊 Status da aba SQL:', {
                    exists: !!sqlModeDiv,
                    hasShow: sqlModeDiv?.classList.contains('show'),
                    hasActive: sqlModeDiv?.classList.contains('active'),
                    display: sqlModeDiv?.style.display,
                    isVisible: isVisible
                });
                
                if (isVisible) {
                    console.log('✅ Aba SQL está visível, inicializando editor...');
                    initMonacoEditor();
                } else {
                    console.log('⏳ Aba SQL ainda não está visível, tentando novamente em 300ms...');
                    setTimeout(() => {
                        const isNowVisible = sqlModeDiv && (
                            sqlModeDiv.classList.contains('show') || 
                            sqlModeDiv.classList.contains('active')
                        );
                        
                        console.log('🔄 Segunda tentativa - Aba visível?', isNowVisible);
                        
                        if (isNowVisible) {
                            initMonacoEditor();
                        } else {
                            console.error('❌ Aba SQL não está abrindo. Possível conflito de JavaScript.');
                        }
                    }, 300);
                }
            }, 200);
        });
    }

    // Controles do Editor
    let editorHeight = 250; // Altura inicial
    let isFullscreen = false;
    
    document.getElementById('decreaseEditorBtn').addEventListener('click', function() {
        if (editorHeight > 150) {
            editorHeight -= 50;
            document.getElementById('sqlEditorContainer').style.height = editorHeight + 'px';
            if (sqlEditor) sqlEditor.layout();
            console.log('📏 Editor reduzido para:', editorHeight + 'px');
        }
    });
    
    document.getElementById('increaseEditorBtn').addEventListener('click', function() {
        if (editorHeight < 800) {
            editorHeight += 50;
            document.getElementById('sqlEditorContainer').style.height = editorHeight + 'px';
            if (sqlEditor) sqlEditor.layout();
            console.log('📏 Editor aumentado para:', editorHeight + 'px');
        }
    });
    
    document.getElementById('fullscreenEditorBtn').addEventListener('click', function() {
        const wrapper = document.getElementById('sqlEditorWrapper');
        const container = document.getElementById('sqlEditorContainer');
        const btn = this;
        
        if (!isFullscreen) {
            // Entrar em tela cheia
            wrapper.style.position = 'fixed';
            wrapper.style.top = '0';
            wrapper.style.left = '0';
            wrapper.style.width = '100%';
            wrapper.style.height = '100%';
            wrapper.style.zIndex = '9999';
            wrapper.style.backgroundColor = '#f8f9fa';
            wrapper.style.padding = '20px';
            
            container.style.height = 'calc(100% - 100px)';
            container.style.border = '2px solid #0d6efd';
            
            // Adicionar header no topo
            const header = document.createElement('div');
            header.id = 'fullscreenHeader';
            header.style.cssText = 'margin-bottom: 15px; padding: 15px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;';
            header.innerHTML = `
                <div>
                    <h5 class="mb-0"><i class="fas fa-code"></i> Editor SQL - Modo Tela Cheia</h5>
                    <small class="text-muted">Pressione ESC ou clique no botão para sair</small>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('fullscreenEditorBtn').click()">
                    <i class="fas fa-times"></i> Fechar (ESC)
                </button>
            `;
            wrapper.insertBefore(header, container);
            
            btn.innerHTML = '<i class="fas fa-compress"></i>';
            btn.title = 'Sair da tela cheia';
            
            isFullscreen = true;
            if (sqlEditor) sqlEditor.layout();
            console.log('🖥️ Modo tela cheia ativado');
        } else {
            // Sair da tela cheia
            const header = document.getElementById('fullscreenHeader');
            if (header) header.remove();
            
            wrapper.style.position = 'relative';
            wrapper.style.top = '';
            wrapper.style.left = '';
            wrapper.style.width = '';
            wrapper.style.height = '';
            wrapper.style.zIndex = '';
            wrapper.style.backgroundColor = '';
            wrapper.style.padding = '';
            
            container.style.height = editorHeight + 'px';
            
            btn.innerHTML = '<i class="fas fa-expand"></i>';
            btn.title = 'Tela cheia';
            
            isFullscreen = false;
            if (sqlEditor) sqlEditor.layout();
            console.log('🖥️ Modo tela cheia desativado');
        }
    });
    
    // ESC para sair do fullscreen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isFullscreen) {
            document.getElementById('fullscreenEditorBtn').click();
        }
    });

    // Prévia
    document.getElementById('previewBtn').addEventListener('click', () => showPreview(false));
    // Botões de atualização
    document.getElementById('refreshFullBtn').addEventListener('click', (e) => {
        e.preventDefault();
        showPreview(true, false); // forceRefresh = true, incremental = false
    });
    
    document.getElementById('refreshIncrementalBtn').addEventListener('click', (e) => {
        e.preventDefault();
        showPreview(true, true); // forceRefresh = true, incremental = true
    });
    
    // Submit
    document.getElementById('reportBuilderForm').addEventListener('submit', onFormSubmit);
});

function addSelectedField(fieldData) {
    // Verificar se já foi adicionado
    if (reportState.selectedFields.find(f => f.field === fieldData.field)) {
        return;
    }
    
    reportState.selectedFields.push(fieldData);
    renderSelectedFields();
}

function renderSelectedFields() {
    const container = document.getElementById('selectedFieldsContainer');
    
    if (reportState.selectedFields.length === 0) {
        container.innerHTML = `
            <p class="text-muted text-center my-5">
                <i class="fas fa-hand-point-left"></i> Clique nos campos à esquerda para adicionar
                <br><small>Ou deixe vazio para SELECT * FROM tabela</small>
            </p>
        `;
        return;
    }
    
    let html = '<div class="list-group">';
    reportState.selectedFields.forEach((field, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-columns text-primary"></i>
                    <strong>${field.field}</strong>
                    <br>
                    <small class="text-muted">${field.label || field.field}</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSelectedField(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function removeSelectedField(index) {
    reportState.selectedFields.splice(index, 1);
    renderSelectedFields();
}

// ========== FILTROS (WHERE) ==========
function addFilter() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo:');
    if (!field) return;
    
    const operator = prompt('Operador (=, !=, >, <, >=, <=, LIKE, IN):');
    if (!operator) return;
    
    const value = prompt('Valor:');
    if (!value) return;
    
    if (!reportState.filters) reportState.filters = [];
    reportState.filters.push({ field, operator, value });
    updateFiltersDisplay();
}

function updateFiltersDisplay() {
    const container = document.getElementById('filtersList');
    const noFilters = document.getElementById('noFilters');
    
    if (!reportState.filters || reportState.filters.length === 0) {
        noFilters.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noFilters.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.filters.forEach((filter, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <code>${filter.field} ${filter.operator} '${filter.value}'</code>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFilter(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('filtersJson').value = JSON.stringify(reportState.filters);
}

function removeFilter(index) {
    reportState.filters.splice(index, 1);
    updateFiltersDisplay();
}

// ========== ORDENAÇÃO (ORDER BY) ==========
function addOrderBy() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo:');
    if (!field) return;
    
    const direction = confirm('Ordenar crescente (ASC)?\nOK = ASC | Cancelar = DESC') ? 'ASC' : 'DESC';
    
    if (!reportState.orderBy) reportState.orderBy = [];
    reportState.orderBy.push({ field, direction });
    updateOrderByDisplay();
}

function updateOrderByDisplay() {
    const container = document.getElementById('orderByList');
    const noOrderBy = document.getElementById('noOrderBy');
    
    if (!reportState.orderBy || reportState.orderBy.length === 0) {
        noOrderBy.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noOrderBy.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.orderBy.forEach((order, index) => {
        const icon = order.direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <i class="fas ${icon} text-primary"></i>
                    <strong>${order.field}</strong> <span class="badge bg-secondary">${order.direction}</span>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeOrderBy(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('orderbyJson').value = JSON.stringify(reportState.orderBy);
}

function removeOrderBy(index) {
    reportState.orderBy.splice(index, 1);
    updateOrderByDisplay();
}

// ========== AGRUPAMENTO (GROUP BY) ==========
function addGroupBy() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo para agrupar:');
    if (!field) return;
    
    if (!reportState.groupBy) reportState.groupBy = [];
    
    // Verificar se já existe
    if (reportState.groupBy.includes(field)) {
        alert('Este campo já está no agrupamento!');
        return;
    }
    
    reportState.groupBy.push(field);
    updateGroupByDisplay();
}

function updateGroupByDisplay() {
    const container = document.getElementById('groupByList');
    const noGroupBy = document.getElementById('noGroupBy');
    
    if (!reportState.groupBy || reportState.groupBy.length === 0) {
        noGroupBy.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noGroupBy.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.groupBy.forEach((field, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <i class="fas fa-layer-group text-primary"></i>
                    <strong>${field}</strong>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeGroupBy(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('groupbyJson').value = JSON.stringify(reportState.groupBy);
}

function removeGroupBy(index) {
    reportState.groupBy.splice(index, 1);
    updateGroupByDisplay();
}

async function showPreview(forceRefresh = false, incremental = false) {
    const activeTab = document.querySelector('.tab-pane.active').id;
    
    if (activeTab === 'sql-mode') {
        await showPreviewSQL(forceRefresh, incremental);
    } else {
        await showPreviewBuilder(forceRefresh, incremental);
    }
}

async function showPreviewBuilder(forceRefresh = false, incremental = false) {
    const dataSource = reportState.dataSource;
    
    if (!dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Executando consulta...</p>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('report_id', 'preview');
    formData.append('data_source', dataSource);
    formData.append('fields', JSON.stringify(reportState.selectedFields));
    formData.append('filters', JSON.stringify(reportState.filters || []));
    formData.append('groupby', JSON.stringify(reportState.groupBy || []));
    formData.append('orderby', JSON.stringify(reportState.orderBy || []));
    formData.append('visualization_type', document.getElementById('visualizationType').value);
    formData.append('query_mode', 'builder');
    formData.append('force_refresh', forceRefresh ? '1' : '0');
    formData.append('incremental', incremental ? '1' : '0');
    formData.append('csrf_token', '<?= $csrfToken ?>');
    
    console.log('📊 Executando (Builder):', {
        dataSource,
        fields: reportState.selectedFields,
        filters: reportState.filters,
        groupBy: reportState.groupBy,
        orderBy: reportState.orderBy,
        forceRefresh,
        incremental
    });
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {method: 'POST', body: formData});
        const result = await response.json();
        console.log('✅ Resultado:', result);
        
        if (result.success) {
            renderPreview(result);
        } else {
            showError(result.error, result.sql);
        }
    } catch (error) {
        showError(error.message);
    }
}

async function showPreviewSQL(forceRefresh = false, incremental = false) {
    // Pegar o valor do Monaco Editor
    const sql = sqlEditor ? sqlEditor.getValue().trim() : document.getElementById('customSql').value.trim();
    
    if (!sql) {
        alert('Digite uma query SQL!');
        return;
    }
    
    document.getElementById('previewCard').style.display = 'block';
    const startTime = Date.now();
    let progressInterval;
    
    const updateProgress = () => {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;
        const timeStr = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
        const progressEl = document.getElementById('previewProgress');
        if (progressEl) {
            progressEl.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 mb-1"><strong>Executando SQL personalizado...</strong></p>
                    <p class="text-muted small mb-2">Tempo decorrido: <strong>${timeStr}</strong></p>
                    <div class="progress mt-2" style="height: 8px; max-width: 400px; margin: 0 auto;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                             role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="text-muted small mt-3">
                        <i class="fas fa-info-circle"></i> 
                        ${forceRefresh 
                            ? (incremental 
                                ? 'Buscando apenas novos registros da API SAP (modo incremental)...' 
                                : 'Buscando dados atualizados da API SAP...') 
                            : 'Verificando cache primeiro...'}
                    </p>
                    ${elapsed > 10 && !incremental ? '<p class="text-warning small mt-2"><i class="fas fa-exclamation-triangle"></i> Consulta demorando mais que o esperado. Considere usar busca incremental.</p>' : ''}
                </div>
            `;
        }
    };
    
    document.getElementById('previewContent').innerHTML = `
        <div id="previewProgress" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 mb-1"><strong>Executando SQL personalizado...</strong></p>
            <p class="text-muted small mb-2">Iniciando consulta...</p>
            <div class="progress mt-2" style="height: 8px; max-width: 400px; margin: 0 auto;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                     role="progressbar" style="width: 100%"></div>
            </div>
            <p class="text-muted small mt-3">
                <i class="fas fa-info-circle"></i> 
                ${forceRefresh 
                    ? (incremental 
                        ? 'Buscando apenas novos registros da API SAP (modo incremental)...' 
                        : 'Buscando dados atualizados da API SAP...') 
                    : 'Verificando cache primeiro...'}
            </p>
        </div>
    `;
    
    progressInterval = setInterval(updateProgress, 500);
    
    const formData = new FormData();
    formData.append('report_id', 'preview');
    formData.append('custom_sql', sql);
    formData.append('query_mode', 'custom_sql');
    formData.append('visualization_type', document.querySelector('[name="visualization_type_sql"]').value);
    formData.append('force_refresh', forceRefresh ? '1' : '0');
    formData.append('incremental', incremental ? '1' : '0');
    formData.append('csrf_token', '<?= $csrfToken ?>');
    
    console.log('📝 Executando SQL:', sql);
    console.log('📝 Force Refresh:', forceRefresh);
    console.log('📝 Incremental:', incremental);
    console.log('📝 CSRF Token:', '<?= $csrfToken ?>');
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {method: 'POST', body: formData});
        const result = await response.json();
        clearInterval(progressInterval);
        console.log('✅ Resultado:', result);
        
        if (result.success) {
            renderPreview(result);
        } else {
            showError(result.error, result.sql);
        }
    } catch (error) {
        clearInterval(progressInterval);
        showError(error.message);
    }
}

function renderPreview(result) {
    if (!result.data || result.data.length === 0) {
        document.getElementById('previewContent').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> Nenhum dado encontrado
            </div>
        `;
        return;
    }
    
    const visualizationType = result.query_mode === 'custom_sql' 
        ? document.querySelector('[name="visualization_type_sql"]').value
        : document.getElementById('visualizationType').value;
    
    if (visualizationType === 'table') {
        renderTable(result.data);
    } else {
        renderChart(result.data, visualizationType);
    }
    
    const connectionLabels = {
        local: 'Banco Local',
        sap_b1: 'SAP B1 HANA',
        sap_api: 'SAP API'
    };
    const connectionInfo = connectionLabels[result.connection_type] || (result.connection_type || 'Desconhecido');
    
    // Mostrar informações e avisos
    let infoHtml = `
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i> 
            ${result.rows_count} registro(s) | ${result.execution_time}s | 
            Conexão: ${connectionInfo}
            ${result.sql ? '<br><small><code>' + result.sql + '</code></small>' : ''}
        </div>
    `;
    
    // Adicionar warning se existir
    if (result.warning) {
        infoHtml += `
            <div class="alert alert-warning mt-2">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Atenção:</strong> ${result.warning}
            </div>
        `;
    }
    
    if (result.cache) {
        const cacheDate = new Date((result.cache.stored_at || 0) * 1000);
        const cacheTs = cacheDate.getTime();
        const cacheDateLabel = Number.isNaN(cacheTs) ? '-' : cacheDate.toLocaleString('pt-BR');
        const cacheLabel = result.cache.from_cache ? 'Dados vindos do cache' : 'Consulta atualizada agora';
        infoHtml += `
            <div class="alert alert-secondary mt-2">
                <i class="fas fa-database"></i> ${cacheLabel} em ${cacheDateLabel}
            </div>
        `;
    }
    
    document.getElementById('previewContent').insertAdjacentHTML('beforeend', infoHtml);
}

function renderTable(data) {
    const headers = Object.keys(data[0]);
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
                <thead class="table-dark">
                    <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
    `;
    data.forEach(row => {
        html += '<tr>';
        headers.forEach(h => html += `<td>${row[h] ?? ''}</td>`);
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('previewContent').innerHTML = html;
}

function renderChart(data, type) {
    document.getElementById('previewContent').innerHTML = '<canvas id="previewChart"></canvas>';
    const labels = data.map(row => Object.values(row)[0]);
    const values = data.map(row => parseFloat(Object.values(row)[1]) || 0);
    
    new Chart(document.getElementById('previewChart'), {
        type: type.replace('_chart', ''),
        data: {
            labels: labels,
            datasets: [{
                label: 'Valores',
                data: values,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {responsive: true, maintainAspectRatio: true, aspectRatio: 2}
    });
}

function showError(error, sql = null) {
    document.getElementById('previewContent').innerHTML = `
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Erro</h5>
            <p><strong>Mensagem:</strong> ${error}</p>
            ${sql ? '<p><strong>SQL:</strong><br><code>' + sql + '</code></p>' : ''}
        </div>
    `;
}

function onFormSubmit(e) {
    const activeTab = document.querySelector('.tab-pane.active').id;
    console.log('💾 Salvando relatório - aba ativa:', activeTab);
    
    if (activeTab === 'sql-mode') {
        // Sincronizar Monaco Editor com textarea antes de enviar
        if (sqlEditor) {
            document.getElementById('customSql').value = sqlEditor.getValue();
        }
        
        // Modo SQL Personalizado
        const sql = document.getElementById('customSql').value.trim();
        console.log('📝 SQL a salvar:', sql);
        
        if (!sql) {
            e.preventDefault();
            alert('Digite o SQL personalizado!');
            return false;
        }
        
        document.getElementById('queryMode').value = 'custom_sql';
        console.log('✅ query_mode definido como: custom_sql');
    } else {
        // Modo Builder
        console.log('🔨 Modo Builder - data_source:', reportState.dataSource);
        
        if (!reportState.dataSource) {
            e.preventDefault();
            alert('Selecione uma tabela!');
            return false;
        }
        
        document.getElementById('queryMode').value = 'builder';
        document.getElementById('fieldsJson').value = JSON.stringify(reportState.selectedFields);
        document.getElementById('filtersJson').value = JSON.stringify(reportState.filters || []);
        document.getElementById('orderbyJson').value = JSON.stringify(reportState.orderBy || []);
        document.getElementById('groupbyJson').value = JSON.stringify(reportState.groupBy || []);
        
        console.log('✅ query_mode definido como: builder');
        console.log('✅ fields:', reportState.selectedFields);
        console.log('✅ filters:', reportState.filters);
        console.log('✅ orderBy:', reportState.orderBy);
        console.log('✅ groupBy:', reportState.groupBy);
    }
    
    console.log('📤 Enviando formulário...');
    return true;
}
</script>

<style>
.table-item:hover {
    background-color: #e9ecef;
}
.field-item:hover {
    background-color: #d1e7ff !important;
}
.min-height-100 {
    min-height: 100px;
}

/* Estilos para o editor em tela cheia */
#sqlEditorWrapper {
    transition: all 0.3s ease;
}

#sqlEditorWrapper[style*="position: fixed"] {
    box-shadow: 0 0 50px rgba(0, 0, 0, 0.5);
}

/* Botões de controle do editor */
#decreaseEditorBtn, #increaseEditorBtn, #fullscreenEditorBtn {
    transition: all 0.2s ease;
}

#decreaseEditorBtn:hover, #increaseEditorBtn:hover {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}

#fullscreenEditorBtn:hover {
    background-color: #0d6efd;
    color: white;
}

/* Animação suave para redimensionamento */
#sqlEditorContainer {
    transition: height 0.3s ease;
}
</style>

<?php
$years = $this->data['years'] ?? [];
$months = $this->data['months'] ?? [];
$currentYear = date('Y');
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mt-3">
            <i class="fas fa-chart-line text-primary"></i> Dashboard de Vendas - SAP B1
        </h2>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#queryEditorModal">
                <i class="fas fa-code"></i> Editar Query SQL
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendas SAP B1</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros de Consulta</h5>
        </div>
        <div class="card-body">
            <form id="filtersForm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Ano *</label>
                        <select class="form-select" id="filterYear" name="year" required>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $year == $currentYear ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Mês</label>
                        <select class="form-select" id="filterMonth" name="month">
                            <option value="">Todos os meses</option>
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>"><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Grupo de Parceiro</label>
                        <select class="form-select" id="filterPartnerGroup" name="partner_group">
                            <option value="">Todos os grupos</option>
                        </select>
                        <small class="text-muted">Carrega após primeira consulta</small>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Vendedor</label>
                        <select class="form-select" id="filterSalesperson" name="salesperson">
                            <option value="">Todos os vendedores</option>
                        </select>
                        <small class="text-muted">Carrega após primeira consulta</small>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100" id="btnConsultar">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-3 text-muted">Consultando dados do SAP B1...</p>
    </div>

    <!-- Conteúdo do Dashboard -->
    <div id="dashboardContent" style="display: none;">
        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">FATURAMENTO</h6>
                                <h3 class="text-success mb-0" id="kpiFaturamento">R$ 0,00</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-dollar-sign fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">TICKET MÉDIO</h6>
                                <h3 class="text-primary mb-0" id="kpiTicketMedio">R$ 0,00</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-receipt fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">VENDAS</h6>
                                <h3 class="text-info mb-0" id="kpiVendas">0</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-shopping-cart fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">ITENS VENDIDOS</h6>
                                <h3 class="text-warning mb-0" id="kpiItensVendidos">0</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-boxes fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segundo conjunto de KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted">CUSTO + IMPOSTOS</h6>
                        <h4 class="text-secondary mb-0" id="kpiCustoImpostos">R$ 0,00</h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-danger shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted">DESCONTO TOTAL</h6>
                        <h4 class="text-danger mb-0" id="kpiDescontoTotal">R$ 0,00</h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-danger shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted">% DESCONTO</h6>
                        <h4 class="text-danger mb-0" id="kpiDescontoPct">0%</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico Mensal -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Faturamento por Mês</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="80"></canvas>
            </div>
        </div>

        <!-- Tabela por Vendedor -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Vendas por Vendedor</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="salespersonTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Vendedor</th>
                                <th class="text-end">Faturamento</th>
                                <th class="text-center">Vendas</th>
                                <th class="text-end">Ticket Médio</th>
                                <th class="text-center">Itens</th>
                            </tr>
                        </thead>
                        <tbody id="salespersonTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Clique em "Consultar" para carregar os dados
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr id="salespersonTotal" style="display: none;">
                                <td>TOTAL</td>
                                <td class="text-end" id="totalFaturamento">R$ 0,00</td>
                                <td class="text-center" id="totalVendas">0</td>
                                <td class="text-end" id="totalTicketMedio">R$ 0,00</td>
                                <td class="text-center" id="totalItens">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info dos dados -->
        <div class="alert alert-info mt-3" id="dataInfo" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="dataInfoText"></span>
        </div>
    </div>
</div>

<!-- Modal para Editar Query SQL -->
<div class="modal fade" id="queryEditorModal" tabindex="-1" aria-labelledby="queryEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="queryEditorModalLabel">
                    <i class="fas fa-code"></i> Editar Query SQL do Dashboard
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Instruções:</strong> Edite a query SQL base do dashboard. 
                    Use as variáveis <code>{ANO}</code>, <code>{MES}</code>, <code>{GRUPO}</code> e <code>{VENDEDOR}</code> que serão substituídas pelos filtros.
                </div>
                
                <div id="queryEditorContainer" style="height: 500px; border: 2px solid #0d6efd; border-radius: 5px;"></div>
                
                <input type="hidden" id="customQuerySql" name="custom_query_sql">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnRestoreDefaultQuery">
                    <i class="fas fa-undo"></i> Restaurar Query Padrão
                </button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnSaveCustomQuery">
                    <i class="fas fa-save"></i> Salvar Query
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
let monthlyChart = null;
let queryEditor = null;
let customQuerySql = localStorage.getItem('sales_dashboard_custom_query') || null;

// Indicador visual se há query customizada
if (customQuerySql) {
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.querySelector('[data-bs-target="#queryEditorModal"]');
        if (btn) {
            btn.classList.add('btn-warning');
            btn.classList.remove('btn-outline-secondary');
            btn.innerHTML = '<i class="fas fa-code"></i> Editar Query SQL <span class="badge bg-danger">Custom</span>';
        }
    });
}

// Inicializar Monaco Editor no modal
document.getElementById('queryEditorModal').addEventListener('shown.bs.modal', function() {
    if (!queryEditor) {
        initQueryEditor();
    } else {
        queryEditor.layout();
    }
});

function initQueryEditor() {
    require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
    
    require(['vs/editor/editor.main'], function() {
        const defaultQuery = getDefaultQuery();
        const initialValue = customQuerySql || defaultQuery;
        
        queryEditor = monaco.editor.create(document.getElementById('queryEditorContainer'), {
            value: initialValue,
            language: 'sql',
            theme: 'vs',
            fontSize: 14,
            automaticLayout: true,
            minimap: { enabled: true },
            wordWrap: 'on',
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            bracketPairColorization: { enabled: true }
        });
        
        console.log('✅ Query Editor inicializado');
    });
}

function getDefaultQuery() {
    return `-- Query Padrão do Dashboard de Vendas
-- Variáveis disponíveis: {ANO}, {MES}, {GRUPO}, {VENDEDOR}

SELECT
    YEAR(T0."DocDate") AS "Ano",
    MONTH(T0."DocDate") AS "Mes",
    T0."DocNum" AS "NumDoc",
    T0."DocDate" AS "DataCriacao",
    T0."CardName" AS "nomePN",
    T7."GroupName" AS "nomeGrupoPN",
    T2."SlpName" AS "nomeVendedor",
    T1."Quantity" AS "Qtde",
    T1."LineTotal" AS "TotalLinha",
    (T1."StockPrice" * T1."Quantity") + COALESCE(T10."TotalImpostos", 0) AS "Custo_Impostos",
    T0."DiscSum" AS "DescontoRodape"
FROM OINV T0
INNER JOIN INV1 T1 ON T0."DocEntry" = T1."DocEntry"
INNER JOIN OSLP T2 ON T0."SlpCode" = T2."SlpCode"
INNER JOIN OITM T4 ON T1."ItemCode" = T4."ItemCode"
INNER JOIN OITB T5 ON T4."ItmsGrpCod" = T5."ItmsGrpCod"
INNER JOIN OCRD T6 ON T0."CardCode" = T6."CardCode"
INNER JOIN OCRG T7 ON T6."GroupCode" = T7."GroupCode"
LEFT JOIN (
    SELECT "DocEntry", "LineNum", SUM("TaxSum") AS "TotalImpostos"
    FROM INV4
    GROUP BY "DocEntry", "LineNum"
) T10 ON T1."DocEntry" = T10."DocEntry" AND T1."LineNum" = T10."LineNum"
WHERE T0."DocType" = 'I'
AND T0."CANCELED" = 'N'
AND YEAR(T0."DocDate") = {ANO}
{MES_FILTER}
{GRUPO_FILTER}
{VENDEDOR_FILTER}
ORDER BY T0."DocDate" DESC
LIMIT 5000`;
}

// Salvar query customizada
document.getElementById('btnSaveCustomQuery').addEventListener('click', function() {
    if (queryEditor) {
        const query = queryEditor.getValue();
        
        // Validar que é SELECT
        if (!query.trim().match(/^\s*SELECT/i)) {
            alert('❌ Apenas queries SELECT são permitidas!');
            return;
        }
        
        localStorage.setItem('sales_dashboard_custom_query', query);
        customQuerySql = query;
        
        // Atualizar aparência do botão
        const btn = document.querySelector('[data-bs-target="#queryEditorModal"]');
        if (btn) {
            btn.classList.add('btn-warning');
            btn.classList.remove('btn-outline-secondary');
            btn.innerHTML = '<i class="fas fa-code"></i> Editar Query SQL <span class="badge bg-danger">Custom</span>';
        }
        
        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('queryEditorModal'));
        modal.hide();
        
        alert('✅ Query customizada salva! Clique em "Consultar" para executar.');
    }
});

// Restaurar query padrão
document.getElementById('btnRestoreDefaultQuery').addEventListener('click', function() {
    if (confirm('Deseja restaurar a query padrão? A query customizada será perdida.')) {
        const defaultQuery = getDefaultQuery();
        if (queryEditor) {
            queryEditor.setValue(defaultQuery);
        }
        localStorage.removeItem('sales_dashboard_custom_query');
        customQuerySql = null;
        
        // Restaurar aparência do botão
        const btn = document.querySelector('[data-bs-target="#queryEditorModal"]');
        if (btn) {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-outline-secondary');
            btn.innerHTML = '<i class="fas fa-code"></i> Editar Query SQL';
        }
        
        alert('✅ Query padrão restaurada!');
    }
});

document.getElementById('filtersForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Adicionar query customizada se existir
    if (customQuerySql) {
        formData.append('custom_query', customQuerySql);
    }
    
    const btnConsultar = document.getElementById('btnConsultar');
    
    // Mostrar loading
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('dashboardContent').style.display = 'none';
    btnConsultar.disabled = true;
    btnConsultar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Consultando...';
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>sales-dashboard-data', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result);
            updateFilters(result.data);
        } else {
            alert('Erro: ' + result.error);
        }
        
    } catch (error) {
        alert('Erro ao carregar dados: ' + error.message);
    } finally {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('dashboardContent').style.display = 'block';
        btnConsultar.disabled = false;
        btnConsultar.innerHTML = '<i class="fas fa-search"></i> Consultar';
    }
});

function updateDashboard(result) {
    const kpis = result.kpis;
    
    // Atualizar KPIs
    document.getElementById('kpiFaturamento').textContent = formatCurrency(kpis.faturamento);
    document.getElementById('kpiTicketMedio').textContent = formatCurrency(kpis.ticket_medio);
    document.getElementById('kpiVendas').textContent = formatNumber(kpis.vendas);
    document.getElementById('kpiItensVendidos').textContent = formatNumber(kpis.itens_vendidos);
    document.getElementById('kpiCustoImpostos').textContent = formatCurrency(kpis.custo_impostos);
    document.getElementById('kpiDescontoTotal').textContent = formatCurrency(kpis.desconto_total);
    document.getElementById('kpiDescontoPct').textContent = kpis.desconto_pct.toFixed(2) + '%';
    
    // Atualizar gráfico mensal
    updateMonthlyChart(result.monthly_data);
    
    // Atualizar tabela de vendedores
    updateSalespersonTable(result.salesperson_data, kpis);
    
    // Mostrar info
    const dataInfo = document.getElementById('dataInfo');
    const dataInfoText = document.getElementById('dataInfoText');
    dataInfoText.textContent = `${result.rows_count} registros processados em ${result.execution_time}s`;
    dataInfo.style.display = 'block';
    
    if (result.warning) {
        dataInfoText.innerHTML += `<br><strong>Atenção:</strong> ${result.warning}`;
        dataInfo.className = 'alert alert-warning mt-3';
    }
}

function updateMonthlyChart(monthlyData) {
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const data = [];
    const labels = [];
    
    for (let i = 1; i <= 12; i++) {
        labels.push(months[i-1]);
        data.push(monthlyData[i] || 0);
    }
    
    if (monthlyChart) {
        monthlyChart.destroy();
    }
    
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Faturamento',
                data: data,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                        }
                    }
                }
            }
        }
    });
}

function updateSalespersonTable(salespersonData, totals) {
    const tbody = document.getElementById('salespersonTableBody');
    tbody.innerHTML = '';
    
    if (Object.keys(salespersonData).length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum dado encontrado</td></tr>';
        return;
    }
    
    for (const [vendedor, data] of Object.entries(salespersonData)) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${vendedor}</td>
            <td class="text-end">${formatCurrency(data.faturamento)}</td>
            <td class="text-center">${data.vendas}</td>
            <td class="text-end">${formatCurrency(data.ticket_medio)}</td>
            <td class="text-center">${formatNumber(data.itens)}</td>
        `;
        tbody.appendChild(tr);
    }
    
    // Atualizar totais
    document.getElementById('totalFaturamento').textContent = formatCurrency(totals.faturamento);
    document.getElementById('totalVendas').textContent = formatNumber(totals.vendas);
    document.getElementById('totalTicketMedio').textContent = formatCurrency(totals.ticket_medio);
    document.getElementById('totalItens').textContent = formatNumber(totals.itens_vendidos);
    document.getElementById('salespersonTotal').style.display = '';
}

function updateFilters(data) {
    // Extrair grupos de parceiros únicos
    const partnerGroups = new Set();
    const salespersons = new Set();
    
    data.forEach(row => {
        if (row.nomeGrupoPN || row.nomegrupoPN) {
            partnerGroups.add(row.nomeGrupoPN || row.nomegrupoPN);
        }
        if (row.nomeVendedor || row.nomevendedor) {
            salespersons.add(row.nomeVendedor || row.nomevendedor);
        }
    });
    
    // Atualizar select de grupos
    const partnerGroupSelect = document.getElementById('filterPartnerGroup');
    const currentPartnerGroup = partnerGroupSelect.value;
    partnerGroupSelect.innerHTML = '<option value="">Todos os grupos</option>';
    Array.from(partnerGroups).sort().forEach(group => {
        const option = document.createElement('option');
        option.value = group;
        option.textContent = group;
        if (group === currentPartnerGroup) option.selected = true;
        partnerGroupSelect.appendChild(option);
    });
    
    // Atualizar select de vendedores
    const salespersonSelect = document.getElementById('filterSalesperson');
    const currentSalesperson = salespersonSelect.value;
    salespersonSelect.innerHTML = '<option value="">Todos os vendedores</option>';
    Array.from(salespersons).sort().forEach(salesperson => {
        const option = document.createElement('option');
        option.value = salesperson;
        option.textContent = salesperson;
        if (salesperson === currentSalesperson) option.selected = true;
        salespersonSelect.appendChild(option);
    });
}

function formatCurrency(value) {
    return 'R$ ' + parseFloat(value).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function formatNumber(value) {
    return parseInt(value).toLocaleString('pt-BR');
}
</script>


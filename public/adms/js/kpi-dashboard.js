/**
 * KPI Dashboard - Atualização em tempo real
 */

const KpiDashboard = {
    refreshInterval: null,
    charts: {},

    init() {
        console.log('📊 Inicializando KPI Dashboard...');
        
        const container = document.getElementById('kpi-dashboard-container');
        if (!container) return;

        const dashboardId = container.dataset.dashboardId;
        const refreshSeconds = parseInt(container.dataset.refreshInterval) || 0;

        // Carregar dados de todos os widgets
        this.loadAllWidgets();

        // Configurar atualização automática se definido
        if (refreshSeconds > 0) {
            console.log(`🔄 Atualização automática configurada: ${refreshSeconds}s`);
            this.refreshInterval = setInterval(() => {
                this.loadAllWidgets();
            }, refreshSeconds * 1000);
        }
    },

    loadAllWidgets() {
        const widgets = document.querySelectorAll('.kpi-widget');
        console.log(`📈 Carregando ${widgets.length} widgets...`);

        widgets.forEach(widget => {
            const widgetId = widget.dataset.widgetId;
            const widgetType = widget.dataset.widgetType;
            
            this.loadWidget(widgetId, widgetType);
        });
    },

    async loadWidget(widgetId, widgetType) {
        try {
            const urlBase = document.querySelector('meta[name="url-base"]')?.content || '';
            const response = await fetch(`${urlBase}get-kpi-widget-data?widget_id=${widgetId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erro desconhecido');
            }

            console.log(`✅ Widget ${widgetId} carregado:`, result);

            // Renderizar conforme o tipo
            switch (widgetType) {
                case 'number':
                case 'gauge':
                    this.renderNumber(widgetId, result.data);
                    break;
                
                case 'chart_bar':
                    this.renderChart(widgetId, result.data, 'bar');
                    break;
                
                case 'chart_line':
                    this.renderChart(widgetId, result.data, 'line');
                    break;
                
                case 'chart_pie':
                    this.renderChart(widgetId, result.data, 'pie');
                    break;
                
                case 'chart_doughnut':
                    this.renderChart(widgetId, result.data, 'doughnut');
                    break;
                
                case 'table':
                    this.renderTable(widgetId, result.data);
                    break;
            }

        } catch (error) {
            console.error(`❌ Erro ao carregar widget ${widgetId}:`, error);
            const valueEl = document.getElementById(`widget-value-${widgetId}`);
            if (valueEl) {
                valueEl.innerHTML = '<span class="text-danger">Erro ao carregar</span>';
            }
        }
    },

    renderNumber(widgetId, data) {
        const valueEl = document.getElementById(`widget-value-${widgetId}`);
        if (!valueEl) return;

        valueEl.textContent = data.formatted || '0';
        valueEl.classList.add('animate__animated', 'animate__fadeIn');

        // Atualizar barra de progresso se houver meta
        if (data.target) {
            const progressEl = document.getElementById(`widget-progress-${widgetId}`);
            if (progressEl) {
                const percentage = Math.min(100, (parseFloat(data.value) / parseFloat(data.target)) * 100);
                progressEl.style.width = `${percentage}%`;
                
                // Cor conforme progresso
                progressEl.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                if (percentage < 50) {
                    progressEl.classList.add('bg-danger');
                } else if (percentage < 80) {
                    progressEl.classList.add('bg-warning');
                } else {
                    progressEl.classList.add('bg-success');
                }
            }
        }
    },

    renderChart(widgetId, data, chartType) {
        const canvas = document.getElementById(`widget-chart-${widgetId}`);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Destruir gráfico anterior se existir
        if (this.charts[widgetId]) {
            this.charts[widgetId].destroy();
        }

        // Cores para gráficos
        const colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(0, 204, 102, 0.8)'
        ];

        const config = {
            type: chartType,
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: data.datasets?.[0]?.label || 'Dados',
                    data: data.values || [],
                    backgroundColor: chartType === 'pie' || chartType === 'doughnut' 
                        ? colors.slice(0, data.values?.length || 1)
                        : colors[0],
                    borderColor: chartType === 'line' ? colors[0] : undefined,
                    borderWidth: 1,
                    fill: chartType === 'line' ? false : undefined
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: chartType === 'pie' || chartType === 'doughnut',
                        position: 'bottom'
                    }
                },
                scales: chartType !== 'pie' && chartType !== 'doughnut' ? {
                    y: {
                        beginAtZero: true
                    }
                } : undefined
            }
        };

        this.charts[widgetId] = new Chart(ctx, config);
    },

    renderTable(widgetId, data) {
        const tableEl = document.getElementById(`widget-table-${widgetId}`);
        if (!tableEl) return;

        if (!data.rows || data.rows.length === 0) {
            tableEl.innerHTML = '<p class="text-muted">Nenhum dado disponível</p>';
            return;
        }

        let html = '<table class="table table-sm table-striped">';
        
        // Cabeçalho
        html += '<thead><tr>';
        data.columns.forEach(col => {
            html += `<th>${col}</th>`;
        });
        html += '</tr></thead>';

        // Corpo
        html += '<tbody>';
        data.rows.forEach(row => {
            html += '<tr>';
            Object.values(row).forEach(value => {
                html += `<td>${value !== null ? value : '-'}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody>';

        html += '</table>';
        tableEl.innerHTML = html;
    },

    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Destruir todos os gráficos
        Object.values(this.charts).forEach(chart => chart.destroy());
        this.charts = {};
    }
};

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => KpiDashboard.init());
} else {
    KpiDashboard.init();
}

// Limpar ao sair da página
window.addEventListener('beforeunload', () => KpiDashboard.destroy());


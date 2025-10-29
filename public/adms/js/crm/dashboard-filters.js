/**
 * DASHBOARD CRM - FILTROS DINÂMICOS INTERATIVOS
 * Funcionalidade estilo Power BI: clique nos gráficos para filtrar
 */

(function() {
    'use strict';

    // Função para atualizar URL com filtros
    function applyFilter(filterType, filterValue) {
        // Mostrar loading
        showFilteringAnimation(filterType, filterValue);
        
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        // Adicionar/atualizar filtro
        params.set(filterType, filterValue);

        // Redirecionar com novo filtro
        setTimeout(() => {
            window.location.href = url.pathname + '?' + params.toString();
        }, 300);
    }

    // Mostrar animação ao aplicar filtro
    function showFilteringAnimation(filterType, filterValue) {
        const filterLabels = {
            'filter_stage': 'Etapa',
            'filter_segment': 'Segmento',
            'responsible_user_id': 'Vendedor'
        };
        
        const label = filterLabels[filterType] || filterType;
        
        const loading = document.createElement('div');
        loading.className = 'filtering-animation';
        loading.innerHTML = `
            <div class="spinner-border text-success mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <h5 class="mb-1">Aplicando Filtro</h5>
            <p class="text-muted mb-0">${label}: <strong>${filterValue}</strong></p>
        `;
        document.body.appendChild(loading);
    }

    // Função para remover filtro
    function removeFilter(filterType) {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        params.delete(filterType);

        window.location.href = url.pathname + (params.toString() ? '?' + params.toString() : '');
    }

    // Expor funções globalmente
    window.CrmDashboard = {
        applyFilter: applyFilter,
        removeFilter: removeFilter,
        
        // Configurar interatividade do Chart.js
        makeChartInteractive: function(chart, filterType, valueExtractor) {
            chart.options.onClick = function(event, activeElements) {
                if (activeElements.length > 0) {
                    const index = activeElements[0].index;
                    const value = valueExtractor(chart, index);
                    
                    if (value) {
                        console.log('🎯 Filtro aplicado:', filterType, '=', value);
                        applyFilter(filterType, value);
                    }
                }
            };

            // Adicionar cursor pointer ao hover
            chart.options.onHover = function(event, activeElements) {
                event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
            };

            chart.update();
        }
    };

    // Mostrar filtros ativos
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const activeFilters = document.getElementById('activeFilters');
        
        if (!activeFilters) return;

        const filterLabels = {
            'filter_stage': 'Etapa',
            'filter_segment': 'Segmento',
            'responsible_user_id': 'Vendedor',
            'periodo_inicio': 'Período Início',
            'periodo_fim': 'Período Fim'
        };

        let hasFilters = false;

        params.forEach((value, key) => {
            if (filterLabels[key] && value) {
                hasFilters = true;
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary me-2 mb-2 d-inline-flex align-items-center';
                badge.style.fontSize = '0.9rem';
                badge.style.padding = '0.5rem 0.75rem';
                badge.innerHTML = `
                    <strong class="me-2">${filterLabels[key]}:</strong> ${decodeURIComponent(value)}
                    <button class="btn-close btn-close-white ms-2" style="font-size: 0.7rem;" 
                            onclick="CrmDashboard.removeFilter('${key}')" aria-label="Remover filtro"></button>
                `;
                activeFilters.appendChild(badge);
            }
        });

        if (hasFilters) {
            activeFilters.classList.remove('d-none');
            
            // Botão para limpar todos os filtros
            const clearAll = document.createElement('button');
            clearAll.className = 'btn btn-sm btn-outline-danger';
            clearAll.innerHTML = '<i class="fas fa-times me-1"></i>Limpar Todos';
            clearAll.onclick = function() {
                window.location.href = window.location.pathname;
            };
            activeFilters.appendChild(clearAll);
        }
    });

    console.log('✅ Dashboard CRM Interativo carregado');

})();


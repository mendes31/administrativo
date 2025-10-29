/**
 * KANBAN PIPELINE - CRM
 * Drag & Drop entre estágios
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        
        console.log('🎯 Kanban CRM: Inicializando...');

        // Buscar todas as colunas do Kanban
        const columns = document.querySelectorAll('.kanban-column-body');

        if (columns.length === 0) {
            console.warn('⚠️ Nenhuma coluna Kanban encontrada');
            return;
        }

        console.log(`✓ ${columns.length} colunas encontradas`);

        // Inicializar Sortable em cada coluna
        columns.forEach(column => {
            const stageId = column.getAttribute('data-stage-id');

            new Sortable(column, {
                group: 'kanban-opportunities',  // Permite mover entre colunas
                animation: 200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                dragClass: 'sortable-drag',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                forceFallback: true,
                fallbackTolerance: 3,

                // Ao soltar o card
                onEnd: function(evt) {
                    const opportunityId = evt.item.getAttribute('data-opportunity-id');
                    const newStageId = evt.to.getAttribute('data-stage-id');
                    const oldStageId = evt.from.getAttribute('data-stage-id');

                    // Se mudou de coluna
                    if (newStageId !== oldStageId) {
                        console.log(`📦 Movendo oportunidade ${opportunityId}: Stage ${oldStageId} → ${newStageId}`);
                        moveOpportunity(opportunityId, newStageId, evt);
                    }
                }
            });

            console.log(`✓ Sortable inicializado na etapa ${stageId}`);
        });

        console.log('✅ Kanban CRM pronto!');
    });

    /**
     * Mover oportunidade via AJAX
     */
    function moveOpportunity(opportunityId, newStageId, evt) {
        // Mostrar loading
        showLoading();

        // Fazer requisição
        fetch(window.location.origin + '/administrativo/crm-move-opportunity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                opportunity_id: opportunityId,
                new_stage_id: newStageId
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();

            if (data.success) {
                console.log('✓ Oportunidade movida com sucesso!');
                
                // Recarregar a página para atualizar contadores e valores
                window.location.reload();
            } else {
                console.error('✗ Erro ao mover:', data.message);
                
                // Reverter a mudança visual
                evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
                
                // Mostrar erro
                alert('Erro ao mover oportunidade: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            hideLoading();
            console.error('✗ Erro na requisição:', error);
            
            // Reverter
            evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
            
            alert('Erro ao mover oportunidade. Verifique sua conexão.');
        });
    }

    /**
     * Mostrar loading
     */
    function showLoading() {
        const loading = document.createElement('div');
        loading.className = 'kanban-loading';
        loading.id = 'kanban-loading';
        loading.innerHTML = `
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2 mb-0">Atualizando pipeline...</p>
        `;
        document.body.appendChild(loading);
    }

    /**
     * Esconder loading
     */
    function hideLoading() {
        const loading = document.getElementById('kanban-loading');
        if (loading) {
            loading.remove();
        }
    }

})();


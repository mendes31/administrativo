// JavaScript completo para a página de permissões

console.log('🚀 JavaScript de permissões carregado! - Timestamp:', new Date().toISOString());

// ===== FUNÇÕES DE GRUPOS =====

// Alternar visibilidade de um grupo
function toggleGroup(groupId) {
    console.log('🔄 Alternando grupo:', groupId);
    
    const groupHeader = document.querySelector(`[data-group="${groupId}"]`);
    const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
    const toggleIcon = groupHeader.querySelector('.toggle-icon');
    
    console.log('🔍 Elementos encontrados:');
    console.log('- groupHeader:', groupHeader);
    console.log('- contentRows:', contentRows);
    console.log('- toggleIcon:', toggleIcon);
    console.log('- Número de linhas de conteúdo:', contentRows.length);
    
    if (!groupHeader || !toggleIcon) {
        console.error('Elementos do grupo não encontrados:', groupId);
        return;
    }
    
    // Verificar se o grupo está expandido
    const isExpanded = groupHeader.classList.contains('expanded');
    console.log('📊 Estado atual do grupo:', isExpanded ? 'EXPANDIDO' : 'COLAPSADO');
    
    if (isExpanded) {
        // Colapsar grupo
        console.log('📁 Colapsando grupo:', groupId);
        groupHeader.classList.remove('expanded');
        contentRows.forEach((row, index) => {
            row.style.display = 'none';
            console.log(`- Linha ${index + 1} ocultada`);
        });
        toggleIcon.style.transform = 'rotate(0deg)';
        console.log('✅ Grupo colapsado:', groupId);
    } else {
        // Expandir grupo
        console.log('📂 Expandindo grupo:', groupId);
        groupHeader.classList.add('expanded');
        contentRows.forEach((row, index) => {
            row.style.display = 'table-row';
            console.log(`- Linha ${index + 1} exibida`);
        });
        toggleIcon.style.transform = 'rotate(90deg)';
        console.log('✅ Grupo expandido:', groupId);
    }
    
    // Verificar estado final
    const finalState = groupHeader.classList.contains('expanded');
    console.log('📊 Estado final do grupo:', finalState ? 'EXPANDIDO' : 'COLAPSADO');
}

// Expandir todos os grupos
function expandAllGroups() {
    console.log('📂 Expandindo todos os grupos');
    
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 Total de grupos encontrados: ${allGroups.length}`);
    
    let expandedCount = 0;
    allGroups.forEach((group, index) => {
        if (!group.classList.contains('expanded')) {
            const groupId = group.dataset.group;
            const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
            const toggleIcon = group.querySelector('.toggle-icon');
            
            console.log(`📂 Expandindo grupo ${index + 1}: ${groupId} (${contentRows.length} linhas)`);
            
            // Expandir diretamente sem chamar toggleGroup
            group.classList.add('expanded');
            contentRows.forEach(row => {
                row.style.display = 'table-row';
            });
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(90deg)';
            }
            
            expandedCount++;
        }
    });
    
    console.log(`✅ ${expandedCount} grupos expandidos de ${allGroups.length} total`);
}

// Colapsar todos os grupos
function collapseAllGroups() {
    console.log('📁 Colapsando todos os grupos');
    
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 Total de grupos encontrados: ${allGroups.length}`);
    
    let collapsedCount = 0;
    allGroups.forEach((group, index) => {
        if (group.classList.contains('expanded')) {
            const groupId = group.dataset.group;
            const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
            const toggleIcon = group.querySelector('.toggle-icon');
            
            console.log(`📁 Colapsando grupo ${index + 1}: ${groupId} (${contentRows.length} linhas)`);
            
            // Colapsar diretamente sem chamar toggleGroup
            group.classList.remove('expanded');
            contentRows.forEach(row => {
                row.style.display = 'none';
            });
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(0deg)';
            }
            
            collapsedCount++;
        }
    });
    
    console.log(`✅ ${collapsedCount} grupos colapsados de ${allGroups.length} total`);
}

// ===== FUNÇÕES DE PERMISSÕES =====

// Autorizar todas as permissões de um grupo
function authorizeGroup(groupId) {
    console.log('✅ Autorizando grupo:', groupId);
    
    const checkboxes = document.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    // Atualizar contadores
    updateGroupCounters(groupId);
    
    console.log(`Grupo ${groupId}: ${checkboxes.length} permissões autorizadas`);
}

// Revogar todas as permissões de um grupo
function revokeGroup(groupId) {
    console.log('❌ Revogando grupo:', groupId);
    
    const checkboxes = document.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Atualizar contadores
    updateGroupCounters(groupId);
    
    console.log(`Grupo ${groupId}: ${checkboxes.length} permissões revogadas`);
}

// ===== FUNÇÕES DE CONTADORES =====

// Atualizar contadores de um grupo específico
function updateGroupCounters(groupId) {
    console.log('📊 Atualizando contadores do grupo:', groupId);
    
    const groupHeader = document.querySelector(`[data-group="${groupId}"]`);
    if (!groupHeader) {
        console.error('Cabeçalho do grupo não encontrado:', groupId);
        return;
    }
    
    const checkboxes = document.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`);
    const totalCount = checkboxes.length;
    let authorizedCount = 0;
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            authorizedCount++;
        }
    });
    
    const revokedCount = totalCount - authorizedCount;
    
    // Atualizar elementos de contador
    const totalElement = groupHeader.querySelector('.group-counters .bg-secondary');
    const authorizedElement = groupHeader.querySelector('.group-counters .bg-success');
    const revokedElement = groupHeader.querySelector('.group-counters .bg-danger');
    
    if (totalElement) totalElement.textContent = `Total: ${totalCount}`;
    if (authorizedElement) authorizedElement.textContent = `Autorizadas: ${authorizedCount}`;
    if (revokedElement) revokedElement.textContent = `Revogadas: ${revokedCount}`;
    
    console.log(`📊 Grupo ${groupId}: ${totalCount} total, ${authorizedCount} autorizadas, ${revokedCount} revogadas`);
}



// ===== FUNÇÕES DE FILTRO =====

// Filtrar grupos por termo de busca
function filterGroups(searchTerm) {
    console.log('🔍 Filtrando grupos por:', searchTerm);
    
    const allGroups = document.querySelectorAll('[data-group]');
    const searchLower = searchTerm.toLowerCase();
    
    allGroups.forEach(group => {
        const groupId = group.dataset.group;
        const groupName = groupId.toLowerCase();
        const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
        
        if (searchTerm === '' || groupName.includes(searchLower)) {
            // Mostrar grupo
            group.style.display = 'table-row';
            contentRows.forEach(row => {
                if (group.classList.contains('expanded')) {
                    row.style.display = 'table-row';
                }
            });
        } else {
            // Ocultar grupo
            group.style.display = 'none';
            contentRows.forEach(row => {
                row.style.display = 'none';
            });
        }
    });
    
    console.log('Filtro aplicado');
}

// ===== FUNÇÕES DE SALVAMENTO =====

// Confirmar e salvar permissões
function confirmAndSavePermissions() {
    console.log('🚀 confirmAndSavePermissions chamada!');
    
    const confirmMessage = 'Deseja realmente salvar as alterações nas permissões?';
    if (confirm(confirmMessage)) {
        console.log('✅ Usuário confirmou, salvando...');
        savePermissions();
    } else {
        console.log('❌ Usuário cancelou');
    }
}

// Salvar permissões via AJAX
function savePermissions() {
    console.log('🚀 === FUNÇÃO SAVEPERMISSIONS INICIADA ===');
    console.log('Timestamp:', new Date().toISOString());
    
    // Encontrar o formulário
    const saveButton = document.getElementById('savePermissionsBtn');
    if (!saveButton) {
        console.error('Botão Salvar Permissões não encontrado');
        showError('Botão Salvar Permissões não encontrado');
        return;
    }
    
    const form = saveButton.closest('form');
    if (!form) {
        console.error('Formulário não encontrado');
        showError('Formulário não encontrado');
        return;
    }
    
    console.log('Formulário encontrado:', form);
    console.log('Action do formulário:', form.action);
    
    // Coletar dados do formulário
    const csrf_token = form.querySelector('input[name="csrf_token"]').value;
    const adms_access_level_id = form.querySelector('input[name="adms_access_level_id"]').value;
    
    if (!csrf_token || !adms_access_level_id) {
        console.error('Dados obrigatórios não encontrados');
        showError('Dados obrigatórios não encontrados');
        return;
    }
    
    // Coletar todas as permissões
    const allToggles = document.querySelectorAll('.permission-toggle');
    console.log('Total de toggles encontrados:', allToggles.length);
    
    const permissions = {};
    allToggles.forEach((toggle, index) => {
        const pageId = toggle.dataset.pageId;
        const isChecked = toggle.checked;
        
        if (pageId) {
            permissions[pageId] = isChecked ? 1 : 0;
            console.log(`Página ${pageId}: ${isChecked ? '✅ Marcado (1)' : '❌ Desmarcado (0)'}`);
        }
    });
    
    console.log('Permissões coletadas:', permissions);
    
    if (Object.keys(permissions).length === 0) {
        showError('Nenhuma permissão encontrada para processar');
        return;
    }
    
    // Mostrar indicador de carregamento
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    saveButton.disabled = true;
    
    // Preparar dados para envio
    const formData = new FormData();
    formData.append('csrf_token', csrf_token);
    formData.append('adms_access_level_id', adms_access_level_id);
    
    // Adicionar todas as permissões
    Object.entries(permissions).forEach(([pageId, value]) => {
        const key = `permissions[${pageId}]`;
        formData.append(key, value);
        console.log(`Adicionado: ${key} = ${value}`);
    });
    
    // Converter para URLSearchParams
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    console.log('Iniciando requisição AJAX...');
    console.log('URL:', form.action);
    console.log('Dados:', params.toString());
    
    // Fazer requisição AJAX
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
    })
    .then(response => {
        console.log('Resposta recebida:', response);
        console.log('Status:', response.status);
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.log('Resposta não é JSON:', text);
                throw new Error('Servidor retornou HTML em vez de JSON');
            });
        }
    })
    .then(data => {
        console.log('Dados da resposta:', data);
        
        if (data.success) {
            showSuccess('Permissões salvas com sucesso!');
            console.log('Recarregando página em 2 segundos...');
            
            // Recarregar a página após 2 segundos
            setTimeout(() => {
                const timestamp = new Date().getTime();
                const currentUrl = window.location.href.split('?')[0];
                window.location.href = currentUrl + '?t=' + timestamp;
            }, 2000);
        } else {
            showError('Erro ao salvar permissões: ' + (data.message || 'Desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro na requisição AJAX:', error);
        showError('Erro de conexão: ' + error.message);
    })
    .finally(() => {
        // Restaurar botão
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
        console.log('=== FUNÇÃO SAVEPERMISSIONS FINALIZADA ===');
    });
}

// ===== FUNÇÕES DE UTILIDADE =====

// Função para mostrar alerta de sucesso
function showSuccess(message) {
    const alert = document.getElementById('successAlert');
    const messageSpan = document.getElementById('successMessage');
    
    if (alert && messageSpan) {
        messageSpan.textContent = message;
        alert.style.display = 'block';
        alert.classList.add('show');
        
        // Auto-ocultar após 5 segundos
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.style.display = 'none', 150);
        }, 5000);
    } else {
        alert('SUCESSO: ' + message);
    }
}

// Função para mostrar alerta de erro
function showError(message) {
    const alert = document.getElementById('errorAlert');
    const messageSpan = document.getElementById('errorMessage');
    
    if (alert && messageSpan) {
        messageSpan.textContent = message;
        alert.style.display = 'block';
        alert.classList.add('show');
        
        // Auto-ocultar após 8 segundos
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.style.display = 'none', 150);
        }, 8000);
    } else {
        alert('ERRO: ' + message);
    }
}

// ===== INICIALIZAÇÃO =====

// Função de inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 === INICIALIZAÇÃO COMPLETA ===');
    
    // Configurar event listener para o botão Salvar Permissões
    const saveButton = document.getElementById('savePermissionsBtn');
    if (saveButton) {
        console.log('✅ Botão Salvar Permissões encontrado:', saveButton);
        
        // Adicionar event listener
        saveButton.addEventListener('click', function(event) {
            console.log('🔔 Evento click capturado no botão Salvar Permissões');
            event.preventDefault();
            confirmAndSavePermissions();
        });
        
        console.log('✅ Event listener configurado para o botão Salvar Permissões');
    } else {
        console.error('❌ Botão Salvar Permissões NÃO encontrado!');
    }
    
    // Configurar event listeners para toggles de permissão
    const permissionToggles = document.querySelectorAll('.permission-toggle');
    permissionToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const groupId = this.dataset.group;
            if (groupId) {
                updateGroupCounters(groupId);
            }
        });
    });
    
    console.log(`✅ ${permissionToggles.length} toggles de permissão configurados`);
    
    // Verificar se há grupos e configurar inicialização
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 ${allGroups.length} grupos encontrados na página`);
    
    // Garantir que todos os grupos iniciem colapsados e atualizar contadores
    allGroups.forEach(group => {
        const groupId = group.dataset.group;
        const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
        const toggleIcon = group.querySelector('.toggle-icon');
        
        // Garantir que grupos iniciem colapsados
        group.classList.remove('expanded');
        contentRows.forEach(row => {
            row.style.display = 'none';
        });
        
        if (toggleIcon) {
            toggleIcon.style.transform = 'rotate(0deg)';
        }
        
        // Atualizar contadores iniciais
        updateGroupCounters(groupId);
    });
    
    console.log(`✅ ${allGroups.length} grupos inicializados como colapsados e contadores atualizados`);
    
    console.log('🏁 === INICIALIZAÇÃO COMPLETA CONCLUÍDA ===');
});

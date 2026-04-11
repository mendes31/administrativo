// JavaScript completo para a página de permissões

console.log('🚀 JavaScript de permissões carregado! - Timestamp:', new Date().toISOString());

// ===== FUNÇÕES DE GRUPOS =====

// Funções auxiliares removidas - botões de ação não são mais necessários

// Alternar visibilidade de um grupo (DESKTOP)
function toggleGroup(groupId) {
    console.log('🔄 Alternando grupo DESKTOP:', groupId);
    
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

// Alternar visibilidade de um grupo (MOBILE)
function toggleGroupMobile(groupId) {
    console.log('🔄 Alternando grupo MOBILE:', groupId);
    
    const groupCard = document.querySelector(`.group-card[data-group="${groupId}"]`);
    const contentMobile = document.querySelector(`.group-content-mobile[data-group-content="${groupId}"]`);
    const toggleIcon = groupCard.querySelector('.toggle-icon-mobile');
    
    console.log('🔍 Elementos mobile encontrados:');
    console.log('- groupCard:', groupCard);
    console.log('- contentMobile:', contentMobile);
    console.log('- toggleIcon:', toggleIcon);
    
    if (!groupCard || !contentMobile || !toggleIcon) {
        console.error('Elementos mobile do grupo não encontrados:', groupId);
        return;
    }
    
    // Verificar se o grupo está expandido
    const isExpanded = groupCard.classList.contains('expanded');
    console.log('📊 Estado atual do grupo mobile:', isExpanded ? 'EXPANDIDO' : 'COLAPSADO');
    
    if (isExpanded) {
        // Colapsar grupo
        console.log('📁 Colapsando grupo mobile:', groupId);
        groupCard.classList.remove('expanded');
        contentMobile.style.display = 'none';
        toggleIcon.style.transform = 'rotate(0deg)';
        console.log('✅ Grupo mobile colapsado:', groupId);
    } else {
        // Expandir grupo
        console.log('📂 Expandindo grupo mobile:', groupId);
        groupCard.classList.add('expanded');
        contentMobile.style.display = 'block';
        toggleIcon.style.transform = 'rotate(90deg)';
        console.log('✅ Grupo mobile expandido:', groupId);
    }
    
    // Verificar estado final
    const finalState = groupCard.classList.contains('expanded');
    console.log('📊 Estado final do grupo mobile:', finalState ? 'EXPANDIDO' : 'COLAPSADO');
}

// Expandir todos os grupos (DESKTOP + MOBILE)
function expandAllGroups() {
    console.log('📂 Expandindo todos os grupos (DESKTOP + MOBILE)');
    
    // Desktop
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 Total de grupos desktop encontrados: ${allGroups.length}`);
    
    let expandedCount = 0;
    allGroups.forEach((group, index) => {
        if (!group.classList.contains('expanded')) {
            const groupId = group.dataset.group;
            const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
            const toggleIcon = group.querySelector('.toggle-icon');
            
            console.log(`📂 Expandindo grupo desktop ${index + 1}: ${groupId} (${contentRows.length} linhas)`);
            
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
    
    // Mobile
    const allGroupCards = document.querySelectorAll('.group-card');
    console.log(`📊 Total de grupos mobile encontrados: ${allGroupCards.length}`);
    
    let expandedMobileCount = 0;
    allGroupCards.forEach((card, index) => {
        if (!card.classList.contains('expanded')) {
            const groupId = card.dataset.group;
            const contentMobile = card.querySelector('.group-content-mobile');
            const toggleIcon = card.querySelector('.toggle-icon-mobile');
            
            console.log(`📂 Expandindo grupo mobile ${index + 1}: ${groupId}`);
            
            // Expandir diretamente
            card.classList.add('expanded');
            if (contentMobile) {
                contentMobile.style.display = 'block';
            }
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(90deg)';
            }
            
            expandedMobileCount++;
        }
    });
    
    console.log(`✅ ${expandedCount} grupos desktop e ${expandedMobileCount} grupos mobile expandidos`);
}

// Colapsar todos os grupos (DESKTOP + MOBILE)
function collapseAllGroups() {
    console.log('📁 Colapsando todos os grupos (DESKTOP + MOBILE)');
    
    // Desktop
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 Total de grupos desktop encontrados: ${allGroups.length}`);
    
    let collapsedCount = 0;
    allGroups.forEach((group, index) => {
        if (group.classList.contains('expanded')) {
            const groupId = group.dataset.group;
            const contentRows = document.querySelectorAll(`[data-group-content="${groupId}"]`);
            const toggleIcon = group.querySelector('.toggle-icon');
            
            console.log(`📁 Colapsando grupo desktop ${index + 1}: ${groupId} (${contentRows.length} linhas)`);
            
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
    
    // Mobile
    const allGroupCards = document.querySelectorAll('.group-card');
    console.log(`📊 Total de grupos mobile encontrados: ${allGroupCards.length}`);
    
    let collapsedMobileCount = 0;
    allGroupCards.forEach((card, index) => {
        if (card.classList.contains('expanded')) {
            const groupId = card.dataset.group;
            const contentMobile = card.querySelector('.group-content-mobile');
            const toggleIcon = card.querySelector('.toggle-icon-mobile');
            
            console.log(`📁 Colapsando grupo mobile ${index + 1}: ${groupId}`);
            
            // Colapsar diretamente
            card.classList.remove('expanded');
            if (contentMobile) {
                contentMobile.style.display = 'none';
            }
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(0deg)';
            }
            
            collapsedMobileCount++;
        }
    });
    
    console.log(`✅ ${collapsedCount} grupos desktop e ${collapsedMobileCount} grupos mobile colapsados`);
}

// ===== FUNÇÕES DE PERMISSÕES =====

// Autorizar todas as permissões de um grupo (DESKTOP + MOBILE)
function authorizeGroup(groupId) {
    console.log('✅ Autorizando grupo:', groupId);
    
    // Desktop: Autorizar checkboxes na tabela (APENAS DESKTOP)
    const desktopTable = document.querySelector('.table-permissions-desktop');
    const desktopCheckboxes = desktopTable ? desktopTable.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`) : [];
    desktopCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    // Mobile: Autorizar checkboxes nos cards (APENAS MOBILE)
    const groupCard = document.querySelector(`.group-card[data-group="${groupId}"]`);
    if (groupCard) {
        const mobileCheckboxes = groupCard.querySelectorAll('.permission-toggle');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    }
    
    // Atualizar contadores
    updateGroupCounters(groupId);
    
    console.log(`✅ Grupo ${groupId}: ${desktopCheckboxes.length} permissões desktop e ${groupCard ? groupCard.querySelectorAll('.permission-toggle').length : 0} mobile autorizadas`);
}

// Revogar todas as permissões de um grupo (DESKTOP + MOBILE)
function revokeGroup(groupId) {
    console.log('❌ Revogando grupo:', groupId);
    
    // Desktop: Revogar checkboxes na tabela (APENAS DESKTOP)
    const desktopTable = document.querySelector('.table-permissions-desktop');
    const desktopCheckboxes = desktopTable ? desktopTable.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`) : [];
    desktopCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Mobile: Revogar checkboxes nos cards (APENAS MOBILE)
    const groupCard = document.querySelector(`.group-card[data-group="${groupId}"]`);
    if (groupCard) {
        const mobileCheckboxes = groupCard.querySelectorAll('.permission-toggle');
        mobileCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    
    // Atualizar contadores
    updateGroupCounters(groupId);
    
    console.log(`❌ Grupo ${groupId}: ${desktopCheckboxes.length} permissões desktop e ${groupCard ? groupCard.querySelectorAll('.permission-toggle').length : 0} mobile revogadas`);
}

// ===== FUNÇÕES DE CONTADORES =====

// Atualizar contadores de um grupo específico (DESKTOP + MOBILE)
function updateGroupCounters(groupId) {
    console.log('📊 Atualizando contadores do grupo:', groupId);
    
    // Desktop: Atualizar contadores na tabela (APENAS DESKTOP)
    const groupHeader = document.querySelector(`[data-group="${groupId}"]`);
    if (groupHeader) {
        console.log('📊 Atualizando contadores DESKTOP para grupo:', groupId);
        
        // BUSCAR APENAS CHECKBOXES DO DESKTOP (tabela visível)
        const desktopTable = document.querySelector('.table-permissions-desktop');
        const checkboxes = desktopTable ? desktopTable.querySelectorAll(`[data-group-content="${groupId}"] .permission-toggle`) : [];
        
        const totalCount = checkboxes.length;
        let authorizedCount = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                authorizedCount++;
            }
        });
        
        const revokedCount = totalCount - authorizedCount;
        
        // Debug: Verificar todos os checkboxes encontrados
        console.log(`🔍 Desktop - Checkboxes encontrados: ${checkboxes.length} (apenas tabela desktop)`);
        
        // Atualizar elementos de contador no desktop
        const totalElement = groupHeader.querySelector('.group-counters .bg-secondary');
        const authorizedElement = groupHeader.querySelector('.group-counters .bg-success');
        const revokedElement = groupHeader.querySelector('.group-counters .bg-danger');
        
        if (totalElement) totalElement.textContent = `Total: ${totalCount}`;
        if (authorizedElement) authorizedElement.textContent = `Autorizadas: ${authorizedCount}`;
        if (revokedElement) revokedElement.textContent = `Revogadas: ${revokedCount}`;
        
        console.log(`📊 Desktop - Grupo ${groupId}: ${totalCount} total, ${authorizedCount} autorizadas, ${revokedCount} revogadas`);
    }
    
    // Mobile: Atualizar contadores nos cards
    const groupCard = document.querySelector(`.group-card[data-group="${groupId}"]`);
    if (groupCard) {
        console.log('📊 Atualizando contadores MOBILE para grupo:', groupId);
        
        const checkboxes = groupCard.querySelectorAll('.permission-toggle');
        const totalCount = checkboxes.length;
        let authorizedCount = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                authorizedCount++;
            }
        });
        
        const revokedCount = totalCount - authorizedCount;
        
        // Debug: Verificar checkboxes mobile
        console.log(`🔍 Mobile - Checkboxes encontrados: ${checkboxes.length}`);
        
        // Atualizar elementos de contador no mobile (CORRIGIDO: buscar diretamente no card-header)
        const totalElement = groupCard.querySelector('.card-header .bg-secondary');
        const authorizedElement = groupCard.querySelector('.card-header .bg-success');
        const revokedElement = groupCard.querySelector('.card-header .bg-danger');
        
        // Debug: Verificar se os elementos foram encontrados
        console.log(`🔍 Mobile - Elementos de contador encontrados:`);
        console.log(`  - Total: ${totalElement ? '✅' : '❌'}`);
        console.log(`  - Autorizadas: ${authorizedElement ? '✅' : '❌'}`);
        console.log(`  - Revogadas: ${revokedElement ? '✅' : '❌'}`);
        
        if (totalElement) totalElement.textContent = `Total: ${totalCount}`;
        if (authorizedElement) authorizedElement.textContent = `Autorizadas: ${authorizedCount}`;
        if (revokedElement) revokedElement.textContent = `Revogadas: ${revokedCount}`;
        
        console.log(`📊 Mobile - Grupo ${groupId}: ${totalCount} total, ${authorizedCount} autorizadas, ${revokedCount} revogadas`);
    }
    
    if (!groupHeader && !groupCard) {
        console.error('❌ Nenhum elemento do grupo encontrado (desktop ou mobile):', groupId);
    }
}



// ===== FUNÇÕES DE FILTRO =====

// Filtrar grupos por termo de busca
function filterGroups(searchTerm) {
    console.log('🔍 Filtrando grupos por:', searchTerm);
    
    // Desktop
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
    
    // Mobile
    const allGroupCards = document.querySelectorAll('.group-card');
    allGroupCards.forEach(card => {
        const groupId = card.dataset.group;
        const groupName = groupId.toLowerCase();
        const contentMobile = card.querySelector('.group-content-mobile');
        
        if (searchTerm === '' || groupName.includes(searchLower)) {
            // Mostrar grupo
            card.style.display = 'block';
        } else {
            // Ocultar grupo
            card.style.display = 'none';
            if (contentMobile) {
                contentMobile.style.display = 'none';
            }
        }
    });
    
    console.log('Filtro aplicado para desktop e mobile');
}

// ===== FUNÇÕES DE SALVAMENTO =====

// Confirmar e salvar permissões
function confirmAndSavePermissions() {
    console.log('🚀 confirmAndSavePermissions chamada!');

    const permFormCheck = document.getElementById('permissionsForm');
    if (permFormCheck && permFormCheck.getAttribute('data-permissions-locked') === '1') {
        showError('As permissões do nível Super Administrador não podem ser alteradas.');
        return;
    }
    
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
    
    // O botão "Salvar" pode estar fora do <form>; capturar o formulário por seletor global
    let form = document.getElementById('permissionsForm');
    if (!form) {
        form = saveButton.closest('form') || document.querySelector('form[action*="list-access-levels-permissions"]');
    }
    if (!form) {
        console.error('Formulário não encontrado');
        showError('Formulário não encontrado');
        return;
    }

    if (form.getAttribute('data-permissions-locked') === '1') {
        showError('As permissões do nível Super Administrador não podem ser alteradas.');
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
    
    // Coletar as permissões apenas da UI visível (evita duplicidade desktop/mobile)
    let allToggles = [];
    try {
        const isDesktop = window.matchMedia('(min-width: 768px)').matches;
        if (isDesktop) {
            const desktopContainer = form.querySelector('.table-permissions-desktop');
            allToggles = desktopContainer ? desktopContainer.querySelectorAll('.permission-toggle') : form.querySelectorAll('.permission-toggle');
        } else {
            const mobileContainer = form.querySelector('.d-block.d-md-none');
            allToggles = mobileContainer ? mobileContainer.querySelectorAll('.permission-toggle') : form.querySelectorAll('.permission-toggle');
        }
    } catch (e) {
        console.warn('Fallback para coleta padrão de toggles:', e);
        allToggles = form.querySelectorAll('.permission-toggle');
    }
    console.log('Total de toggles encontrados:', allToggles.length);
    
    const permissions = {};
    function addPermission(pageId, isChecked) {
        if (!pageId || parseInt(pageId, 10) <= 0) return; // evita envio de page_id 0 (causa Duplicate key no banco)
        permissions[pageId] = isChecked ? 1 : 0;
    }

    // Coletar APENAS toggles cujo estado foi alterado em relação ao inicial
    allToggles.forEach((toggle, index) => {
        const pageId = toggle.dataset.pageId;
        const initial = toggle.dataset.initial || '0';
        const current = toggle.checked ? '1' : '0';

        if (pageId && initial !== current) {
            addPermission(pageId, toggle.checked);
            console.log(
                `Página ${pageId}: estado alterado de ${initial} para ${current} ` +
                (current === '1' ? '✅ (Autorizar)' : '❌ (Revogar)')
            );
        }
    });

    // Fallback: se por algum motivo nenhuma permissão alterada foi detectada,
    // faz a coleta completa (comportamento antigo) para não quebrar o fluxo.
    if (Object.keys(permissions).length === 0) {
        console.log('Nenhuma alteração detectada; aplicando fallback para coleta completa.');
        const backupToggles = form.querySelectorAll('.permission-toggle');
        backupToggles.forEach((toggle) => {
            const pageId = toggle.dataset.pageId;
            if (!pageId) return;
            addPermission(pageId, toggle.checked);
        });
    }
    
    console.log('Permissões coletadas (apenas alterações ou fallback):', permissions);
    
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
    
    // Adicionar todas as permissões (nunca enviar permissions[0] — evita erro Duplicate key no banco)
    Object.entries(permissions).forEach(([pageId, value]) => {
        if (pageId === '0' || parseInt(pageId, 10) <= 0) return;
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
        
        // Atualizar token CSRF no formulário para permitir novo envio sem recarregar a página
        if (data.csrf_token && form) {
            const tokenInput = form.querySelector('input[name="csrf_token"]');
            if (tokenInput) {
                tokenInput.value = data.csrf_token;
                console.log('Token CSRF atualizado no formulário');
            }
        }
        
        if (data.success) {
            showSuccess('Permissões salvas com sucesso!');
            console.log('Recarregando página em 2 segundos...');
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

    // Configurar event listener para o botão Salvar (mobile)
    const saveButtonMobile = document.getElementById('savePermissionsBtnMobile');
    if (saveButtonMobile) {
        console.log('✅ Botão Salvar Permissões (mobile) encontrado:', saveButtonMobile);
        saveButtonMobile.addEventListener('click', function(event) {
            console.log('🔔 Evento click capturado no botão Salvar (mobile)');
            event.preventDefault();
            confirmAndSavePermissions();
        });
    }
    
    // Configurar event listeners para toggles de permissão
    const permissionToggles = document.querySelectorAll('.permission-toggle');
    permissionToggles.forEach(toggle => {
        // Salvar estado inicial (para enviar apenas alterações)
        toggle.dataset.initial = toggle.checked ? '1' : '0';

        toggle.addEventListener('change', function() {
            const groupId = this.dataset.group;
            if (groupId) {
                updateGroupCounters(groupId);
            }
        });
    });
    
    console.log(`✅ ${permissionToggles.length} toggles de permissão configurados (estado inicial salvo)`);
    
    // Verificar se há grupos e configurar inicialização
    const allGroups = document.querySelectorAll('[data-group]');
    console.log(`📊 ${allGroups.length} grupos desktop encontrados na página`);
    
    // Garantir que todos os grupos desktop iniciem colapsados e atualizar contadores
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
    
    // Verificar grupos mobile
    const allGroupCards = document.querySelectorAll('.group-card');
    console.log(`📊 ${allGroupCards.length} grupos mobile encontrados na página`);
    
    // Garantir que todos os grupos mobile iniciem colapsados
    allGroupCards.forEach(card => {
        const groupId = card.dataset.group;
        const contentMobile = card.querySelector('.group-content-mobile');
        const toggleIcon = card.querySelector('.toggle-icon-mobile');
        
        // Garantir que grupos iniciem colapsados
        card.classList.remove('expanded');
        if (contentMobile) {
            contentMobile.style.display = 'none';
        }
        
        if (toggleIcon) {
            toggleIcon.style.transform = 'rotate(0deg)';
        }
        
        // Atualizar contadores iniciais (já feito na função updateGroupCounters)
    });
    
    console.log(`✅ ${allGroups.length} grupos desktop e ${allGroupCards.length} grupos mobile inicializados como colapsados e contadores atualizados`);
    
    console.log('🏁 === INICIALIZAÇÃO COMPLETA CONCLUÍDA ===');
});

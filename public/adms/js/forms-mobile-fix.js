/**
 * Forms Mobile Fix - Garante que formulários funcionem em mobile
 * Previne que menus ou overlays bloqueiem submits
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // Detectar se é mobile
        const isMobile = window.innerWidth <= 991;
        if (!isMobile) {
            console.log('✓ Forms mobile fix: desktop detectado, não necessário');
            return;
        }
        
        console.log('📱 Forms mobile fix: mobile detectado, aplicando proteções');
        
        /**
         * Garantir que todos os botões de submit sejam clicáveis
         */
        const submitButtons = document.querySelectorAll('button[type="submit"], button.btn-primary, .btn-filtros-mobile');
        
        submitButtons.forEach(button => {
            button.style.position = 'relative';
            button.style.zIndex = '2000'; // Muito acima de tudo
            button.style.pointerEvents = 'auto';
            button.style.touchAction = 'manipulation';
            
            console.log('✅ Botão protegido:', button.textContent.trim());
        });
        
        /**
         * Garantir que todos os formulários sejam submit-áveis
         */
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.style.position = 'relative';
            form.style.zIndex = '1999';
            form.style.pointerEvents = 'auto';
            
            // Event listener para debug
            form.addEventListener('submit', function(e) {
                console.log('📤 Formulário sendo enviado:', {
                    action: this.action || 'mesma página',
                    method: this.method || 'GET',
                    menuAberto: document.body.classList.contains('sb-sidenav-toggled')
                });
            });
        });
        
        /**
         * Garantir que inputs e selects sejam clicáveis
         */
        const inputs = document.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.style.pointerEvents = 'auto';
            input.style.touchAction = 'manipulation';
        });
        
        /**
         * Proteger especialmente a caixa de pesquisa do menu
         */
        const menuSearch = document.getElementById('menuSearch');
        const menuSearchBox = document.querySelector('.menu-search-box');
        const menuSearchContainer = document.querySelector('.menu-search-container');
        
        if (menuSearch) {
            menuSearch.style.pointerEvents = 'auto';
            menuSearch.style.zIndex = '2001';
            menuSearch.style.touchAction = 'manipulation';
            console.log('✅ Caixa de pesquisa do menu protegida');
        }
        
        if (menuSearchBox) {
            menuSearchBox.style.pointerEvents = 'auto';
            menuSearchBox.style.zIndex = '2001';
        }
        
        if (menuSearchContainer) {
            menuSearchContainer.style.pointerEvents = 'auto';
            menuSearchContainer.style.zIndex = '2001';
        }
        
        console.log(`✓ Forms mobile fix aplicado:`,{
            formularios: forms.length,
            botoes: submitButtons.length,
            inputs: inputs.length,
            menuSearch: menuSearch ? 'protegido' : 'não encontrado'
        });
    });
    
})();


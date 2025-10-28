/**
 * Menu Mobile - Otimizações para dispositivos móveis
 * SIMPLIFICADO para evitar conflitos com sbadmin.js
 */

(function() {
    'use strict';
    
    // Detectar se é dispositivo mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    if (!isMobile && !isTouch) {
        console.log('✓ Menu mobile: não é dispositivo touch, otimizações desabilitadas');
        return; // Não executar em desktop
    }
    
    const sidenav = document.getElementById('layoutSidenav_nav');
    if (!sidenav) {
        console.warn('Menu mobile: layoutSidenav_nav não encontrado');
        return;
    }
    
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;
    
    /**
     * Fecha submenus automaticamente ao tocar fora (mobile)
     * DESABILITADO: Pode interferir com navegação
     */
    function setupAutoCloseOnOutsideTouch() {
        // Função desabilitada para evitar conflitos
        // O comportamento padrão do Bootstrap já é suficiente
    }
    
    /**
     * Melhora o feedback visual ao tocar em itens do menu
     * CORRIGIDO: Usa delegação de eventos para evitar memory leaks
     */
    function setupTouchFeedback() {
        if (!sidenav) return;
        
        // Usar delegação de eventos no container principal
        sidenav.addEventListener('touchstart', function(e) {
            const link = e.target.closest('.nav-link');
            if (link) {
                link.style.transform = 'scale(0.98)';
            }
        }, { passive: true });
        
        sidenav.addEventListener('touchend', function(e) {
            const link = e.target.closest('.nav-link');
            if (link) {
                link.style.transform = 'scale(1)';
            }
        }, { passive: true });
        
        sidenav.addEventListener('touchcancel', function(e) {
            const link = e.target.closest('.nav-link');
            if (link) {
                link.style.transform = 'scale(1)';
            }
        }, { passive: true });
    }
    
    /**
     * Otimiza o scroll do menu em mobile
     */
    function optimizeMenuScroll() {
        const menuContainer = sidenav.querySelector('.sb-sidenav-menu');
        if (!menuContainer) return;
        
        let isScrolling = false;
        
        menuContainer.addEventListener('scroll', function() {
            // Adiciona classe durante o scroll para otimizações CSS
            if (!isScrolling) {
                menuContainer.classList.add('is-scrolling');
            }
            
            // Remove a classe após parar de rolar
            clearTimeout(window.scrollTimer);
            window.scrollTimer = setTimeout(function() {
                menuContainer.classList.remove('is-scrolling');
                isScrolling = false;
            }, 150);
            
            isScrolling = true;
        }, { passive: true });
    }
    
    /**
     * Previne scroll do body quando rola o menu (mobile)
     * SIMPLIFICADO: Usando CSS overscroll-behavior ao invés de JavaScript
     */
    function preventBodyScrollWhenMenuScrolls() {
        // Função simplificada - o CSS já trata isso com overscroll-behavior
        const menuContainer = sidenav.querySelector('.sb-sidenav-menu');
        if (menuContainer) {
            menuContainer.style.overscrollBehavior = 'contain';
        }
    }
    
    /**
     * Adiciona indicador visual de scroll disponível
     * CORRIGIDO: Otimizado para evitar travamentos
     */
    function addScrollIndicators() {
        const menuContainer = sidenav.querySelector('.sb-sidenav-menu');
        if (!menuContainer) return;
        
        let updateTimeout;
        
        function updateScrollIndicators() {
            // Throttle para evitar muitas execuções
            if (updateTimeout) return;
            
            updateTimeout = setTimeout(() => {
                const scrollTop = menuContainer.scrollTop;
                const scrollHeight = menuContainer.scrollHeight;
                const clientHeight = menuContainer.clientHeight;
                
                // Adiciona classe se pode rolar para baixo
                if (scrollTop + clientHeight < scrollHeight - 10) {
                    menuContainer.classList.add('has-scroll-bottom');
                } else {
                    menuContainer.classList.remove('has-scroll-bottom');
                }
                
                // Adiciona classe se pode rolar para cima
                if (scrollTop > 10) {
                    menuContainer.classList.add('has-scroll-top');
                } else {
                    menuContainer.classList.remove('has-scroll-top');
                }
                
                updateTimeout = null;
            }, 100);
        }
        
        menuContainer.addEventListener('scroll', updateScrollIndicators, { passive: true });
        
        // Verificar inicialmente
        setTimeout(updateScrollIndicators, 300);
        
        // Observer mais leve - observa apenas mudanças nos collapses
        const observer = new MutationObserver((mutations) => {
            // Verificar apenas se houve mudança na classe 'show' dos collapses
            const hasCollapseChange = mutations.some(mutation => 
                mutation.target.classList && 
                mutation.target.classList.contains('collapse')
            );
            
            if (hasCollapseChange) {
                setTimeout(updateScrollIndicators, 350);
            }
        });
        
        observer.observe(menuContainer, { 
            attributes: true,
            attributeFilter: ['class'],
            subtree: true
        });
    }
    
    /**
     * Melhora a performance removendo transições durante o scroll
     */
    function optimizePerformance() {
        const menuContainer = sidenav.querySelector('.sb-sidenav-menu');
        if (!menuContainer) return;
        
        let scrollTimeout;
        
        menuContainer.addEventListener('scroll', function() {
            // Desabilita transições durante o scroll
            this.classList.add('disable-transitions');
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.classList.remove('disable-transitions');
            }, 150);
        }, { passive: true });
    }
    
    // Inicializar apenas otimizações seguras (sem interferir no toggle)
    document.addEventListener('DOMContentLoaded', function() {
        // setupAutoCloseOnOutsideTouch(); // DESABILITADO - causa conflito
        // setupTouchFeedback(); // DESABILITADO - pode interferir
        // optimizeMenuScroll(); // DESABILITADO - desnecessário
        preventBodyScrollWhenMenuScrolls(); // Apenas CSS
        addScrollIndicators(); // Apenas visual
        optimizePerformance(); // Apenas visual
        
        console.log('✓ Menu mobile otimizado (modo seguro)');
    });
    
})();


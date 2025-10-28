/**
 * Menu Toggle Fix - Garante funcionamento do botão hamburguer
 * Script simplificado que não interfere com sbadmin.js
 */

(function() {
    'use strict';
    
    // Aguardar o DOM e o sbadmin.js carregarem
    window.addEventListener('DOMContentLoaded', function() {
        
        // Pequeno delay para garantir que sbadmin.js execute primeiro
        setTimeout(function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (!sidebarToggle) {
                console.warn('Toggle fix: botão não encontrado');
                return;
            }
            
            // Adicionar listener adicional (não substitui o original)
            sidebarToggle.addEventListener('click', function(e) {
                console.log('🔘 Toggle clicado - Estado atual:', 
                    document.body.classList.contains('sb-sidenav-toggled') ? 'ABERTO' : 'FECHADO'
                );
                
                // Verificar após o clique se a classe mudou
                setTimeout(function() {
                    const isToggled = document.body.classList.contains('sb-sidenav-toggled');
                    console.log('📊 Novo estado:', isToggled ? 'ABERTO' : 'FECHADO');
                    
                    // Garantir que o z-index esteja correto
                    const nav = document.getElementById('layoutSidenav_nav');
                    if (nav && !isToggled) {
                        nav.style.zIndex = '1038';
                    }
                }, 50);
            }, false);
            
            // Em mobile, garantir que inicie fechado
            // LÓGICA CORRETA: SEM classe = fechado, COM classe = aberto
            if (window.innerWidth <= 991) {
                if (document.body.classList.contains('sb-sidenav-toggled')) {
                    console.log('📱 Mobile detectado - menu já está aberto, mantendo');
                } else {
                    console.log('📱 Mobile detectado - menu já está fechado, ok');
                }
            }
            
                   /**
                    * Log de debug para cliques em mobile (EXCETO modais e backdrop)
                    */
                   if (window.innerWidth <= 991) {
                       document.addEventListener('click', function(e) {
                           // NÃO logar cliques em modais ou backdrop para evitar spam
                           const isInModal = e.target.closest('.modal');
                           const isBackdrop = e.target.classList.contains('modal-backdrop');
                           
                           if (!isInModal && !isBackdrop) {
                               console.log('🖱️ Clique detectado:', {
                                   target: e.target.tagName,
                                   classes: e.target.className,
                                   id: e.target.id,
                                   menuAberto: document.body.classList.contains('sb-sidenav-toggled'),
                                   pointerEvents: window.getComputedStyle(e.target).pointerEvents
                               });
                           }
                       }, true);
                   }
            
            console.log('✓ Menu toggle fix aplicado (modo debug)');
            
        }, 100);
    });
    
})();


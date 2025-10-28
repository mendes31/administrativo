/**
 * Pesquisa no Menu - Funcionalidade completa
 * Busca em tempo real com destaque e expansão automática
 */

(function() {
    'use strict';
    
    let searchTimeout = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('menuSearch');
        const clearBtn = document.getElementById('clearMenuSearch');
        const resultsDiv = document.getElementById('menuSearchResults');
        const sidenavMenu = document.querySelector('.sb-sidenav-menu');
        
        if (!searchInput || !sidenavMenu) {
            console.warn('Menu search: elementos não encontrados');
            return;
        }
        
        // Prevenir que a pesquisa interfira com navegação
        let isNavigating = false;
        
        // Em mobile, pesquisa funciona independente do estado do menu
        function shouldExecuteSearch() {
            // SEMPRE permitir pesquisa
            return true;
        }
        
        // Coletar todos os links do menu (exceto os que são apenas containers de collapse)
        const allLinks = Array.from(sidenavMenu.querySelectorAll('.nav-link'))
            .filter(link => {
                // Filtrar apenas links que realmente levam a algum lugar
                return link.hasAttribute('href') && 
                       !link.getAttribute('href').includes('#collapse') &&
                       link.getAttribute('href') !== '#';
            });
        
        /**
         * Normalizar texto para pesquisa (remove acentos)
         */
        function normalizeText(text) {
            return text
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }
        
        /**
         * Extrair texto visível do link (sem ícones)
         */
        function getLinkText(link) {
            // Clonar o elemento para manipular
            const clone = link.cloneNode(true);
            
            // Remover ícones e setas
            const icons = clone.querySelectorAll('.sb-nav-link-icon, .sb-sidenav-collapse-arrow, i');
            icons.forEach(icon => icon.remove());
            
            return clone.textContent.trim();
        }
        
        /**
         * Expandir todos os pais de um elemento
         * CORRIGIDO: Não tenta usar Bootstrap Collapse API que pode travar
         */
        function expandParents(element) {
            let current = element.parentElement;
            
            while (current && current !== sidenavMenu) {
                if (current.classList.contains('collapse')) {
                    // Apenas adicionar classe show - mais simples e seguro
                    if (!current.classList.contains('show')) {
                        current.classList.add('show');
                    }
                }
                current = current.parentElement;
            }
        }
        
        /**
         * Recolher todos os submenus
         * CORRIGIDO: Não usa Bootstrap API
         */
        function collapseAll() {
            const allCollapses = sidenavMenu.querySelectorAll('.collapse.show');
            allCollapses.forEach(collapse => {
                collapse.classList.remove('show');
            });
        }
        
        /**
         * Realizar a pesquisa
         */
        function performSearch(query) {
            // Não executar se estiver navegando ou menu fechado em mobile
            if (isNavigating || !shouldExecuteSearch()) return;
            
            const normalizedQuery = normalizeText(query);
            
            if (normalizedQuery.length === 0) {
                clearSearch();
                return;
            }
            
            let matchCount = 0;
            const matches = [];
            
            // Marcar todos os links como hidden primeiro
            allLinks.forEach(link => {
                link.classList.remove('search-match', 'search-hidden');
                link.style.display = '';
            });
            
            // Procurar matches
            allLinks.forEach(link => {
                const linkText = getLinkText(link);
                const normalizedLinkText = normalizeText(linkText);
                
                if (normalizedLinkText.includes(normalizedQuery)) {
                    // Match encontrado
                    link.classList.add('search-match');
                    matches.push(link);
                    matchCount++;
                    
                    // Expandir pais
                    expandParents(link);
                } else {
                    // Não é match - ocultar
                    link.classList.add('search-hidden');
                    link.style.display = 'none';
                }
            });
            
            // Atualizar contador
            resultsDiv.style.display = 'block';
            const countSpan = resultsDiv.querySelector('.menu-search-count');
            if (countSpan) {
                countSpan.textContent = `${matchCount} resultado${matchCount !== 1 ? 's' : ''}`;
            }
            
            // Se houver matches, rolar para o primeiro
            if (matches.length > 0) {
                setTimeout(() => {
                    matches[0].scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 400); // Aguardar expansão dos collapses
            }
            
            // Mostrar botão limpar
            clearBtn.style.display = 'block';
        }
        
        /**
         * Limpar pesquisa e comprimir menu mostrando apenas item ativo
         */
        function clearSearch() {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            resultsDiv.style.display = 'none';
            
            // Remover classes de pesquisa
            allLinks.forEach(link => {
                link.classList.remove('search-match', 'search-hidden');
                link.style.display = '';
            });
            
            // Recolher TODOS os submenus primeiro
            collapseAll();
            
            // Expandir apenas o caminho do item ativo
            const activeLink = sidenavMenu.querySelector('.nav-link.active');
            if (activeLink) {
                expandParents(activeLink);
                
                // Rolar até o item ativo
                setTimeout(() => {
                    activeLink.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 400);
                
                console.log('🎯 Menu comprimido - apenas item ativo visível');
            } else {
                console.log('🎯 Menu comprimido - nenhum item ativo encontrado');
            }
        }
        
        /**
         * Event Listeners
         */
        
        // Pesquisar ao digitar (com debounce)
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                performSearch(e.target.value);
            }, 300); // Debounce de 300ms
        });
        
        // Botão limpar - comprimir menu ao limpar
        clearBtn.addEventListener('click', function() {
            clearSearch();
            searchInput.blur(); // Remover foco para melhor UX em mobile
            console.log('🧹 Pesquisa limpa - menu comprimido');
        });
        
        // Atalho de teclado Ctrl+K ou Cmd+K
        document.addEventListener('keydown', function(e) {
            // Apenas funciona se o menu estiver aberto ou for desktop
            if (!shouldExecuteSearch()) return;
            
            // Ctrl+K ou Cmd+K
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            
            // Ctrl+F dentro do menu
            if ((e.ctrlKey || e.metaKey) && e.key === 'f' && 
                sidenavMenu.contains(document.activeElement)) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            
            // ESC para limpar e comprimir menu
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                clearSearch();
                searchInput.blur();
                console.log('⌨️ ESC pressionado - menu comprimido');
            }
            
            // Enter para ir ao primeiro resultado
            if (e.key === 'Enter' && document.activeElement === searchInput) {
                const firstMatch = sidenavMenu.querySelector('.nav-link.search-match');
                if (firstMatch && firstMatch.getAttribute('href') !== '#') {
                    isNavigating = true;
                    window.location.href = firstMatch.getAttribute('href');
                }
            }
        });
        
        // Limpar ao clicar fora (DESABILITADO para evitar conflitos)
        // document.addEventListener('click', function(e) {
        //     if (!searchInput.contains(e.target) && 
        //         !clearBtn.contains(e.target) &&
        //         searchInput.value.length > 0) {
        //         // clearSearch();
        //     }
        // });
        
        // Prevenir submit se estiver em um form
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
        
        // Detectar quando usuário clica em um link do menu
        allLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Não bloquear o clique
                isNavigating = true;
                
                // Limpar pesquisa ao navegar
                setTimeout(() => {
                    if (searchInput.value) {
                        clearSearch();
                    }
                }, 100);
            }, { passive: true, capture: false });
        });
        
        // Detectar quando a página está prestes a ser descarregada
        window.addEventListener('beforeunload', function() {
            isNavigating = true;
            clearTimeout(searchTimeout);
        });
        
        console.log('✓ Pesquisa do menu inicializada');
        console.log(`  - ${allLinks.length} itens indexados`);
        console.log('  - Atalhos: Ctrl+K para focar, ESC para limpar, Enter para ir ao primeiro resultado');
    });
    
})();


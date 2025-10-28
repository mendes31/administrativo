/**
 * Script para rolar automaticamente o menu até o item ativo
 * Garante que o submenu selecionado fique sempre visível
 */
document.addEventListener('DOMContentLoaded', function() {
    const activeLink = document.querySelector('.sb-sidenav .nav-link.active');
    if (activeLink) {
        // Aguardar um momento para garantir que os colapses estejam abertos
        setTimeout(function() {
            const sidenavMenu = document.querySelector('.sb-sidenav-menu');
            if (sidenavMenu) {
                // Calcular a posição do item ativo em relação ao menu
                const linkRect = activeLink.getBoundingClientRect();
                const menuRect = sidenavMenu.getBoundingClientRect();
                
                // Rolar suavemente para o item, centralizando-o na tela
                const scrollOffset = activeLink.offsetTop - (sidenavMenu.clientHeight / 2) + (linkRect.height / 2);
                sidenavMenu.scrollTo({
                    top: scrollOffset,
                    behavior: 'smooth'
                });
            }
        }, 300);
    }
});


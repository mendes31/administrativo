/**
 * Sistema de Detecção de Resolução de Tela
 * Adapta automaticamente o layout baseado na resolução do dispositivo
 */

// Verificar se já foi inicializado para evitar execução dupla
if (window.screenResolutionManagerInitialized) {
    // Script já foi executado, não fazer nada
} else {
    // Marcar como inicializado
    window.screenResolutionManagerInitialized = true;
    
    // Função de inicialização
    function initializeScreenResolutionManager() {
        // Inicializar quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.screenResolutionManager = new ScreenResolutionManager();
            });
        } else {
            window.screenResolutionManager = new ScreenResolutionManager();
        }
    }

    // Chamar a função de inicialização
    initializeScreenResolutionManager();
}

class ScreenResolutionManager {
    constructor() {
        this.resolution = null;
        this.classes = null;
        this.pagination = null;
        this.init();
    }

    /**
     * Inicializa o sistema
     */
    init() {
        this.detectScreenResolution();
        this.setupResizeListener();
        this.applyResponsiveClasses();
    }

    /**
     * Detecta a resolução da tela
     */
    detectScreenResolution() {
        const width = window.screen.width || window.innerWidth;
        const height = window.screen.height || window.innerHeight;
        
        this.sendResolutionToServer(width, height);
    }

    /**
     * Envia a resolução para o servidor
     */
    sendResolutionToServer(width, height) {
        const formData = new FormData();
        formData.append('width', width);
        formData.append('height', height);

        fetch('/administrativo2/screen-resolution/set', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Verificar se a resposta é válida antes de tentar fazer JSON
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                this.resolution = data.resolution;
                this.classes = data.classes;
                this.pagination = data.pagination;
                this.applyResponsiveClasses();
                this.updatePaginationOptions();
            } else {
                console.warn('Resposta inválida do servidor para screen-resolution');
                this.applyDefaultClasses();
            }
        })
        .catch(error => {
            console.warn('Erro ao detectar resolução (usando classes padrão):', error.message);
            this.applyDefaultClasses();
        });
    }

    /**
     * Configura listener para mudanças de resolução
     */
    setupResizeListener() {
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.detectScreenResolution();
            }, 250);
        });
    }

    /**
     * Aplica classes responsivas baseadas na resolução
     */
    applyResponsiveClasses() {
        if (!this.classes) {
            this.applyDefaultClasses();
            return;
        }

        // Aplicar classes ao container principal
        const mainContainer = document.querySelector('.container-fluid');
        if (mainContainer) {
            mainContainer.className = this.classes.container;
        }

        // Aplicar classes às tabelas
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            table.className = this.classes.table;
        });

        // Aplicar classes aos cards
        const cardRows = document.querySelectorAll('.row.g-4, .row.g-3, .row.g-2');
        cardRows.forEach(row => {
            row.className = this.classes.cards;
        });

        // Aplicar classes aos filtros
        const filterRows = document.querySelectorAll('form .row');
        filterRows.forEach(row => {
            if (row.closest('form')) {
                row.className = this.classes.filters;
            }
        });

        // Aplicar classes às colunas dos filtros
        const filterCols = document.querySelectorAll('form .col-md-3, form .col-md-2, form .col-md-4, form .col-md-6');
        filterCols.forEach(col => {
            col.className = this.classes.filter_cols;
        });

        // Aplicar classes às colunas dos cards
        const cardCols = document.querySelectorAll('.col-md-3, .col-md-4, .col-md-6, .col-lg-2, .col-lg-3, .col-lg-4');
        cardCols.forEach(col => {
            if (col.closest('.card')) {
                col.className = this.classes.card_cols;
            }
        });

        // Aplicar classes específicas baseadas na categoria
        this.applyCategorySpecificClasses();
    }

    /**
     * Aplica classes específicas baseadas na categoria de resolução
     */
    applyCategorySpecificClasses() {
        if (!this.resolution) return;

        const category = this.resolution.category;
        const body = document.body;

        // Remover classes anteriores
        body.classList.remove('resolution-large', 'resolution-medium', 'resolution-small', 'resolution-mobile');

        // Adicionar classe atual
        body.classList.add(`resolution-${category}`);

        // Aplicar estilos específicos
        switch (category) {
            case 'large':
                this.applyLargeScreenStyles();
                break;
            case 'medium':
                this.applyMediumScreenStyles();
                break;
            case 'small':
                this.applySmallScreenStyles();
                break;
            case 'tablet':
                this.applyTabletStyles();
                break;
            case 'mobile':
                this.applyMobileStyles();
                break;
        }
    }

    /**
     * Estilos para telas grandes (1920px+)
     */
    applyLargeScreenStyles() {
        // Aumentar tamanho das fontes
        document.documentElement.style.setProperty('--bs-body-font-size', '1rem');
        
        // Aumentar espaçamentos
        document.documentElement.style.setProperty('--bs-spacer', '1.5rem');
        
        // Mostrar mais elementos
        const hiddenElements = document.querySelectorAll('.d-none.d-lg-block');
        hiddenElements.forEach(el => el.classList.remove('d-none'));
    }

    /**
     * Estilos para telas médias (1440px-1919px)
     */
    applyMediumScreenStyles() {
        // Tamanho padrão
        document.documentElement.style.setProperty('--bs-body-font-size', '0.875rem');
        document.documentElement.style.setProperty('--bs-spacer', '1rem');
    }

    /**
     * Estilos para telas pequenas (1366px-1439px) - Notebooks HD
     */
    applySmallScreenStyles() {
        // Reduzir tamanho das fontes
        document.documentElement.style.setProperty('--bs-body-font-size', '0.75rem');
        
        // Reduzir espaçamentos
        document.documentElement.style.setProperty('--bs-spacer', '0.5rem');
        
        // Ocultar elementos desnecessários
        const optionalElements = document.querySelectorAll('.d-lg-block');
        optionalElements.forEach(el => el.classList.add('d-none'));
    }

    /**
     * Estilos para tablets (1024px-1365px)
     */
    applyTabletStyles() {
        // Reduzir tamanho das fontes
        document.documentElement.style.setProperty('--bs-body-font-size', '0.8rem');
        
        // Reduzir espaçamentos
        document.documentElement.style.setProperty('--bs-spacer', '0.75rem');
        
        // Ocultar elementos desnecessários
        const optionalElements = document.querySelectorAll('.d-lg-block');
        optionalElements.forEach(el => el.classList.add('d-none'));
    }

    /**
     * Estilos para mobile (até 1023px)
     */
    applyMobileStyles() {
        // Fontes menores
        document.documentElement.style.setProperty('--bs-body-font-size', '0.75rem');
        
        // Espaçamentos mínimos
        document.documentElement.style.setProperty('--bs-spacer', '0.5rem');
        
        // Ocultar elementos desktop
        const desktopElements = document.querySelectorAll('.d-md-block');
        desktopElements.forEach(el => el.classList.add('d-none'));
    }

    /**
     * Aplica classes padrão (fallback)
     */
    applyDefaultClasses() {
        // Usar classes médias como padrão
        this.classes = {
            container: 'container-fluid px-3',
            table: 'table-responsive',
            cards: 'row g-3',
            card_cols: 'col-md-4 col-lg-3',
            filters: 'row g-2',
            filter_cols: 'col-md-4 col-lg-3'
        };
        
        this.applyResponsiveClasses();
    }

    /**
     * Atualiza opções de paginação baseadas na resolução
     */
    updatePaginationOptions() {
        if (!this.pagination) return;

        const perPageSelect = document.getElementById('per_page');
        if (perPageSelect) {
            // Limpar opções atuais
            perPageSelect.innerHTML = '';
            
            // Adicionar novas opções
            this.pagination.options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                if (option === this.pagination.per_page) {
                    optionElement.selected = true;
                }
                perPageSelect.appendChild(optionElement);
            });
        }
    }

    /**
     * Retorna informações da resolução atual
     */
    getCurrentResolution() {
        return {
            resolution: this.resolution,
            classes: this.classes,
            pagination: this.pagination
        };
    }
}

// Exportar para uso global
window.ScreenResolutionManager = ScreenResolutionManager; 
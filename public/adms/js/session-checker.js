/**
 * Session Checker - Verifica periodicamente se a sessão ainda é válida
 * Redireciona automaticamente para o login se a sessão expirar
 */

class SessionChecker {
    constructor() {
        // Configuração da sessão
        this.config = window.sessionConfig || {
            enabled: false,
            warningTime: 60000, // 1 minuto (usado só para verificação com backend)
            timeoutMinutes: 30,
            lockOffsetMinutes: 5 // minutos de inatividade para bloqueio de tela
        };
        
        // Bloqueio por inatividade (independente do tempo de expiração no servidor)
        this.lockTimeoutMs = (this.config.lockOffsetMinutes || 5) * 60 * 1000;
        this.lockTimer = null;
        
        // Tempos de save/block antigos (mantidos apenas para compatibilidade, não usados para avisos)
        this.saveTime = this.lockTimeoutMs; // salvar estado próximo do bloqueio
        this.blockTime = this.lockTimeoutMs;
        
        // Flags para controle de avisos
        this.warningShown = false;
        this.saveWarningShown = false;
        this.blockWarningShown = false;
        
        // Identificador único para esta aba
        this.tabId = this.generateTabId();
        
        // Inicializar se habilitado
        if (this.config.enabled) {
            this.init();
        }
    }
    
    // Gerar ID único para esta aba
    generateTabId() {
        // Detectar navegador para estratégias específicas
        this.browser = this.detectBrowser();
        console.log('Navegador detectado:', this.browser);
        
        // Estratégia específica para Firefox
        if (this.browser === 'firefox') {
            console.log('Firefox detectado - usando estratégia específica');
            return this.generateTabIdForFirefox();
        }
        
        // Estratégia padrão para outros navegadores
        let tabId = this.getTabIdFromAnyStorage();
        if (!tabId) {
            tabId = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            this.saveTabIdToAllStorages(tabId);
        }
        return tabId;
    }
    
    // Estratégia específica para Firefox
    generateTabIdForFirefox() {
        console.log('Firefox detectado - usando estratégia específica');
        
        // DEBUG: Verificar todas as opções disponíveis
        this.debugFirefoxCapabilities();
        
        // Tentar todas as estratégias em ordem de preferência
        const strategies = [
            () => this.tryLocalStorage(),
            () => this.trySessionStorage(),
            () => this.tryCookies(),
            () => this.tryIndexedDB(),
            () => this.tryGlobalVariable(),
            () => this.tryDOMStorage(),
            () => this.tryWebSQL(),
            () => this.tryFileSystem()
        ];
        
        for (let i = 0; i < strategies.length; i++) {
            try {
                const result = strategies[i]();
                if (result) {
                    console.log(`Firefox: Tab ID gerado com estratégia ${i + 1}:`, result);
                    return result;
                }
            } catch (error) {
                console.warn(`Firefox: Estratégia ${i + 1} falhou:`, error);
            }
        }
        
        // Fallback final: gerar novo ID e salvar em TODOS os lugares possíveis
        const fallbackId = 'firefox_fallback_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        console.log('Firefox: Usando fallback final:', fallbackId);
        
        // Tentar salvar em TODOS os lugares possíveis
        this.saveTabIdEverywhere(fallbackId);
        
        return fallbackId;
    }
    
    // Debug das capacidades do Firefox
    debugFirefoxCapabilities() {
        console.log('=== DEBUG FIREFOX CAPABILITIES ===');
        
        // Testar localStorage
        try {
            const testKey = 'firefox_test_' + Date.now();
            localStorage.setItem(testKey, 'test');
            const testValue = localStorage.getItem(testKey);
            localStorage.removeItem(testKey);
            console.log('Firefox localStorage:', testValue === 'test' ? 'FUNCIONANDO' : 'FALHOU');
        } catch (error) {
            console.log('Firefox localStorage: BLOQUEADO -', error.message);
        }
        
        // Testar sessionStorage
        try {
            const testKey = 'firefox_test_' + Date.now();
            sessionStorage.setItem(testKey, 'test');
            const testValue = sessionStorage.getItem(testKey);
            sessionStorage.removeItem(testKey);
            console.log('Firefox sessionStorage:', testValue === 'test' ? 'FUNCIONANDO' : 'FALHOU');
        } catch (error) {
            console.log('Firefox sessionStorage: BLOQUEADO -', error.message);
        }
        
        // Testar cookies
        try {
            const testKey = 'firefox_cookie_test';
            document.cookie = `${testKey}=test; path=/; max-age=60`;
            const hasCookie = document.cookie.includes(testKey);
            document.cookie = `${testKey}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
            console.log('Firefox cookies:', hasCookie ? 'FUNCIONANDO' : 'FALHOU');
        } catch (error) {
            console.log('Firefox cookies: BLOQUEADO -', error.message);
        }
        
        // Testar IndexedDB
        try {
            if ('indexedDB' in window) {
                console.log('Firefox IndexedDB: DISPONÍVEL');
            } else {
                console.log('Firefox IndexedDB: NÃO DISPONÍVEL');
            }
        } catch (error) {
            console.log('Firefox IndexedDB: ERRO -', error.message);
        }
        
        console.log('=== FIM DEBUG FIREFOX ===');
    }
    
    // Tentar localStorage
    tryLocalStorage() {
        try {
            let tabId = localStorage.getItem('current_tab_id');
            if (!tabId) {
                tabId = 'firefox_tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('current_tab_id', tabId);
                console.log('Firefox: Tab ID salvo no localStorage:', tabId);
            }
            return tabId;
        } catch (error) {
            console.warn('Firefox: localStorage falhou:', error);
            return null;
        }
    }
    
    // Tentar sessionStorage
    trySessionStorage() {
        try {
            let tabId = sessionStorage.getItem('current_tab_id');
            if (!tabId) {
                tabId = 'firefox_tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem('current_tab_id', tabId);
                console.log('Firefox: Tab ID salvo no sessionStorage:', tabId);
            }
            return tabId;
        } catch (error) {
            console.warn('Firefox: sessionStorage falhou:', error);
            return null;
        }
    }
    
    // Tentar cookies
    tryCookies() {
        try {
            const cookies = document.cookie.split(';');
            let tabId = null;
            
            for (let cookie of cookies) {
                const [cookieKey, cookieValue] = cookie.trim().split('=');
                if (cookieKey === 'current_tab_id') {
                    tabId = decodeURIComponent(cookieValue);
                    break;
                }
            }
            
            if (!tabId) {
                tabId = 'firefox_tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                document.cookie = `current_tab_id=${encodeURIComponent(tabId)}; path=/; max-age=86400`;
                console.log('Firefox: Tab ID salvo em cookie:', tabId);
            }
            
            return tabId;
        } catch (error) {
            console.warn('Firefox: cookies falharam:', error);
            return null;
        }
    }
    
    // Tentar IndexedDB (específico para Firefox)
    tryIndexedDB() {
        try {
            if ('indexedDB' in window) {
                const request = indexedDB.open('FirefoxTabStorage', 1);
                
                request.onerror = () => {
                    console.warn('Firefox: IndexedDB falhou');
                };
                
                request.onsuccess = (event) => {
                    const db = event.target.result;
                    const transaction = db.transaction(['tabs'], 'readwrite');
                    const store = transaction.objectStore('tabs');
                    
                    const getRequest = store.get('current_tab_id');
                    getRequest.onsuccess = () => {
                        if (getRequest.result) {
                            console.log('Firefox: Tab ID encontrado no IndexedDB:', getRequest.result);
                        } else {
                            const newTabId = 'firefox_tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            store.put(newTabId, 'current_tab_id');
                            console.log('Firefox: Tab ID salvo no IndexedDB:', newTabId);
                        }
                    };
                };
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains('tabs')) {
                        db.createObjectStore('tabs', { keyPath: 'id' });
                    }
                };
                
                // Retornar um ID temporário enquanto IndexedDB processa
                return 'firefox_temp_' + Date.now();
            }
        } catch (error) {
            console.warn('Firefox: IndexedDB falhou:', error);
        }
        return null;
    }
    
    // Tentar variável global
    tryGlobalVariable() {
        try {
            if (!window.firefoxTabId) {
                window.firefoxTabId = 'firefox_global_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                console.log('Firefox: Tab ID salvo em variável global:', window.firefoxTabId);
            }
            return window.firefoxTabId;
        } catch (error) {
            console.warn('Firefox: variável global falhou:', error);
            return null;
        }
    }
    
    // Salvar Tab ID em TODOS os lugares possíveis (Firefox)
    saveTabIdEverywhere(tabId) {
        console.log('Firefox: Salvando Tab ID em TODOS os lugares possíveis:', tabId);
        
        const storageMethods = [
            { name: 'localStorage', save: () => localStorage.setItem('current_tab_id', tabId) },
            { name: 'sessionStorage', save: () => sessionStorage.setItem('current_tab_id', tabId) },
            { name: 'cookies', save: () => document.cookie = `current_tab_id=${encodeURIComponent(tabId)}; path=/; max-age=86400` },
            { name: 'global', save: () => window.currentTabId = tabId },
            { name: 'firefoxGlobal', save: () => window.firefoxTabId = tabId },
            { name: 'documentData', save: () => document.documentElement.setAttribute('data-tab-id', tabId) },
            { name: 'metaTag', save: () => {
                let meta = document.querySelector('meta[name="firefox-tab-id"]');
                if (!meta) {
                    meta = document.createElement('meta');
                    meta.name = 'firefox-tab-id';
                    document.head.appendChild(meta);
                }
                meta.content = tabId;
            }},
            { name: 'titleSuffix', save: () => {
                const originalTitle = document.title;
                if (!originalTitle.includes(tabId)) {
                    document.title = originalTitle + ' [TAB:' + tabId + ']';
                }
            }}
        ];
        
        storageMethods.forEach(method => {
            try {
                method.save();
                console.log(`Firefox: Tab ID salvo em ${method.name}:`, tabId);
            } catch (error) {
                console.warn(`Firefox: Erro ao salvar em ${method.name}:`, error);
            }
        });
    }
    
    // Tentar DOM Storage (Firefox específico)
    tryDOMStorage() {
        try {
            // Verificar se há ID salvo no DOM
            const domTabId = document.documentElement.getAttribute('data-tab-id');
            if (domTabId) {
                console.log('Firefox: Tab ID encontrado no DOM:', domTabId);
                return domTabId;
            }
            
            // Verificar meta tag
            const metaTabId = document.querySelector('meta[name="firefox-tab-id"]')?.content;
            if (metaTabId) {
                console.log('Firefox: Tab ID encontrado na meta tag:', metaTabId);
                return metaTabId;
            }
            
            // Verificar título da página
            const titleMatch = document.title.match(/\[TAB:([^\]]+)\]/);
            if (titleMatch) {
                console.log('Firefox: Tab ID encontrado no título:', titleMatch[1]);
                return titleMatch[1];
            }
            
            // Gerar novo ID e salvar no DOM
            const newTabId = 'firefox_dom_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            document.documentElement.setAttribute('data-tab-id', newTabId);
            console.log('Firefox: Novo Tab ID salvo no DOM:', newTabId);
            return newTabId;
            
        } catch (error) {
            console.warn('Firefox: DOM Storage falhou:', error);
            return null;
        }
    }
    
    // Tentar WebSQL (Firefox específico)
    tryWebSQL() {
        try {
            if ('openDatabase' in window) {
                const db = openDatabase('FirefoxTabDB', '1.0', 'Firefox Tab Storage', 2 * 1024 * 1024);
                
                db.transaction(function(tx) {
                    tx.executeSql('CREATE TABLE IF NOT EXISTS tabs (id TEXT, tab_id TEXT)');
                    tx.executeSql('INSERT OR REPLACE INTO tabs (id, tab_id) VALUES (?, ?)', ['current_tab_id', 'firefox_websql_' + Date.now()]);
                });
                
                console.log('Firefox: Tab ID salvo no WebSQL');
                return 'firefox_websql_' + Date.now();
            }
        } catch (error) {
            console.warn('Firefox: WebSQL falhou:', error);
        }
        return null;
    }
    
    // Tentar FileSystem API (Firefox específico)
    tryFileSystem() {
        try {
            if ('requestFileSystem' in window) {
                const fs = requestFileSystem(window.PERSISTENT, 1024, function(fs) {
                    fs.root.getFile('firefox_tab_id.txt', {create: true}, function(fileEntry) {
                        fileEntry.createWriter(function(fileWriter) {
                            const tabId = 'firefox_file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            fileWriter.write(tabId);
                            console.log('Firefox: Tab ID salvo no FileSystem:', tabId);
                        });
                    });
                });
                return 'firefox_file_' + Date.now();
            }
        } catch (error) {
            console.warn('Firefox: FileSystem falhou:', error);
        }
        return null;
    }
    
    // Salvar Tab ID em todos os storages disponíveis
    saveTabIdToAllStorages(tabId) {
        const storages = [
            { name: 'localStorage', save: () => localStorage.setItem('current_tab_id', tabId) },
            { name: 'sessionStorage', save: () => sessionStorage.setItem('current_tab_id', tabId) },
            { name: 'cookies', save: () => document.cookie = `current_tab_id=${encodeURIComponent(tabId)}; path=/; max-age=86400` },
            { name: 'global', save: () => window.currentTabId = tabId }
        ];
        
        storages.forEach(storage => {
            try {
                storage.save();
                console.log(`Tab ID salvo em ${storage.name}:`, tabId);
            } catch (error) {
                console.warn(`Erro ao salvar em ${storage.name}:`, error);
            }
        });
    }
    
    // Obter Tab ID de qualquer storage disponível
    getTabIdFromAnyStorage() {
        // Se for Firefox, usar estratégia específica
        if (this.browser === 'firefox') {
            return this.getTabIdFromFirefox();
        }
        
        // Estratégia padrão para outros navegadores
        const storages = [
            { name: 'localStorage', get: () => localStorage.getItem('current_tab_id') },
            { name: 'sessionStorage', get: () => sessionStorage.getItem('current_tab_id') },
            { name: 'cookies', get: () => {
                const cookies = document.cookie.split(';');
                for (let cookie of cookies) {
                    const [cookieKey, cookieValue] = cookie.trim().split('=');
                    if (cookieKey === 'current_tab_id') {
                        return decodeURIComponent(cookieValue);
                    }
                }
                return null;
            }},
            { name: 'global', get: () => window.currentTabId }
        ];
        
        for (let storage of storages) {
            try {
                const tabId = storage.get();
                if (tabId) {
                    console.log(`Tab ID encontrado em ${storage.name}:`, tabId);
                    return tabId;
                }
            } catch (error) {
                console.warn(`Erro ao ler de ${storage.name}:`, error);
            }
        }
        
        console.log('Nenhum Tab ID encontrado em nenhum storage');
        return null;
    }
    
    // Estratégia específica para Firefox
    getTabIdFromFirefox() {
        console.log('Firefox: Buscando Tab ID em todos os métodos disponíveis...');
        
        const firefoxMethods = [
            { name: 'localStorage', get: () => localStorage.getItem('current_tab_id') },
            { name: 'sessionStorage', get: () => sessionStorage.getItem('current_tab_id') },
            { name: 'cookies', get: () => {
                const cookies = document.cookie.split(';');
                for (let cookie of cookies) {
                    const [cookieKey, cookieValue] = cookie.trim().split('=');
                    if (cookieKey === 'current_tab_id') {
                        return decodeURIComponent(cookieValue);
                    }
                }
                return null;
            }},
            { name: 'global', get: () => window.currentTabId },
            { name: 'firefoxGlobal', get: () => window.firefoxTabId },
            { name: 'DOM', get: () => document.documentElement.getAttribute('data-tab-id') },
            { name: 'metaTag', get: () => document.querySelector('meta[name="firefox-tab-id"]')?.content },
            { name: 'title', get: () => {
                const titleMatch = document.title.match(/\[TAB:([^\]]+)\]/);
                return titleMatch ? titleMatch[1] : null;
            }},
            { name: 'WebSQL', get: () => this.getTabIdFromWebSQL() },
            { name: 'FileSystem', get: () => this.getTabIdFromFileSystem() }
        ];
        
        for (let method of firefoxMethods) {
            try {
                const tabId = method.get();
                if (tabId) {
                    console.log(`Firefox: Tab ID encontrado em ${method.name}:`, tabId);
                    return tabId;
                }
            } catch (error) {
                console.warn(`Firefox: Erro ao ler de ${method.name}:`, error);
            }
        }
        
        console.log('Firefox: Nenhum Tab ID encontrado em nenhum método');
        return null;
    }
    
    // Recuperar Tab ID do WebSQL (Firefox)
    getTabIdFromWebSQL() {
        try {
            if ('openDatabase' in window) {
                const db = openDatabase('FirefoxTabDB', '1.0', 'Firefox Tab Storage', 2 * 1024 * 1024);
                
                return new Promise((resolve) => {
                    db.transaction(function(tx) {
                        tx.executeSql('SELECT tab_id FROM tabs WHERE id = ?', ['current_tab_id'], function(tx, result) {
                            if (result.rows.length > 0) {
                                resolve(result.rows[0].tab_id);
                            } else {
                                resolve(null);
                            }
                        });
                    });
                });
            }
        } catch (error) {
            console.warn('Firefox: Erro ao ler do WebSQL:', error);
        }
        return null;
    }
    
    // Recuperar Tab ID do FileSystem (Firefox)
    getTabIdFromFileSystem() {
        try {
            if ('requestFileSystem' in window) {
                return new Promise((resolve) => {
                    requestFileSystem(window.PERSISTENT, 1024, function(fs) {
                        fs.root.getFile('firefox_tab_id.txt', {}, function(fileEntry) {
                            fileEntry.file(function(file) {
                                const reader = new FileReader();
                                reader.onloadend = function() {
                                    resolve(this.result);
                                };
                                reader.readAsText(file);
                            });
                        }, function() {
                            resolve(null);
                        });
                    });
                });
            }
        } catch (error) {
            console.warn('Firefox: Erro ao ler do FileSystem:', error);
        }
        return null;
    }
    
    // Detectar navegador para estratégias específicas
    detectBrowser() {
        const userAgent = navigator.userAgent;
        let browser = 'unknown';
        
        if (userAgent.includes('Firefox')) {
            browser = 'firefox';
        } else if (userAgent.includes('Chrome')) {
            browser = 'chrome';
        } else if (userAgent.includes('Safari')) {
            browser = 'safari';
        } else if (userAgent.includes('Edge')) {
            browser = 'edge';
        } else if (userAgent.includes('MSIE') || userAgent.includes('Trident')) {
            browser = 'ie';
        }
        
        return browser;
    }
    
    // Limpar dados de storage com fallbacks
    clearFromStorage(key) {
        // Estratégia 1: localStorage
        try {
            localStorage.removeItem(key);
            console.log(`Dados removidos do localStorage: ${key}`);
        } catch (error) {
            console.warn(`Erro ao remover do localStorage para ${key}:`, error);
        }
        
        // Estratégia 2: sessionStorage
        try {
            sessionStorage.removeItem(key);
            console.log(`Dados removidos do sessionStorage: ${key}`);
        } catch (error) {
            console.warn(`Erro ao remover do sessionStorage para ${key}:`, error);
        }
        
        // Estratégia 3: Cookies
        try {
            document.cookie = `${key}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
            console.log(`Dados removidos de cookie: ${key}`);
        } catch (error) {
            console.warn(`Erro ao remover de cookie para ${key}:`, error);
        }
        
        // Estratégia 4: Variável global
        try {
            delete window[`storage_${key}`];
            console.log(`Dados removidos de variável global: ${key}`);
        } catch (error) {
            console.warn(`Erro ao remover de variável global para ${key}:`, error);
        }
    }
    
    // Função melhorada para salvar dados (inclui limpeza quando value é null)
    saveToStorage(key, value) {
        // Se value é null, limpar o storage
        if (value === null) {
            this.clearFromStorage(key);
            return true;
        }
        
        const data = JSON.stringify(value);
        
        // Estratégia 1: localStorage (padrão)
        try {
            localStorage.setItem(key, data);
            console.log(`Dados salvos no localStorage: ${key}`);
            return true;
        } catch (error) {
            console.warn(`Erro no localStorage para ${key}:`, error);
        }
        
        // Estratégia 2: sessionStorage (fallback)
        try {
            sessionStorage.setItem(key, data);
            console.log(`Dados salvos no sessionStorage (fallback): ${key}`);
            return true;
        } catch (error) {
            console.warn(`Erro no sessionStorage para ${key}:`, error);
        }
        
        // Estratégia 3: Cookies (fallback para navegadores muito restritivos)
        try {
            document.cookie = `${key}=${encodeURIComponent(data)}; path=/; max-age=3600`;
            console.log(`Dados salvos em cookie (fallback): ${key}`);
            return true;
        } catch (error) {
            console.warn(`Erro em cookie para ${key}:`, error);
        }
        
        // Estratégia 4: Variável global (último recurso)
        try {
            window[`storage_${key}`] = data;
            console.log(`Dados salvos em variável global (último recurso): ${key}`);
            return true;
        } catch (error) {
            console.error(`Erro em variável global para ${key}:`, error);
        }
        
        return false;
    }
    
    // Recuperar dados com fallbacks para diferentes navegadores
    getFromStorage(key) {
        // Estratégia 1: localStorage (padrão)
        try {
            const data = localStorage.getItem(key);
            if (data) {
                console.log(`Dados recuperados do localStorage: ${key}`);
                return JSON.parse(data);
            }
        } catch (error) {
            console.warn(`Erro ao ler do localStorage para ${key}:`, error);
        }
        
        // Estratégia 2: sessionStorage (fallback)
        try {
            const data = sessionStorage.getItem(key);
            if (data) {
                console.log(`Dados recuperados do sessionStorage (fallback): ${key}`);
                return JSON.parse(data);
            }
        } catch (error) {
            console.warn(`Erro ao ler do sessionStorage para ${key}:`, error);
        }
        
        // Estratégia 3: Cookies (fallback)
        try {
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [cookieKey, cookieValue] = cookie.trim().split('=');
                if (cookieKey === key) {
                    console.log(`Dados recuperados de cookie (fallback): ${key}`);
                    return JSON.parse(decodeURIComponent(cookieValue));
                }
            }
        } catch (error) {
            console.warn(`Erro ao ler de cookie para ${key}:`, error);
        }
        
        // Estratégia 4: Variável global (último recurso)
        try {
            const data = window[`storage_${key}`];
            if (data) {
                console.log(`Dados recuperados de variável global (último recurso): ${key}`);
                return JSON.parse(data);
            }
        } catch (error) {
            console.warn(`Erro ao ler de variável global para ${key}:`, error);
        }
        
        return null;
    }
    
    // Salvar contexto específico desta aba
    saveApplicationState() {
        console.log('Salvando estado da aplicação para aba:', this.tabId);
        try {
            const forms = document.querySelectorAll('form');
            forms.forEach((form, index) => {
                const formData = new FormData(form);
                const formObject = {};
                for (let [key, value] of formData.entries()) {
                    formObject[key] = value;
                }
                this.saveToStorage(`form_state_${this.tabId}_${index}`, {
                    action: form.action,
                    method: form.method,
                    data: formObject,
                    timestamp: Date.now()
                });
            });
            
            this.saveToStorage(`scroll_position_${this.tabId}`, {
                x: window.scrollX,
                y: window.scrollY,
                timestamp: Date.now()
            });
            
            // Validar e limpar a URL antes de salvar
            let currentUrl = window.location.href;
            
            // Remover aspas duplas se existirem
            if (currentUrl.includes('"')) {
                currentUrl = currentUrl.replace(/"/g, '');
                console.warn('URL com aspas duplas detectada e corrigida:', currentUrl);
            }
            
            // Verificar se a URL é válida
            try {
                const urlObj = new URL(currentUrl);
                if (urlObj.origin === window.location.origin) {
                    this.saveToStorage(`current_url_${this.tabId}`, currentUrl);
                    console.log('URL válida salva:', currentUrl);
                } else {
                    console.warn('URL com origem diferente, não salvando:', currentUrl);
                }
            } catch (error) {
                console.error('URL inválida, não salvando:', currentUrl, error);
            }
            
            console.log('Estado da aplicação salvo com sucesso para aba:', this.tabId);
        } catch (error) {
            console.error('Erro ao salvar estado:', error);
        }
    }

    init() {
        console.log('SessionChecker: Inicializando...');
        
        // Verificar se está na página de login após expiração de sessão
        this.checkLoginAfterExpiration();
        
        // Configurar listeners de atividade
        this.setupActivityListeners();

        // Considerar o carregamento da página como atividade inicial
        this.updateLastActivity();
        
        // Primeira verificação após 5 segundos
        setTimeout(() => {
            this.checkSession();
        }, 5000);
        
        // Configurar verificação periódica
        this.scheduleNextCheck();
    }

    checkLoginAfterExpiration() {
        // Verificar se está na página de login com mensagem de sessão expirada
        if (window.location.pathname.includes('login')) {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            
            if (msg && msg.includes('Sessão expirada')) {
                console.log('Detectada página de login após expiração de sessão');
                
                // Verificar se há credenciais salvas ou se deve redirecionar
                this.handleLoginAfterExpiration();
            }
        }
    }

    async handleLoginAfterExpiration() {
        try {
            // Tentar verificar se há uma sessão válida
            const response = await fetch(window.location.origin + '/administrativo/check-session', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.valid) {
                    console.log('Sessão válida detectada, redirecionando para dashboard');
                    window.location.href = window.location.origin + '/administrativo/dashboard';
                    return;
                }
            }
        } catch (error) {
            console.log('Erro ao verificar sessão após expiração:', error);
        }
        
        // Se não há sessão válida, mostrar instruções para o usuário
        this.showLoginInstructions();
    }

    showLoginInstructions() {
        // Adicionar instruções visuais para o usuário
        const instructionsDiv = document.createElement('div');
        instructionsDiv.className = 'alert alert-info alert-dismissible fade show';
        instructionsDiv.style.cssText = 'margin-top: 20px;';
        
        instructionsDiv.innerHTML = `
            <strong><i class="fas fa-info-circle"></i> Instruções:</strong><br>
            Sua sessão expirou. Por favor, insira suas credenciais e clique em "Acessar" para continuar.<br>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Inserir após o título "Login"
        const loginTitle = document.querySelector('h3');
        if (loginTitle && loginTitle.parentElement) {
            loginTitle.parentElement.appendChild(instructionsDiv);
        }
    }

    setupActivityListeners() {
        // Detectar atividade do usuário
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateLastActivity();
            }, true);
        });

        // Detectar quando a página fica visível
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkSession();
            }
        });

        // Detectar quando a janela ganha foco
        window.addEventListener('focus', () => {
            this.checkSession();
        });
    }

    updateLastActivity() {
        // Salvar no localStorage para persistir entre abas
        this.saveToStorage('lastActivity', Date.now().toString());

        // Reiniciar temporizador de bloqueio por inatividade
        if (this.lockTimer) {
            clearTimeout(this.lockTimer);
        }
        // Só arma o bloqueio se ainda não estiver bloqueado
        if (!document.getElementById('app-block-overlay')) {
            this.lockTimer = setTimeout(() => {
                this.blockApplication();
            }, this.lockTimeoutMs);
        }
    }

    scheduleNextCheck() {
        if (this.checkTimer) {
            clearTimeout(this.checkTimer);
        }
        
        this.checkTimer = setTimeout(() => {
            this.checkSession();
        }, this.checkInterval);
    }

    async checkSession() {
        // Evitar verificações simultâneas
        if (this.isChecking) {
            return;
        }
        
        // Evitar verificações muito frequentes
        const now = Date.now();
        if (now - this.lastCheck < 10000) { // Mínimo 10 segundos entre verificações
            return;
        }
        
        this.isChecking = true;
        this.lastCheck = now;
        
        console.log('Verificando sessão...');
        
        try {
            const response = await fetch(window.location.origin + '/administrativo/check-session', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Erro na resposta do servidor');
            }

            const data = await response.json();
            
            // Verificar se a sessão atual ainda é a válida
            const backendUserId = typeof data.user_id !== 'undefined' ? parseInt(data.user_id, 10) : null;
            const currentUserId = typeof window.currentUserId !== 'undefined' ? parseInt(window.currentUserId, 10) : null;
            
            if (backendUserId && currentUserId && backendUserId !== currentUserId) {
                console.log('⚠️  Diferente user_id detectado - continuando sessão atual');
                this.scheduleNextCheck();
                return;
            }

            if (!data.valid) {
                console.log('Sessão inválida/expirada, redirecionando');
                this.handleSessionExpired();
                return;
            }

            // Ajustar intervalo de verificação baseado no tempo restante informado pelo backend
            if (typeof data.expiresIn !== 'undefined' && data.expiresIn !== null) {
                let expiresInMs = Number(data.expiresIn);
                if (!Number.isNaN(expiresInMs)) {
                    if (expiresInMs < 100000) {
                        expiresInMs = expiresInMs * 1000;
                    }

                    if (expiresInMs < 60000) { // Menos de 1 minuto
                        this.checkInterval = 10000; // Verificar a cada 10 segundos
                    } else if (expiresInMs < 300000) { // Menos de 5 minutos
                        this.checkInterval = 30000; // Verificar a cada 30 segundos
                    } else {
                        this.checkInterval = 60000; // Verificar a cada 1 minuto
                    }
                }
            }
            
            // Agendar próxima verificação
            this.scheduleNextCheck();

        } catch (error) {
            console.error('Erro ao verificar sessão:', error);
            // Em caso de erro, agendar nova verificação
            this.scheduleNextCheck();
        } finally {
            this.isChecking = false;
        }
    }

    handleSessionExpired() {
        if (this.expiredHandled) return;
        this.expiredHandled = true;
        
        // Parar verificações
        this.stopSessionCheck();
        
        // Verificar se já está sendo redirecionado pelo backend
        if (window.location.pathname.includes('login')) {
            console.log('Já na página de login, não redirecionando novamente');
            return;
        }
        
        // Mostrar mensagem
        this.showExpiredMessage();
        
        // Redirecionar para login após 2 segundos
        setTimeout(() => {
            // Verificar novamente se não foi redirecionado pelo backend
            if (!window.location.pathname.includes('login')) {
                window.location.href = window.location.origin + '/administrativo/login?msg=Sessão+expirada.+Faça+login+novamente.';
            }
        }, 2000);
    }

    // REMOVIDO: Função de popup laranja não é mais usada
    // showSessionWarning(expiresIn) {
    //     // POPUP LARANJA REMOVIDO - NÃO APARECE MAIS!
    // }

    // Bloquear aplicação
    blockApplication() {
        console.log('Bloqueando aplicação...');
        
        // Criar overlay de bloqueio
        const blockOverlay = document.createElement('div');
        blockOverlay.id = 'app-block-overlay';
        blockOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        `;
        
        const userName = window.currentUserName || 'Usuário';
        
        blockOverlay.innerHTML = `
            <div class="card" style="max-width: 420px; text-align: center; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-lock fa-3x text-warning"></i>
                    </div>
                    <h4 class="card-title text-warning mb-3">Tela Bloqueada</h4>
                    <p class="card-text mb-3">
                        Por segurança, o sistema foi bloqueado por inatividade.<br>
                        <strong>Digite sua senha para continuar.</strong>
                    </p>
                    <div class="mb-3 text-start">
                        <p class="mb-1 small text-muted">Usuário logado:</p>
                        <p class="mb-2 fw-semibold" id="lockscreen-username">${userName}</p>
                        <label for="lockscreen-password" class="form-label small">Senha</label>
                        <div class="input-group input-group-sm">
                            <input type="password" id="lockscreen-password" class="form-control" autocomplete="current-password" />
                            <button type="button" class="btn btn-outline-secondary" id="lockscreen-toggle-password" tabindex="-1" aria-label="Mostrar ou ocultar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="lockscreen-error" class="text-danger small mt-2" style="display:none;"></div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-warning" id="lockscreen-unlock-btn">
                            <i class="fas fa-unlock-alt me-2"></i>Desbloquear
                        </button>
                        <button type="button" class="btn btn-secondary" id="lockscreen-logout-btn">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(blockOverlay);
        
        // Desabilitar interações no conteúdo de fundo
        document.body.style.pointerEvents = 'none';
        document.body.style.userSelect = 'none';
        // Permitir apenas o overlay
        blockOverlay.style.pointerEvents = 'auto';

        // Marcar como bloqueado
        this.blockWarningShown = true;

        const passwordInput = blockOverlay.querySelector('#lockscreen-password');
        const unlockBtn = blockOverlay.querySelector('#lockscreen-unlock-btn');
        const logoutBtn = blockOverlay.querySelector('#lockscreen-logout-btn');
        const togglePasswordBtn = blockOverlay.querySelector('#lockscreen-toggle-password');
        const errorDiv = blockOverlay.querySelector('#lockscreen-error');

        if (passwordInput) {
            passwordInput.focus();
            passwordInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    unlockBtn?.click();
                }
            });
        }

        if (togglePasswordBtn && passwordInput) {
            togglePasswordBtn.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                const icon = togglePasswordBtn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
                passwordInput.focus();
            });
        }

        if (unlockBtn) {
            unlockBtn.addEventListener('click', async () => {
                if (!passwordInput) return;
                const senha = passwordInput.value.trim();
                if (!senha) {
                    if (errorDiv) {
                        errorDiv.textContent = 'Informe sua senha para desbloquear.';
                        errorDiv.style.display = 'block';
                    }
                    return;
                }
                try {
                    unlockBtn.disabled = true;
                    unlockBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verificando...';
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                        errorDiv.textContent = '';
                    }
                    const response = await fetch(window.location.origin + '/administrativo/ajax-password-policy/validate-password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ senha })
                    });
                    const data = await response.json();
                    if (data && data.sucesso) {
                        // Renovar sessão no servidor (se disponível)
                        try {
                            await this.extendSession();
                        } catch (e) {
                            console.warn('Falha ao estender sessão após desbloqueio, seguindo mesmo assim:', e);
                        }
                        // Remover bloqueio
                        this.removeBlock();
                        this.blockWarningShown = false;
                        // Resetar temporizador de bloqueio a partir de agora
                        this.updateLastActivity();
                    } else {
                        if (errorDiv) {
                            errorDiv.textContent = data && data.mensagem ? data.mensagem : 'Senha incorreta. Tente novamente.';
                            errorDiv.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Erro ao validar senha no bloqueio de tela:', error);
                    if (errorDiv) {
                        errorDiv.textContent = 'Erro ao validar senha. Tente novamente.';
                        errorDiv.style.display = 'block';
                    }
                } finally {
                    unlockBtn.disabled = false;
                    unlockBtn.innerHTML = '<i class="fas fa-unlock-alt me-2"></i>Desbloquear';
                }
            });
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                window.location.href = window.location.origin + '/administrativo/logout';
            });
        }
    }

    // REMOVIDO: Função de popup azul não é mais usada
    // showSaveWarning(expiresIn) {
    //     // POPUP AZUL REMOVIDO - NÃO APARECE MAIS!
    // }

    // REMOVIDO: Função de popup amarelo não é mais usada
    // showBlockWarning(expiresIn) {
    //     // POPUP AMARELO REMOVIDO - NÃO APARECE MAIS!
    // }

    showExpiredMessage() {
        const expiredDiv = document.createElement('div');
        expiredDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        expiredDiv.style.cssText = `
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10000;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        `;
        
        expiredDiv.innerHTML = `
            <strong><i class="fas fa-exclamation-triangle"></i> Sessão Expirada!</strong><br><br>
            Sua sessão expirou por inatividade.<br>
            Você será redirecionado para a tela de login em alguns segundos.<br><br>
            <div class="spinner-border spinner-border-sm text-danger" role="status">
                <span class="visually-hidden">Redirecionando...</span>
            </div>
        `;

        document.body.appendChild(expiredDiv);
    }

    async extendSession() {
        try {
            const response = await fetch(window.location.origin + '/administrativo/extend-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Remover todos os avisos
                    this.removeAllWarnings();
                    
                    // Remover bloqueio se existir
                    this.removeBlock();
                    
                    // Mostrar mensagem de sucesso
                    this.showSuccessMessage('Sessão estendida com sucesso!');
                    
                    // Resetar flags
                    this.warningShown = false;
                    this.blockWarningShown = false;
                    this.saveWarningShown = false;
                    
                    // Resetar intervalo de verificação
                    this.checkInterval = 30000;
                    
                    // Verificar sessão novamente
                    setTimeout(() => {
                        this.checkSession();
                    }, 1000);
                }
            }
        } catch (error) {
            console.error('Erro ao estender sessão:', error);
            this.showErrorMessage('Erro ao estender sessão. Tente novamente.');
        }
    }

    showSuccessMessage(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
        successDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        successDiv.innerHTML = `
            <strong><i class="fas fa-check-circle"></i> Sucesso!</strong><br>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(successDiv);

        // Auto-remover após 3 segundos
        setTimeout(() => {
            if (successDiv.parentElement) {
                successDiv.remove();
            }
        }, 3000);
    }

    showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        errorDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        errorDiv.innerHTML = `
            <strong><i class="fas fa-exclamation-triangle"></i> Erro!</strong><br>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(errorDiv);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }

    stopSessionCheck() {
        if (this.checkTimer) {
            clearTimeout(this.checkTimer);
            this.checkTimer = null;
        }
    }

    // Encerrar sessão imediatamente
    logoutNow() {
        console.log('Encerrando sessão...');
        
        // Remover bloqueio
        this.removeBlock();
        
        // Redirecionar para logout
        window.location.href = window.location.origin + '/administrativo/logout';
    }

    // Remover todos os avisos (apenas elementos que existem)
    removeAllWarnings() {
        // REMOVIDO: Todos os popups foram removidos, apenas modal de bloqueio permanece
        // Esta função agora é apenas para compatibilidade
        console.log('Todos os popups foram removidos - apenas modal de bloqueio permanece');
    }

    // Remover bloqueio da aplicação
    removeBlock() {
        const blockOverlay = document.getElementById('app-block-overlay');
        if (blockOverlay) {
            blockOverlay.remove();
        }
        
        // Restaurar interações
        document.body.style.pointerEvents = '';
        document.body.style.userSelect = '';
        
        console.log('Bloqueio da aplicação removido');
    }

    // Restaurar contexto específico desta aba
    restoreApplicationContext() {
        console.log('Restaurando contexto da aplicação para aba:', this.tabId);
        try {
            // Restaurar posição de scroll
            const savedScroll = this.getFromStorage(`scroll_position_${this.tabId}`);
            if (savedScroll) {
                const scrollData = savedScroll;
                const timeDiff = Date.now() - scrollData.timestamp;
                if (timeDiff < 3600000) { // Restore if not too old (less than 1 hour)
                    setTimeout(() => {
                        window.scrollTo(scrollData.x, scrollData.y);
                        console.log('Posição de scroll restaurada para aba:', this.tabId);
                    }, 500);
                }
                this.saveToStorage(`scroll_position_${this.tabId}`, null); // Clear after restore
            }

            // Restaurar dados de formulários
            const formKeys = Object.keys(localStorage).filter(key => key.startsWith(`form_state_${this.tabId}_`));
            formKeys.forEach(key => {
                const formData = this.getFromStorage(key);
                const timeDiff = Date.now() - formData.timestamp;
                if (timeDiff < 3600000) { // Restore if not too old (less than 1 hour)
                    const forms = document.querySelectorAll('form');
                    const formIndex = parseInt(key.replace(`form_state_${this.tabId}_`, ''));
                    if (forms[formIndex] && formData.action === forms[formIndex].action) {
                        Object.keys(formData.data).forEach(fieldName => {
                            const field = forms[formIndex].querySelector(`[name="${fieldName}"]`);
                            if (field && field.type !== 'password') {
                                field.value = formData.data[fieldName];
                            }
                        });
                        console.log(`Formulário ${formIndex} restaurado para aba:`, this.tabId);
                    }
                }
                this.saveToStorage(key, null); // Clear after restore
            });

            // Limpar URL salva após restaurar
            this.saveToStorage(`current_url_${this.tabId}`, null);
            
            console.log('Contexto da aplicação restaurado com sucesso para aba:', this.tabId);
        } catch (error) {
            console.error('Erro ao restaurar contexto:', error);
        }
    }
    
    // Obter URL de retorno para esta aba específica
    getReturnUrlForTab() {
        const savedUrl = this.getFromStorage(`current_url_${this.tabId}`);
        if (savedUrl && savedUrl !== window.location.href) {
            console.log('URL de retorno encontrada para aba:', this.tabId, savedUrl);
            return savedUrl;
        }
        return null;
    }
}

// Proteção contra múltiplas instâncias
if (window.sessionChecker) {
    console.log('SessionChecker já está rodando, não criando nova instância');
} else {
    // Inicializar apenas uma vez
    const initSessionChecker = () => {
        if (!window.sessionChecker) {
            console.log('Criando nova instância do SessionChecker');
            window.sessionChecker = new SessionChecker();
        }
    };

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSessionChecker);
    } else {
        // DOM já está pronto
        initSessionChecker();
    }
}

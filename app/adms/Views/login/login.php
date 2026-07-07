<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;

// Mantemos a leitura de msg/error apenas para exibir alertas, 
// sem mais alterar o estado do botão.
?>

<style>
    .login-app-shell {
        width: 100%;
        max-width: none;
        min-height: 100vh;
        min-height: 100dvh;
        margin: 0;
        padding: 1.2rem 0.6rem;
        box-sizing: border-box;
        display: grid;
        place-items: center;
        /* Opção B: gradiente em tons de verde (mais leve no rodapé) */
        background: linear-gradient(180deg, #0a5b30 0%, #18914a 38%, #e9fff4 100%);
    }

    .login-app-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 14px 38px rgba(16, 24, 40, 0.16);
        background: #fff;
        width: 100%;
        max-width: 460px;
    }

    .login-app-brand {
        padding: 1.4rem 1.2rem 1rem;
        text-align: center;
        background: linear-gradient(180deg, #ffffff 0%, #f7fbf9 100%);
        border-bottom: 1px solid #e8edf3;
    }

    .login-app-logo {
        width: min(240px, 72%);
        height: auto;
    }

    .login-app-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 700;
        color: #1f2937;
        letter-spacing: -0.02em;
    }

    .login-app-subtitle {
        margin-top: 0.25rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .login-app-body {
        padding: 1.15rem 1.2rem 1.1rem;
    }

    .login-app-body .form-floating > .form-control {
        border-radius: 12px;
        border-color: #d7dfeb;
        box-shadow: none;
    }

    .login-app-body .form-floating > .form-control:focus {
        border-color: #219150;
        box-shadow: 0 0 0 0.18rem rgba(33, 145, 80, 0.16);
    }

    .login-app-footer {
        padding: 0.85rem 1.2rem 1.1rem;
        text-align: center;
        border-top: 1px solid #e8edf3;
        background: #fbfcfe;
    }

    .login-app-hint {
        font-size: 0.82rem;
    }

    @media (max-width: 576px) {
        .login-app-shell {
            padding: 0.8rem 0.35rem;
        }
        .login-app-brand {
            padding-top: 1rem;
        }
        .login-app-body {
            padding: 0.95rem 0.85rem;
        }
        .login-app-footer {
            padding: 0.75rem 0.85rem 0.95rem;
        }
    }
</style>

<div class="login-app-shell">
    <div class="login-app-card">

        <div class="login-app-brand">
            <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/image/logo/Logo-Tiaraju.png" alt="Logo Tiaraju" class="login-app-logo">
            <h1 class="login-app-title mt-2">Acesso</h1>
            <p class="login-app-subtitle">Entre com seu usuário corporativo</p>
        </div>

        <div class="login-app-body">
            <?php if (!empty($_GET['msg'])): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> <?= htmlspecialchars($_GET['msg']) ?>
                    <br><small class="mt-2 d-block">Por favor, insira suas credenciais e clique em "Acessar" para continuar.</small>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Erro:</strong> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Erro:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form action="" method="POST" id="form-login" autocomplete="off">


                <!-- Campo usuário: autocomplete off + readonly até o foco reduz sugestão de e-mail/dados salvos do Chrome -->
                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="username" placeholder="Digite seu usuário" value="<?php echo $this->data['form']['username'] ?? ''; ?>"
                           oninput="this.value = this.value.replace(/\s/g, '')"
                           onpaste="this.value = this.value.replace(/\s/g, '')"
                           readonly
                           onfocus="this.removeAttribute('readonly');"
                           autocomplete="off"
                           autocapitalize="none"
                           spellcheck="false"
                           inputmode="text"
                           required>
                    <label for="username">Usuário</label>
                </div>

                <!-- Campo para a senha do usuário (com opção de mostrar/ocultar) -->
                <div class="mb-3 position-relative">
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control" id="password" placeholder="Digite sua senha." value="<?php echo $this->data['form']['password'] ?? ''; ?>"
                               oninput="this.value = this.value.replace(/\s/g, '')" 
                               onpaste="this.value = this.value.replace(/\s/g, '')"
                               autocomplete="current-password" required>
                        <label for="password">Senha</label>
                    </div>
                    <button type="button"
                            id="toggle-password-visibility"
                            class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2"
                            style="z-index: 3;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>forgot-password" class="small text-decoration-none">Esqueceu a Senha?</a>
                    <!-- Botão para submeter o formulário -->
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btn-acessar">
                        <i class="fas fa-sign-in-alt me-2"></i>Acessar
                    </button>
                </div>
                
                <!-- Instruções adicionais -->
                <div class="text-center mt-3">
                    <small class="text-muted login-app-hint">
                        <i class="fas fa-info-circle me-1"></i>
                        Clique em "Acessar" para fazer login no sistema
                    </small>
                </div>
            </form>

        </div>

        <div class="login-app-footer">
            <div class="small mb-2">
                <a href="<?php echo htmlspecialchars(WhistleblowingPublicUrlHelper::baseUrl(), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none" target="_blank" rel="noopener">
                    <i class="fas fa-shield-alt me-1"></i>Canal de Denúncias (anônimo)
                </a>
            </div>
            <div class="small">
                <a href="<?php echo $_ENV['URL_ADM']; ?>new-user" class="text-decoration-none">Cadastrar</a>
            </div>
        </div>

    </div>
</div>

<script>
// Variável global para controlar o estado do submit
let formSubmitting = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== LOGIN SCRIPT INICIADO ===');

    // Limpar qualquer estado residual de controle de sessão (SessionChecker) ao entrar na tela de login
    try {
        const sessionKeys = [
            'current_tab_id',
            'session_last_activity',
            'session_last_check',
            'session_warning_shown',
            'session_blocked',
            'session_save_shown',
            'session_expired'
        ];
        sessionKeys.forEach((key) => {
            try {
                localStorage.removeItem(key);
            } catch (e) {}
            try {
                sessionStorage.removeItem(key);
            } catch (e) {}
        });

        // Limpar cookie usado pelo SessionChecker (se existir)
        document.cookie = 'current_tab_id=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        console.log('Estado de sessão (SessionChecker) limpo na tela de login.');
    } catch (e) {
        console.warn('Falha ao limpar estado de sessão na tela de login:', e);
    }
    
    const form = document.getElementById('form-login');
    const submitBtn = document.getElementById('btn-acessar');
    const togglePasswordBtn = document.getElementById('toggle-password-visibility');
    const passwordInput = document.getElementById('password');
    
    console.log('Form encontrado:', !!form);
    console.log('Botão encontrado:', !!submitBtn);
    console.log('Toggle senha encontrado:', !!togglePasswordBtn);
    
    // Toggle mostrar/ocultar senha
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                passwordInput.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    }

    // Proteção contra duplo submit
    form.addEventListener('submit', function(e) {
        console.log('=== FORM SUBMIT EVENT ===');
        
        if (formSubmitting) {
            console.log('Submit já em progresso, prevenindo evento');
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        console.log('Submit permitido, marcando como em progresso');
        formSubmitting = true;

        // Salvar usuário no localStorage para uso futuro
        const usernameField = document.getElementById('username');
        if (usernameField && usernameField.value.trim()) {
            localStorage.setItem('saved_username', usernameField.value.trim());
            console.log('Usuário salvo no localStorage:', usernameField.value.trim());
            
            // Se o campo estiver desabilitado, criar um campo hidden para preservar o valor
            if (usernameField.disabled) {
                // Remover campo hidden anterior se existir
                const existingHidden = document.querySelector('input[name="username_hidden"]');
                if (existingHidden) {
                    existingHidden.remove();
                }
                
                // Criar novo campo hidden com o valor do usuário
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'username';
                hiddenField.value = usernameField.value.trim();
                form.appendChild(hiddenField);
                
                console.log('Campo hidden criado para usuário:', usernameField.value.trim());
            }
        }

        // Salvar URL de retorno específica desta aba se existir
        const tabId = localStorage.getItem('current_tab_id');
        if (tabId) {
            const savedUrl = localStorage.getItem(`current_url_${tabId}`);
            if (savedUrl) {
                // Validar e limpar a URL antes de usar
                let cleanUrl = savedUrl;
                
                // Remover aspas duplas se existirem
                if (cleanUrl.includes('"')) {
                    cleanUrl = cleanUrl.replace(/"/g, '');
                }
                
                // Verificar se a URL é válida
                try {
                    const urlObj = new URL(cleanUrl);
                    if (urlObj.origin === window.location.origin) {
                        // Adicionar campo hidden com a URL de retorno limpa
                        const returnUrlField = document.createElement('input');
                        returnUrlField.type = 'hidden';
                        returnUrlField.name = 'return_url';
                        returnUrlField.value = cleanUrl;
                        form.appendChild(returnUrlField);
                        console.log('URL de retorno limpa adicionada ao formulário:', cleanUrl);
                    } else {
                        console.warn('URL de retorno com origem diferente, ignorando:', cleanUrl);
                    }
                } catch (error) {
                    console.error('URL de retorno inválida, ignorando:', cleanUrl, error);
                }
            }
        }

        // Desabilitar botão e mostrar loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
        
        // Remover classes de destaque
        submitBtn.classList.remove('btn-warning', 'btn-danger');
        submitBtn.classList.add('btn-secondary');
        
        // Remover animação se existir
        submitBtn.style.animation = 'none';
        
        console.log('Botão desabilitado e loading ativo');
        
        // Re-habilitar após 15 segundos (fallback de segurança)
        setTimeout(() => {
            if (submitBtn.disabled) {
                console.log('Re-habilitando botão após timeout de segurança');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Acessar';
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-primary');
                formSubmitting = false;
            }
        }, 15000);
        
        // Permitir o submit continuar
        return true;
    });

    // Prevenir Enter múltiplo
    let enterPressed = false;
    form.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            if (enterPressed) {
                console.log('Enter múltiplo prevenido');
                e.preventDefault();
                return false;
            }
            enterPressed = true;
            
            // Reset após 2 segundos
            setTimeout(() => {
                enterPressed = false;
            }, 2000);
        }
    });

    // Reset do estado quando a página é recarregada
    window.addEventListener('beforeunload', function() {
        formSubmitting = false;
    });

    // Verificar se há estado salvo para restaurar após login
    checkSavedState();

    // Usuário salvo no navegador: preenche usuário; senão mantém campo livre (readonly some no foco)
    checkSavedUser();

    const usernameEl = document.getElementById('username');
    const passwordEl = document.getElementById('password');
    if (localStorage.getItem('saved_username')) {
        if (passwordEl) {
            passwordEl.focus();
            console.log('Foco no campo senha (usuário já salvo neste navegador)');
        }
    } else if (usernameEl) {
        usernameEl.focus();
        console.log('Foco definido no campo usuário');
    }

    console.log('=== LOGIN SCRIPT CONFIGURADO ===');
});

// Função para verificar estado salvo
function checkSavedState() {
    console.log('Verificando estado salvo...');
    
    // Verificar se há contexto salvo para esta aba específica
    const tabId = getTabIdFromAnyStorage();
    if (tabId) {
        const savedUrl = getUrlFromAnyStorage(tabId);
        if (savedUrl) {
            console.log('Estado salvo detectado:', savedUrl);
            
            // Mostrar mensagem de contexto salvo
            const contextDiv = document.createElement('div');
            contextDiv.className = 'alert alert-info alert-dismissible fade show';
            contextDiv.style.cssText = 'margin-top: 20px;';
            
            contextDiv.innerHTML = `
                <strong><i class="fas fa-info-circle"></i> Contexto Salvo Detectado!</strong><br>
                <strong>URL:</strong> ${savedUrl}<br>
                <strong>Tab ID:</strong> ${tabId}<br>
                <small>Seus dados e posição serão restaurados após o login.</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Inserir após o formulário
            const form = document.getElementById('form-login');
            if (form && form.parentElement) {
                form.parentElement.appendChild(contextDiv);
            }
            
            console.log('Mensagem de contexto salvo exibida');
        } else {
            console.log('Tab ID encontrado mas sem URL salva');
        }
    } else {
        console.log('Nenhum contexto salvo encontrado');
    }
}

// Função robusta para obter Tab ID de qualquer storage
function getTabIdFromAnyStorage() {
    console.log('Buscando Tab ID em todos os storages disponíveis...');
    
    // Detectar navegador
    const userAgent = navigator.userAgent;
    const isFirefox = userAgent.includes('Firefox');
    
    if (isFirefox) {
        console.log('Firefox detectado - usando estratégia específica');
        return getTabIdFromFirefox();
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
function getTabIdFromFirefox() {
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
        }}
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

// Função robusta para obter URL de qualquer storage
function getUrlFromAnyStorage(tabId) {
    console.log(`Buscando URL para Tab ID: ${tabId}`);
    
    const isFirefox = navigator.userAgent.includes('Firefox');
    
    if (isFirefox) {
        return getUrlFromFirefox(tabId);
    }
    
    // Estratégia padrão
    const storages = [
        { name: 'localStorage', get: () => localStorage.getItem(`current_url_${tabId}`) },
        { name: 'sessionStorage', get: () => sessionStorage.getItem(`current_url_${tabId}`) },
        { name: 'cookies', get: () => {
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [cookieKey, cookieValue] = cookie.trim().split('=');
                if (cookieKey === `current_url_${tabId}`) {
                    return decodeURIComponent(cookieValue);
                }
            }
            return null;
        }},
        { name: 'global', get: () => window[`storage_current_url_${tabId}`] }
    ];
    
    for (let storage of storages) {
        try {
            const url = storage.get();
            if (url) {
                console.log(`URL encontrada em ${storage.name}:`, url);
                return url;
            }
        } catch (error) {
            console.warn(`Erro ao ler URL de ${storage.name}:`, error);
        }
    }
    
    return null;
}

// Estratégia específica para Firefox
function getUrlFromFirefox(tabId) {
    console.log(`Firefox: Buscando URL para Tab ID: ${tabId}`);
    
    const firefoxMethods = [
        { name: 'localStorage', get: () => localStorage.getItem(`current_url_${tabId}`) },
        { name: 'sessionStorage', get: () => sessionStorage.getItem(`current_url_${tabId}`) },
        { name: 'cookies', get: () => {
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [cookieKey, cookieValue] = cookie.trim().split('=');
                if (cookieKey === `current_url_${tabId}`) {
                    return decodeURIComponent(cookieValue);
                }
            }
            return null;
        }},
        { name: 'global', get: () => window[`storage_current_url_${tabId}`] },
        { name: 'firefoxGlobal', get: () => window[`firefox_storage_current_url_${tabId}`] },
        { name: 'DOM', get: () => document.documentElement.getAttribute(`data-current-url-${tabId}`) },
        { name: 'metaTag', get: () => {
            const meta = document.querySelector(`meta[name="firefox-current-url-${tabId}"]`);
            return meta ? meta.content : null;
        }}
    ];
    
    for (let method of firefoxMethods) {
        try {
            const url = method.get();
            if (url) {
                console.log(`Firefox: URL encontrada em ${method.name}:`, url);
                return url;
            }
        } catch (error) {
            console.warn(`Firefox: Erro ao ler URL de ${method.name}:`, error);
        }
    }
    
    return null;
}

// (Removido o fluxo de atualização de CSRF via AJAX, já que o login não usa mais CSRF)

// Função para verificar se há usuário salvo
function checkSavedUser() {
    const savedUser = localStorage.getItem('saved_username');
    if (savedUser) {
        console.log('Usuário salvo detectado:', savedUser);
        
        // Preencher campo usuário
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.removeAttribute('readonly');
            usernameField.value = savedUser;
            usernameField.disabled = true;
            usernameField.classList.add('form-control-plaintext');
            usernameField.classList.remove('form-control');
        }
        
        // Mostrar opções de ação
        showUserOptions(savedUser);
    }
}

// Função para mostrar opções quando usuário já está definido
function showUserOptions(username) {
    // Criar div de opções
    const optionsDiv = document.createElement('div');
    optionsDiv.id = 'user-options';
    optionsDiv.className = 'alert alert-info alert-dismissible fade show user-options';
    optionsDiv.style.cssText = 'margin-top: 20px;';
    
    optionsDiv.innerHTML = `
        <strong><i class="fas fa-user-check"></i> Usuário: ${username}</strong><br>
        <small class="text-muted">Digite sua senha para continuar ou escolha uma opção:</small><br><br>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm" onclick="continueWithUser()">
                <i class="fas fa-sign-in-alt me-2"></i>Continuar com ${username}
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="changeUser()">
                <i class="fas fa-user-edit me-2"></i>Trocar Usuário
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearSavedUser()">
                <i class="fas fa-times me-2"></i>Limpar
            </button>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Inserir após o formulário
    const form = document.getElementById('form-login');
    if (form && form.parentElement) {
        form.parentElement.appendChild(optionsDiv);
    }
}

// Função para continuar com usuário salvo
function continueWithUser() {
    console.log('Continuando com usuário salvo');
    
    // Remover opções de usuário
    const userOptionsDiv = document.querySelector('.user-options');
    if (userOptionsDiv) {
        userOptionsDiv.remove();
    }
    
    // Manter usuário preenchido e desabilitado
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.removeAttribute('readonly');
        usernameField.disabled = true;
        usernameField.classList.add('form-control-plaintext');
        usernameField.classList.remove('form-control');
    }
    
    // Focar no campo senha
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.focus();
        passwordField.select();
    }
    
    // Criar campo hidden para preservar o valor do usuário no submit
    const form = document.getElementById('form-login');
    if (form) {
        // Remover campo hidden anterior se existir
        const existingHidden = document.querySelector('input[name="username_hidden"]');
        if (existingHidden) {
            existingHidden.remove();
        }
        
        // Criar novo campo hidden com o valor do usuário
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'username';
        hiddenField.value = usernameField.value.trim();
        form.appendChild(hiddenField);
        
        console.log('Campo hidden criado para usuário:', usernameField.value.trim());
    }
    
    console.log('Usuário configurado para continuar');
}

// Função para trocar usuário
function changeUser() {
    console.log('Trocando usuário');
    
    // Remover opções de usuário
    const userOptionsDiv = document.querySelector('.user-options');
    if (userOptionsDiv) {
        userOptionsDiv.remove();
    }
    
    // Restaurar campo usuário
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.disabled = false;
        usernameField.classList.remove('form-control-plaintext');
        usernameField.classList.add('form-control');
        usernameField.value = '';
        usernameField.setAttribute('readonly', '');
        usernameField.focus();
    }
    
    // Remover campo hidden se existir
    const hiddenField = document.querySelector('input[name="username_hidden"]');
    if (hiddenField) {
        hiddenField.remove();
        console.log('Campo hidden removido');
    }
    
    // Limpar usuário salvo
    localStorage.removeItem('saved_username');
    console.log('Usuário salvo removido do localStorage');
}

// Função para limpar usuário salvo
function clearSavedUser() {
    console.log('Limpando usuário salvo');
    
    // Remover opções de usuário
    const userOptionsDiv = document.querySelector('.user-options');
    if (userOptionsDiv) {
        userOptionsDiv.remove();
    }
    
    // Restaurar campo usuário
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.disabled = false;
        usernameField.classList.remove('form-control-plaintext');
        usernameField.classList.add('form-control');
        usernameField.value = '';
        usernameField.setAttribute('readonly', '');
        usernameField.focus();
    }
    
    // Remover campo hidden se existir
    const hiddenField = document.querySelector('input[name="username_hidden"]');
    if (hiddenField) {
        hiddenField.remove();
        console.log('Campo hidden removido');
    }
    
    // Limpar usuário salvo
    localStorage.removeItem('saved_username');
    console.log('Usuário salvo removido do localStorage');
}
</script>
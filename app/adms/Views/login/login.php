<?php

use App\adms\Helpers\CSRFHelper;

$rawMsg   = $_GET['msg']   ?? '';
$rawError = $_GET['error'] ?? '';

// Considera tanto msg quanto error com textos de sessão expirada
$isSessionExpiredMsg = (
    (!empty($rawMsg)   && (str_contains($rawMsg, 'Sessão expirada') || str_contains($rawMsg, 'Sua sessão expirou')))
    || (!empty($rawError) && (str_contains($rawError, 'Sessão expirada') || str_contains($rawError, 'Sua sessão expirou')))
);
?>

<div class="col-lg-5">
    <div class="card shadow-lg border-0 rounded-lg mt-5">

        <div class="text-center mt-4">
                            <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/image/logo/Logo-Tiaraju.png" alt="Logo Tiaraju" style="max-width: 200px;">
        </div>

        <div class="card-header">
            <h3 class="text-center font-weight-light my-4">Login</h3>
        </div>

        <div class="card-body">
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

            <form action="" method="POST" id="form-login">
                <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_login'); ?>">


                <!-- Campo usuário -->
                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="username" placeholder="Digite seu usuário" value="<?php echo $this->data['form']['username'] ?? ''; ?>" 
                           oninput="this.value = this.value.replace(/\s/g, '')" 
                           onpaste="this.value = this.value.replace(/\s/g, '')"
                           autocomplete="username" required>
                    <label for="username">Usuário</label>
                </div>

                <!-- Campo para a senha do usuário -->
                <div class="form-floating mb-3">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Digite sua senha." value="<?php echo $this->data['form']['password'] ?? ''; ?>"
                           oninput="this.value = this.value.replace(/\s/g, '')" 
                           onpaste="this.value = this.value.replace(/\s/g, '')"
                           autocomplete="current-password" required>
                    <label for="password">Senha</label>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>forgot-password" class="small text-decoration-none">Esqueceu a Senha?</a>
                    <!-- Botão para submeter o formulário -->
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-acessar" <?= $isSessionExpiredMsg ? 'disabled' : '' ?>>
                        <i class="fas fa-sign-in-alt me-2"></i><?= $isSessionExpiredMsg ? 'Atualizando token...' : 'Acessar' ?>
                    </button>
                </div>
                
                <!-- Instruções adicionais -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Clique em "Acessar" para fazer login no sistema
                    </small>
                </div>
            </form>

        </div>

        <div class="card-footer text-center py-3">
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
    
    console.log('Form encontrado:', !!form);
    console.log('Botão encontrado:', !!submitBtn);
    
    // Verificar se há mensagem de sessão expirada
    const urlParams = new URLSearchParams(window.location.search);
    const msg   = urlParams.get('msg')   || '';
    const error = urlParams.get('error') || '';

    console.log('Mensagem da URL (msg):', msg);
    console.log('Mensagem da URL (error):', error);

    const hasSessionExpired =
        (msg && (msg.includes('Sessão expirada') || msg.includes('Sua sessão expirou'))) ||
        (error && (error.includes('Sessão expirada') || error.includes('Sua sessão expirou')));

    if (hasSessionExpired) {
        console.log('=== SESSÃO EXPIRADA DETECTADA ===');

        // Atualizar o token CSRF via AJAX para evitar erro de token inválido
        // Desabilita o botão até o novo token ser aplicado
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        updateCSRFToken(submitBtn);

        // Destacar o botão quando a sessão expirou
        submitBtn.classList.add('btn-warning');
        submitBtn.classList.remove('btn-primary');
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Sessão Expirada - Atualizando Token...';

        // Adicionar efeito de pulso
        submitBtn.style.animation = 'pulse 2s infinite';

        // Adicionar CSS para animação
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);

        console.log('Botão destacado para sessão expirada');
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

    // Adicionar foco automático no campo usuário
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
        console.log('Foco definido no campo usuário');
    }

    // Reset do estado quando a página é recarregada
    window.addEventListener('beforeunload', function() {
        formSubmitting = false;
    });

    // Verificar se há estado salvo para restaurar após login
    checkSavedState();

    // Verificar se há usuário salvo para mostrar apenas campo senha
    checkSavedUser();

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

// Função para atualizar o token CSRF via AJAX
function updateCSRFToken(submitBtn) {
    console.log('Atualizando token CSRF...');
    
    // Fazer requisição AJAX para obter novo token
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Extrair novo token do HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newToken = doc.querySelector('input[name="csrf_token"]')?.value;
        
        if (newToken) {
            // Atualizar o token no formulário
            const tokenInput = document.querySelector('input[name="csrf_token"]');
            if (tokenInput) {
                tokenInput.value = newToken;
                console.log('Token CSRF atualizado com sucesso');
                
                // Atualizar texto do botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Acessar';
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('btn-primary');
                    submitBtn.style.animation = 'none';
                }
                
                console.log('Token CSRF atualizado e botão restaurado');
            }
        } else {
            console.error('Token CSRF não encontrado na resposta');
            // Em vez de recarregar, mostrar mensagem de erro
            showTokenError(submitBtn);
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar token CSRF:', error);
        // Em vez de recarregar, mostrar mensagem de erro
        showTokenError(submitBtn);
    });
}

// Função para mostrar erro de token sem recarregar a página
function showTokenError(submitBtn) {
    console.log('Mostrando erro de token sem recarregar página');
    
    // Atualizar botão para mostrar erro
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erro no Token - Clique para Tentar';
        submitBtn.classList.remove('btn-warning');
        submitBtn.classList.add('btn-danger');
        submitBtn.style.animation = 'none';
    }
    
    // Adicionar evento de clique para tentar novamente
    submitBtn.onclick = function() {
        updateCSRFToken(submitBtn);
    };
    
    // Mostrar mensagem para o usuário
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.style.cssText = 'margin-top: 20px;';
    
    errorDiv.innerHTML = `
        <strong><i class="fas fa-exclamation-triangle"></i> Erro no Token de Segurança</strong><br>
        Clique no botão "Acessar" para tentar novamente ou recarregue a página manualmente.<br>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Inserir após o formulário
    const form = document.getElementById('form-login');
    if (form && form.parentElement) {
        form.parentElement.appendChild(errorDiv);
    }
}

// Função para verificar se há usuário salvo
function checkSavedUser() {
    const savedUser = localStorage.getItem('saved_username');
    if (savedUser) {
        console.log('Usuário salvo detectado:', savedUser);
        
        // Preencher campo usuário
        const usernameField = document.getElementById('username');
        if (usernameField) {
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
    optionsDiv.className = 'alert alert-info alert-dismissible fade show';
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
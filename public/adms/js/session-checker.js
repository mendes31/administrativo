/**
 * Session Checker - Verifica periodicamente se a sessão ainda é válida
 * Redireciona automaticamente para o login se a sessão expirar
 */

class SessionChecker {
    constructor() {
        // Verificar se a funcionalidade está habilitada
        if (!window.sessionConfig || !window.sessionConfig.enabled) {
            console.log('Verificação de sessão desabilitada pela política de senhas');
            return;
        }

        // Configuração dinâmica baseada na política
        const timeoutMinutes = window.sessionConfig.timeoutMinutes;
        this.sessionTimeout = (timeoutMinutes * 60 * 1000); // Converter minutos para milissegundos
        this.warningTime = 60000; // Avisar apenas quando faltar menos de 1 minuto
        this.thresholdMs = 50000; // Agendar checagem 50s antes do vencimento
        this.marginMs = 10000; // Margem de rede/clock
        this.minPollMs = 5000;  // 5s na janela final
        this.maxPollMs = 60000; // 60s quando longe do vencimento
        this.lastActivity = Date.now();
        this.isChecking = false;
        this.checkTimer = null; // setTimeout dinâmico
        this.warningTimer = null;
        this.lastWarningAt = 0;
        this.lastServerSyncAt = 0;
        this.lastExpiresInMs = null;
        
        console.log('Session Checker configurado:', {
            enabled: window.sessionConfig.enabled,
            timeoutMinutes: timeoutMinutes,
            scheduling: 'dynamic',
            warningTime: this.warningTime,
            sessionTimeout: this.sessionTimeout
        });
        
        this.init();
    }

    init() {
        // Só executar se não estiver na página de login
        if (window.location.pathname.includes('login')) {
            return;
        }

        // Inicializar verificações dinâmicas
        this.setupActivityListeners();
        // Verificar sessão imediatamente e agendar próxima
        this.checkSession();
    }

    setupActivityListeners() {
        // Detectar atividade do usuário
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.updateLastActivity();
            }, true);
        });

        // Detectar quando a página fica visível/invisível
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
        this.lastActivity = Date.now();
        // Salvar no localStorage para persistir entre abas
        localStorage.setItem('lastActivity', this.lastActivity.toString());

        // Se estamos na janela final, sincronizar imediatamente (com cooldown) para recalcular expiração
        const now = Date.now();
        const inFinalWindow = this.lastExpiresInMs !== null && this.lastExpiresInMs <= (this.thresholdMs + this.marginMs);
        if (inFinalWindow && (now - this.lastServerSyncAt > 10000)) { // cooldown 10s
            this.extendSession(false).then(() => {
                this.lastServerSyncAt = Date.now();
                // Revalidar logo após estender
                this.scheduleNextCheck(1000);
            }).catch(() => {});
        }
    }

    scheduleNextCheck(delayMs) {
        if (this.checkTimer) {
            clearTimeout(this.checkTimer);
            this.checkTimer = null;
        }
        const safeDelay = Math.max(this.minPollMs, Math.min(this.maxPollMs, delayMs));
        this.checkTimer = setTimeout(() => this.checkSession(), safeDelay);
    }

    async checkSession() {
        if (this.isChecking) return;
        
        this.isChecking = true;
        
        try {
            const response = await fetch(window.location.origin + '/administrativo/check-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Sessão inválida');
            }

            const data = await response.json();
            
            if (!data.valid) {
                this.handleSessionExpired();
                return;
            }

            // Verificar se está próximo de expirar
            if (typeof data.expiresIn !== 'undefined' && data.expiresIn !== null) {
                // Backend retorna segundos; converter para ms. Se já vier em ms, manter.
                let expiresInMs = Number(data.expiresIn);
                if (!Number.isNaN(expiresInMs)) {
                    // Heurística: valores < 100000 provavelmente são segundos
                    if (expiresInMs < 100000) {
                        expiresInMs = expiresInMs * 1000;
                    }
                    // Memorizar última estimativa para lógica de atividade
                    this.lastExpiresInMs = expiresInMs;

                    // Não repetir alerta se mostrado há menos de 60s
                    const now = Date.now();
                    if (expiresInMs < this.warningTime && (now - this.lastWarningAt > 60000)) {
                        this.lastWarningAt = now;
                        this.showSessionWarning(expiresInMs);
                    }

                    // Sincronizar atividade recente com o servidor ~a cada 60s quando houver uso
                    const hasRecentActivity = (now - this.lastActivity) < 60000;
                    const needSync = (now - this.lastServerSyncAt) > 60000;
                    if (hasRecentActivity && needSync) {
                        this.extendSession(false)
                            .then(() => { this.lastServerSyncAt = Date.now(); })
                            .catch(() => {});
                    }

                    // Agendar próxima checagem: 50s antes do vencimento (com margem)
                    let nextDelay = this.maxPollMs;
                    if (expiresInMs > (this.thresholdMs + this.marginMs)) {
                        nextDelay = expiresInMs - this.thresholdMs - this.marginMs;
                    } else {
                        nextDelay = this.minPollMs; // janela final
                    }
                    this.scheduleNextCheck(nextDelay);
                }
            } else {
                // Sem expiresIn informado, agendar checagem padrão
                this.scheduleNextCheck(this.maxPollMs);
            }

        } catch (error) {
            console.log('Erro ao verificar sessão:', error);
            this.handleSessionExpired();
        } finally {
            this.isChecking = false;
        }
    }

    handleSessionExpired() {
        // Parar verificações
        this.stopSessionCheck();
        
        // Mostrar mensagem
        this.showExpiredMessage();
        
        // Redirecionar para login após 2 segundos
        setTimeout(() => {
            window.location.href = window.location.origin + '/administrativo/login?msg=Sessão+expirada.+Faça+login+novamente.';
        }, 2000);
    }

    showSessionWarning(expiresIn) {
        // Evitar múltiplos avisos
        if (document.getElementById('session-warning')) {
            return;
        }

        const warningDiv = document.createElement('div');
        warningDiv.id = 'session-warning';
        warningDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        warningDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        const minutes = Math.ceil(expiresIn / 60000);
        warningDiv.innerHTML = `
            <strong><i class="fas fa-clock"></i> Sessão Expirando!</strong><br>
            Sua sessão expira em ${minutes} minuto${minutes > 1 ? 's' : ''}.<br>
            <button type="button" class="btn btn-sm btn-warning mt-2" onclick="sessionChecker.extendSession()">
                <i class="fas fa-sync-alt"></i> Estender Sessão
            </button>
            <button type="button" class="btn btn-sm btn-secondary mt-2 ms-2" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i> Fechar
            </button>
        `;

        document.body.appendChild(warningDiv);

        // Auto-remover após 10 segundos
        setTimeout(() => {
            if (warningDiv.parentElement) {
                warningDiv.remove();
            }
        }, 10000);
    }

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

    async extendSession(showToast = true) {
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
                    // Remover aviso
                    const warning = document.getElementById('session-warning');
                    if (warning) {
                        warning.remove();
                    }
                    
                    // Mostrar mensagem de sucesso (apenas em interação do usuário)
                    if (showToast) {
                        this.showSuccessMessage('Sessão estendida com sucesso!');
                    }
                    
                    // Atualizar última atividade
                    this.updateLastActivity();

                    // Permitir novo aviso no novo ciclo
                    this.lastWarningAt = 0;
                    this.lastExpiresInMs = null;
                    this.scheduleNextCheck(1000);
                }
            }
        } catch (error) {
            console.error('Erro ao estender sessão:', error);
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

    stopSessionCheck() {
        if (this.checkTimer) {
            clearTimeout(this.checkTimer);
            this.checkTimer = null;
        }
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
            this.warningTimer = null;
        }
    }
}

// Proteção contra múltiplas instâncias
if (window.sessionChecker) {
    console.log('SessionChecker já está rodando, não criando nova instância');
} else {
    // Inicializar quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.sessionChecker) {
            window.sessionChecker = new SessionChecker();
        }
    });

    // Inicializar também se o DOM já estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.sessionChecker) {
                window.sessionChecker = new SessionChecker();
            }
        });
    } else {
        if (!window.sessionChecker) {
            window.sessionChecker = new SessionChecker();
        }
    }
}

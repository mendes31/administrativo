<!-- PWA: modais compartilhados (perfil, dashboard, etc.) -->
<div class="modal fade" id="pwaInstallChromeModal" tabindex="-1" aria-labelledby="pwaInstallChromeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pwaInstallChromeModalLabel">
                    <i class="fab fa-chrome text-warning me-2"></i>Instalar com Google Chrome
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body small">
                <p>Para instalar o aplicativo com suporte completo (atalho na tela inicial e notificações), abra esta página no <strong>Google Chrome</strong>.</p>
                <p class="text-muted mb-0">No Android você pode tentar abrir no Chrome automaticamente. Não é garantido em todos os aparelhos — se não funcionar, copie o endereço e abra no Chrome manualmente.</p>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btnPwaDismissChromeHint">Lembrar depois</button>
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#pwaInstallGenericModal">Ver outras opções</button>
                <button type="button" class="btn btn-warning" id="btnPwaOpenChrome">
                    <i class="fab fa-chrome me-1"></i>Abrir no Chrome
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pwaInstallSafariModal" tabindex="-1" aria-labelledby="pwaInstallSafariModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pwaInstallSafariModalLabel">
                    <i class="fab fa-apple me-2"></i>Adicionar à Tela de Início
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Abra o portal no <strong>Safari</strong> (não apenas dentro de outro app).</li>
                    <li class="mb-2">Toque em <strong>Compartilhar</strong> <i class="fas fa-share-square"></i>.</li>
                    <li class="mb-2">Escolha <strong>Adicionar à Tela de Início</strong>.</li>
                    <li>Confirme em <strong>Adicionar</strong>.</li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pwaInstallGenericModal" tabindex="-1" aria-labelledby="pwaInstallGenericModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pwaInstallGenericModalLabel">
                    <i class="fas fa-download me-2"></i>Como instalar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body small">
                <p><strong>Chrome ou Edge (computador):</strong> ícone de instalação na barra de endereço, ou menu ⋮ → <em>Instalar Portal…</em> / <em>Aplicativo disponível</em>.</p>
                <p><strong>Chrome (Android):</strong> menu ⋮ → <em>Instalar aplicativo</em> ou <em>Adicionar à tela inicial</em>.</p>
                <p class="text-muted mb-0">O navegador só mostra a instalação quando o site está em HTTPS e atende aos requisitos do PWA. Se não aparecer, use o Google Chrome.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pwaInstallAlreadyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                <p class="mb-0 small">O aplicativo já está instalado neste dispositivo. Abra pelo ícone na tela inicial ou na área de trabalho.</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center mb-3 d-none" id="pwaDashboardPushRow">
    <div class="col-12 col-lg-11">
        <div class="card border-warning shadow-sm bg-warning bg-opacity-10" id="pwaDashboardPushCard">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-1 text-warning text-dark">
                        <i class="fas fa-bell me-2"></i>Ative as notificações
                    </h5>
                    <p class="card-text small text-muted mb-0" id="pwaDashboardPushText">
                        O aplicativo está instalado. Permita as notificações para receber alertas do portal sem abrir o Meu Perfil.
                    </p>
                    <p class="small text-danger mb-0 mt-1 d-none" id="pwaDashboardPushError"></p>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-warning btn-sm" id="btnPwaDashboardPushActivate">
                        <i class="fas fa-bell me-1"></i>Ativar notificações
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPwaDashboardPushLater">
                        Depois
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center mb-3 d-none" id="pwaDashboardInstallRow">
    <div class="col-12 col-lg-11">
        <div class="card border-success shadow-sm" id="pwaDashboardInstallCard">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-1 text-success">
                        <i class="fas fa-mobile-alt me-2"></i>Instale o aplicativo do portal
                    </h5>
                    <div class="card-text small text-muted mb-0" id="pwaDashboardInstallText">
                        <p class="mb-2">Acesso rápido pela tela inicial, experiência em tela cheia e melhor suporte a notificações.</p>
                        <p class="mb-1" id="pwaDashboardInstallDesktopHint">
                            <strong><i class="fab fa-android me-1"></i>Android:</strong> Google Chrome (recomendado).
                            <strong class="ms-2"><i class="fab fa-windows me-1"></i>Windows / Edge:</strong> ícone <em>Instalar</em> na barra de endereço ou menu ⋯ → Aplicativos.
                        </p>
                        <p class="mb-0" id="pwaDashboardInstallIosHint">
                            <strong><i class="fab fa-apple me-1"></i>iPhone / iPad (Safari):</strong>
                            toque em <strong>Compartilhar</strong> <i class="fas fa-share-square"></i>
                            → <strong>Adicionar à Tela de Início</strong> → <strong>Adicionar</strong>.
                            Use o Safari (não apenas abrir dentro de outro app).
                        </p>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-success btn-sm" id="btnPwaDashboardInstall">
                        <i class="fas fa-download me-1"></i>Instalar aplicativo
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPwaDashboardInstallDismiss">
                        Já tenho instalado
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

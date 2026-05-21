<?php
// Verificar se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $_ENV['URL_ADM'] . 'login');
    exit;
}

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Helpers\PositionDisplayHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Meu Perfil</h1>
    
    <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Meu Perfil</li>
    </ol>
    <?php if (!empty($this->data['buttonPermission']) && in_array('MyCalendar', $this->data['buttonPermission'], true)): ?>
        <p class="mb-4"><a href="<?php echo $_ENV['URL_ADM']; ?>my-calendar" class="btn btn-sm btn-outline-primary"><i class="fas fa-calendar-alt me-1"></i>Meu calendário</a></p>
    <?php endif; ?>

    <div class="row">
        <!-- Coluna da foto e informações básicas -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Foto do Perfil
                </div>
                <div class="card-body text-center">
                    <?php
                    $profileImagePath = null;
                    if (ImageHelper::userImageExists((int)($_SESSION['user_id'] ?? 0), (string)($this->data['form']['image'] ?? ''))) {
                        $profileImagePath = 'users/' . ($_SESSION['user_id'] ?? 0) . '/' . $this->data['form']['image'];
                    }

                    if ($profileImagePath !== null) {
                        echo '<button type="button" class="btn p-0 border-0 bg-transparent" style="cursor: zoom-in;" onclick="openProfilePhotoModal();">';
                        echo ImageHelper::displayImage($profileImagePath, [
                            'alt' => 'Foto do usuário',
                            'id' => 'profileAvatarImg',
                            'class' => 'img-fluid rounded-circle mb-3',
                            'style' => 'width: 150px; height: 150px; object-fit: cover;',
                        ], 'icon_user.png', 'users');
                        echo '</button>';
                    } else {
                        echo ImageHelper::renderInitialsAvatar((string)($this->data['form']['name'] ?? 'Usuário'), 150, [
                            'class' => 'mb-3',
                            'id' => 'profileAvatarImg',
                        ]);
                    }
                    ?>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?></h5>
                    <?php
                    $profilePosDisplay = PositionDisplayHelper::formatForDisplay((string)($this->data['form']['pos_name'] ?? ''));
                    ?>
                    <?php if ($profilePosDisplay !== ''): ?>
                    <p class="card-text text-muted">
                        <i class="fas fa-briefcase me-1"></i>
                        <?php echo htmlspecialchars($profilePosDisplay); ?>
                    </p>
                    <?php endif; ?>
                    <p class="card-text text-muted">
                        <i class="fas fa-building me-1"></i>
                        <?php echo htmlspecialchars($this->data['form']['dep_name'] ?? ''); ?>
                    </p>
                    
                    <div class="d-flex flex-column gap-2 align-items-stretch">
                        <a href="<?php echo htmlspecialchars($_ENV['URL_ADM'] ?? ''); ?>update-password" class="btn btn-warning btn-sm">
                            <i class="fas fa-key me-1"></i>
                            Alterar Senha
                        </a>
                        <form action="" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_profile'); ?>">
                            <input type="file"
                                   name="image"
                                   class="form-control d-none"
                                   id="profilePhotoInput"
                                   accept="image/*">
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="triggerProfilePhotoPicker();">
                                <i class="fas fa-camera me-1"></i>
                                Alterar Foto
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm d-none" id="profilePhotoSaveBtn">
                                <i class="fas fa-save me-1"></i>
                                Salvar Foto
                            </button>
                            <?php if (ImageHelper::userImageExists((int)($_SESSION['user_id'] ?? 0), (string)($this->data['form']['image'] ?? ''))): ?>
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#removePhotoModal">
                                    <i class="fas fa-trash me-1"></i>
                                    Remover Foto
                                </button>
                            <?php endif; ?>
                            <small class="form-text text-muted d-block">
                                Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.
                            </small>
                        </form>
                        <a href="<?php echo htmlspecialchars($_ENV['URL_ADM'] ?? ''); ?>timeline-profile/<?php echo (int)($_SESSION['user_id'] ?? 0); ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-stream me-1"></i>
                            Perfil na Timeline
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna do formulário de edição -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-eye me-1"></i>
                    Informações do Perfil
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="name" class="form-label">Nome Completo</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   value="<?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">O nome completo não pode ser alterado</small>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   value="<?php echo htmlspecialchars($this->data['form']['email'] ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">O e-mail não pode ser alterado</small>
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">Nome de Usuário</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   value="<?php echo htmlspecialchars($this->data['form']['username'] ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">O nome de usuário não pode ser alterado</small>
                        </div>

                        <div class="col-md-6">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="data_nascimento" 
                                   value="<?php echo date('d/m/Y', strtotime($this->data['form']['data_nascimento'] ?? '')); ?>" 
                                   readonly>
                            <small class="form-text text-muted">A data de nascimento não pode ser alterada</small>
                        </div>

                        <div class="col-md-6">
                            <label for="dep_name" class="form-label">Departamento</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="dep_name" 
                                   value="<?php echo htmlspecialchars($this->data['form']['dep_name'] ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">O departamento não pode ser alterado</small>
                        </div>

                        <div class="col-md-6">
                            <label for="pos_name_display" class="form-label">Cargo</label>
                            <input type="text"
                                   class="form-control"
                                   id="pos_name_display"
                                   value="<?php echo htmlspecialchars($profilePosDisplay); ?>"
                                   readonly>
                            <small class="form-text text-muted">O cargo não pode ser alterado</small>
                        </div>

                        <div class="col-12">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instalar aplicativo (PWA) -->
            <div class="card mb-4" id="pwaInstallCard">
                <div class="card-header d-flex align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-mobile-alt me-1"></i> Instalar aplicativo (PWA)</span>
                    <span class="ms-md-auto" id="pwaInstallStatus">
                        <span class="badge bg-secondary">Verificando…</span>
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3" id="pwaInstallHint">
                        Instale o portal na tela inicial para abrir como aplicativo, com ícone próprio e melhor experiência em celular.
                    </p>
                    <p class="small text-muted mb-3">
                        <strong>Recomendado:</strong> Google Chrome (Android e PC). No iPhone/iPad use o Safari.
                    </p>
                    <div class="d-grid gap-2 d-sm-flex">
                        <button type="button" class="btn btn-success btn-sm" id="btnPwaInstall">
                            <i class="fas fa-download me-1"></i> Instalar aplicativo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notificações push (PWA) -->
            <div class="card mb-4" id="pushNotificationsCard">
                <div class="card-header d-flex align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-bell me-1"></i> Notificações push (PWA)</span>
                    <span class="ms-md-auto" id="pushNotificationStatus">
                        <span class="badge bg-secondary">Verificando…</span>
                    </span>
                </div>
                <div class="card-body">
                    <div id="pushNotificationAlert" class="alert alert-info small mb-3 d-none" role="alert"></div>
                    <p class="text-muted small mb-3">
                        Receba alertas do portal mesmo com o aplicativo em segundo plano. Funciona em navegadores compatíveis e no PWA instalado.
                    </p>
                    <div class="d-grid gap-2 d-sm-flex">
                        <button type="button" class="btn btn-primary btn-sm" id="btnPushEnable">
                            <i class="fas fa-bell me-1"></i> Ativar notificações
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnPushDisable">
                            <i class="fas fa-bell-slash me-1"></i> Desativar neste dispositivo
                        </button>
                    </div>
                    <div id="pushDevicesSection" class="mt-3 d-none">
                        <h6 class="small text-uppercase text-muted mb-2">Dispositivos com push ativo</h6>
                        <ul class="list-group list-group-flush small" id="pushDevicesList"></ul>
                        <p class="text-muted small mb-0 mt-2">
                            Cada navegador conta como um dispositivo: <strong>Chrome no PC</strong>, <strong>Edge no PC</strong> e <strong>Firefox</strong> são inscrições separadas — ative em cada um. O celular também é separado. O site precisa estar em <strong>HTTPS</strong> (exceto localhost). Safari no iPhone exige o PWA na Tela de Início.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card com informações adicionais -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Informações Adicionais
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <span class="badge <?php echo ($this->data['form']['status'] ?? '') === 'Ativo' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($this->data['form']['status'] ?? ''); ?>
                                </span>
                            </p>
                            <p><strong>Data de Criação:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($this->data['form']['created_at'] ?? '')); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Atualização:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($this->data['form']['updated_at'] ?? '')); ?>
                            </p>
                            <p><strong>Último Login:</strong> 
                                <span class="text-muted">Não disponível</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PWA: recomendar Chrome (Firefox / Android sem Chrome) -->
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

<!-- PWA: iPhone / iPad -->
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

<!-- PWA: instruções genéricas (menu do navegador) -->
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

<!-- Modal para ampliar foto do perfil -->
<div class="modal fade" id="profilePhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-lg">
        <div class="modal-content border-0 bg-dark bg-opacity-75">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center p-2 p-md-3">
                <img id="profilePhotoModalImg" src="" alt="Foto do perfil" class="img-fluid" style="max-height:90vh;object-fit:contain;">
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Remover Foto -->
<div class="modal fade" id="removePhotoModal" tabindex="-1" aria-labelledby="removePhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removePhotoModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Remoção
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja remover sua foto de perfil?</p>
                <p class="text-muted mb-0">Após a remoção, será exibida a foto padrão do sistema.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <form action="" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_remove_photo'); ?>">
                    <input type="hidden" name="remove_photo" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Remover Foto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function triggerProfilePhotoPicker() {
    var input = document.getElementById('profilePhotoInput');
    if (!input) return;
    input.click();
}

document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('profilePhotoInput');
    var saveBtn = document.getElementById('profilePhotoSaveBtn');
    if (!input || !saveBtn) return;
    input.addEventListener('change', function () {
        if (input.files && input.files.length > 0) {
            saveBtn.classList.remove('d-none');
        } else {
            saveBtn.classList.add('d-none');
        }
    });
});

window.__PushNotificationsInit = {
    urlAdm: <?php echo json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), JSON_UNESCAPED_SLASHES); ?>,
    csrfToken: <?php echo json_encode(CSRFHelper::generateCSRFToken('form_push_subscribe'), JSON_UNESCAPED_UNICODE); ?>,
    swVersion: '20260520-13'
};

function openProfilePhotoModal() {
    var img = document.getElementById('profileAvatarImg');
    var modalImg = document.getElementById('profilePhotoModalImg');
    var modalEl = document.getElementById('profilePhotoModal');
    if (!img || !modalImg || !modalEl) return;
    modalImg.src = img.getAttribute('src') || '';
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true });
    modal.show();
}
</script>
<script src="<?php echo htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), ENT_QUOTES, 'UTF-8'); ?>/public/adms/js/push-notifications.js?v=10"></script>

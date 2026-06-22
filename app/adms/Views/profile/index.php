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
                    ?>
                    <div id="profileAvatarStage" class="mx-auto mb-2 position-relative" style="width:150px;height:150px;">
                        <div id="profileAvatarCurrent">
                            <?php
                            if ($profileImagePath !== null) {
                                echo '<button type="button" class="btn p-0 border-0 bg-transparent" style="cursor: zoom-in;" onclick="openProfilePhotoModal();">';
                                echo ImageHelper::displayImage($profileImagePath, [
                                    'alt' => 'Foto do usuário',
                                    'id' => 'profileAvatarImg',
                                    'class' => 'img-fluid rounded-circle',
                                    'style' => 'width: 150px; height: 150px; object-fit: cover;',
                                ], 'icon_user.png', 'users');
                                echo '</button>';
                            } else {
                                echo ImageHelper::renderInitialsAvatar((string)($this->data['form']['name'] ?? 'Usuário'), 150, [
                                    'class' => 'mb-0',
                                    'id' => 'profileAvatarImg',
                                ]);
                            }
                            ?>
                        </div>
                        <img id="profileAvatarPreview"
                             src=""
                             alt="Pré-visualização da nova foto"
                             class="rounded-circle border border-3 border-primary d-none position-absolute top-0 start-0"
                             style="width:150px;height:150px;object-fit:cover;">
                    </div>
                    <p id="profilePhotoSelectedHint" class="small text-primary mb-2 d-none">
                        <i class="fas fa-image me-1"></i>Recorte aplicado — confirme com Salvar alteração
                    </p>
                    
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
                        <form id="profilePhotoForm" action="" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_profile'); ?>">
                            <input type="file"
                                   name="image"
                                   class="d-none"
                                   id="profilePhotoInput"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="d-flex flex-column gap-2">
                                <button type="button"
                                        class="btn btn-outline-warning btn-sm"
                                        id="profilePhotoActionBtn">
                                    <i class="fas fa-camera me-1" id="profilePhotoActionIcon"></i>
                                    <span id="profilePhotoActionLabel">Alterar foto</span>
                                </button>
                                <button type="button"
                                        class="btn btn-link btn-sm text-muted py-0 d-none"
                                        id="profilePhotoRecropBtn">
                                    <i class="fas fa-crop-alt me-1"></i>Ajustar recorte
                                </button>
                                <button type="button"
                                        class="btn btn-link btn-sm text-muted py-0 d-none"
                                        id="profilePhotoCancelBtn">
                                    Cancelar seleção
                                </button>
                            </div>
                            <?php if (ImageHelper::userImageExists((int)($_SESSION['user_id'] ?? 0), (string)($this->data['form']['image'] ?? ''))): ?>
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        id="profilePhotoRemoveBtn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#removePhotoModal">
                                    <i class="fas fa-trash me-1"></i>
                                    Remover foto
                                </button>
                            <?php endif; ?>
                            <small class="form-text text-muted d-block">
                                Selecione a foto e ajuste o enquadramento (arrastar e zoom). O arquivo final terá até 2&nbsp;MB.
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
                    <p class="text-muted small mb-2" id="pwaInstallLead">
                        Instale o portal na tela inicial para abrir como aplicativo, com ícone próprio e melhor experiência em celular.
                    </p>
                    <div class="text-muted small mb-3" id="pwaInstallHintsBlock">
                        <p class="mb-1" id="pwaInstallDesktopHint">
                            <strong><i class="fab fa-android me-1"></i>Android:</strong> Google Chrome (recomendado).
                            <strong class="ms-2"><i class="fab fa-windows me-1"></i>Windows:</strong> o Edge também costuma funcionar.
                        </p>
                        <p class="mb-0" id="pwaInstallIosHint">
                            <strong><i class="fab fa-apple me-1"></i>iPhone / iPad (Safari):</strong>
                            <strong>Compartilhar</strong> <i class="fas fa-share-square"></i>
                            → <strong>Adicionar à Tela de Início</strong> → <strong>Adicionar</strong>.
                            Use o Safari (não apenas abrir dentro de outro app).
                        </p>
                    </div>
                    <div class="d-grid gap-2 d-sm-flex">
                        <button type="button" class="btn btn-success btn-sm" id="btnPwaInstall">
                            <i class="fas fa-download me-1"></i> Instalar aplicativo
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPwaInstallDismiss">
                            Já tenho instalado
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

<!-- Modal para recortar / posicionar foto -->
<div class="modal fade" id="profilePhotoCropModal" tabindex="-1" aria-labelledby="profilePhotoCropModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profilePhotoCropModalLabel">
                    <i class="fas fa-crop-alt me-2"></i>Ajustar enquadramento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Arraste a imagem, use a roda do mouse ou os pinos para zoom. Enquadre apenas a área que deve aparecer no perfil (formato circular).
                </p>
                <div class="profile-photo-crop-stage bg-dark rounded overflow-hidden">
                    <img id="profilePhotoCropImg" src="" alt="Recortar foto" class="d-block max-w-100">
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="profilePhotoCropConfirmBtn">
                    <i class="fas fa-check me-1"></i>Usar este recorte
                </button>
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
.profile-photo-crop-stage { max-height: min(65vh, 480px); }
.profile-photo-crop-stage .cropper-view-box,
.profile-photo-crop-stage .cropper-face { border-radius: 50%; }
.profile-photo-crop-stage .cropper-view-box {
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.65);
    outline: none;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    function initProfilePhotoWorkflow() {
        if (typeof bootstrap === 'undefined') {
            window.setTimeout(initProfilePhotoWorkflow, 40);
            return;
        }

    var MAX_SOURCE_BYTES = 8 * 1024 * 1024;
    var MAX_OUTPUT_BYTES = 2 * 1024 * 1024;
    var OUTPUT_SIZE = 512;
    var previewUrl = null;
    var cropSourceUrl = null;
    var sourceFile = null;
    var photoPending = false;
    var cropper = null;
    var cropConfirmed = false;

    var form = document.getElementById('profilePhotoForm');
    var input = document.getElementById('profilePhotoInput');
    var actionBtn = document.getElementById('profilePhotoActionBtn');
    var actionIcon = document.getElementById('profilePhotoActionIcon');
    var actionLabel = document.getElementById('profilePhotoActionLabel');
    var cancelBtn = document.getElementById('profilePhotoCancelBtn');
    var recropBtn = document.getElementById('profilePhotoRecropBtn');
    var hint = document.getElementById('profilePhotoSelectedHint');
    var preview = document.getElementById('profileAvatarPreview');
    var current = document.getElementById('profileAvatarCurrent');
    var removeBtn = document.getElementById('profilePhotoRemoveBtn');
    var cropModalEl = document.getElementById('profilePhotoCropModal');
    var cropImg = document.getElementById('profilePhotoCropImg');
    var cropConfirmBtn = document.getElementById('profilePhotoCropConfirmBtn');
    var cropModalInstance = null;

    function getCropModal() {
        if (!cropModalEl) return null;
        if (!cropModalInstance) {
            cropModalInstance = bootstrap.Modal.getOrCreateInstance(cropModalEl);
        }
        return cropModalInstance;
    }

    function revokePreviewUrl() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    }

    function revokeCropSourceUrl() {
        if (cropSourceUrl) {
            URL.revokeObjectURL(cropSourceUrl);
            cropSourceUrl = null;
        }
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function setFileInputFromBlob(blob, filename) {
        if (!input || typeof DataTransfer === 'undefined') return false;
        var dt = new DataTransfer();
        dt.items.add(new File([blob], filename, { type: blob.type || 'image/jpeg' }));
        input.files = dt.files;
        return input.files.length > 0;
    }

    function setActionIdle() {
        photoPending = false;
        sourceFile = null;
        destroyCropper();
        revokeCropSourceUrl();
        if (actionBtn) {
            actionBtn.type = 'button';
            actionBtn.disabled = false;
            actionBtn.className = 'btn btn-outline-warning btn-sm';
        }
        if (actionIcon) actionIcon.className = 'fas fa-camera me-1';
        if (actionLabel) actionLabel.textContent = 'Alterar foto';
        if (cancelBtn) cancelBtn.classList.add('d-none');
        if (recropBtn) recropBtn.classList.add('d-none');
        if (hint) hint.classList.add('d-none');
        if (preview) {
            preview.classList.add('d-none');
            preview.removeAttribute('src');
        }
        if (current) current.classList.remove('opacity-50');
        if (removeBtn) removeBtn.classList.remove('d-none');
        if (input) input.value = '';
        revokePreviewUrl();
    }

    function setActionPending() {
        photoPending = true;
        if (actionBtn) {
            actionBtn.type = 'submit';
            actionBtn.className = 'btn btn-primary btn-sm';
        }
        if (actionIcon) actionIcon.className = 'fas fa-save me-1';
        if (actionLabel) actionLabel.textContent = 'Salvar alteração';
        if (cancelBtn) cancelBtn.classList.remove('d-none');
        if (recropBtn) recropBtn.classList.remove('d-none');
        if (hint) hint.classList.remove('d-none');
        if (preview) preview.classList.remove('d-none');
        if (current) current.classList.add('opacity-50');
        if (removeBtn) removeBtn.classList.add('d-none');
    }

    function openPicker() {
        if (input) input.click();
    }

    function initCropper() {
        if (!cropImg || typeof Cropper === 'undefined') {
            alert('O editor de recorte não carregou. Recarregue a página e tente novamente.');
            return;
        }
        destroyCropper();
        cropper = new Cropper(cropImg, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.92,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            minContainerWidth: 200,
            minContainerHeight: 200,
        });
    }

    function openCropModal(file) {
        var cropModal = getCropModal();
        if (!file || !cropModal || !cropImg) {
            alert('Não foi possível abrir o editor de foto. Recarregue a página e tente novamente.');
            return;
        }
        sourceFile = file;
        revokeCropSourceUrl();
        cropSourceUrl = URL.createObjectURL(file);
        cropImg.src = cropSourceUrl;
        cropConfirmed = false;
        cropModal.show();
    }

    function blobToOutputFile(blob, originalName) {
        var base = (originalName || 'foto-perfil').replace(/\.[^.]+$/, '');
        var ext = blob.type === 'image/png' ? 'png' : 'jpg';
        return new File([blob], base + '-perfil.' + ext, { type: blob.type || 'image/jpeg' });
    }

    function exportCroppedBlob(callback) {
        if (!cropper) {
            callback(null);
            return;
        }
        var qualities = [0.92, 0.85, 0.75, 0.65];
        var sizes = [OUTPUT_SIZE, 448, 384];
        var mime = 'image/jpeg';

        function tryExport(sizeIdx, qualityIdx) {
            if (sizeIdx >= sizes.length) {
                callback(null);
                return;
            }
            var size = sizes[sizeIdx];
            var canvas = cropper.getCroppedCanvas({
                width: size,
                height: size,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            if (!canvas) {
                tryExport(sizeIdx + 1, 0);
                return;
            }
            var q = qualities[qualityIdx];
            canvas.toBlob(function (blob) {
                if (!blob) {
                    tryExport(sizeIdx + 1, 0);
                    return;
                }
                if (blob.size <= MAX_OUTPUT_BYTES || qualityIdx >= qualities.length - 1) {
                    callback(blob);
                    return;
                }
                tryExport(sizeIdx, qualityIdx + 1);
            }, mime, q);
        }
        tryExport(0, 0);
    }

    function applyCroppedBlob(blob) {
        if (!blob) {
            alert('Não foi possível processar o recorte. Tente novamente.');
            return;
        }
        var file = blobToOutputFile(blob, sourceFile ? sourceFile.name : 'foto');
        if (!setFileInputFromBlob(file, file.name)) {
            alert('Seu navegador não suporta o envio do recorte. Tente outro navegador.');
            return;
        }
        revokePreviewUrl();
        previewUrl = URL.createObjectURL(blob);
        if (preview) preview.src = previewUrl;
        setActionPending();
        cropConfirmed = true;
        var cropModal = getCropModal();
        if (cropModal) cropModal.hide();
    }

    if (cropModalEl) {
        cropModalEl.addEventListener('shown.bs.modal', function () {
            initCropper();
        });
        cropModalEl.addEventListener('hidden.bs.modal', function () {
            destroyCropper();
            if (!cropConfirmed && !photoPending) {
                if (input) input.value = '';
                revokeCropSourceUrl();
                sourceFile = null;
            }
        });
    }

    if (cropConfirmBtn) {
        cropConfirmBtn.addEventListener('click', function () {
            cropConfirmBtn.disabled = true;
            exportCroppedBlob(function (blob) {
                cropConfirmBtn.disabled = false;
                applyCroppedBlob(blob);
            });
        });
    }

    if (actionBtn) {
        actionBtn.addEventListener('click', function (e) {
            if (!photoPending) {
                e.preventDefault();
                openPicker();
            }
        });
    }

    if (recropBtn) {
        recropBtn.addEventListener('click', function () {
            if (sourceFile) {
                openCropModal(sourceFile);
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            setActionIdle();
        });
    }

    if (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                if (!photoPending) setActionIdle();
                return;
            }
            if (!file.type.match(/^image\//)) {
                alert('Selecione um arquivo de imagem (JPG, PNG ou GIF).');
                input.value = '';
                return;
            }
            if (file.size > MAX_SOURCE_BYTES) {
                alert('A imagem original deve ter no máximo 8 MB.');
                input.value = '';
                return;
            }
            openCropModal(file);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!photoPending || !input || !input.files || !input.files.length) {
                e.preventDefault();
                return;
            }
            if (actionBtn) {
                actionBtn.disabled = true;
                if (actionIcon) actionIcon.className = 'fas fa-spinner fa-spin me-1';
                if (actionLabel) actionLabel.textContent = 'Salvando…';
            }
            if (cancelBtn) cancelBtn.classList.add('d-none');
            if (recropBtn) recropBtn.classList.add('d-none');
        });
    }

    window.openProfilePhotoModal = function () {
        if (photoPending && preview && preview.src) {
            var modalImg = document.getElementById('profilePhotoModalImg');
            var modalEl = document.getElementById('profilePhotoModal');
            if (modalImg && modalEl) {
                modalImg.src = preview.src;
                bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }).show();
            }
            return;
        }
        var img = document.getElementById('profileAvatarImg');
        var modalImg = document.getElementById('profilePhotoModalImg');
        var modalEl = document.getElementById('profilePhotoModal');
        if (!img || !modalImg || !modalEl) return;
        var src = img.getAttribute('src') || '';
        if (!src && img.tagName === 'IMG') src = img.src || '';
        if (!src) return;
        modalImg.src = src;
        bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }).show();
    };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfilePhotoWorkflow);
    } else {
        initProfilePhotoWorkflow();
    }
})();

window.__PushNotificationsInit = {
    urlAdm: <?php echo json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), JSON_UNESCAPED_SLASHES); ?>,
    csrfToken: <?php echo json_encode(CSRFHelper::generateCSRFToken('form_push_subscribe'), JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="<?php echo htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), ENT_QUOTES, 'UTF-8'); ?>/public/adms/js/push-notifications.js?v=10"></script>

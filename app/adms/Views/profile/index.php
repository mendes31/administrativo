<?php
// Verificar se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $_ENV['URL_ADM'] . 'login');
    exit;
}

use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Meu Perfil</h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Meu Perfil</li>
    </ol>

    <div class="row">
        <!-- Coluna da foto e informações básicas -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Foto do Perfil
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($this->data['form']['image']) && $this->data['form']['image'] !== 'icon_user.png'): ?>
                        <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/<?php echo $_SESSION['user_id']; ?>/<?php echo $this->data['form']['image']; ?>" 
                             alt="Foto do usuário" 
                             class="img-fluid rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/icon_user.png" 
                             alt="Foto padrão" 
                             class="img-fluid rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?></h5>
                    <p class="card-text text-muted">
                        <i class="fas fa-briefcase me-1"></i>
                        <?php echo htmlspecialchars($this->data['form']['pos_name'] ?? ''); ?>
                    </p>
                    <p class="card-text text-muted">
                        <i class="fas fa-building me-1"></i>
                        <?php echo htmlspecialchars($this->data['form']['dep_name'] ?? ''); ?>
                    </p>
                    
                                         <a href="<?php echo $_ENV['URL_ADM']; ?>update-password" class="btn btn-warning btn-sm">
                        <i class="fas fa-key me-1"></i>
                        Alterar Senha
                    </a>
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

                    <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_profile'); ?>">

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
                            <label for="pos_name" class="form-label">Cargo</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pos_name" 
                                   value="<?php echo htmlspecialchars($this->data['form']['pos_name'] ?? ''); ?>" 
                                   readonly>
                            <small class="form-text text-muted">O cargo não pode ser alterado</small>
                        </div>

                        <div class="col-12">
                            <label for="image" class="form-label">Foto do Perfil</label>
                            <input type="file" 
                                   name="image" 
                                   class="form-control mb-2" 
                                   id="image" 
                                   accept="image/*">
                            <small class="form-text text-muted mb-3 d-block">
                                Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.
                            </small>
                            
                            <?php if (!empty($this->data['form']['image']) && $this->data['form']['image'] !== 'users/icon_user.png'): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm" 
                                            id="removePhotoBtn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#removePhotoModal">
                                        <i class="fas fa-trash me-1"></i>
                                        Remover Foto
                                    </button>
                                    <small class="text-muted">Clique para remover a foto atual</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Salvar Foto
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Voltar
                            </a>
                        </div>
                    </form>
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

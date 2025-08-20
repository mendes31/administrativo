<?php
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gerenciar Matriz de Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Matriz</li>
        </ol>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary">
                        <i class="fas fa-users"></i>
                        <?php echo number_format($this->data['stats']['total_users'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Total de Colaboradores</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info">
                        <i class="fas fa-briefcase"></i>
                        <?php echo number_format($this->data['stats']['total_positions'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Total de Cargos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo number_format($this->data['stats']['total_trainings'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Total de Treinamentos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning">
                        <i class="fas fa-table"></i>
                        <?php echo number_format($this->data['stats']['total_matrix_entries'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Entradas na Matriz</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Status -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estatísticas por Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($this->data['status_stats'] as $stat): ?>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <?php 
                                        $icon = 'fas fa-clock';
                                        $color = 'text-warning';
                                        switch ($stat['status']) {
                                            case 'concluido':
                                                $icon = 'fas fa-check-circle';
                                                $color = 'text-success';
                                                break;
                                            case 'vencido':
                                                $icon = 'fas fa-exclamation-triangle';
                                                $color = 'text-danger';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $icon; ?> <?php echo $color; ?> fa-2x me-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h4 class="mb-0"><?php echo number_format($stat['count']); ?></h4>
                                        <small class="text-muted">
                                            <?php echo ucfirst($stat['status']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações de Atualização -->
    <div class="row">
        <!-- Atualizar Todos -->
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sync-alt me-2"></i>
                        Atualizar Matriz Completa
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Atualiza a matriz de treinamentos para todos os colaboradores baseado nos vínculos atuais.
                    </p>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_all">
                        <button type="submit" class="btn btn-primary w-100" 
                                onclick="return confirm('Tem certeza que deseja atualizar a matriz para todos os usuários?')">
                            <i class="fas fa-sync-alt me-2"></i>
                            Atualizar Todos
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Atualizar por Usuário -->
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Atualizar por Colaborador
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_user">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Selecionar Colaborador</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Escolha um colaborador...</option>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-user-edit me-2"></i>
                            Atualizar Colaborador
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Atualizar por Cargo -->
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>
                        Atualizar por Cargo
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_position">
                        <div class="mb-3">
                            <label for="position_id" class="form-label">Selecionar Cargo</label>
                            <select name="position_id" id="position_id" class="form-select" required>
                                <option value="">Escolha um cargo...</option>
                                <?php foreach ($this->data['positions'] as $position): ?>
                                    <option value="<?php echo $position['id']; ?>">
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-briefcase me-2"></i>
                            Atualizar Cargo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Como funciona a Matriz de Treinamentos</h6>
                <ul class="mb-0">
                    <li>A matriz é gerada automaticamente baseada nos vínculos entre treinamentos e cargos</li>
                    <li>Quando um treinamento é vinculado a um cargo, todos os colaboradores desse cargo recebem o treinamento como obrigatório</li>
                    <li>Use as opções acima para atualizar a matriz quando houver mudanças nos vínculos</li>
                    <li>A atualização "Atualizar Todos" pode demorar dependendo do número de colaboradores</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Links Úteis -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar aos Treinamentos
                </a>
                <div>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-info me-2">
                        <i class="fas fa-chart-bar me-2"></i>Ver Status dos Treinamentos
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Listar Treinamentos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 
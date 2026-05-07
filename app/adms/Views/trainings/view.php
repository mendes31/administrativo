<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
$csrfVersionToken = CSRFHelper::generateCSRFToken('form_new_training_version');
$currentVersion = (int)($this->data['training']['versao'] ?? 0);
$nextVersion = $currentVersion > 0 ? ($currentVersion + 1) : 1;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes do Treinamento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <!-- Informações do Treinamento -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo htmlspecialchars($this->data['training']['nome']); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Código:</strong></td>
                            <td><?php echo htmlspecialchars($this->data['training']['codigo']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Versão:</strong></td>
                            <td>
                                <?php if (!empty($this->data['training']['versao'])): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($this->data['training']['versao']); ?></span>
                                    <?php if (!empty($this->data['training']['is_current_version'])): ?>
                                        <span class="badge bg-success ms-1">Atual</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Família:</strong></td>
                            <td>
                                <?php echo htmlspecialchars((string)($this->data['training']['training_family_key'] ?? $this->data['training']['codigo'] ?? '-')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td>
                                <?php if (!empty($this->data['training']['tipo'])): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($this->data['training']['tipo']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Instrutor:</strong></td>
                            <td>
                                <?php if (!empty($this->data['training']['instructor_name'])): ?>
                                    <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($this->data['training']['instructor_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Prazo Treinamento (dias):</strong></td>
                            <td>
                                <?php if (isset($this->data['training']['prazo_treinamento'])): ?>
                                    <span class="badge bg-info">
                                        <?php echo (int)$this->data['training']['prazo_treinamento']; ?> dias
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Carga Horária (hh:mm):</strong></td>
                            <td>
                                <?php if (!empty($this->data['training']['carga_horaria'])): ?>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo htmlspecialchars(substr($this->data['training']['carga_horaria'], 0, 5)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Não informada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php if ($this->data['training']['ativo']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Inativo
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td>
                                <?php if (!empty($this->data['training']['created_at'])): ?>
                                    <?php echo (new DateTime($this->data['training']['created_at']))->format('d/m/Y H:i'); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (!isset($this->data['training']['is_current_version']) || (int)$this->data['training']['is_current_version'] === 1): ?>
    <!-- Nova versão -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-code-branch me-2"></i>Nova Versão</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>new-training-version">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfVersionToken; ?>">
                <input type="hidden" name="source_training_id" value="<?php echo (int)$this->data['training']['id']; ?>">
                <input type="hidden" name="nome" value="<?php echo htmlspecialchars((string)($this->data['training']['nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="prazo_treinamento" value="<?php echo (int)($this->data['training']['prazo_treinamento'] ?? 0); ?>">
                <input type="hidden" name="tipo" value="<?php echo htmlspecialchars((string)($this->data['training']['tipo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="instrutor" value="<?php echo htmlspecialchars((string)($this->data['training']['instrutor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="carga_horaria" value="<?php echo htmlspecialchars((string)($this->data['training']['carga_horaria'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="instructor_user_id" value="<?php echo (int)($this->data['training']['instructor_user_id'] ?? 0); ?>">
                <input type="hidden" name="instructor_email" value="<?php echo htmlspecialchars((string)($this->data['training']['instructor_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="instructor_name" value="<?php echo htmlspecialchars((string)($this->data['training']['instructor_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="reciclagem" value="<?php echo (int)($this->data['training']['reciclagem'] ?? 0); ?>">
                <input type="hidden" name="reciclagem_periodo" value="<?php echo (int)($this->data['training']['reciclagem_periodo'] ?? 0); ?>">
                <input type="hidden" name="area_responsavel_id" value="<?php echo (int)($this->data['training']['area_responsavel_id'] ?? 0); ?>">
                <input type="hidden" name="area_elaborador_id" value="<?php echo (int)($this->data['training']['area_elaborador_id'] ?? 0); ?>">
                <input type="hidden" name="tipo_obrigatoriedade" value="<?php echo htmlspecialchars((string)($this->data['training']['tipo_obrigatoriedade'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="versao" class="form-label"><strong>Nova versão</strong></label>
                        <input type="text" name="versao" id="versao" class="form-control" value="<?php echo $nextVersion; ?>" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label for="require_retraining" class="form-label"><strong>Exige retreinamento?</strong></label>
                        <select name="require_retraining" id="require_retraining" class="form-select">
                            <option value="1" selected>Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="change_summary" class="form-label"><strong>Resumo das alterações</strong></label>
                        <input type="text" name="change_summary" id="change_summary" class="form-control" maxlength="500" required>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar nova versão
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Histórico de versões -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Versões</h5>
        </div>
        <div class="card-body">
            <?php $versions = $this->data['trainingVersions'] ?? []; ?>
            <?php if (!empty($versions)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Versão</th>
                                <th>Status</th>
                                <th>Retreinamento</th>
                                <th>Resumo</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($versions as $version): ?>
                            <tr>
                                <td>v<?php echo htmlspecialchars((string)($version['versao'] ?? '-')); ?></td>
                                <td>
                                    <?php if (!empty($version['is_current_version'])): ?>
                                        <span class="badge bg-success">Atual</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Anterior</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($version['require_retraining']) && (int)$version['require_retraining'] === 0): ?>
                                        <span class="badge bg-info text-dark">Não</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Sim</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)($version['change_summary'] ?? '-')); ?></td>
                                <td>
                                    <?php if (!empty($version['created_at'])): ?>
                                        <?php echo (new DateTime((string)$version['created_at']))->format('d/m/Y H:i'); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)($version['id'] ?? 0) !== (int)($this->data['training']['id'] ?? 0)): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-training/<?php echo (int)$version['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Atual</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">Nenhuma outra versão encontrada para esta família.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary">
                        <i class="fas fa-users"></i>
                        <?php echo (int)($this->data['userStats']['total_users'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Total de Colaboradores</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning">
                        <i class="fas fa-clock"></i>
                        <?php echo (int)($this->data['userStats']['pendente_count'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo (int)($this->data['userStats']['concluido_count'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Concluídos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo (int)($this->data['userStats']['vencido_count'] ?? 0); ?>
                    </h3>
                    <p class="card-text">Vencidos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cargos Vinculados -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-link me-2"></i>
                Cargos Vinculados
                <span class="badge bg-primary ms-2">
                    <?php echo count($this->data['positions']); ?>
                </span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['positions'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Cargo</th>
                                <th class="text-center">Período Reciclagem</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['positions'] as $position): ?>
                                <?php 
                                $reciclagem = null;
                                foreach ($this->data['linkedPositions'] as $link) {
                                    if ($link['adms_position_id'] == $position['id']) {
                                        $reciclagem = $link['reciclagem_periodo'];
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $position['id']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($position['name']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($reciclagem)): ?>
                                            <?php echo FormatHelper::formatReciclagemPeriodo((int)$reciclagem); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Não aplicável</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Vinculado
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Nenhum cargo vinculado a este treinamento.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ações -->
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
                <div>
                    <?php if (!isset($this->data['training']['is_current_version']) || (int)$this->data['training']['is_current_version'] === 1): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-training/<?php echo $this->data['training']['id']; ?>" 
                           class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                    <?php endif; ?>
                    <?php if (!isset($this->data['training']['is_current_version']) || (int)$this->data['training']['is_current_version'] === 1): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>training-positions/<?php echo $this->data['training']['id']; ?>" 
                           class="btn btn-info me-2">
                            <i class="fas fa-link me-2"></i>Vincular Cargos
                        </a>
                    <?php endif; ?>
                    <?php if (!isset($this->data['training']['is_current_version']) || (int)$this->data['training']['is_current_version'] === 1): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>delete-training/<?php echo $this->data['training']['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Tem certeza que deseja excluir este treinamento?');">
                            <i class="fas fa-trash me-2"></i>Excluir
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 
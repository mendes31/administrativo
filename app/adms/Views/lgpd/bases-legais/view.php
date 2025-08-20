<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes da Base Legal LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-bases-legais" class="text-decoration-none">Bases Legais</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <!-- Informações da Base Legal -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-gavel me-2"></i>
                <?php echo htmlspecialchars($this->data['base_legal']['base_legal']); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?php echo $this->data['base_legal']['id']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Base Legal:</strong></td>
                            <td><?php echo htmlspecialchars($this->data['base_legal']['base_legal']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php if ($this->data['base_legal']['status'] === 'Ativo'): ?>
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
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Descrição:</strong></td>
                            <td>
                                <?php if (!empty($this->data['base_legal']['descricao'])): ?>
                                    <?php echo htmlspecialchars($this->data['base_legal']['descricao']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Exemplo:</strong></td>
                            <td>
                                <?php if (!empty($this->data['base_legal']['exemplo'])): ?>
                                    <?php echo htmlspecialchars($this->data['base_legal']['exemplo']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td><?php echo FormatHelper::formatDate($this->data['base_legal']['created_at']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Atualizado em:</strong></td>
                            <td>
                                <?php if (!empty($this->data['base_legal']['updated_at'])): ?>
                                    <?php echo FormatHelper::formatDate($this->data['base_legal']['updated_at']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não atualizado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Ações</h6>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-bases-legais-edit/<?php echo $this->data['base_legal']['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-bases-legais" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar à Lista
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-bases-legais-delete/<?php echo $this->data['base_legal']['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta base legal?')">
                    <i class="fas fa-trash me-1"></i>Excluir
                </a>
            </div>
        </div>
    </div>
</div> 
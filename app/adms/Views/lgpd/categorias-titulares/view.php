<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes da Categoria de Titular LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares" class="text-decoration-none">Categorias de Titulares</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <!-- Informações da Categoria de Titular -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>
                <?php echo htmlspecialchars($this->data['categoria_titular']['titular']); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?php echo $this->data['categoria_titular']['id']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Titular:</strong></td>
                            <td><?php echo htmlspecialchars($this->data['categoria_titular']['titular']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php if ($this->data['categoria_titular']['status'] === 'Ativo'): ?>
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
                            <td><strong>Exemplo:</strong></td>
                            <td>
                                <?php if (!empty($this->data['categoria_titular']['exemplo'])): ?>
                                    <?php echo htmlspecialchars($this->data['categoria_titular']['exemplo']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td><?php echo FormatHelper::formatDate($this->data['categoria_titular']['created_at']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Atualizado em:</strong></td>
                            <td>
                                <?php if (!empty($this->data['categoria_titular']['updated_at'])): ?>
                                    <?php echo FormatHelper::formatDate($this->data['categoria_titular']['updated_at']); ?>
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
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-edit/<?php echo $this->data['categoria_titular']['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar à Lista
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-delete/<?php echo $this->data['categoria_titular']['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta categoria de titular?')">
                    <i class="fas fa-trash me-1"></i>Excluir
                </a>
            </div>
        </div>
    </div>
</div> 
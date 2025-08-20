<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Inventário LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="text-decoration-none">Inventário</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Visualizar detalhes:</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('LgpdInventoryEdit', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory-edit/{$this->data['inventory']['id']}' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a> ";
                }
                if (in_array('LgpdRopaCreate', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa-create-from-inventory/{$this->data['inventory']['id']}' class='btn btn-success btn-sm me-1 mb-1'><i class='fa-solid fa-plus-circle'></i> Criar ROPA</a> ";
                }
                if (in_array('ListLgpdInventory', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- DETALHES DO INVENTÁRIO -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-info-circle"></i> DETALHES DO INVENTÁRIO</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['inventory']['area']); ?></p>
                                    <p><strong>Titular:</strong> <?php echo htmlspecialchars($this->data['inventory']['data_subject']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Local:</strong> <?php echo htmlspecialchars($this->data['inventory']['storage_location']); ?></p>
                                    <p><strong>Acesso:</strong> <?php echo htmlspecialchars($this->data['inventory']['access_level']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRUPOS CADASTRADOS -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-database"></i> GRUPOS CADASTRADOS</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($this->data['data_groups'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-success">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="25%">Grupo de Dados</th>
                                                <th width="35%">Dados Tratados</th>
                                                <th width="15%">Categoria</th>
                                                <th width="15%">Nível de Risco</th>
                                                <th width="5%">Obs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($this->data['data_groups'] as $index => $group): ?>
                                                <tr class="<?php echo $index % 2 == 0 ? 'table-light' : ''; ?>">
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?php echo ($index + 1); ?></span>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($group['example_fields'] ?? 'N/A'); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $group['data_category'] === 'Sensível' ? 'danger' : 'primary'; ?>">
                                                            <i class="fa-solid fa-shield-alt me-1"></i>
                                                            <?php echo $group['data_category']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $risk_class = $group['risk_level'] === 'Alto' ? 'danger' : 
                                                                   ($group['risk_level'] === 'Médio' ? 'warning' : 'success');
                                                        $risk_icon = $group['risk_level'] === 'Alto' ? 'fa-exclamation-triangle' : 
                                                                   ($group['risk_level'] === 'Médio' ? 'fa-exclamation-circle' : 'fa-check-circle');
                                                        ?>
                                                        <span class="badge bg-<?php echo $risk_class; ?>">
                                                            <i class="fa-solid <?php echo $risk_icon; ?> me-1"></i>
                                                            <?php echo $group['risk_level']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($group['notes'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    data-bs-toggle="tooltip" data-bs-placement="left" 
                                                                    title="<?php echo htmlspecialchars($group['notes']); ?>">
                                                                <i class="fa-solid fa-info-circle"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Script para ativar tooltips -->
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                        return new bootstrap.Tooltip(tooltipTriggerEl);
                                    });
                                });
                                </script>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fa-solid fa-info-circle"></i> Nenhum grupo de dados cadastrado para este inventário.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AÇÕES -->
            <div class="row mt-4">
                <div class="col-12">
                    <?php if (in_array('LgpdInventoryEdit', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-edit/<?php echo $this->data['inventory']['id']; ?>" class="btn btn-warning">
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </a>
                    <?php endif; ?>
                    <?php if (in_array('LgpdInventoryDelete', $this->data['buttonPermission'])): ?>
                        <form id="formDelete<?php echo $this->data['inventory']['id']; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-delete" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_delete_inventory'); ?>">
                            <input type="hidden" name="id" value="<?php echo $this->data['inventory']['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="confirmDeletion(event, <?php echo $this->data['inventory']['id']; ?>)">
                                <i class="fa-regular fa-trash-can"></i> Apagar
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function confirmDeletion(event, id) {
    event.preventDefault();
    
    if (confirm('Tem certeza que deseja excluir este inventário? Esta ação não pode ser desfeita.')) {
        document.getElementById('formDelete' + id).submit();
    }
}
</script>
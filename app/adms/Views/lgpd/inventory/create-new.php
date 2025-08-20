<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Inventário LGPD - Nova Abordagem</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="text-decoration-none">Inventário</a>
            </li>
            <li class="breadcrumb-item">Cadastrar (Nova Abordagem)</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar Grupo de Dados</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('LgpdInventory', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar um novo grupo de dados -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_inventory_new'); ?>">

                <div class="col-md-6 col-sm-12">
                    <label for="department_id" class="form-label">Departamento</label>
                    <select name="department_id" class="form-select" id="department_id" required>
                        <option value="" selected>Selecione</option>
                        <?php if (isset($this->data['departments'])): ?>
                            <?php foreach ($this->data['departments'] as $department): ?>
                                <option value="<?= $department['id'] ?>" <?= (isset($this->data['form']['department_id']) && $this->data['form']['department_id'] == $department['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_subject" class="form-label">Titular</label>
                    <select name="data_subject" class="form-select" id="data_subject" required>
                        <option value="" selected>Selecione</option>
                        <?php if (isset($this->data['categorias_titulares'])): ?>
                            <?php foreach ($this->data['categorias_titulares'] as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= (isset($this->data['form']['data_subject']) && $this->data['form']['data_subject'] == $categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['titular']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="storage_location" class="form-label">Local de Armazenamento</label>
                    <input type="text" name="storage_location" class="form-control" id="storage_location" placeholder="Ex: ERP - Servidor local, RH - Servidor interno" value="<?php echo $this->data['form']['storage_location'] ?? ''; ?>" required>
                </div>

                <div class="col-12">
                    <label for="access_level" class="form-label">Quem Tem Acesso</label>
                    <input type="text" name="access_level" class="form-control" id="access_level" placeholder="Ex: Vendas, Financeiro, RH" value="<?php echo $this->data['form']['access_level'] ?? ''; ?>" required>
                </div>

                <hr class="my-4">

                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fa-solid fa-database"></i> 
                        Grupo de Dados a Ser Cadastrado
                    </h5>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_group_id" class="form-label">Grupo de Dados</label>
                    <select name="data_group_id" class="form-select" id="data_group_id" required>
                        <option value="" selected>Selecione um grupo</option>
                        <?php if (isset($this->data['data_groups'])): ?>
                            <?php foreach ($this->data['data_groups'] as $group): ?>
                                <option value="<?= $group['id'] ?>" <?= (isset($this->data['form']['data_group_id']) && $this->data['form']['data_group_id'] == $group['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['name']) ?> 
                                    (<?= $group['is_sensitive'] ? 'Sensível' : 'Pessoal' ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Selecione o tipo de dados que será tratado</small>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="data_category" class="form-label">Categoria</label>
                    <select name="data_category" class="form-select" id="data_category" required>
                        <option value="Pessoal" <?php echo isset($this->data['form']['data_category']) && $this->data['form']['data_category'] == 'Pessoal' ? 'selected' : ''; ?>>Pessoal</option>
                        <option value="Sensível" <?php echo isset($this->data['form']['data_category']) && $this->data['form']['data_category'] == 'Sensível' ? 'selected' : ''; ?>>Sensível</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="risk_level" class="form-label">Nível de Risco</label>
                    <select name="risk_level" class="form-select" id="risk_level" required>
                        <option value="Baixo" <?php echo isset($this->data['form']['risk_level']) && $this->data['form']['risk_level'] == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                        <option value="Médio" <?php echo isset($this->data['form']['risk_level']) && $this->data['form']['risk_level'] == 'Médio' ? 'selected' : ''; ?>>Médio</option>
                        <option value="Alto" <?php echo isset($this->data['form']['risk_level']) && $this->data['form']['risk_level'] == 'Alto' ? 'selected' : ''; ?>>Alto</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="notes" class="form-label">Observações Específicas</label>
                    <textarea name="notes" class="form-control" id="notes" rows="3" placeholder="Observações específicas sobre este grupo de dados neste contexto..."><?php echo $this->data['form']['notes'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Adicionar Grupo
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>

            </form>

        </div>

    </div>

    <!-- Seção para mostrar inventários existentes -->
    <?php if (isset($this->data['existing_inventories']) && !empty($this->data['existing_inventories'])): ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fa-solid fa-list"></i> 
                Inventários Existentes
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Departamento</th>
                            <th>Titular</th>
                            <th>Local de Armazenamento</th>
                            <th>Acesso</th>
                            <th>Grupos Cadastrados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['existing_inventories'] as $inventory): ?>
                            <tr>
                                <td><?= htmlspecialchars($inventory['departamento_nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inventory['data_subject'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inventory['storage_location'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inventory['access_level'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                    $inventoryRepo = new \App\adms\Models\Repository\LgpdInventoryRepository();
                                    $groups = $inventoryRepo->getDataGroupsByInventoryId($inventory['id']);
                                    if (!empty($groups)) {
                                        foreach ($groups as $group) {
                                            echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($group['name']) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Nenhum grupo</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Auto-selecionar categoria e risco baseado no grupo selecionado
document.getElementById('data_group_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const groupName = selectedOption.text;
    
    // Auto-selecionar categoria baseada no nome do grupo
    const categorySelect = document.getElementById('data_category');
    const riskSelect = document.getElementById('risk_level');
    
    if (groupName.includes('Sensível')) {
        categorySelect.value = 'Sensível';
        riskSelect.value = 'Alto';
    } else {
        categorySelect.value = 'Pessoal';
        riskSelect.value = 'Médio';
    }
});
</script> 
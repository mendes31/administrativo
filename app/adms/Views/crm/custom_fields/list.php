<?php
$fields = $this->data['fields'] ?? [];
$entityTypeFilter = $this->data['entity_type_filter'] ?? '';
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-sliders-h text-primary me-2"></i>
            Campos Customizáveis
        </h1>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-create-custom-field<?= $entityTypeFilter ? '?entity_type=' . $entityTypeFilter : '' ?>" 
               class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Novo Campo
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filtrar por Entidade</label>
                    <select name="entity_type" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="partner" <?= $entityTypeFilter === 'partner' ? 'selected' : '' ?>>Parceiros</option>
                        <option value="opportunity" <?= $entityTypeFilter === 'opportunity' ? 'selected' : '' ?>>Oportunidades</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Campos -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($fields)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum campo customizável cadastrado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 10%;">Ordem</th>
                                <th style="width: 12%;">Entidade</th>
                                <th style="width: 18%;">Nome do Campo</th>
                                <th style="width: 18%;">Label</th>
                                <th style="width: 12%;">Tipo</th>
                                <th style="width: 8%;" class="text-center">Obrigatório</th>
                                <th style="width: 8%;" class="text-center">Status</th>
                                <th style="width: 9%;" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $index => $field): ?>
                                <tr>
                                    <td><?= $field['id'] ?></td>
                                    <td>
                                        <span class="badge bg-info" title="Ordem de exibição">
                                            <?= $field['display_order'] ?? 0 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $field['entity_type'] === 'partner' ? 'bg-primary' : 'bg-success' ?>">
                                            <?= $field['entity_type'] === 'partner' ? 'Parceiro' : 'Oportunidade' ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($field['field_name']) ?></code></td>
                                    <td><?= htmlspecialchars($field['field_label']) ?></td>
                                    <td>
                                        <?php
                                        $typeIcons = [
                                            'text' => 'fa-font',
                                            'number' => 'fa-hashtag',
                                            'date' => 'fa-calendar',
                                            'select' => 'fa-list',
                                            'textarea' => 'fa-align-left',
                                            'checkbox' => 'fa-check-square'
                                        ];
                                        $icon = $typeIcons[$field['field_type']] ?? 'fa-question';
                                        ?>
                                        <i class="fas <?= $icon ?> me-1"></i>
                                        <?= ucfirst($field['field_type']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($field['is_required']): ?>
                                            <span class="badge bg-danger">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($field['is_active']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-update-custom-field/<?= $field['id'] ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-custom-field/<?= $field['id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir este campo? Todos os dados associados serão perdidos!')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>


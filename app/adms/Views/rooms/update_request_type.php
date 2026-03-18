<?php
use App\adms\Helpers\CSRFHelper;
$type = $this->data['requestType'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Tipo de Solicitação (Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Tipos de Solicitação</li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow">
        <div class="card-header">
            <i class="fas fa-edit me-2"></i>Editar Tipo de Solicitação
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_room_request_type'); ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($type['code'] ?? ''); ?>" disabled>
                        <div class="form-text">O código não pode ser alterado.</div>
                    </div>
                    <div class="col-md-8">
                        <label for="name" class="form-label">Nome *</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="<?php echo htmlspecialchars($type['name'] ?? ''); ?>" required>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea name="description" id="description" class="form-control" rows="2"><?php
                            echo htmlspecialchars($type['description'] ?? '');
                        ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="default_responsible_group_id" class="form-label">Equipe Responsável (Padrão)</label>
                        <select name="default_responsible_group_id" id="default_responsible_group_id" class="form-select">
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['groups'] ?? []) as $group): ?>
                                <option value="<?php echo (int)$group['id']; ?>"
                                    <?php echo (!empty($type['default_responsible_group_id']) && (int)$type['default_responsible_group_id'] === (int)$group['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block">Configurações</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="requires_responsible" name="requires_responsible" value="1"
                                <?php echo !empty($type['requires_responsible']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requires_responsible">Requer Responsável</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="requires_quantity" name="requires_quantity" value="1"
                                <?php echo !empty($type['requires_quantity']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requires_quantity">Requer Quantidade</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                <?php echo !empty($type['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-request-types" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>


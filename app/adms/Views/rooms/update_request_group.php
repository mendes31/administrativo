<?php
use App\adms\Helpers\CSRFHelper;
$group = $this->data['group'] ?? [];
$memberIds = $this->data['groupMemberIds'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Equipe/Grupo (Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Equipes</li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow">
        <div class="card-header">
            <i class="fas fa-edit me-2"></i>Editar Grupo
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_room_request_group'); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nome *</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                <?php echo !empty($group['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea name="description" id="description" class="form-control" rows="2"><?php
                            echo htmlspecialchars($group['description'] ?? '');
                        ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="members" class="form-label">Membros (usuários ativos)</label>
                        <select name="members[]" id="members" class="form-select" multiple size="10">
                            <?php foreach (($this->data['users'] ?? []) as $user): ?>
                                <?php $uid = (int)$user['id']; ?>
                                <option value="<?php echo $uid; ?>" <?php echo in_array($uid, $memberIds, true) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Dica: segure Ctrl para selecionar vários.</div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-request-groups" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>


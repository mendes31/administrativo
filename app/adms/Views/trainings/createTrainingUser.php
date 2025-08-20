<?php
// ... código existente ...
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Vincular Colaboradores ao Treinamento</h2>
    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>training-users/store">
        <input type="hidden" name="training_id" value="<?php echo htmlspecialchars($this->data['training_id']); ?>">
        <div class="card mb-4 border-light shadow">
            <div class="card-header">
                <strong>Selecione os colaboradores:</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($this->data['users'] as $user): ?>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>">
                                <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success">Vincular Selecionados</button>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</div> 
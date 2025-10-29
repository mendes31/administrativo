<?php
$isEdit = !empty($this->data['tag']);
$tag = $this->data['tag'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-tag me-2"></i><?php echo $isEdit ? 'Editar' : 'Nova'; ?> Tag
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-tags">Tags</a></li>
            <li class="breadcrumb-item active"><?php echo $isEdit ? 'Editar' : 'Nova'; ?></li>
        </ol>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Informações da Tag</h5></div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?><?php echo $isEdit ? 'crm-update-tag/' . $tag['id'] : 'crm-create-tag'; ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Nome da Tag *</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="Ex: Cliente VIP"
                                   value="<?php echo htmlspecialchars($tag['name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cor *</label>
                            <div class="row g-2">
                                <?php foreach ($this->data['colors'] as $colorCode => $colorName): ?>
                                    <div class="col-6 col-md-4">
                                        <label class="d-block">
                                            <input type="radio" name="color" value="<?= $colorCode ?>" 
                                                   <?= ($tag['color'] ?? '#007bff') === $colorCode ? 'checked' : '' ?>
                                                   class="btn-check" id="color-<?= str_replace('#', '', $colorCode) ?>">
                                            <label class="btn btn-outline-secondary w-100" for="color-<?= str_replace('#', '', $colorCode) ?>">
                                                <span class="badge" style="background-color: <?= $colorCode ?>; width: 20px; height: 20px; display: inline-block; margin-right: 5px;"></span>
                                                <?= $colorName ?>
                                            </label>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição (opcional)</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Descrição da tag..."><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Preview -->
                        <div class="mb-4">
                            <label class="form-label">Preview:</label>
                            <div>
                                <span id="tag-preview" class="badge" style="background-color: <?= htmlspecialchars($tag['color'] ?? '#007bff') ?>; font-size: 1.1rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-tag me-1"></i><span id="tag-preview-text"><?= htmlspecialchars($tag['name'] ?? 'Nome da Tag') ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Salvar Tag
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-tags" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Preview dinâmico da tag
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const colorInputs = document.querySelectorAll('input[name="color"]');
    const preview = document.getElementById('tag-preview');
    const previewText = document.getElementById('tag-preview-text');

    // Atualizar nome
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            previewText.textContent = this.value || 'Nome da Tag';
        });
    }

    // Atualizar cor
    colorInputs.forEach(input => {
        input.addEventListener('change', function() {
            preview.style.backgroundColor = this.value;
        });
    });
});
</script>


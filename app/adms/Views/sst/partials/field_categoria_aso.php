<?php
use App\adms\Helpers\SstCategoriaAsoHelper;
$selected = $selected ?? '';
?>
<div class="col-md-6 mb-3">
    <label class="form-label" for="categoria_aso">Categoria ASO</label>
    <select name="categoria_aso" id="categoria_aso" class="form-select">
        <option value="">Todas as categorias</option>
        <?php foreach (SstCategoriaAsoHelper::all() as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($selected === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="form-text">Tipo do evento de avaliação (Admissional, Periódico…). Vazio = vale para todas.</div>
</div>

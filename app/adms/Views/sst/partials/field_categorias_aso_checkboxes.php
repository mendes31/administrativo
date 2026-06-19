<?php
use App\adms\Helpers\SstCategoriaAsoHelper;

/** @var list<string> $selectedCategorias Categorias já vinculadas; vazio = todas */
$selectedCategorias = $selectedCategorias ?? [];
$todasCategorias = $selectedCategorias === [];
?>
<div class="col-12 mb-3">
    <span class="form-label d-block">Categorias ASO</span>
    <div class="row g-2">
        <?php foreach (SstCategoriaAsoHelper::all() as $cat): ?>
            <?php $checked = !$todasCategorias && in_array($cat, $selectedCategorias, true); ?>
            <div class="col-md-6 col-lg-4">
                <div class="form-check">
                    <input type="checkbox"
                           name="categorias_aso[]"
                           id="categoria_aso_<?= md5($cat) ?>"
                           class="form-check-input"
                           value="<?= htmlspecialchars($cat) ?>"
                           <?= $checked ? 'checked' : '' ?>>
                    <label class="form-check-label" for="categoria_aso_<?= md5($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="form-text">
        Marque uma ou mais categorias no mesmo vínculo. Nenhuma marcada = vale para todas as categorias de ASO.
        A periodicidade do vínculo, se informada, sobrescreve a do catálogo do exame.
    </div>
</div>

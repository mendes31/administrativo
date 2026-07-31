<?php
/** @var array $form */
/** @var array $tipos */
$form = $form ?? [];
$tipos = $tipos ?? [];
?>
<div class="col-md-3">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" name="codigo" id="codigo" class="form-control" maxlength="40"
           value="<?= htmlspecialchars((string) ($form['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Opcional">
</div>
<div class="col-md-5">
    <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
    <input type="text" name="nome" id="nome" class="form-control" required maxlength="180"
           value="<?= htmlspecialchars((string) ($form['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-md-2">
    <label for="tipo" class="form-label">Tipo</label>
    <select name="tipo" id="tipo" class="form-select">
        <?php foreach ($tipos as $t): ?>
            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-2">
    <label for="status" class="form-label">Status</label>
    <select name="status" id="status" class="form-select">
        <option value="ativo" <?= ($form['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo" <?= ($form['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
    </select>
</div>
<div class="col-md-6">
    <label for="localizacao" class="form-label">Localização / área</label>
    <input type="text" name="localizacao" id="localizacao" class="form-control" maxlength="180"
           value="<?= htmlspecialchars((string) ($form['localizacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Ex.: Linha 1, Portaria, Almoxarifado">
</div>
<div class="col-12">
    <label for="descricao" class="form-label">Descrição</label>
    <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<div class="col-12">
    <label for="observacoes" class="form-label">Observações</label>
    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

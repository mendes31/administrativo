<?php
$form = $this->data['form'] ?? [];
$editableHeader = array_key_exists('editable', $this->data) ? !empty($this->data['editable']) : true;
$roH = $editableHeader ? '' : 'readonly';
?>
<div class="col-12 col-lg-6">
    <label for="nome" class="form-label">Nome <?= $editableHeader ? '<span class="text-danger">*</span>' : '' ?></label>
    <input type="text" name="nome" id="nome" maxlength="180" <?= $editableHeader ? 'required' : '' ?> <?= $roH ?>
           class="form-control form-control-sm"
           value="<?= htmlspecialchars((string) ($form['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Ex.: Physician Choice 13.10">
</div>
<div class="col-12">
    <label for="observacao" class="form-label">Observação</label>
    <textarea name="observacao" id="observacao" rows="2" class="form-control form-control-sm" <?= $roH ?>><?= htmlspecialchars((string) ($form['observacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<div class="col-6 col-md-2">
    <label for="icms_padrao" class="form-label">ICMS padrão %</label>
    <input type="text" name="icms_padrao" id="icms_padrao" class="form-control form-control-sm" <?= $roH ?>
           value="<?= htmlspecialchars((string) ($form['icms_padrao'] ?? '12,00'), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-2">
    <label for="pis_padrao" class="form-label">PIS padrão %</label>
    <input type="text" name="pis_padrao" id="pis_padrao" class="form-control form-control-sm" <?= $roH ?>
           value="<?= htmlspecialchars((string) ($form['pis_padrao'] ?? '1,65'), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-2">
    <label for="cofins_padrao" class="form-label">COFINS padrão %</label>
    <input type="text" name="cofins_padrao" id="cofins_padrao" class="form-control form-control-sm" <?= $roH ?>
           value="<?= htmlspecialchars((string) ($form['cofins_padrao'] ?? '7,60'), ENT_QUOTES, 'UTF-8') ?>">
</div>
<p class="small text-muted col-12 mb-0">As alíquotas padrão preenchem linhas novas. Em cada produto você pode alterar ICMS, PIS e COFINS.</p>

<?php
/** @var array $form */
/** @var array $tipos */
/** @var list<array<string, mixed>> $filiais */
/** @var string|null $codigoPreview */
$form = $form ?? [];
$tipos = $tipos ?? [];
$filiais = $filiais ?? [];
$isEmbarcado = (($form['tipo'] ?? '') === 'embarcado');
$isEdit = !empty($form['id']) || !empty($sistemaId);
$codigoAtual = trim((string) ($form['codigo'] ?? ''));
$codigoPreview = trim((string) ($codigoPreview ?? ''));
?>
<div class="col-12 col-md-3">
    <label for="codigo" class="form-label">Código interno</label>
    <?php if ($isEdit && $codigoAtual !== ''): ?>
        <input type="text" id="codigo" class="form-control" value="<?= htmlspecialchars($codigoAtual, ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigoAtual, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-text">Gerado automaticamente; não pode ser alterado.</div>
    <?php else: ?>
        <input type="text" id="codigo" class="form-control" value="<?= htmlspecialchars($codigoPreview !== '' ? $codigoPreview : '00001', ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
        <div class="form-text">Gerado automaticamente ao salvar (sequencial).</div>
    <?php endif; ?>
</div>
<div class="col-12 col-md-5">
    <label for="nome" class="form-label">Nome do sistema / software <span class="text-danger">*</span></label>
    <input type="text" name="nome" id="nome" class="form-control" required maxlength="180"
           value="<?= htmlspecialchars((string) ($form['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Ex.: IHM Siemens, Portal RH">
</div>
<div class="col-6 col-md-2">
    <label for="tipo" class="form-label">Tipo</label>
    <select name="tipo" id="tipo" class="form-select">
        <?php foreach ($tipos as $t): ?>
            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-2">
    <label for="status" class="form-label">Status</label>
    <select name="status" id="status" class="form-select">
        <option value="ativo" <?= ($form['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo" <?= ($form['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
    </select>
</div>

<div class="col-12 col-md-4">
    <label for="adms_branch_id" class="form-label">Filial</label>
    <select name="adms_branch_id" id="adms_branch_id" class="form-select">
        <option value="">Selecione…</option>
        <?php foreach ($filiais as $filial): ?>
            <?php
            $fid = (int) ($filial['id'] ?? 0);
            $flabel = trim((string) ($filial['nome_fantasia'] ?? ''));
            if ($flabel === '') {
                $flabel = (string) ($filial['name'] ?? '');
            }
            $fcode = trim((string) ($filial['code'] ?? ''));
            if ($fcode !== '') {
                $flabel .= ' (' . $fcode . ')';
            }
            ?>
            <option value="<?= $fid ?>" <?= (int) ($form['adms_branch_id'] ?? 0) === $fid ? 'selected' : '' ?>>
                <?= htmlspecialchars($flabel, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="form-text">Unidade onde o sistema/equipamento está instalado.</div>
</div>
<div class="col-12 col-md-4">
    <label for="localizacao" class="form-label">Localização / área</label>
    <input type="text" name="localizacao" id="localizacao" class="form-control" maxlength="180"
           value="<?= htmlspecialchars((string) ($form['localizacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Ex.: Linha 1, Portaria, Almoxarifado">
</div>
<div class="col-12 col-md-4">
    <label for="equipamento_tag" class="form-label">
        Tag do equipamento
        <span class="text-danger ti-embarcado-required<?= $isEmbarcado ? '' : ' d-none' ?>">*</span>
    </label>
    <input type="text" name="equipamento_tag" id="equipamento_tag" class="form-control" maxlength="80"
           value="<?= htmlspecialchars((string) ($form['equipamento_tag'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Ex.: EXT-03, IHM-L1"
           <?= $isEmbarcado ? 'required' : '' ?>>
    <div class="form-text">Identifica a máquina física. Obrigatório para tipo <em>embarcado</em> (mesmo software pode existir em vários equipamentos).</div>
</div>

<div id="ti-embarcado-extra" class="col-12<?= $isEmbarcado ? '' : ' d-none' ?>">
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <label for="fabricante" class="form-label">Fabricante</label>
            <input type="text" name="fabricante" id="fabricante" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars((string) ($form['fabricante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-4">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" name="modelo" id="modelo" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars((string) ($form['modelo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-4">
            <label for="numero_serie" class="form-label">Nº de série / ref. patrimônio</label>
            <input type="text" name="numero_serie" id="numero_serie" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars((string) ($form['numero_serie'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Texto livre (sem vínculo com Patrimônio)">
        </div>
    </div>
</div>

<div class="col-12">
    <label for="descricao" class="form-label">Descrição</label>
    <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<div class="col-12">
    <label for="observacoes" class="form-label">Observações</label>
    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<script>
(function () {
    var tipo = document.getElementById('tipo');
    var extra = document.getElementById('ti-embarcado-extra');
    var tag = document.getElementById('equipamento_tag');
    var reqMark = document.querySelector('.ti-embarcado-required');
    if (!tipo || !extra || !tag) {
        return;
    }
    function sync() {
        var emb = tipo.value === 'embarcado';
        extra.classList.toggle('d-none', !emb);
        if (reqMark) {
            reqMark.classList.toggle('d-none', !emb);
        }
        if (emb) {
            tag.setAttribute('required', 'required');
        } else {
            tag.removeAttribute('required');
        }
    }
    tipo.addEventListener('change', sync);
    sync();
})();
</script>

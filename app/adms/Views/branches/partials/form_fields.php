<?php

use App\adms\Helpers\BranchFormHelper;

/** @var array<string, mixed> $form */
$form = $form ?? [];
$cnpjDisplay = !empty($form['cnpj']) ? BranchFormHelper::formatCnpj((string) $form['cnpj']) : '';
$cepDisplay = !empty($form['cep']) ? BranchFormHelper::formatCep((string) $form['cep']) : '';
$typeSelected = BranchFormHelper::normalizeType($form['establishment_type'] ?? null) ?? BranchFormHelper::TYPE_FILIAL;
$ufSelected = strtoupper(trim((string) ($form['uf'] ?? '')));
$dataAbertura = (string) ($form['data_abertura'] ?? '');
if ($dataAbertura !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAbertura)) {
    // input type=date usa yyyy-mm-dd
} elseif ($dataAbertura !== '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataAbertura, $m)) {
    $dataAbertura = $m[3] . '-' . $m[2] . '-' . $m[1];
}

// Grade: campo padrão = col-md-3 | campo largo = col-md-6 (2× padrão)
?>
<div class="col-12">
    <h6 class="text-secondary mb-0">Identificação (Receita Federal)</h6>
    <hr class="mt-1 mb-0">
</div>

<div class="col-12 col-md-3">
    <label for="establishment_type" class="form-label">Tipo <span class="text-danger">*</span></label>
    <select name="establishment_type" class="form-select" id="establishment_type" required>
        <?php foreach (BranchFormHelper::typeOptions() as $slug => $label): ?>
            <option value="<?= $slug ?>" <?= $typeSelected === $slug ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-md-3">
    <label for="cnpj" class="form-label">CNPJ</label>
    <input type="text" name="cnpj" class="form-control" id="cnpj" placeholder="00.000.000/0000-00" maxlength="18" value="<?= htmlspecialchars($cnpjDisplay) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="data_abertura" class="form-label">Data de abertura</label>
    <input type="date" name="data_abertura" class="form-control" id="data_abertura" value="<?= htmlspecialchars($dataAbertura) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="code" class="form-label">Código interno <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" id="code" placeholder="Ex.: MATRIZ, FILIAL-01" value="<?= htmlspecialchars((string) ($form['code'] ?? '')) ?>">
</div>

<div class="col-12 col-md-6">
    <label for="razao_social" class="form-label">Nome empresarial (razão social)</label>
    <input type="text" name="razao_social" class="form-control" id="razao_social" placeholder="Como no comprovante CNPJ" value="<?= htmlspecialchars((string) ($form['razao_social'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="porte" class="form-label">Porte</label>
    <input type="text" name="porte" class="form-control" id="porte" placeholder="Demais, ME, EPP" value="<?= htmlspecialchars((string) ($form['porte'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="situacao_cadastral" class="form-label">Situação cadastral</label>
    <input type="text" name="situacao_cadastral" class="form-control" id="situacao_cadastral" placeholder="ATIVA" value="<?= htmlspecialchars((string) ($form['situacao_cadastral'] ?? '')) ?>">
</div>

<div class="col-12 col-md-6">
    <label for="nome_fantasia" class="form-label">Nome fantasia <span class="text-danger">*</span></label>
    <input type="text" name="nome_fantasia" class="form-control" id="nome_fantasia" placeholder="Título do estabelecimento" value="<?= htmlspecialchars((string) ($form['nome_fantasia'] ?? '')) ?>">
</div>
<div class="col-12 col-md-6">
    <label for="name" class="form-label">Nome interno (lotação)</label>
    <input type="text" name="name" class="form-control" id="name" placeholder="Se vazio, usa o nome fantasia" value="<?= htmlspecialchars((string) ($form['name'] ?? '')) ?>">
</div>

<div class="col-12 col-md-6">
    <label for="cnae_principal" class="form-label">CNAE principal</label>
    <input type="text" name="cnae_principal" class="form-control" id="cnae_principal" placeholder="1099607 - Fabricação de alimentos dietéticos..." value="<?= htmlspecialchars((string) ($form['cnae_principal'] ?? '')) ?>">
</div>
<div class="col-12 col-md-6">
    <label for="natureza_juridica" class="form-label">Natureza jurídica</label>
    <input type="text" name="natureza_juridica" class="form-control" id="natureza_juridica" placeholder="2054 - Sociedade Anônima Fechada" value="<?= htmlspecialchars((string) ($form['natureza_juridica'] ?? '')) ?>">
</div>

<div class="col-12">
    <h6 class="text-secondary mb-0 mt-2">Endereço</h6>
    <hr class="mt-1 mb-0">
</div>

<div class="col-12 col-md-6">
    <label for="logradouro" class="form-label">Logradouro</label>
    <input type="text" name="logradouro" class="form-control" id="logradouro" placeholder="Avenida, Rua..." value="<?= htmlspecialchars((string) ($form['logradouro'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="numero" class="form-label">Número</label>
    <input type="text" name="numero" class="form-control" id="numero" value="<?= htmlspecialchars((string) ($form['numero'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="complemento" class="form-label">Complemento</label>
    <input type="text" name="complemento" class="form-control" id="complemento" placeholder="Anexo, Sala..." value="<?= htmlspecialchars((string) ($form['complemento'] ?? '')) ?>">
</div>

<div class="col-12 col-md-3">
    <label for="cep" class="form-label">CEP</label>
    <input type="text" name="cep" class="form-control" id="cep" placeholder="00000-000" maxlength="9" value="<?= htmlspecialchars($cepDisplay) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="bairro" class="form-label">Bairro / Distrito</label>
    <input type="text" name="bairro" class="form-control" id="bairro" value="<?= htmlspecialchars((string) ($form['bairro'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="municipio" class="form-label">Município</label>
    <input type="text" name="municipio" class="form-control" id="municipio" value="<?= htmlspecialchars((string) ($form['municipio'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="uf" class="form-label">UF</label>
    <select name="uf" class="form-select" id="uf">
        <option value="">—</option>
        <?php foreach (BranchFormHelper::ufOptions() as $ufCode => $ufLabel): ?>
            <option value="<?= $ufCode ?>" <?= $ufSelected === $ufCode ? 'selected' : '' ?>><?= $ufLabel ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="col-12">
    <h6 class="text-secondary mb-0 mt-2">Contato e situação</h6>
    <hr class="mt-1 mb-0">
</div>

<div class="col-12 col-md-6">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" name="email" class="form-control" id="email" placeholder="endereço eletrônico" value="<?= htmlspecialchars((string) ($form['email'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="phone" class="form-label">Telefone</label>
    <input type="text" name="phone" class="form-control" id="phone" placeholder="(00) 0000-0000" value="<?= htmlspecialchars((string) ($form['phone'] ?? '')) ?>">
</div>
<div class="col-12 col-md-3">
    <label for="active" class="form-label">Status no sistema</label>
    <select name="active" class="form-select" id="active">
        <option value="1" <?= (isset($form['active']) && (int) $form['active'] === 1) || !isset($form['active']) ? 'selected' : '' ?>>Ativo</option>
        <option value="0" <?= (isset($form['active']) && (int) $form['active'] === 0) ? 'selected' : '' ?>>Inativo</option>
    </select>
</div>

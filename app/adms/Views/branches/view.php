<?php

use App\adms\Helpers\BranchFormHelper;
use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_branch');
?>
<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Estabelecimento</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-branches" class="text-decoration-none">Filiais</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>
            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListBranches', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-branches' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                $id = ($this->data['branch']['id'] ?? '');
                if (in_array('UpdateBranch', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-branch/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm me-1 mb-1';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                if (in_array('DeleteBranch', $this->data['buttonPermission'])) {
                ?>
                    <form id="formDelete<?php echo ($this->data['branch']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-branch" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['branch']['id'] ?? ''); ?>">
                        <input type="hidden" name="name" id="name" value="<?php echo ($this->data['branch']['name'] ?? ''); ?>">
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['branch']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                    </form>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (isset($this->data['branch'])) {
                extract($this->data['branch']);
                $cnpjFmt = !empty($cnpj) ? BranchFormHelper::formatCnpj((string) $cnpj) : '—';
                $cepFmt = !empty($cep) ? BranchFormHelper::formatCep((string) $cep) : '—';
                $aberturaFmt = !empty($data_abertura) ? date('d/m/Y', strtotime((string) $data_abertura)) : '—';
                $dash = static fn ($v) => htmlspecialchars((string) (($v ?? '') !== '' ? $v : '—'));
                ?>
                <h6 class="text-secondary">Identificação</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-3">ID</dt><dd class="col-sm-9"><?= (int) $id ?></dd>
                    <dt class="col-sm-3">Tipo</dt><dd class="col-sm-9"><?= BranchFormHelper::typeLabel($establishment_type ?? null) ?></dd>
                    <dt class="col-sm-3">Nº de inscrição (CNPJ)</dt><dd class="col-sm-9"><?= htmlspecialchars($cnpjFmt) ?></dd>
                    <dt class="col-sm-3">Data de abertura</dt><dd class="col-sm-9"><?= htmlspecialchars($aberturaFmt) ?></dd>
                    <dt class="col-sm-3">Nome empresarial</dt><dd class="col-sm-9"><?= $dash($razao_social ?? null) ?></dd>
                    <dt class="col-sm-3">Nome fantasia</dt><dd class="col-sm-9"><?= $dash($nome_fantasia ?? null) ?></dd>
                    <dt class="col-sm-3">Porte</dt><dd class="col-sm-9"><?= $dash($porte ?? null) ?></dd>
                    <dt class="col-sm-3">CNAE principal</dt><dd class="col-sm-9"><?= $dash($cnae_principal ?? null) ?></dd>
                    <dt class="col-sm-3">Natureza jurídica</dt><dd class="col-sm-9"><?= $dash($natureza_juridica ?? null) ?></dd>
                    <dt class="col-sm-3">Nome interno</dt><dd class="col-sm-9"><?= $dash($name ?? null) ?></dd>
                    <dt class="col-sm-3">Código interno</dt><dd class="col-sm-9"><?= $dash($code ?? null) ?></dd>
                </dl>

                <h6 class="text-secondary">Endereço</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-3">Logradouro</dt><dd class="col-sm-9"><?= $dash($logradouro ?? null) ?></dd>
                    <dt class="col-sm-3">Número</dt><dd class="col-sm-9"><?= $dash($numero ?? null) ?></dd>
                    <dt class="col-sm-3">Complemento</dt><dd class="col-sm-9"><?= $dash($complemento ?? null) ?></dd>
                    <dt class="col-sm-3">CEP</dt><dd class="col-sm-9"><?= htmlspecialchars($cepFmt) ?></dd>
                    <dt class="col-sm-3">Bairro / Distrito</dt><dd class="col-sm-9"><?= $dash($bairro ?? null) ?></dd>
                    <dt class="col-sm-3">Município</dt><dd class="col-sm-9"><?= $dash($municipio ?? null) ?></dd>
                    <dt class="col-sm-3">UF</dt><dd class="col-sm-9"><?= $dash($uf ?? null) ?></dd>
                    <dt class="col-sm-3">Endereço (resumo)</dt><dd class="col-sm-9"><?= $dash($address ?? null) ?></dd>
                </dl>

                <h6 class="text-secondary">Contato e situação</h6>
                <dl class="row">
                    <dt class="col-sm-3">E-mail</dt><dd class="col-sm-9"><?= $dash($email ?? null) ?></dd>
                    <dt class="col-sm-3">Telefone</dt><dd class="col-sm-9"><?= $dash($phone ?? null) ?></dd>
                    <dt class="col-sm-3">Situação cadastral</dt><dd class="col-sm-9"><?= $dash($situacao_cadastral ?? null) ?></dd>
                    <dt class="col-sm-3">Status no sistema</dt><dd class="col-sm-9"><?= !empty($active) ? 'Ativo' : 'Inativo' ?></dd>
                    <dt class="col-sm-3">Cadastrado</dt><dd class="col-sm-9"><?= !empty($created_at) ? date('d/m/Y H:i:s', strtotime((string) $created_at)) : '—' ?></dd>
                    <dt class="col-sm-3">Editado</dt><dd class="col-sm-9"><?= !empty($updated_at) ? date('d/m/Y H:i:s', strtotime((string) $updated_at)) : '—' ?></dd>
                </dl>
            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Filial não encontrada!</div>";
            }
            ?>
        </div>
    </div>
</div>

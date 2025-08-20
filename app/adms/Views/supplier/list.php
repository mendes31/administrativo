<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_supplier');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Fornecedores</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Fornecedores</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-truck me-2"></i>Listar Fornecedores</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <!-- Upload de arquivo -->
                <form method="POST" action="<?php echo $_ENV['URL_ADM'] . 'process-file'; ?>" enctype="multipart/form-data" class="d-flex align-items-center gap-2 mb-1">
                    <input type="file" name="arquivo" id="arquivo" class="form-control form-control-sm" style="max-width: 200px;">
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-upload"></i> Enviar</button>
                </form>
                
                <?php
                if (in_array('CreateSupplier', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-supplier' class='btn btn-success btn-sm mb-1'><i class='fa-regular fa-square-plus'></i> Cadastrar</a>";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há Fornecedor no array
            if ($this->data['suppliers'] ?? false) {
            ?>
                <form method="GET" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search" class="form-label mb-1">Buscar</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($criteria['search'] ?? '') ?>" placeholder="Nome, código ou status...">
                    </div>
                    <div class="col-auto mb-2">
                        <label for="per_page" class="form-label mb-1">Mostrar</label>
                        <div class="d-flex align-items-center">
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 20) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-label mb-1 ms-1">registros</span>
                        </div>
                    </div>
                    <div class="col-md-2 filtros-btns-row w-100 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm btn-filtros-mobile"><i class="fa fa-times"></i> Limpar Filtros</a>
                    </div>
                </form>

                <!-- Tabela Desktop -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nome</th>
                                <th scope="col" class="d-none d-md-table-cell">Tipo</th>
                                <th scope="col" class="d-none d-md-table-cell">Documento</th>
                                <th scope="col" class="d-none d-md-table-cell">Telefone</th>
                                <th scope="col" class="d-none d-md-table-cell">E-mail</th>
                                <th scope="col" class="d-none d-md-table-cell">Endereço</th>
                                <th scope="col" class="d-none d-md-table-cell">Descrição</th>
                                <th scope="col" class="d-none d-md-table-cell">Data Nasc.</th>
                                <th scope="col">Ativo</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de Fornecedores
                            foreach ($this->data['suppliers'] as $supplier) {

                                // Extrai variáveis do array de Fornecedores
                                extract($supplier); ?>
                                <tr>
                                    <td><?php echo $card_code; ?></td>
                                    <td><?php echo $card_name; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $type_person; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $doc; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $phone; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $email; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $address; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $description; ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo $date_birth; ?></td>
                                    <td><?php echo ($active == 1) ? 'SIM' : 'NÃO'; ?></td>

                                    <td class="text-center">
                                        <div class="tabela-acoes">

                                            <?php
                                            if (in_array('ViewSupplier', $this->data['buttonPermission'])) {
                                                echo "<a href='{$_ENV['URL_ADM']}view-supplier/$id'
                                                class='btn btn-primary btn-sm me-1 mb-1' 
                                                data-bs-toggle='tooltip' 
                                                data-bs-placement='top' 
                                                data-bs-custom-class='tooltip-visualizar' 
                                                title='Visualizar'>
                                                <i class='fa-regular fa-eye'></i>
                                              </a>";
                                            }

                                            if (in_array('UpdateSupplier', $this->data['buttonPermission'])) {
                                                echo "<a href='{$_ENV['URL_ADM']}update-supplier/$id'
                                                class='btn btn-warning btn-sm me-1 mb-1' 
                                                data-bs-toggle='tooltip' 
                                                data-bs-placement='top' 
                                                data-bs-custom-class='tooltip-editar' 
                                                title='Editar'>
                                                <i class='fa-solid fa-pen-to-square'></i>
                                              </a>";
                                            }

                                            if (in_array('DeleteSupplier', $this->data['buttonPermission'])) {
                                            ?>

                                                <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-supplier" method="POST" class="d-inline">

                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                                                    <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">

                                                    <input type="hidden" name="card_name" id="card_name" value="<?php echo $card_name ?? ''; ?>">

                                                    <button type="submit"
                                                        class="btn btn-danger btn-sm me-1 mb-1"
                                                        onclick="confirmDeletion(event, <?php echo $id; ?>)"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-custom-class="tooltip-deletar"
                                                        title="Excluir">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>

                                                </form>
                                            <?php } ?>
                                        </div>

                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>
                    </table>
                </div>

                <!-- Paginação e informações abaixo da tabela/cards -->
                <div class="w-100 mt-2">
                    <!-- Desktop: frase à esquerda, paginação à direita -->
                    <div class="d-none d-md-flex justify-content-between align-items-center w-100">
                        <div class="text-secondary small">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php else: ?>
                                Exibindo <?= count($this->data['suppliers']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div>
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>

                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['suppliers'] as $i => $supplier) { extract($supplier); ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($card_name) ?></b></h5>
                                        <div class="mb-1"><b>Código:</b> <?= htmlspecialchars($card_code) ?></div>
                                        <div class="mb-1"><b>Status:</b> <?= ($active == 1) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardSupplierDetails<?= $i ?>" aria-expanded="false" aria-controls="cardSupplierDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardSupplierDetails<?= $i ?>">
                                    <div><b>Tipo:</b> <?= htmlspecialchars($type_person) ?></div>
                                    <div><b>Documento:</b> <?= htmlspecialchars($doc) ?></div>
                                    <div><b>Telefone:</b> <?= htmlspecialchars($phone) ?></div>
                                    <div><b>E-mail:</b> <?= htmlspecialchars($email) ?></div>
                                    <div><b>Endereço:</b> <?= htmlspecialchars($address) ?></div>
                                    <div><b>Descrição:</b> <?= htmlspecialchars($description) ?></div>
                                    <div><b>Data Nascimento:</b> <?= htmlspecialchars($date_birth) ?></div>
                                    <div class="mt-2">
                                        <?php
                                        if (in_array('ViewSupplier', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-supplier/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('UpdateSupplier', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-supplier/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeleteSupplier', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDeleteMobile<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-supplier" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                                <input type="hidden" name="id" id="id" value="<?= $id ?? ''; ?>">
                                                <input type="hidden" name="card_name" id="card_name" value="<?= $card_name ?? ''; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    
                    <!-- Paginação e informações abaixo dos cards no mobile -->
                    <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                        <div class="text-secondary small w-100 text-center mb-1">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php else: ?>
                                Exibindo <?= count($this->data['suppliers']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum fornecedor for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum Fornecedor encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script type="text/javascript">
    // DataTables removido para padronização do sistema
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
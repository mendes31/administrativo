<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_customer');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Clientes</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Clientes</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreateCustomer', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-customer' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>

            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há cliente no array
            if ($this->data['customers'] ?? false) {
            ?>
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search" class="form-label mb-1">Buscar</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nome, código, documento, etc.">
                    </div>
                    <div class="col-auto mb-2">
                        <label for="per_page" class="form-label mb-1">Mostrar</label>
                        <div class="d-flex align-items-center">
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
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
                
                <!-- Tabela -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Pessoa</th>
                                <th scope="col">Documento</th>
                                <th scope="col">Telefone</th>
                                <th scope="col">Email</th>
                                <th scope="col">Endereço</th>
                                <th scope="col">Descrição/Observações</th>
                                <th scope="col">Data Nascimento</th>
                                <th scope="col">Ativo</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de Clientes
                            foreach ($this->data['customers'] as $customer) {

                                // Extrai variáveis do array de Clientes
                                extract($customer); ?>
                                <tr>
                                    <td><?php echo $card_code; ?></td>
                                    <td><?php echo $card_name; ?></td>
                                    <td><?php echo $type_person; ?></td>
                                    <td><?php echo $doc; ?></td>
                                    <td><?php echo $phone; ?></td>
                                    <td><?php echo $email; ?></td>
                                    <td><?php echo $address; ?></td>
                                    <td><?php echo $description; ?></td>
                                    <td><?php echo $date_birth; ?></td>
                                    <td><?php echo ($active == 1) ? 'SIM' : 'NÃO'; ?></td>

                                    <td class="text-center">

                                        <?php
                                        if (in_array('ViewCustomer', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-customer/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('UpdateCustomer', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-customer/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeleteCustomer', $this->data['buttonPermission'])) {
                                        ?>

                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-customer" method="POST" class="d-inline">

                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">

                                                <input type="hidden" name="card_name" id="card_name" value="<?php echo $card_name ?? ''; ?>">

                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                                            </form>
                                        <?php } ?>

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
                                Exibindo <?= count($this->data['customers']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div>
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>
                
                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['customers'] as $i => $customer): ?>
                        <?php extract($customer); ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($card_name) ?></b></h5>
                                        <div class="mb-1"><b>ID:</b> <?= htmlspecialchars($card_code) ?></div>
                                        <div class="mb-1"><b>Status:</b> <?= ($active == 1) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardCustomerDetails<?= $i ?>" aria-expanded="false" aria-controls="cardCustomerDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardCustomerDetails<?= $i ?>">
                                    <div><b>Tipo de Pessoa:</b> <?= htmlspecialchars($type_person) ?></div>
                                    <div><b>Documento:</b> <?= htmlspecialchars($doc) ?></div>
                                    <div><b>Telefone:</b> <?= htmlspecialchars($phone) ?></div>
                                    <div><b>Email:</b> <?= htmlspecialchars($email) ?></div>
                                    <div><b>Endereço:</b> <?= htmlspecialchars($address) ?></div>
                                    <div><b>Descrição:</b> <?= htmlspecialchars($description) ?></div>
                                    <div><b>Data Nascimento:</b> <?= htmlspecialchars($date_birth) ?></div>
                                    <div class="mt-2">
                                        <?php
                                        if (in_array('ViewCustomer', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-customer/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('UpdateCustomer', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-customer/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeleteCustomer', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-customer" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">
                                                <input type="hidden" name="card_name" id="card_name" value="<?php echo $card_name ?? ''; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Paginação e informações abaixo dos cards no mobile -->
                    <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                        <div class="text-secondary small w-100 text-center mb-1">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php else: ?>
                                Exibindo <?= count($this->data['customers']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>
            <?php
            } else { // Exibe mensagem se nenhum cliente for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum Cliente encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script type="text/javascript">
    // DataTables removido para padronização do sistema
</script>
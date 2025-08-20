<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Clientes</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-customers" class="text-decoration-none">Clientes</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>

            <span class="ms-auto d-sm-flex flex-row">

                <?php
                if (in_array('ListCustomers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-customers' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar um novo Cliente -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_customer'); ?>">

                <div class="col-4">
                    <label for="card_code" class="form-label">Código do Cliente</label>
                    <input type="text" name="card_code" class="form-control" id="card_code" placeholder="Código do Cliente"
                        value="<?php echo $this->data['form']['card_code']  ?? ''; ?>" readonly>
                </div>

                <div class="col-4">
                    <label for="card_name" class="form-label">Nome</label>
                    <input type="text" name="card_name" class="form-control" id="card_name" placeholder="Nome do(a) Cliente" value="<?php echo $this->data['form']['card_name'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="type_person" class="form-label">Pessoa</label>
                    <select name="type_person" class="form-control" onchange="alterarMascaraDoc()" id="type_person">
                        <option value="Física" <?php echo (isset($this->data['form']['type_person']) && $this->data['form']['type_person'] === 'Física') ? 'selected' : ''; ?>>Física</option>
                        <option value="Jurídica" <?php echo (isset($this->data['form']['type_person']) && $this->data['form']['type_person'] === 'Jurídica') ? 'selected' : ''; ?>>Jurídica</option>
                    </select>
                </div>
                
                <!-- Campo Documento -->
                <div class="col-4">
                    <label for="doc" class="form-label">Documento</label>
                    <input type="text" name="doc" class="form-control" id="doc" placeholder="Digite o CPF ou CNPJ" value="<?php echo $this->data['form']['doc'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="phone" class="form-label">Telefone</label>
                    <input type="tel" name="phone" class="form-control" id="phone"
                        placeholder="(XX) XXXXX-XXXX"
                        value="<?php echo $this->data['form']['phone'] ?? ''; ?>"
                        maxlength="15"
                        inputmode="numeric">
                </div>

                <div class="col-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Email." value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="address" class="form-label">Endereço</label>
                    <input type="text" name="address" class="form-control" id="address" placeholder="Endereço." value="<?php echo $this->data['form']['address'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="description" class="form-label">Descrição/Observações</label>
                    <input type="text" name="description" class="form-control" id="description" placeholder="Descrição." value="<?php echo $this->data['form']['description'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="date_birth" class="form-label">Data de Nascimento</label>
                    <input type="date" name="date_birth" class="form-control" id="date_birth"
                        value="<?php echo isset($this->data['form']['date_birth']) ? date('Y-m-d', strtotime($this->data['form']['date_birth'])) : ''; ?>">
                </div>

                <?php
                $userActive = $this->data['form']['active'] ?? '';
                $checked = ($userActive === "1") ? 'checked' : '';
                ?>

                <div class="col-4">
                    <label for="active" class="form-label">Ativo</label>
                    <div class="form-check form-switch">
                        <!-- Campo oculto para garantir o envio do valor "0" se o checkbox não for marcado -->
                        <input type="hidden" name="active" value="0">

                        <!-- Checkbox para enviar "1" se estiver marcado -->
                        <input type="checkbox" name="active" class="form-check-input" id="active" value="1" <?php echo $checked; ?> onchange="toggleActive(this)">

                        <label class="form-check-label" for="active">
                            <?php echo ($userActive == 1) ? 'Sim' : 'Não'; ?>
                        </label>
                    </div>
                </div>

                <script>
                    function toggleActive(element) {
                        element.nextElementSibling.innerText = element.checked ? 'Sim' : 'Não';
                    }
                </script>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>

            </form>


        </div>

    </div>

</div>
<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Cliente</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-customers" class="text-decoration-none">Clientes</a>
            </li>
            <li class="breadcrumb-item">Editar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">

            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListCustomers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-customers' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['form']['id'] ?? '');
                if (in_array('ViewCustomer', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-customers/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_customer'); ?>">

                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id'] ?? ''; ?>">

                <div class="col-4">
                    <label for="card_code" class="form-label">Codigo</label>
                    <input type="text" name="card_code" class="form-control" id="card_code" placeholder="Código Cliente." value="<?php echo $this->data['form']['card_code'] ?? ''; ?>"readonly>
                </div>

                <div class="col-4">
                    <label for="card_name" class="form-label">Nome</label>
                    <input type="text" name="card_name" class="form-control" id="card_name" placeholder="Nome Cliente." value="<?php echo $this->data['form']['card_name'] ?? ''; ?>">
                </div>

                <!-- <div class="col-4">
                    <label for="type_person" class="form-label">Pessoa</label>
                    <input type="text" name="type_person" class="form-control" id="type_person" placeholder="Pessoa Física ou Jurídica." value="<?php echo $this->data['form']['type_person'] ?? ''; ?>">
                </div> -->

                <div class="col-4">
                    <label for="type_person" class="form-label">Pessoa</label>
                    <select name="type_person" class="form-control" onchange="alterarMascaraDoc()" id="type_person">
                        <option value="Física" <?php echo (isset($this->data['form']['type_person']) && $this->data['form']['type_person'] === 'Física') ? 'selected' : ''; ?>>Física</option>
                        <option value="Jurídica" <?php echo (isset($this->data['form']['type_person']) && $this->data['form']['type_person'] === 'Jurídica') ? 'selected' : ''; ?>>Jurídica</option>
                    </select>
                </div>
                

                <div class="col-4">
                    <label for="doc" class="form-label">Documento</label>
                    <input type="text" name="doc" class="form-control" id="doc" placeholder="Documento." value="<?php echo $this->data['form']['doc'] ?? ''; ?>">
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
                // Verifica o valor da chave 'active' vindo do banco de dados e converte para inteiro
                $userActive = isset($this->data['form']['active']) ? (int) $this->data['form']['active'] : 0;
                $checked = ($userActive == 1) ? 'checked' : ''; // Agora a comparação funciona corretamente
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
                    // Função para alternar o texto entre 'Sim' e 'Não' quando o checkbox for clicado
                    function toggleActive(element) {
                        element.nextElementSibling.innerText = element.checked ? 'Sim' : 'Não';
                    }
                </script>



                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>
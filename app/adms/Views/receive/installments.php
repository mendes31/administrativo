<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Conta</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-receipts" class="text-decoration-none">Contas</a>
            </li>
            <li class="breadcrumb-item">Editar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">

            <span>Parcelar</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListReceipts', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-receipts' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                echo "<button onclick='history.back()' class='btn btn-secondary btn-sm me-1 mb-1'><i class='fa-solid fa-arrow-left'></i> Voltar</button> ";

                $id = ($this->data['form']['id'] ?? '');
                    if (in_array('ViewReceive', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-receive/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar uma nova Conta à Pagar -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_installments_receive'); ?>">

                <input type="hidden" name="id_receive" id="id_receive" value="<?php echo $this->data['form']['id_receive'] ?? ''; ?>">
                
                <div class="col-4">
                    <label for="num_doc" class="form-label">Nº Documento</label>
                    <input type="text" name="num_doc" class="form-control" id="num_doc" placeholder="Nº Documento" value="<?php echo $this->data['form']['num_doc'] ?? ''; ?>" readonly>
                </div>

                <div class="col-md-8">
                    <label for="partner_id" class="form-label">Cliente</label>
                    <!-- <input type="text" class="form-control" id="partner_id" value="<?php echo $this->data['form']['card_name'] ?? ''; ?>" readonly> -->

                    <select name="partner_id" class="form-select" id="partner_id">
                        <?php

                        // Verifica se existe uma lista de clientes
                        if (!empty($this->data['listCustomers'])) {
                            foreach ($this->data['listCustomers'] as $listCustomer) {
                                extract($listCustomer);

                                // Verifica se já há um cliente salvo no banco e seleciona a opção correspondente
                                $selected = (!empty($this->data['form']['card_name']) && $this->data['form']['card_name'] == $card_name) ? 'selected' : '';

                                echo "<option value='$id' $selected>$card_name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-4">
                    <label for="value" class="form-label">Valor</label>
                    <input type="text" name="value" class="form-control" id="value" placeholder="Valor"
                        value="<?php echo $this->data['form']['original_value'] ?? ''; ?>" readonly>
                </div>

                <div class="col-4">
                    <label for="installments" class="form-label">Parcelas</label>
                    <input type="text" name="installments" class="form-control" id="installments" placeholder="Nº Parcelas"
                        value="">
                </div>


                <div class="col-md-4">
                    <label for="frequency_id" class="form-label">Frequência</label>
                    <select name="frequency_id" class="form-select" id="frequency_id">
                        <!-- <option value="" selected>Selecione uma Frequência</option> -->
                        <?php
                        // Verificar se existe frequencias
                        if ($this->data['listFrequencies'] ?? false) {
                            // percorrer o array de frequencias
                            foreach ($this->data['listFrequencies'] as $listFrequency) {
                                // Extrari as variáveis do array
                                extract($listFrequency);
                                // // Verificar se deve manter selecionado a opção

                                $selected = (!empty($this->data['form']['name_freq']) && $this->data['form']['name_freq'] == $name) ? 'selected' : '';
                                // $selected = isset($this->data['form']['name_freq']) && $this->data['form']['name_freq'] == $name ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
               
                <div class="col-md-3">
                    <button type="submit" class="btn btn-warning btn-sm">Parcelar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<!-- <script>
    window.addEventListener('beforeunload', function () {
        const id = document.getElementById('id_pay')?.value;
        if (id) {
            navigator.sendBeacon("<?php echo $_ENV['URL_ADM']; ?>clear-busy-pay/" + id);
        }
    });
</script> -->


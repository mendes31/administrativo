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
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-payments" class="text-decoration-none">Contas</a>
            </li>
            <li class="breadcrumb-item">Pagar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">

            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                $filtros = $_GET;
                if (empty($filtros) && isset($_SESSION['filtros_list_payments'])) {
                    $filtros = $_SESSION['filtros_list_payments'];
                }
                $urlList = $_ENV['URL_ADM'] . 'list-payments';
                if (!empty($filtros)) {
                    $urlList .= '?' . http_build_query($filtros);
                }
                echo "<a href='{$_ENV['URL_ADM']}list-payments' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                echo "<button onclick='history.back()' class='btn btn-secondary btn-sm me-1 mb-1'><i class='fa-solid fa-arrow-left'></i> Voltar</button> ";

                $id = ($this->data['form']['id_pay'] ?? $this->data['form']['id'] ?? '');
                if (in_array('ViewPay', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-pay/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const campos = ['value', 'discount_value', 'fine_value', 'interest'];

                    campos.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.addEventListener('input', function() {
                                this.value = this.value.replace(',', '.');
                            });
                        }
                    });
                });
            </script>

            <!-- Formulário para cadastrar uma nova Conta à Pagar -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_payment'); ?>">

                <input type="hidden" name="id_pay" id="id_pay" value="<?php echo $this->data['form']['id_pay'] ?? ''; ?>">

                <div class="col-4">
                    <label for="num_doc" class="form-label">Nº Documento</label>
                    <input type="text" name="num_doc" class="form-control" id="num_doc" placeholder="Nº Documento" value="<?php echo $this->data['form']['num_doc'] ?? ''; ?>" readonly>
                </div>

                <div class="col-4">
                    <label for="description" class="form-label">Descrição - Observações</label>
                    <input type="text" name="description" class="form-control" id="description" placeholder="Observações" value="<?php echo $this->data['form']['description'] ?? ''; ?>" readonly>
                </div>

                <div class="col-4">
                    <label for="value" class="form-label">Valor</label>
                    <input type="text" name="value" class="form-control" id="value" placeholder="Valor"
                        value="<?php echo $this->data['form']['value'] ?? ''; ?>">
                </div>



                <div class="col-md-4">
                    <label for="pay_method_id" class="form-label">Forma de Pagamento</label>
                    <select name="pay_method_id" class="form-select" id="pay_method_id">
                        <option value="" selected>Selecione uma Forma de Pagamento</option>
                        <?php
                        if ($this->data['listPaymentMethods'] ?? false) {
                            foreach ($this->data['listPaymentMethods'] as $listPaymentMethod) {
                                extract($listPaymentMethod);
                                $selected = isset($this->data['form']['pay_method_id']) && $this->data['form']['pay_method_id'] == $id ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="bank_id" class="form-label">Banco Saída</label>
                    <select name="bank_id" class="form-select" id="bank_id">
                        <option value="" selected>Selecione a Origem da Saída</option>
                        <?php
                        if ($this->data['listBanks'] ?? false) {
                            foreach ($this->data['listBanks'] as $listBank) {
                                extract($listBank);
                                $selected = isset($this->data['form']['bank_id']) && $this->data['form']['bank_id'] == $id ? 'selected' : '';
                                echo "<option value='$id' $selected >$bank_name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="fine_value" class="form-label">Multa em R$</label>
                    <input type="text" name="fine_value" class="form-control" id="fine_value" placeholder="Multa em R$" value="<?php echo $this->data['form']['fine_value'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="interest_value" class="form-label">Juros em R$</label>
                    <input type="text" name="interest_value" class="form-control" id="interest_value" placeholder="Juros em R$" value="<?php echo $this->data['form']['interest_value'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="discount_value" class="form-label">Desconto em R$</label>
                    <input type="text" name="discount_value" class="form-control" id="discount_value" placeholder="Desconto em R$" value="<?php echo $this->data['form']['discount_value'] ?? ''; ?>">
                </div>

                <div class="col-4">
                    <label for="subtotal" class="form-label">Subtotal</label>
                    <input type="text" name="subtotal" class="form-control" id="subtotal" placeholder="Subtotal"
                        value="<?php echo $this->data['form']['subtotal'] ?? ''; ?>" readonly>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm">Pagar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function calcularSubtotal() {
            let value = parseFloat(document.getElementById("value").value.replace(',', '.')) || 0;
            let fine_value = parseFloat(document.getElementById("fine_value").value.replace(',', '.')) || 0;
            let interest_value = parseFloat(document.getElementById("interest_value").value.replace(',', '.')) || 0;
            let discount_value = parseFloat(document.getElementById("discount_value").value.replace(',', '.')) || 0;
            let subtotal = value + fine_value + interest_value - discount_value;
            document.getElementById("subtotal").value = subtotal.toFixed(2);
        }
        ["value", "fine_value", "interest_value", "discount_value"].forEach(function(id) {
            let el = document.getElementById(id);
            if (el) el.addEventListener("input", calcularSubtotal);
        });
        calcularSubtotal();
    });
</script>

<script>
    //     window.addEventListener('beforeunload', function () {
    //         const id = document.getElementById('id')?.value;
    //         if (id) {
    //             navigator.sendBeacon("<?php echo $_ENV['URL_ADM']; ?>clear-busy-pay/" + id);
    //         }
    //     });
    // 
</script>

<!-- <script>
    function sendClearBusy() {
        const id = document.getElementById('id_pay')?.value;
        if (id) {
            navigator.sendBeacon("<?php echo $_ENV['URL_ADM']; ?>clear-busy-pay/" + id);
        }
    }

    window.addEventListener('pagehide', sendClearBusy);
</script> -->
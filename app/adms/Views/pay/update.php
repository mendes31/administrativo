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
            <li class="breadcrumb-item">Editar</li>

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

                $id = ($this->data['form']['id_pay'] ?? '');
                if (in_array('ViewPay', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-pay/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar uma nova Conta à Pagar -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_pay'); ?>">

                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id_pay'] ?? ''; ?>">

                <input type="hidden" name="partner_id" id="partner_id" value="<?php echo $this->data['form']['partner_id'] ?? ''; ?>">

                <div class="col-2">
                    <label for="num_doc" class="form-label">Nº Documento</label>
                    <?php
                    $readonly = '';
                    $disabled = '';
                    $readonly_doc = 'readonly';
                    $amount_paid = isset($this->data['form']['amount_paid']) ? (float)$this->data['form']['amount_paid'] : 0;
                    $disabled_fornecedor = '';
                    if ($amount_paid > 0) {
                        $readonly = 'readonly';
                        $disabled = 'disabled';
                        $disabled_fornecedor = 'disabled';
                    }
                    ?>
                    <input type="text" name="num_doc" class="form-control" id="num_doc" placeholder="Nº Documento" value="<?php echo $this->data['form']['num_doc'] ?? ''; ?>" <?php echo $readonly_doc; ?>>
                </div>

                <div class="col-2">
                    <label for="num_nota" class="form-label">Nº Nota</label>
                    <input type="text" name="num_nota" class="form-control" id="num_nota" placeholder="Nº Nota" value="<?php echo $this->data['form']['num_nota'] ?? ''; ?>">
                </div>

                <div class="col-2">
                    <label for="issue_date" class="form-label">Data Emissão</label>
                    <input type="date" name="issue_date" class="form-control" id="issue_date" value="<?php echo !empty($this->data['form']['issue_date']) ? date('Y-m-d', strtotime($this->data['form']['issue_date'])) : ''; ?>">
                </div>

                <div class="col-md-3">
                    <label for="partner_id" class="form-label">Fornecedor</label>
                    <select name="partner_id" class="form-select" id="partner_id" <?php echo $disabled_fornecedor; ?>>
                        <?php

                        // Verifica se existe uma lista de clientes
                        if (!empty($this->data['listSuppliers'])) {
                            foreach ($this->data['listSuppliers'] as $listSupplier) {
                                extract($listSupplier);

                                // Verifica se já há um cliente salvo no banco e seleciona a opção correspondente
                                $selected = (!empty($this->data['form']['card_name']) && $this->data['form']['card_name'] == $card_name) ? 'selected' : '';

                                echo "<option value='$id' $selected>$card_name</option>";   
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-3">
                    <label for="value" class="form-label">Valor</label>
                    <input type="text" name="value" class="form-control" id="value" placeholder="Valor"
                        value="<?php echo $this->data['form']['value'] ?? ''; ?>" <?php echo $readonly; ?>>
                </div>


                <div class="col-2">
                    <label for="due_date" class="form-label">Vencimento</label>
                    <input type="date" name="due_date" class="form-control" id="due_date"
                        value="<?php echo !empty($this->data['form']['due_date']) ? date('Y-m-d', strtotime($this->data['form']['due_date'])) : ''; ?>" <?php echo $readonly; ?>>
                </div>

                <div class="col-2">
                    <label for="expected_date" class="form-label">Previsão Pagamento</label>
                    <input type="date" name="expected_date" class="form-control" id="expected_date"
                        value="<?php echo !empty($this->data['form']['expected_date']) ? date('Y-m-d', strtotime($this->data['form']['expected_date'])) : ''; ?>" readonly>
                </div>


                <div class="col-md-2">
                    <label for="frequency_id" class="form-label">Frequência</label>
                    <select name="frequency_id" class="form-select" id="frequency_id" <?php echo $disabled; ?>>
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
                    <label for="account_id" class="form-label">Plano de Contas</label>
                    <select name="account_id" class="form-select" id="account_id">
                        <option value="" selected>Selecione o Plano de Contas</option>
                        <?php
                        // Verificar se existe plano de contas
                        if ($this->data['listAccountsPlan'] ?? false) {
                            // percorrer o array de plano de contas
                            foreach ($this->data['listAccountsPlan'] as $listAccountPlan) {
                                // Extrari as variáveis do array
                                extract($listAccountPlan);
                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['name_aap']) && $this->data['form']['name_aap'] == $name ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="cost_center_id" class="form-label">Centro de Custo</label>
                    <select name="cost_center_id" class="form-select" id="cost_center_id">
                        <option value="" selected>Selecione o Centro de Custo</option>
                        <?php
                        // Verificar se existe centros de custo
                        if ($this->data['listCostCenters'] ?? false) {
                            // percorrer o array de centros de custo
                            foreach ($this->data['listCostCenters'] as $listCostCenter) {
                                // Extrari as variáveis do array
                                extract($listCostCenter);
                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['name_cc']) && $this->data['form']['name_cc'] == $name ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição - Observações</label>
                    <input type="text" name="description" class="form-control" id="description" placeholder="Observações" value="<?php echo $this->data['form']['description'] ?? ''; ?>">
                </div>


                <!-- <div class="col-md-4">
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="file" onChange="carregarImg();" id="arquivo">
                    </div>
                </div>
                <div class="col-md-2">
                    <div id="divImg">
                        <img src="<?php echo $_ENV['URL_ADM'] ?>public/adms/image/contas/sem-foto.png" width="100px" id="target">
                    </div>
                </div> -->



                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
    // Lista de feriados fixos (você pode expandir com feriados móveis ou buscar de API)
    const feriadosFixos = [
        "01-01", // Confraternização
        "03-22", // Aniversário Cidade
        "04-21", // Tiradentes
        "05-01", // Dia do Trabalho
        "09-07", // Independência
        "10-12", // Nossa Senhora
        "11-02", // Finados
        "11-15", // Proclamação
        "12-25" // Natal
    ];

    // Função para verificar se é fim de semana
    function isWeekend(date) {
        const day = date.getDay();
        return day === 0 || day === 6; // domingo ou sábado
    }

    // Função para verificar se é feriado
    function isHoliday(date) {
        const mesDia = ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + date.getDate()).slice(-2);
        return feriadosFixos.includes(mesDia);
    }

    // Função para obter o próximo dia útil
    function getNextBusinessDay(date) {
        let nextDate = new Date(date);
        do {
            nextDate.setDate(nextDate.getDate() + 1);
        } while (isWeekend(nextDate) || isHoliday(nextDate));
        return nextDate;
    }

    // Listener para o campo due_date
    document.getElementById("due_date").addEventListener("change", function() {
        const dueDateValue = this.value;
        if (!dueDateValue) return;

        let [year, month, day] = dueDateValue.split("-");
        let date = new Date(year, month - 1, day); // Isso força data local correta

        if (isWeekend(date) || isHoliday(date)) {
            date = getNextBusinessDay(date);
        }

        const yyyy = date.getFullYear();
        const mm = ("0" + (date.getMonth() + 1)).slice(-2);
        const dd = ("0" + date.getDate()).slice(-2);

        document.getElementById("expected_date").value = `${yyyy}-${mm}-${dd}`;
    });
</script>

<!-- <script>
    window.addEventListener('beforeunload', function () {
        const id = document.getElementById('id')?.value;
        if (id) {
            navigator.sendBeacon("<?php echo $_ENV['URL_ADM']; ?>clear-busy-pay/" + id);
        }
    });
</script> -->
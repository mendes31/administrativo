<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PaymentsRepository;

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pagamentos - Conta</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Pagamentos - Conta</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('ListPayments', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-payments' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                // Calcula a soma dos valores pagos
                $totalPago = 0;
                if (!empty($this->data['partialValues'])) {
                    foreach ($this->data['partialValues'] as $partialValue) {
                        $totalPago += $partialValue['partial_value'];
                    }
                }

                // Exibe a soma formatada
                echo "<span class='badge bg-primary ms-3'>Total Pago: R$ " . number_format($totalPago, 2, ',', '.') . "</span>";
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há pagamentos no array
            if ($this->data['partialValues'] ?? false) {
            ?>

                <table class="table table-striped table-hover" id="tabela">
                    <thead>
                        <tr>
                            <th scope="col" class="d-none d-md-table-cell">Id</th>
                            <th scope="col" class="d-none d-md-table-cell">Data</th>
                            <th scope="col">Nº Doc</th>
                            <th scope="col">Valor Pago</th>
                            <th scope="col">Tipo</th>
                            <th scope="col" class="d-none d-md-table-cell">Usuário</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        // Percorre o array de pagamentos
                        foreach ($this->data['partialValues'] as $partialValue) {
                            extract($partialValue); ?>

                            <tr>
                                <td class="d-none d-md-table-cell"><?php echo $id; ?></td>
                                <td class="d-none d-md-table-cell"><?php echo date("d-m-Y", strtotime($created_at)); ?></td>
                                <td><?php echo $account_id; ?></td>
                                <td><?php echo 'R$ ' . number_format($partial_value, 2, ',', '.'); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo $type; ?></td>
                                <td class="d-none d-md-table-cell"><?php echo $user_id; ?></td>
                            </tr>

                        <?php } ?>

                    </tbody>
                </table>

            <?php
                // Inclui o arquivo de paginação
                include_once './app/adms/Views/partials/pagination.php';
            } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhuma Conta encontrada!</div>";
            } ?>

        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#tabela').DataTable({
            "language": {
                "decimal": ",",
                "thousands": ".",
                "sProcessing": "Processando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "Nenhum registro encontrado",
                "sEmptyTable": "Nenhum dado disponível na tabela",
                "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
                "sInfoFiltered": "(filtrado de _MAX_ registros no total)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primeiro",
                    "sPrevious": "Anterior",
                    "sNext": "Próximo",
                    "sLast": "Último"
                },
                "oAria": {
                    "sSortAscending": ": Ordenar colunas de forma ascendente",
                    "sSortDescending": ": Ordenar colunas de forma descendente"
                }
            },
            "columnDefs": [{
                "className": "text-start",
                "targets": "_all"
            }]
        });
    });
</script>

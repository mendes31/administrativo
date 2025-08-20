<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_receive');

?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Contas à Receber
            <?php if (isset($this->data['total_to_receive'])): ?>
                <span class="badge bg-success ms-3" style="font-size: 1rem; vertical-align: middle;">Total a Receber: R$ <?php echo number_format($this->data['total_to_receive'], 2, ',', '.'); ?></span>
            <?php endif; ?>
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Contas à Receber</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <form method="POST" action="<?php echo $_ENV['URL_ADM'] . 'process-file-receipts'; ?>" enctype="multipart/form-data">
                    <label>Arquivo: </label>
                    <input type="file" name="arquivo" id="arquivo">
                    <input type="submit" value="Enviar">
                </form><br>
                <?php
                if (in_array('CreateReceive', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-receive' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Formulário de filtros SEMPRE visível
            ?>
            <form method="GET" class="row mb-3">
                <div class="col-md-2">
                    <label for="filtroNumDoc">Nº Documento</label>
                    <input type="text" name="num_doc" id="filtroNumDoc" class="form-control" value="<?php echo $_GET['num_doc'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="filtroNumNota">Nº Nota</label>
                    <input type="text" name="num_nota" id="filtroNumNota" class="form-control" value="<?php echo $_GET['num_nota'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="filtroCodCliente">Cód. Cliente</label>
                    <input type="text" name="card_code_cliente" id="filtroCodCliente    " class="form-control" value="<?php echo $_GET['card_code_cliente'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="filtroCliente">Cliente</label>
                    <input type="text" name="cliente" id="filtroCliente" class="form-control" value="<?php echo $_GET['cliente'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_type">Tipo de Data</label>
                    <select name="data_type" id="data_type" class="form-control">
                        <option value="due_date" <?php echo (($_GET['data_type'] ?? '') === 'due_date') ? 'selected' : ''; ?>>Data Vencimento</option>
                        <option value="issue_date" <?php echo (($_GET['data_type'] ?? '') === 'issue_date') ? 'selected' : ''; ?>>Data Emissão</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="data_inicial">Data Inicial</label>
                    <input type="date" name="data_inicial" id="data_inicial" class="form-control" value="<?php echo $_GET['data_inicial'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="data_final">Data Final</label>
                    <input type="date" name="data_final" id="data_final" class="form-control" value="<?php echo $_GET['data_final'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="" <?php echo empty($_GET['status']) ? 'selected' : ''; ?>>Todos</option>
                        <option value="pendente" <?php echo (($_GET['status'] ?? '') === 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="pago" <?php echo (($_GET['status'] ?? '') === 'pago') ? 'selected' : ''; ?>>Pagos</option>
                        <option value="vencidos" <?php echo (($_GET['status'] ?? '') === 'vencidos') ? 'selected' : ''; ?>>Vencidos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label for="per_page" class="form-label mb-1 me-2">Exibir</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="ms-2">por página</span>
                </div>
                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
                    <a href="?limpar_filtros=1" class="btn btn-secondary mt-4 ms-2">Limpar Filtros</a>
                </div>
            </form>

            <?php
            // Exibe tabela se houver registros
            if ($this->data['receipts'] ?? false) {
            ?>

                <!-- Tabela desktop -->
                <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Nº Pedido</th>
                            <th scope="col">Parcela</th>
                            <th scope="col" class="text-nowrap" style="min-width:110px;">Emissão</th>
                            <th scope="col">Nº Nota</th>
                            <th scope="col">Cód. Cliente</th>
                            <th scope="col">Cliente</th>
                            <th scope="col" class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">Valor</th>
                            <th scope="col" class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">Recebido</th>
                            <th scope="col" class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">Receber</th>
                            <th scope="col" class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">Vencimento</th>
                            <th scope="col" class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">Previsão</th>
                            <th scope="col" class="d-none d-md-table-cell">Status </th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($this->data['receipts'] as $receive) {
                            extract($receive);
                            $totalRecebido = 0;
                            $totalDesconto = 0;
                            if (!empty($movements)) {
                                foreach ($movements as $mov) {
                                    $totalRecebido += $mov['movement_value'];
                                    $totalDesconto += $mov['discount_value'] ?? 0;
                                }
                            }
                            if ($totalDesconto > 0) {
                                $saldoReceber = $original_value - ($totalRecebido + $totalDesconto);
                            } else {
                                $saldoReceber = $original_value - $totalRecebido;
                            }
                            if ($saldoReceber < 0) {
                                $saldoReceber = 0;
                            }
                            $classe_recebido = ($paid == 1) ? 'text-success' : 'text-danger';
                            $ocultar = ($paid == 1) ? 'ocultar' : '';
                        ?>
                        <tr id="linha-<?php echo $id_receive; ?>" data-busy="<?php echo $busy; ?>">
                            <td><i class="fa fa-square <?php echo $classe_recebido; ?> mr-1"></i>&nbsp;<?php echo $num_doc; ?></td>
                            <td><?php
                                if (!empty($installment_number) && !empty($total_installments)) {
                                    echo $installment_number . '/' . $total_installments;
                                } else {
                                    echo $installment_number ?? '';
                                }
                                ?></td>
                            <td class="text-nowrap" style="min-width:110px;"><?php echo !empty($issue_date) ? date("d-m-Y", strtotime($issue_date)) : ''; ?></td>
                            <td><?php echo $num_nota ?? ''; ?></td>
                            <td><?php echo $card_code_cliente ?? ''; ?></td>
                            <td><?php echo $card_name; ?></td>
                            <td class="d-none d-md-table-cell text-nowrap" style="min-width:110px;">
                                <?php echo 'R$ ' . number_format($original_value, 2, ',', '.'); ?>
                            </td>
                            <td class="d-none d-md-table-cell text-success text-nowrap" style="min-width:110px;"><?php echo 'R$ ' . number_format($totalRecebido, 2, ',', '.'); ?></td>
                            <td class="d-none d-md-table-cell text-danger text-nowrap" style="min-width:110px;"><?php echo 'R$ ' . number_format($saldoReceber, 2, ',', '.'); ?></td>
                            <td class="d-none d-md-table-cell text-nowrap" style="min-width:110px;"><?php echo date("d-m-Y", strtotime($due_date)); ?></td>
                            <td class="d-none d-md-table-cell text-nowrap" style="min-width:110px;"><?php echo !empty($expected_date) ? date("d-m-Y", strtotime($expected_date)) : 'N/A'; ?></td>
                            <td class="d-none d-md-table-cell text" data-status-receive>
                                <?php if ($busy == 1): ?>
                                    <span class="text-danger" title="Registro ocupado por:<?= $name_user_temp ?? 'usuário desconhecido' ?>">
                                        <i class="fa-solid fa-lock"></i> Ocupado
                                    </span>
                                <?php else: ?>
                                    <span class="text-success" title="Registro livre">
                                        <i class="fa-solid fa-unlock"></i> Livre
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="tabela-acoes">
                                    <?php
                                    $base = $_ENV['URL_ADM'];
                                    if (in_array('ViewReceive', $this->data['buttonPermission'])) {
                                        echo "<a href='{$base}view-receive/$id_receive' class='btn btn-primary btn-sm me-1 mb-1 acao' data-id='$id_receive' data-busy='$busy' data-user-temp='$name_user_temp' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-custom-class='tooltip-visualizar' title='Visualizar'><i class='fa-regular fa-eye'></i></a>";
                                    }
                                    if ($paid != 1) {
                                        if (in_array('UpdateReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) {
                                            echo "<a href='{$base}update-receive/$id_receive' class='btn btn-warning btn-sm me-1 mb-1 acao' data-id='$id_receive' data-busy='$busy' data-user-temp='$name_user_temp' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-custom-class='tooltip-editar' title='Editar'><i class='fa-solid fa-pen-to-square'></i></a>";
                                        }
                                        if (in_array('InstallmentsReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) {
                                            echo "<a href='{$base}installments-receive/$id_receive' class='btn btn-sm me-1 mb-1 btn-parcelar acao $ocultar' data-id='$id_receive' data-busy='$busy' data-user-temp='$name_user_temp' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-custom-class='tooltip-parcelar' title='Parcelar'><i class='fa-solid fa-coins'></i></a>";
                                        }
                                        if (in_array('Receive', $this->data['buttonPermission'])) {
                                            echo "<a href='{$base}receive/$id_receive' class='btn btn-success btn-sm me-1 mb-1 acao $ocultar' data-id='$id_receive' data-busy='$busy' data-user-temp='$name_user_temp' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-custom-class='tooltip-receber' title='Receber'><i class='fa-solid fa-money-bill-wave'></i></a>";
                                        }
                                        if (in_array('DeleteReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) { ?>
                                            <form id="formDelete<?= $id_receive ?>" action="<?= $base ?>delete-receive" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id_receive ?? '' ?>">
                                                <input type="hidden" name="num_doc" value="<?= $num_doc ?? '' ?>">
                                                <input type="hidden" name="partner_id" value="<?= $partner_id ?? '' ?>">
                                                <button type="submit"
                                                    class="btn btn-danger btn-sm me-1 mb-1 btn-verificar-busy <?= $ocultar ?>"
                                                    onclick="confirmDeletion(event, <?= $id_receive ?>)"
                                                    data-busy="<?= $busy ?>"
                                                    data-user-temp="<?= $name_user_temp ?>"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-custom-class="tooltip-deletar"
                                                    title="Excluir">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                    <?php }
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>

                <!-- Cards mobile -->
                <div class="d-md-none">
                    <?php foreach ($this->data['receipts'] as $receive) {
                        extract($receive);
                        $totalRecebido = 0;
                        $totalDesconto = 0;
                        if (!empty($movements)) {
                            foreach ($movements as $mov) {
                                $totalRecebido += $mov['movement_value'];
                                $totalDesconto += $mov['discount_value'] ?? 0;
                            }
                        }
                        if ($totalDesconto > 0) {
                            $saldoReceber = $original_value - ($totalRecebido + $totalDesconto);
                        } else {
                            $saldoReceber = $original_value - $totalRecebido;
                        }
                        if ($saldoReceber < 0) {
                            $saldoReceber = 0;
                        }
                        $classe_recebido = ($paid == 1) ? 'text-success' : 'text-danger';
                        $ocultar = ($paid == 1) ? 'ocultar' : '';
                    ?>
                    <div class="card mb-2 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Nº Pedido: <?= $num_doc ?></strong>
                                <span class="text-muted small">Venc: <?= date('d-m-Y', strtotime($due_date)) ?></span>
                            </div>
                            <div><b>Parcela:</b> <?= !empty($installment_number) && !empty($total_installments) ? ($installment_number . '/' . $total_installments) : ($installment_number ?? '') ?></div>
                            <div><b>Emissão:</b> <?= !empty($issue_date) ? date('d-m-Y', strtotime($issue_date)) : '' ?></div>
                            <div><b>Nº Nota:</b> <?= $num_nota ?? '' ?></div>
                            <div><b>Cód. Cliente:</b> <?= $card_code_cliente ?? '' ?></div>
                            <div><b>Cliente:</b> <?= $card_name ?></div>
                            <div><b>Valor:</b> R$ <?= number_format($original_value, 2, ',', '.') ?></div>
                            <div><b>Recebido:</b> R$ <?= number_format($totalRecebido, 2, ',', '.') ?></div>
                            <div><b>Receber:</b> R$ <?= number_format($saldoReceber, 2, ',', '.') ?></div>
                            <div><b>Status:</b> <span class="<?= $classe_recebido ?>"><?= $paid == 1 ? 'Recebido' : 'Pendente' ?></span></div>
                            <div class="mt-2">
                                <?php $base = $_ENV['URL_ADM'];
                                if (in_array('ViewReceive', $this->data['buttonPermission'])) {
                                    echo "<a href='{$base}view-receive/$id_receive' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                }
                                if ($paid != 1) {
                                    if (in_array('UpdateReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) {
                                        echo "<a href='{$base}update-receive/$id_receive' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }
                                    if (in_array('InstallmentsReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) {
                                        echo "<a href='{$base}installments-receive/$id_receive' class='btn btn-sm me-1 mb-1 btn-parcelar $ocultar'><i class='fa-solid fa-coins'></i> Parcelar</a>";
                                    }
                                    if (in_array('Receive', $this->data['buttonPermission'])) {
                                        echo "<a href='{$base}receive/$id_receive' class='btn btn-success btn-sm me-1 mb-1 $ocultar'><i class='fa-solid fa-money-bill-wave'></i> Receber</a>";
                                    }
                                    if (in_array('DeleteReceive', $this->data['buttonPermission']) && !($totalRecebido > 0 && $paid != 1)) { ?>
                                        <form id="formDelete<?= $id_receive ?>" action="<?= $base ?>delete-receive" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="id" value="<?= $id_receive ?? '' ?>">
                                            <input type="hidden" name="num_doc" value="<?= $num_doc ?? '' ?>">
                                            <input type="hidden" name="partner_id" value="<?= $partner_id ?? '' ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1 btn-verificar-busy $ocultar" onclick="confirmDeletion(event, <?= $id_receive ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                        </form>
                                <?php }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <div class="text-end text-secondary small mb-2">
                        Exibindo <?php echo count($this->data['receipts']); ?> registro(s) nesta página.
                </div>

            <?php
                // Inclui o arquivo de paginação
                include_once './app/adms/Views/partials/pagination.php';
            } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhuma Conta à Receber encontrada!</div>";
            } ?>

        </div>

    </div>
</div>

<!-- Plugin para ordenação dd-mm-yyyy -->

<script>
    $(document).ready(function() {
        if (jQuery.fn.dataTable) {
            jQuery.extend(jQuery.fn.dataTable.ext.type.order, {
                "date-eu-pre": function(date) {
                    if (!date) return 0;
                    const eu_date = date.split('-');
                    return new Date(`${eu_date[2]}-${eu_date[1]}-${eu_date[0]}`).getTime();
                },
                "date-eu-asc": function(a, b) {
                    return a - b;
                },
                "date-eu-desc": function(a, b) {
                    return b - a;
                }
            });
        } else {
            console.error("DataTables não está carregado no momento da extensão.");
        }
    });
</script>

<!-- Inicialização da DataTable: Depois da extensão, você inicializa a tabela: -->
<script type="text/javascript">
    $(document).ready(function() {
        console.log('$.fn.dataTable:', $.fn.dataTable);
        console.log('$.fn.dataTable.ext:', $.fn.dataTable?.ext);
        // Filtro por data dinâmica (emissão ou vencimento) usando os campos do formulário principal
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const min = $('#data_inicial').val();
            const max = $('#data_final').val();
            const tipoData = $('#data_type').val();
            // Índices das colunas: Emissão = 2, Vencimento = 9
            let dateColIndex = tipoData === 'issue_date' ? 2 : 9;
            const dateStr = data[dateColIndex];
            if (!dateStr) return false;
            const parts = dateStr.split('-'); // dd-mm-yyyy
            if (parts.length !== 3) return false;
            const parsedDate = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
            if ((min === "" || new Date(min) <= parsedDate) &&
                (max === "" || new Date(max) >= parsedDate)) {
                return true;
            }
            return false;
        });

        // Filtro por status (pago / pendente)
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const status = $('#filtroStatus').val();
            const valor = data[4] || ''; // Coluna "Pagar"

            // Limpeza do valor: remove espaços, NBSP, pontos e vírgula
            const valorLimpo = valor.replace(/\s|&nbsp;/g, '').replace(/\./g, '').replace(',', '.');

            const valorNumerico = parseFloat(valorLimpo.replace(/[^\d.-]/g, '')) || 0;

            if (status === "pendente") {
                return valorNumerico > 0;
            } else if (status === "pago") {
                return valorNumerico === 0;
            }

            return true;
        });

        const table = $('#tabela').DataTable({
            "scrollX": true, // Ativa rolagem horizontal
            "autoWidth": false, // Impede que as colunas fiquem largas demais
            "responsive": true, // Torna a tabela responsiva
            "paging": true, // Mantém a paginação ativada
            "lengthChange": false, // Oculta opção de alterar quantidade de registros
            "info": false, // Remove a informação "Mostrando X de Y"
            "columnDefs": [{
                    "width": "100px",
                    "targets": "_all"
                }, // Reduz a largura mínima das colunas
                {
                    type: 'date-eu',
                    targets: 5
                }
            ],
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
            order: [
                [5, 'desc']
            ], // Ordenar por Vencimento (coluna 5), decrescente
        });

        // Redesenha ao mudar filtros
        $('#data_inicial, #data_final, #data_type').on('change', function() {
            table.draw();
        });

    });
</script>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script>
    // Definindo a URL usando a variável PHP
    const URL_ADM = "<?= $urlAdm ?>";
    console.log('URL da API:', URL_ADM);

    // Função auxiliar para (re)inicializar um tooltip
    function atualizarTooltip(elemento) {
        if (!elemento) return;
        const antigo = bootstrap.Tooltip.getInstance(elemento);
        if (antigo) antigo.dispose();
        new bootstrap.Tooltip(elemento);
    }

    // Função para verificar os recebimentos
    async function verificarRecebimentos() {
        try {
            const response = await fetch(`${URL_ADM}get-receipts-status`, {
                cache: "no-store" // evita resposta cacheada
            });

            const text = await response.text();
            console.log('Resposta da API:', text);

            // Remove o BOM se existir
            const cleanedText = text.replace(/^\uFEFF/, '');

            try {
                const data = JSON.parse(cleanedText);
                console.log('Status dos recebimentos:', data);
                data.forEach(receive => {
                    console.log('Objeto receive:', receive);
                    console.log('receive.id_receive:', receive.id_receive);
                    console.log('receive.name_user_temp:', receive.name_user_temp);

                    if (receive && receive.ext) {
                        console.log('Propriedade ext:', receive.ext);
                    } else {
                        console.log('A propriedade "ext" não está presente ou receive é undefined');
                    }

                    const row = document.getElementById(`linha-${receive.id_receive}`);
                    const statusCell = row?.querySelector('td[data-status-receive]');

                    if (statusCell) {
                        const actionButtons = row.querySelectorAll('button, a.btn');
                        let novoStatusHTML;

                        if (receive.busy == 1) {
                            novoStatusHTML = `<span class="text-danger" title="Registro ocupado por: ${receive.name_user_temp ?? 'usuário desconhecido'}">
                                <i class="fa-solid fa-lock"></i> Ocupado
                            </span>`;

                            actionButtons.forEach(btn => {
                                btn.setAttribute('disabled', true);
                                btn.style.pointerEvents = 'none';
                                btn.style.opacity = '0.6';
                            });
                        } else {
                            novoStatusHTML = `<span class="text-success" title="Registro livre">
                                <i class="fa-solid fa-unlock"></i> Livre
                            </span>`;

                            actionButtons.forEach(btn => {
                                btn.removeAttribute('disabled');
                                btn.style.pointerEvents = 'auto';
                                btn.style.opacity = '1';
                            });
                        }

                        // Remove tooltip antigo
                        const spanAntigo = statusCell.querySelector('span');
                        if (spanAntigo) {
                            const oldTooltip = bootstrap.Tooltip.getInstance(spanAntigo);
                            if (oldTooltip) oldTooltip.dispose();
                        }

                        // Atualiza o HTML do status
                        statusCell.innerHTML = novoStatusHTML;

                        // Reativa tooltip no novo span
                        const novoSpan = statusCell.querySelector('span');
                        atualizarTooltip(novoSpan);
                    }
                });

            } catch (e) {
                console.error('Erro ao interpretar JSON:', e);
                console.log('Resposta bruta:', cleanedText);
            }

        } catch (error) {
            console.error('Erro ao buscar status de recebimentos:', error);
        }
    }

    // Atualiza os recebimentos a cada 3 segundos
    setInterval(verificarRecebimentos, 1000);
</script>



<!-- <script>
    // Definindo a URL usando a variável PHP
    const URL_ADM = "<?= $urlAdm ?>";
    console.log('URL da API:', URL_ADM);

    // Função para verificar os pagamentos
    async function verificarPagamentos() {
        try {
            const response = await fetch(`${URL_ADM}get-payments-status`, {
                cache: "no-store" // evita resposta cacheada
            });
            console.log(response);
            const text = await response.text();
            console.log(text);

            // Verifique a resposta antes de tentar fazer o JSON.parse
            console.log('Resposta da API:', text);

            // Remove o BOM se existir
            const cleanedText = text.replace(/^\uFEFF/, '');


            try {
                const data = JSON.parse(text);
                console.log('Status dos pagamentos:', data);

                // Adicione a verificação aqui
                data.forEach(payment => {
                    console.log('Objeto payment:', payment); // Verifique o que está sendo retornado
                    if (payment && payment.ext) {
                        const extValue = payment.ext;
                        // Faça algo com a propriedade ext
                        console.log('Propriedade ext:', extValue);
                    } else {
                        console.log('A propriedade "ext" não está presente ou payment é undefined');
                    }

                    // Continuar com a lógica de atualização dos status
                    // const row = document.getElementById(`linha-${payment.id_pay}`);
                    // const statusCell = row?.querySelector('td[data-status]');
                    // if (statusCell) {
                    //     if (payment.busy == 1) {
                    //         statusCell.innerHTML = '<span class="text-danger" title="Registro ocupado"><i class="fa-solid fa-lock"></i> Ocupado</span>';
                    //     } else {
                    //         statusCell.innerHTML = '<span class="text-success" title="Registro livre"><i class="fa-solid fa-unlock"></i> Livre</span>';
                    //     }
                    // }
                    // Dentro do forEach do verificarPagamentos
                    const row = document.getElementById(`linha-${payment.id_pay}`);
                    const statusCell = row?.querySelector('td[data-status]');

                    if (statusCell) {
                        const actionButtons = row.querySelectorAll('button, a.btn');

                        if (payment.busy == 1) {
                            statusCell.innerHTML = '<span class="text-danger" title="Registro ocupado por: <?= $user_temp ?? 'usuário desconhecido' ?>"><i class="fa-solid fa-lock"></i> Ocupado</span>';

                            // Desativa os botões
                            actionButtons.forEach(btn => {
                                btn.setAttribute('disabled', true);
                                btn.style.pointerEvents = 'none';
                                btn.style.opacity = '0.6';
                            });
                        } else {
                            statusCell.innerHTML = '<span class="text-success" title="Registro livre"><i class="fa-solid fa-unlock"></i> Livre</span>';

                            // Reativa os botões
                            actionButtons.forEach(btn => {
                                btn.removeAttribute('disabled');
                                btn.style.pointerEvents = 'auto';
                                btn.style.opacity = '1';
                            });
                        }
                    }
                });

            } catch (e) {
                console.error('Resposta não é JSON válido:', text);
            }

        } catch (error) {
            console.error('Erro ao buscar status de pagamentos:', error);
        }
    }


    // Atualiza os pagamentos a cada 3 segundos
    setInterval(verificarPagamentos, 3000);
</script> -->

<!-- <script>
    $(document).ready(function () {
        $('#tabela tbody tr').each(function () {
            const statusText = $(this).find('td[data-status]').text().trim();

            if (statusText.includes('Ocupado')) {
                // Desabilita todos os botões dentro da linha
                $(this).find('button, a.btn').each(function () {
                    $(this).attr('disabled', true);
                    $(this).css('pointer-events', 'none'); // Para links
                    $(this).css('opacity', '0.6'); // Deixa o botão com aparência desabilitada
                });
            }
        });
    });
</script> -->



<!-- <script>
    // Atualização automática do status a cada 30 segundos
    setInterval(() => {
        const paymentRows = document.querySelectorAll('tr[data-busy]');
        paymentRows.forEach(row => {
            const paymentId = row.id.split('-')[1]; // Extrai o ID do pagamento
            checkPaymentStatus(paymentId);
        });
    }, 5000); // 5 segundos
</script> -->
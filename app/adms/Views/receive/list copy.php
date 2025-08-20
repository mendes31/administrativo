<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_pay');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Contas à Pagar</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Contas à Pagar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreatePay', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-pay' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há departamento no array
            if ($this->data['payments'] ?? false) {
            ?>


                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Data Inicial</label>
                        <input type="date" id="min-date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>Data Final</label>
                        <input type="date" id="max-date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label for="filtroStatus">Filtrar status:</label>
                        <select id="filtroStatus" class="form-control">
                            <option value="">Ambas</option>
                            <option value="pendente">Pendentes</option>
                            <option value="pago">Pagas</option>
                        </select>
                    </div>
                </div>


                <table class="table table-striped table-hover" id="tabela">
                    <thead>
                        <tr>
                            <!-- <i class="fa-solid fa-square"></i> -->
                            <!-- <th scope="col" class="d-none d-md-table-cell">Id</th> -->
                            <!-- <th scope="col" class="d-none d-md-table-cell">Data</th> -->
                            <th scope="col">Nº Doc</th>
                            <!-- <th scope="col" class="d-none d-md-table-cell">Descrição</th> -->
                            <th scope="col">Fornecedor</th>
                            <th scope="col" class="d-none d-md-table-cell">Valor</th>
                            <th scope="col" class="d-none d-md-table-cell">Pago</th>
                            <th scope="col" class="d-none d-md-table-cell">Pagar</th>
                            <th scope="col" class="d-none d-md-table-cell">Vencimento</th>
                            <th scope="col" class="d-none d-md-table-cell">Previsão</th>
                            <!-- <th scope="col" class="d-none d-md-table-cell">Frequencia</th> -->
                            <th scope="col" class="d-none d-md-table-cell">Forma Pgto</th>
                            <th scope="col" class="d-none d-md-table-cell">Saída</th>
                            <th scope="col" class="d-none d-md-table-cell">Status</th>
                            <!-- <th scope="col">Arquivo</th> -->
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php

                        // var_dump($this->data['payments']);

                        // Percorre o array de cargo
                        foreach ($this->data['payments'] as $pay) {

                            // Extrai variáveis do array de cargos
                            extract($pay);

                            if ($discount_value > 0) {
                                $saldoPagar = $original_value - ($amount_paid + $discount_value);
                            } else {
                                $saldoPagar = $original_value - $amount_paid;
                            }

                            if ($saldoPagar < 0) {
                                $saldoPagar = 0;
                            }

                        ?>


                            <?php
                            $classe_pago = ''; // Inicializa a variável para evitar o erro

                            if ($paid == 1) {
                                $classe_pago = 'text-success'; // Define a cor verde
                                $ocultar = 'ocultar'; // clase para ocultar funcionalidades - botões
                            } else {
                                $classe_pago = 'text-danger'; // Define a cor vermelha
                                $ocultar = '';
                            }
                            ?>

                            <tr id="linha-<?php echo $id_pay; ?>" data-busy="<?php echo $busy; ?>">
                                <!-- <td class="d-none d-md-table-cell"><?php echo $id_pay; ?></td> -->
                                <!-- <td class="d-none d-md-table-cell"><?php echo date("d-m-Y", strtotime($doc_date)); ?></td> -->
                                <td><i class="fa fa-square <?php echo $classe_pago; ?> mr-1"></i>&nbsp;<?php echo $num_doc; ?></td>
                                <!-- <td class="d-none d-md-table-cell"><?php echo $description; ?></td> -->
                                <td><?php echo $card_name; ?></td>
                                <!-- <td><?php echo $value; ?></td> -->

                                <td class="d-none d-md-table-cell"><?php echo 'R$ ' . number_format($original_value, 2, ',', '.'); ?></td>

                                <!-- <td><?php echo 'R$ ' . number_format($value, 2, ',', '.'); ?></td> -->

                                <!-- <td>
                                    <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'list-partial-values/' . urlencode((string) $id_pay)) ?>" class="text-danger">
                                        R$ <?= number_format((float) $value, 2, ',', '.') ?>
                                    </a>
                                </td> -->

                                <!-- <td>
                                    <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'list-partial-values/' . urlencode((string) $id_pay)) ?>"
                                        class="text-danger"
                                        title="Pagamentos Parciais">
                                        R$ <?= number_format((float) $value, 2, ',', '.') ?>
                                    </a>
                                </td> -->

                                <td class="d-none d-md-table-cell text-success"><?php echo 'R$ ' . number_format($amount_paid, 2, ',', '.'); ?></td>

                                <!-- <td class="d-none d-md-table-cell">
                                    <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'list-partial-values/' . urlencode((string) $id_pay)) ?>"
                                        class="text-success custom-tooltip"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-custom-class='tooltip-pago'
                                        title="Pagamentos">
                                        R$ <?= number_format((float) $amount_paid, 2, ',', '.') ?>
                                    </a>
                                </td> -->

                                <td class="d-none d-md-table-cell text-danger"><?php echo 'R$ ' . number_format($saldoPagar, 2, ',', '.'); ?></td>

                                <!-- <td>
                                    <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'list-partial-values/' . urlencode((string) $id_pay)) ?>"
                                        class="text-danger custom-tooltip"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-custom-class='tooltip-pagamentos'>
                                        R$ <?= number_format((float) $saldoPagar, 2, ',', '.') ?>
                                    </a>
                                </td> -->



                                <td class="d-none d-md-table-cell"><?php echo date("d-m-Y", strtotime($due_date)); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo !empty($expected_date) ? date("d-m-Y", strtotime($expected_date)) : 'N/A'; ?></td>

                                <!-- <td class="d-none d-md-table-cell"><?php echo $name_freq; ?></td> -->
                                <td class="d-none d-md-table-cell"><?php echo $name_apm; ?></td>
                                <td class="d-none d-md-table-cell"><?php echo $bank_name; ?></td>
                                <td class="d-none d-md-table-cell text-center">
                                    <?php if ($busy == 1): ?>
                                        <span class="text-danger" title="Registro ocupado">
                                            <i class="fa-solid fa-lock"></i> Ocupado
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success" title="Registro livre">
                                            <i class="fa-solid fa-unlock"></i> Livre
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- <td><?php echo $file; ?></td> -->

                                <td class="text-center">
                                    <div class="tabela-acoes">

                                        <?php
                                        $base = $_ENV['URL_ADM'];

                                        // Botão Visualizar
                                        if (in_array('ViewPay', $this->data['buttonPermission'])) {
                                            echo "<a href='{$base}view-pay/$id_pay'
                    class='btn btn-primary btn-sm me-1 mb-1 acao'
                    data-id='$id_pay'
                    data-busy='$busy'
                    data-user-temp='$name_user_temp'
                    data-bs-toggle='tooltip'
                    data-bs-placement='top'
                    data-bs-custom-class='tooltip-visualizar'
                    title='Visualizar'>
                    <i class='fa-regular fa-eye'></i>
                  </a>";
                                        }

                                        // Botão Editar
                                        if (in_array('UpdatePay', $this->data['buttonPermission'])) {
                                            echo "<a href='{$base}update-pay/$id_pay'
                    class='btn btn-warning btn-sm me-1 mb-1 acao'
                    data-id='$id_pay'
                    data-busy='$busy'
                    data-user-temp='$name_user_temp'
                    data-bs-toggle='tooltip'
                    data-bs-placement='top'
                    data-bs-custom-class='tooltip-editar'
                    title='Editar'>
                    <i class='fa-solid fa-pen-to-square'></i>
                  </a>";
                                        }

                                        // Botão Parcelar
                                        if (in_array('Installments', $this->data['buttonPermission'])) {
                                            echo "<a href='{$base}installments/$id_pay'
                    class='btn btn-sm me-1 mb-1 btn-parcelar acao $ocultar'
                    data-id='$id_pay'
                    data-busy='$busy'
                    data-user-temp='$name_user_temp'
                    data-bs-toggle='tooltip'
                    data-bs-placement='top'
                    data-bs-custom-class='tooltip-parcelar'
                    title='Parcelar'>
                    <i class='fa-solid fa-coins'></i>
                  </a>";
                                        }

                                        // Botão Pagar
                                        if (in_array('Payment', $this->data['buttonPermission'])) {
                                            echo "<a href='{$base}payment/$id_pay'
                    class='btn btn-success btn-sm me-1 mb-1 acao $ocultar'
                    data-id='$id_pay'
                    data-busy='$busy'
                    data-user-temp='$name_user_temp'
                    data-bs-toggle='tooltip'
                    data-bs-placement='top'
                    data-bs-custom-class='tooltip-pagar'
                    title='Pagar'>
                    <i class='fa-solid fa-money-bill-wave'></i>
                  </a>";
                                        }

                                        // Botão Excluir (formulário com POST)
                                        if (in_array('DeletePay', $this->data['buttonPermission'])) { ?>
                                            <form id="formDelete<?= $id_pay ?>" action="<?= $base ?>delete-pay" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id_pay ?? '' ?>">
                                                <input type="hidden" name="num_doc" value="<?= $num_doc ?? '' ?>">
                                                <input type="hidden" name="partner_id" value="<?= $partner_id ?? '' ?>">

                                                <button type="submit"
                                                    class="btn btn-danger btn-sm me-1 mb-1 btn-verificar-busy <?= $ocultar ?>"
                                                    onclick="confirmDeletion(event, <?= $id_pay ?>)"
                                                    data-busy="<?= $busy ?>"
                                                    data-user-temp="<?= $name_user_temp ?>"
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


            <?php
                // Inclui o arquivo de paginação
                include_once './app/adms/Views/partials/pagination.php';
            } else { // Exibe mensagem se nenhuma conta for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhuma Conta encontrada!</div>";
            } ?>

        </div>

    </div>
</div>

<!-- <script>
    setInterval(() => {
        location.reload();
    }, 15000); // 15 segundos = 15000 ms
</script> -->

<!-- Plugin para ordenação dd-mm-yyyy -->
<script>
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
</script>


<script type="text/javascript">
    $(document).ready(function() {
        // Filtro por data de vencimento
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const min = $('#min-date').val();
            const max = $('#max-date').val();
            const dateColIndex = 5; // Coluna de "Vencimento"

            const dateStr = data[dateColIndex];
            if (!dateStr) return false;

            const parts = dateStr.split('-'); // dd-mm-yyyy
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
                } // Reduz a largura mínima das colunas
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
            // columnDefs: [{
            //     className: "text-start",
            //     targets: "_all"
            // }]

            columnDefs: [{
                    type: 'date-eu',
                    targets: 5
                } // Coluna 5 = Vencimento
            ],
            order: [
                [5, 'desc']
            ], // Ordenar por Vencimento (coluna 5), decrescente



        });

        // Redesenha ao mudar filtros
        $('#min-date, #max-date, #filtroStatus').on('change', function() {
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
// list-payments.js

function checkPaymentStatus(id) {
    fetch(`/get-payments-status?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
            } else {
                console.log(`ID: ${data.id_pay}, Status: ${data.busy}`);
                // Aqui você pode fazer o que precisar com os dados recebidos
            }
        })
        .catch(error => console.error('Erro:', error));
}

// Exemplo de uso - chamada para um ID específico, pode ser dentro de um evento
const somePaymentId = 123;  // Use o ID correto
checkPaymentStatus(somePaymentId);
</script>


<!-- <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Bloquear cliques se busy == 1
        document.querySelectorAll('.btn-verificar-busy').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (btn.dataset.busy == 1) {
                    e.preventDefault();
                    alert("Esta conta está em processamento. Aguarde.");
                }
            });
        });
    });
</script> -->

<!-- 
<script>
    // Aguarda o carregamento completo do DOM para começar a executar o código JS.
    // Isso garante que todos os elementos <a> já estejam disponíveis no momento da leitura.
    document.addEventListener("DOMContentLoaded", () => {

        document.querySelectorAll("a.acao").forEach(link => { //Seleciona todos os links (<a>) que tenham a classe acao. - Para cada um desses links, aplica uma função.
            // Adiciona um ouvinte de evento click no link.
            // Como o callback é async, ele permite usar await dentro dele (requisições AJAX).
            link.addEventListener("click", async (e) => {
                // Impede o comportamento padrão do link (navegar para a URL imediatamente).
                // Assim você consegue verificar se o registro está ocupado antes de navegar.
                e.preventDefault();

                // id: pega o valor de data-id do link (ex: <a data-id="123">).
                // url: guarda a URL original do link (usada mais tarde para redirecionar).
                const id = link.dataset.id;
                const url = link.href;

                // Verificação rápida se o ID existe
                // Se não tiver id, mostra um aviso no console.
                // E prossegue com o redirecionamento normalmente, pois não há como verificar o status do registro sem ID.
                if (!id) {
                    console.warn("ID não encontrado.");
                    window.location.href = url;
                    return;
                }

                // Montar URL da verificação
                // BASE_URL: injeta via PHP a URL base do sistema (ex: http://localhost/sistema/).
                // verifyURL: monta a URL completa para a requisição AJAX que vai verificar o "busy". Exemplo: http://localhost/sistema/check-busy/123
                const BASE_URL = "<?php echo rtrim($_ENV['URL_ADM']) ; ?>";
                const verifyURL = `${BASE_URL}check-busy/${id}`;

                try {
                    // Faz o AJAX para verificar o status busy
                    // Faz uma requisição AJAX (GET) para a URL que verifica se o registro está em uso.
                    const res = await fetch(verifyURL);

                    // Se a resposta não for OK (ex: 404, 500), joga erro
                    // Verifica se a resposta HTTP foi bem-sucedida (200 OK).
                    // Se não foi (ex: 404, 500), lança um erro manualmente.
                    if (!res.ok) throw new Error(`Erro HTTP: ${res.status}`);

                    // Converte a resposta para JSON.
                    // Exemplo de resposta esperada: { "busy": 1, "user_temp": "Maria" }
                    const data = await res.json();

                    // Se busy for 1, significa que o registro está sendo usado por outro usuário.
                    // Mostra um alerta indicando quem está utilizando (se user_temp vier no JSON).
                    if (data.busy === 1) {
                        alert(`Este registro está sendo editado por: ${data.user_temp ?? 'Outro usuário'}`);
                    } else {
                        // Libera o redirecionamento
                        // Se não estiver ocupado, o script prossegue com o redirecionamento para a página de destino.
                        window.location.href = url;
                    }
                } catch (err) {
                    // Se der erro durante a requisição (ex: URL incorreta, servidor fora do ar), mostra erro no console e alerta o usuário.
                    console.error("Erro na verificação busy:", err);
                    alert("Erro ao verificar status. Tente novamente.");
                }
            });
        });

    });
</script> -->
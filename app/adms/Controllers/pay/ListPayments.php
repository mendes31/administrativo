<?php

namespace App\adms\Controllers\pay;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Contas à pagar
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Bancos no sistema. Utiliza um repositório
 * para obter dados dos Bancos e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\pay
 * @author Rafael Mendes
 */
class ListPayments
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10;

    /**
     * Recuperar e listarContas à pagar com paginação.
     * 
     * Este método recupera os Bancos a partir do repositório de Contas à pagar com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Bancos.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Se existir $_GET['page'], sobrescreve o $page
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        // Novo: permitir escolha do usuário
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        // // Receber os dados do formulário (GET)
        // $filtros = [
        //     'num_doc' => $_GET['num_doc'] ?? '',
        //     'num_nota' => $_GET['num_nota'] ?? '',
        //     'card_code_fornecedor' => $_GET['card_code_fornecedor'] ?? '',
        //     'fornecedor' => $_GET['fornecedor'] ?? '',
        //     'forma_pgto' => $_GET['forma_pgto'] ?? '',
        //     'saida' => $_GET['saida'] ?? '',
        //     'data_inicial' => $_GET['data_inicial'] ?? '',
        //     'data_final' => $_GET['data_final'] ?? '',
        //     'status' => $_GET['status'] ?? '',
        // ];
        // Receber os dados do formulário (GET)
        $filtros = [
            'num_doc' => $_GET['num_doc'] ?? '',
            'num_nota' => $_GET['num_nota'] ?? '',
            'card_code_fornecedor' => $_GET['card_code_fornecedor'] ?? '',
            'fornecedor' => $_GET['fornecedor'] ?? '',
            'forma_pgto' => $_GET['forma_pgto'] ?? '',
            'saida' => $_GET['saida'] ?? '',
            'data_type' => $_GET['data_type'] ?? 'due_date',
            'data_inicial' => $_GET['data_inicial'] ?? '',
            'data_final' => $_GET['data_final'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        // Filtro padrão: vencimento/emissão do dia, se nenhum filtro real estiver preenchido
        $camposIgnorados = ['data_type'];
        $filtrosVerificacao = array_diff_key($filtros, array_flip($camposIgnorados));
        $temFiltro = false;
        foreach ($filtrosVerificacao as $valor) {
            if (!empty($valor)) {
                $temFiltro = true;
                break;
            }
        }
        if (!$temFiltro) {
            $filtros['vencimento_hoje'] = true;
        }

        $this->data['form'] = $filtros;

        // Instanciar o Repository para recuperar os registros do banco de dados
        $listPayments = new PaymentsRepository();

        // Recuperar os Bancos para a página atual com filtros
        $this->data['payments'] = $listPayments->getAllPayments((int) $page, (int) $this->limitResult, $filtros);

        // Buscar o totalizador de contas a pagar filtrado
        $this->data['total_to_pay'] = $listPayments->getTotalToPay($filtros);

        // Se não houver registros e filtro padrão de vencimento do dia, buscar próxima data de vencimento
        if (isset($filtros['vencimento_hoje']) && empty($this->data['payments'])) {
            // Buscar próxima data conforme o tipo selecionado
            $dataType = $filtros['data_type'] ?? 'due_date';
            $proximaData = $listPayments->getProximaDataVencimento($dataType);
            if ($proximaData) {
                $filtros = [
                    'data_type' => $dataType,
                    'data_inicial' => $proximaData,
                    'data_final' => $proximaData
                ];
                $this->data['form'] = $filtros;
                $this->data['payments'] = $listPayments->getAllPayments((int) $page, (int) $this->limitResult, $filtros);
            }
        }

        // Atualizar o campo busy e user_temp
        $payRepo = new PaymentsRepository();
        $payRepo->getUserTemp($_SESSION['user_id']);
        if ($payRepo) {
            $payRepo->clearUser($_SESSION['user_id']);
        }

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $listPayments->getAmountPayments($filtros),
            (int) $this->limitResult,
            (int) $page,
            'list-payments',
            array_merge($filtros, ['per_page' => $this->limitResult])
        );

        // Limpar filtros se solicitado
        if (isset($_GET['limpar_filtros'])) {
            unset($_SESSION['filtros_list_payments']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-payments');
            exit;
        }
        // Salvar filtros na sessão
        $_SESSION['filtros_list_payments'] = $filtros;

        $this->data['per_page'] = $this->limitResult;

        $pageElements = [
            'title_head' => 'Listar Contas à pagar',
            'menu' => 'list-payments',
            'buttonPermission' => ['CreatePay', 'ViewPay', 'Installments', 'Payment', 'UpdatePay', 'DeletePay'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/pay/list", $this->data);
        $loadView->loadView();
    }
}

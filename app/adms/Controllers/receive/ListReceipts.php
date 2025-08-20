<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\ReceiptsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Contas à Receber
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Contas à Receber no sistema. Utiliza um repositório
 * para obter dados dos Bancos e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\receive
 * @author Rafael Mendes
 */
class ListReceipts
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10;

    /**
     * Recuperar e listar Contas à Receber com paginação.
     * 
     * Este método recupera os Contas à Receber a partir do repositório de Contas à Receber com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Contas à Receber.
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

        // Receber os dados do formulário (GET)
        $filtros = [
            'num_doc' => $_GET['num_doc'] ?? '',
            'num_nota' => $_GET['num_nota'] ?? '',
            'card_code_cliente' => $_GET['card_code_cliente'] ?? '',
            'cliente' => $_GET['cliente'] ?? '',
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
        $listReceipts = new ReceiptsRepository();

        // Recuperar os Bancos para a página atual com filtros
        $this->data['receipts'] = $listReceipts->getAllReceipts((int) $page, (int) $this->limitResult, $filtros);

        // Buscar o totalizador de contas a Receber filtrado
        $this->data['total_to_receive'] = $listReceipts->getTotalToReceive($filtros);

        // Se não houver registros e filtro padrão de vencimento do dia, buscar próxima data de vencimento
        if (isset($filtros['vencimento_hoje']) && empty($this->data['receipts'])) {
            // Buscar próxima data conforme o tipo selecionado
            $dataType = $filtros['data_type'] ?? 'due_date';
            $proximaData = $listReceipts->getProximaDataVencimento($dataType);
            if ($proximaData) {
                $filtros = [
                    'data_type' => $dataType,
                    'data_inicial' => $proximaData,
                    'data_final' => $proximaData
                ];
                $this->data['form'] = $filtros;
                $this->data['receipts'] = $listReceipts->getAllReceipts((int) $page, (int) $this->limitResult, $filtros);
            }
        }

        // Atualizar o campo busy e user_temp
        $receiveRepo = new ReceiptsRepository();
        $receiveRepo->getUserTemp($_SESSION['user_id']);
        if ($receiveRepo) {
            $receiveRepo->clearUser($_SESSION['user_id']); 
        }

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $listReceipts->getAmountReceipts($filtros),
            (int) $this->limitResult,
            (int) $page,
            'list-receipts',
            array_merge($filtros, ['per_page' => $this->limitResult])
        );

        // Limpar filtros se solicitado
        if (isset($_GET['limpar_filtros'])) {
            unset($_SESSION['filtros_list_receipts']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-receipts');
            exit;
        }
        // Salvar filtros na sessão
        $_SESSION['filtros_list_receipts'] = $filtros;

        $this->data['per_page'] = $this->limitResult;

        $pageElements = [
            'title_head' => 'Listar Contas à Receber',
            'menu' => 'list-receipts',
            'buttonPermission' => ['CreateReceive', 'ViewReceive', 'InstallmentsReceive', 'Receive', 'UpdateReceive', 'DeleteReceive'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/receive/list", $this->data);
        $loadView->loadView();
    }
}

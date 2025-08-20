<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PartialValuesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar valores pagos para a conta
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Bancos no sistema. Utiliza um repositório
 * para obter dados dos Bancos e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\pay
 * @author Rafael Mendes
 */
class ListPartialValuesReceive
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;
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
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Instanciar o Repository para recuperar os registros do banco de dados
        $payUpdate = new PartialValuesRepository();

        // Recuperar os Bancos para a página atual
        $this->data['partialValues'] = $payUpdate->getPartialValue($id);

        $this->data['movementValues'] = $payUpdate->getMovementValues($id);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Pagamentos',
            'menu' => 'list-payments',
            'buttonPermission' => ['ListPayments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/pay/listPartialValues", $this->data);
        $loadView->loadView();
    }
}

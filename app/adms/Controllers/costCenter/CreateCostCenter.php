<?php

namespace App\adms\Controllers\costCenter;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCostCenterService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Centro de Custo
 *
 * Esta classe é responsável pelo processo de criação de novos Centro de Custo. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Centro de Custo no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\costCenter
 * @author Rafael Mendes
 */
class CreateCostCenter
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Centro de Custo
     *
     * Este método é chamado para processar a criação de um novo Centro de Custo Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Centro de Custo Caso contrário, carrega a
     * visualização de criação do Centro de Custocom mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_cost_center', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Centro de Custo            
            $this->addCostCenter();
        } else {
            // Chamar o método para carregar a view de criação de Centro de Custo 
            $this->viewCostCenter();
        }
    }

    /**
     * Carregar a visualização de criação do Centro de Custo
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Centro de Custo
     * 
     * @return void
     */
    private function viewCostCenter(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Centro de Custo',
            'menu' => 'list-departments',
            'buttonPermission' => ['ListDepartment'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/costCenter/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Centro de Custo ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationCostCenterService` e,
     * se não houver erros, cria o departametno no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addCostCenter(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationCostCenters = new ValidationCostCenterService();
        $this->data['errors'] = $validationCostCenters->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewCostCenter();
            return;
        }

        // Instanciar o Repository para criar o Centro de Custo        
        $costCentersCreate = new CostCentersRepository();
        $result = $costCentersCreate->createCostCenter($this->data['form']);

        // Se a criação do Centro de Custo for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Centro de Custo cadastrado com sucesso!";

            // Redirecionar para a página de visualização do centro de custo recém-criado
            header("Location: {$_ENV['URL_ADM']}view-cost-center/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Centro de Custo não cadastrado!";

            // Recarregar a view com erro
            $this->viewCostCenter();
        }
    }
}

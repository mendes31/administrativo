<?php

namespace App\adms\Controllers\costCenter;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCostCenterService;
use App\adms\Controllers\Services\Validation\ValidationDepartmentService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Centro de Custo
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Centro de Custo existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Centro de Custo no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Centro de Custo não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\costCenter;
 * @author Rafael Mendes
 */
class UpdateCostCenter
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Centro de Custo.
     *
     * Este método gerencia o processo de edição de um Centro de Custo. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Centro de Custo, e chama o método adequado para editar o Centro de Custo ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Centro de Custo a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_cost_center', $this->data['form']['csrf_token'])) 
        {
            // Editar o Centro de Custo
            $this->editCostCenter();
        } else {
            // Recuperar o registro do Centro de Custo
            $viewCostCenter = new CostCentersRepository();
            $this->data['form'] = $viewCostCenter->getCostCenter((int) $id);

            // Verificar se o Centro de Custo foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Centro de Custo não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Centro de Custo não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-cost-centers");
                return;
            }

            // Carregar a visualização para edição do Centro de Custo
            $this->viewCostCenter();
        }
    }

    /**
     * Carregar a visualização para edição do Centro de Custo.
     *
     * Este método define o título da página e carrega a visualização de edição do Centro de Custo com os dados necessários.
     * 
     * @return void
     */
    private function viewCostCenter(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Centro de Custo',
            'menu' => 'list-cost-centers',
            'buttonPermission' => ['ListCostCenters', 'ViewCostCenter'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/costCenter/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Centro de Custo.
     *
     * Este método valida os dados do formulário, atualiza as informações do Centro de Custo no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Centro de Custo é redirecionado para a página de visualização do Centro de Custo.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editCostCenter(): void
    {
        // Validar os dados do formulário
        $validationCostCenter = new ValidationCostCenterService();
        $this->data['errors'] = $validationCostCenter->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewCostCenter();
            return;
        }

        // Atualizar o Centro de Custo
        $costCenterUpdate = new CostCentersRepository();
        $result = $costCenterUpdate->updateCostCenter($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Centro de Custo editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-cost-center/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Centro de Custo não editado!";
            $this->viewCostCenter();
        }
    }
}

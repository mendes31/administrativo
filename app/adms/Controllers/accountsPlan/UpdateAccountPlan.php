<?php

namespace App\adms\Controllers\accountsPlan;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationAccountPlanService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Plano de Contas
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Plano de Contas existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Plano de Contas no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Plano de Contas não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\accountsPlan;
 * @author Rafael Mendes
 */
class UpdateAccountPlan
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Plano de Contas.
     *
     * Este método gerencia o processo de edição de um Plano de Contas. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Plano de Contas, e chama o método adequado para editar o Plano de Contas ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Plano de Contas a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_account_plan', $this->data['form']['csrf_token'])) 
        {
            // Editar o Plano de Contas
            $this->editAccountPlan();
        } else {
            // Recuperar o registro do Plano de Contas
            $viewAccountPlan = new AccountPlanRepository();
            $this->data['form'] = $viewAccountPlan->getAccountPlan((int) $id);

            // Verificar se o Plano de Contas foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Plano de Contas não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Plano de Contas não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
                return;
            }

            // Carregar a visualização para edição do Plano de Contas
            $this->viewAccountPlan();
        }
    }

    /**
     * Carregar a visualização para edição do Plano de Contas.
     *
     * Este método define o título da página e carrega a visualização de edição do Plano de Contas com os dados necessários.
     * 
     * @return void
     */
    private function viewAccountPlan(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Plano de Contas',
            'menu' => 'list-accounts-plan',
            'buttonPermission' => ['ListAccountsPlan', 'ViewAccountPlan'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accountsPlan/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Plano de Contas.
     *
     * Este método valida os dados do formulário, atualiza as informações do Plano de Contas no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Plano de Contas é redirecionado para a página de visualização do Plano de Contas.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editAccountPlan(): void
    {
        // Validar os dados do formulário
        $validationAccountPlan = new ValidationAccountPlanService();
        $this->data['errors'] = $validationAccountPlan->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewAccountPlan();
            return;
        }

        // Atualizar o Plano de Contas
        $accountPlanUpdate = new AccountPlanRepository();
        $result = $accountPlanUpdate->updateAccountPlan($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Plano de Contas editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-account-plan/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Plano de Contas não editado!";
            $this->viewAccountPlan();
        }
    }
}

<?php

namespace App\adms\Controllers\accountsPlan;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationAccountPlanService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Plano de Contas
 *
 * Esta classe é responsável pelo processo de criação de novas Plano de Contas. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Plano de Contas no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\accountsPlan
 * @author Rafael Mendes
 */
class CreateAccountPlan
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Plano de Contas
     *
     * Este método é chamado para processar a criação de um novo Plano de Contas Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Plano de Contas Caso contrário, carrega a
     * visualização de criação do Plano de Contascom mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_account_plan', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Plano de Contas            
            $this->addAccountPlan();
        } else {
            // Chamar o método para carregar a view de criação de Plano de Contas 
            $this->viewAccountPlan();
        }
    }

    /**
     * Carregar a visualização de criação do Plano de Contas
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Plano de Contas
     * 
     * @return void
     */
    private function viewAccountPlan(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Plano de Contas',
            'menu' => 'list-accounts-plan',
            'buttonPermission' => ['ListAccountsPlan'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accountsPlan/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Plano de Contas ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationAccountPlanService` e,
     * se não houver erros, cria o plano de contas no banco de dados usando o `AccountPlanRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addAccountPlan(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationAccountPlan = new ValidationAccountPlanService();
        $this->data['errors'] = $validationAccountPlan->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewAccountPlan();
            return;
        }

        // Instanciar o Repository para criar o Plano de Contas        
        $accountPlanCreate = new AccountPlanRepository();
        $result = $accountPlanCreate->createAccountPlan($this->data['form']);

        // Se a criação do Plano de Contas for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Plano de Contas cadastrado com sucesso!";

            // Redirecionar para a página de visualização do Plano de Contas recém-criado
            header("Location: {$_ENV['URL_ADM']}view-account-plan/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Plano de Contas não cadastrado!";

            // Recarregar a view com erro
            $this->viewAccountPlan();
        }
    }
}

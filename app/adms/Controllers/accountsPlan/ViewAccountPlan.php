<?php

namespace App\adms\Controllers\accountsPlan;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Plano de Contas
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Plano de Contas específico. Ela recupera os dados
 * do Plano de Contas a partir do repositório, valida se o Plano de Contas existe e carrega a visualização apropriada. Se o Plano de Contas
 * não for encontrado, uma mensagem de erro é exibida e o Plano de Contas é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\accountsPlan
 * @author Rafael Mendes de Oliveira
 */
class ViewAccountPlan
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Plano de Contas.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Plano de Contas específico. Ele valida o ID fornecido,
     * recupera os dados do Plano de Contas do repositório e carrega a visualização. Se o Plano de Contas não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Plano de Contas.
     *
     * @param int|string $id ID do Plano de Contas a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Plano de Contas não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Plano de Contas não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewAccountPlan = new AccountPlanRepository();
        $this->data['accountPlan'] = $viewAccountPlan->getAccountPlan((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['accountPlan']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Plano de Contas não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Plano de Contas não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
            return;
        }

        // Registrar a visualização do Plano de Contas
        GenerateLog::generateLog("info", "Visualizado o Plano de Contas.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Plano de Contas',
            'menu' => 'list-accounts-plan',
            'buttonPermission' => ['ListAccountsPlan', 'UpdateAccountPlan', 'DeleteAccountPlan'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accountsPlan/view", $this->data);
        $loadView->loadView();
    }
}

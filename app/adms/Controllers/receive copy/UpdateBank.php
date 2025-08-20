<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationBankService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Banco
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Banco existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Banco no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Banco não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes
 */
class UpdateBank
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Banco.
     *
     * Este método gerencia o processo de edição de um Banco. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Banco, e chama o método adequado para editar o Banco ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Banco a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_banks', $this->data['form']['csrf_token'])) 
        {
            // Editar o Banco
            $this->editBank();
        } else {
            // Recuperar o registro do Banco
            $viewBank = new BanksRepository();
            $this->data['form'] = $viewBank->getBank((int) $id);

            // Verificar se o Banco foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Banco não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Banco não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-banks");
                return;
            }

            // Carregar a visualização para edição do Banco
            $this->viewBank();
        }
    }

    /**
     * Carregar a visualização para edição do Banco.
     *
     * Este método define o título da página e carrega a visualização de edição do Banco com os dados necessários.
     * 
     * @return void
     */
    private function viewBank(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Banco',
            'menu' => 'list-banks',
            'buttonPermission' => ['ListBanks', 'ViewBank'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Banco.
     *
     * Este método valida os dados do formulário, atualiza as informações do Banco no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Banco é redirecionado para a página de visualização do Banco.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editBank(): void
    {
        // Validar os dados do formulário
        $validatioBank = new ValidationBankService();
        $this->data['errors'] = $validatioBank->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewBank();
            return;
        }

        // Atualizar o Banco
        $bankUpdate = new BanksRepository();
        $result = $bankUpdate->updateBank($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
           
            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_bank_accounts',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['id'],
                    'description' => $this->data['form']['bank_name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
        

            $_SESSION['success'] = "Banco editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-bank/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Banco não editado!";
            $this->viewBank();
        }
    }
}

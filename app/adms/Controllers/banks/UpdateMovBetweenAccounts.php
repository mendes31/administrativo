<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\MovBetweenAccountsRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Services\ValidationMovBetweenAccountsService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar transferência entre contas
 *
 * Esta classe é responsável por editar as transferências entre contas bancárias. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos e execução da transferência no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes
 */
class UpdateMovBetweenAccounts
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que edita a transferência entre contas
     *
     * Este método é chamado para processar a edição da transferência entre contas. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, executa a edição. Caso contrário, carrega a
     * visualização de edição com mensagens de erro.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_mov_between_accounts', $this->data['form']['csrf_token'])) {
            // Chamar o método para editar a transferência entre contas
            $this->editTransfer($id);
        } else {
            // Recuperar o registro da transferência entre contas
            $viewTransfer = new MovBetweenAccountsRepository();
            $this->data['form'] = $viewTransfer->getMovBetweenAccounts((int) $id);

            // Mapear campos para o formulário
            if ($this->data['form']) {
                $this->data['form']['source_account_id'] = $this->data['form']['origin_id'] ?? '';
                $this->data['form']['destination_account_id'] = $this->data['form']['destination_id'] ?? '';
                $this->data['form']['value'] = $this->data['form']['amount'] ?? '';
            }

            // Verificar se a transferência entre contas foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Transferência entre contas não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Transferência entre contas não encontrada!";
                header("Location: {$_ENV['URL_ADM']}list-mov-between-accounts");
                return;
            }

            // Carregar a visualização para edição da transferência entre contas
            $this->viewTransfer();
        }
    }

    /**
     * Carregar a visualização de transferência entre contas
     * 
     * Este método configura os dados necessários e carrega a view para a transferência entre contas.
     * 
     * @return void
     */
    private function viewTransfer(): void
    {
        $model = new MovBetweenAccountsRepository();
        $this->data['accounts'] = $model->getAllAccounts();
        // var_dump($this->data['accounts']);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Transferência entre Contas',
            'menu' => 'list-mov-between-accounts',
            'buttonPermission' => ['ListMovBetweenAccounts', 'ViewTransfer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/updateMove", $this->data);
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
    private function editTransfer(): void
    {
        // Validar os dados do formulário
        $validatioTransfer = new ValidationMovBetweenAccountsService();
        $this->data['errors'] = $validatioTransfer->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewTransfer();
            return;
        }

        // Atualizar a transferência entre contas
        $transferUpdate = new MovBetweenAccountsRepository();
        $result = $transferUpdate->updateMovBetweenAccounts($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
           
            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_bank_transfers',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['id'],
                    'description' => $this->data['form']['description'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
        

            $_SESSION['success'] = "Transferência editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-transfer/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Transferência não editada!";
            $this->viewTransfer();
        }
    }
}
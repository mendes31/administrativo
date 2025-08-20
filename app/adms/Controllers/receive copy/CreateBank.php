<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationBankService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de banco
 *
 * Esta classe é responsável pelo processo de criação de novos Bancos. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Bancos no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes
 */
class CreateBank
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Banco.
     *
     * Este método é chamado para processar a criação de um novo departamento. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Banco. Caso contrário, carrega a
     * visualização de criação do Banco com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // var_dump($this->data['form']);
        // exit;

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_bank', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Departamento
            $this->addBank();
        } else {
            // Chamar o método para carregar a view de criação de Departamento
            $this->viewBank();
        }
    }

    /**
     * Carregar a visualização de criação do Banco.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Banco.
     * 
     * @return void
     */
    private function viewBank(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Banco',
            'menu' => 'list-banks',
            'buttonPermission' => ['ListBanks'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Banco ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationDepartmentsService` e,
     * se não houver erros, cria o Banco no banco de dados usando o `BanksRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addBank(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationBanks = new ValidationBankService();
        $this->data['errors'] = $validationBanks->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewBank();
            return;
        }

        // Instanciar o Repository para criar o Banco
        $bankCreate = new BanksRepository();
        $result = $bankCreate->createBank($this->data['form']);

        // Se a criação do Banco for bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_bank_accounts',
                    'action' => 'inserção',
                    'record_id' => $result,
                    'description' => $this->data['form']['bank_name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            
            // Mensagem de sucesso
            $_SESSION['success'] = "Banco cadastrado com sucesso!";

            // Redirecionar para a página de visualização do Banco recém-criado
            header("Location: {$_ENV['URL_ADM']}view-bank/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Banco não cadastrado!";

            // Recarregar a view com erro
            $this->viewBank();
        }
    }
}

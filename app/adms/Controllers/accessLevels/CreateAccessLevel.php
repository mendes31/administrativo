<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationAccessLevelService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de nível de acesso
 *
 * Esta classe é responsável pelo processo de criação de novos níveis de acesso. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do nível de acesso no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\acessLevels
 * @author Rafael Mendes
 */
class CreateAccessLevel
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do nível de acesso.
     *
     * Este método é chamado para processar a criação de um novo nível de acesso. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o nível de acesso. Caso contrário, carrega a
     * visualização de criação de nível de acesso com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_access_level', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o nível de acesso
            $this->addAccessLevel();
        } else {
            // Chamar o método para carregar a view de criação de nível de acesso
            $this->viewAccessLevel();
        }
    }

    /**
     * Carregar a visualização de criação de nível de acesso.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo nível de acesso.
     * 
     * @return void
     */
    private function viewAccessLevel(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão     
        $pageElements = [
            'title_head' => 'Cadastrar Nível de acesso',
            'menu' => 'list-access-levels',
            'buttonPermission' => ['ListAccessLevels'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accessLevels/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo nível de acesso ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationAcessLevelService` e,
     * se não houver erros, cria o nível de acesso no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addAccessLevel(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationAccessLevel = new ValidationAccessLevelService();
        $this->data['errors'] = $validationAccessLevel->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewAccessLevel();
            return;
        }

        // Instanciar o Repository para criar o nível de acesso
        $accessLevelCreate = new AccessLevelsRepository();
        $result = $accessLevelCreate->createAccessLevel($this->data['form']);
        

        // Se a criação do nível de acesso for bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_access_levels',
                    'action' => 'inserção',
                    'record_id' => $result,
                    'description' => $this->data['form']['name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            // Mensagem de sucesso
            $_SESSION['success'] = "Nível de acesso cadastrado com sucesso!";

            // Redirecionar para a página de visualização do nível de acesso recém-criado
            header("Location: {$_ENV['URL_ADM']}view-access-level/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Nível de acesso não cadastrado!";

            // Recarregar a view com erro
            $this->viewAccessLevel();
        }
    }
}

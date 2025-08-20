<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationAccessLevelService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar nível de acesso
 *
 * Esta classe é responsável por gerenciar a edição de informações de um nível de acesso existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do nível de acesso no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um nível de acesso não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\acessLevels
 * @author Rafael Mendes
 */
class UpdateAccessLevel
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o nível de acesso.
     *
     * Este método gerencia o processo de edição de um nível de acesso. Recebe os dados do formulário, valida o CSRF token e
     * a existência do nível de acesso, e chama o método adequado para editar o nível de acesso ou carregar a visualização de edição.
     *
     * @param int|string $id ID do nível de acesso a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_access_level', $this->data['form']['csrf_token'])) 
        {
            // Editar o nível de acesso
            $this->editAccessLevel();
        } else {
            // Recuperar o registro do nível de acesso
            $viewAccessLevel = new AccessLevelsRepository();
            $this->data['form'] = $viewAccessLevel->getAccessLevel((int) $id);

            // Verificar se o nível de acesso foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Nível de acesso não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Nível de acesso não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-access-levels");
                return;
            }

            // Carregar a visualização para edição do nível de acesso
            $this->viewAccessLevel();
        }
    }

    /**
     * Carregar a visualização para edição do nível de acesso.
     *
     * Este método define o título da página e carrega a visualização de edição do nível de acesso com os dados necessários.
     * 
     * @return void
     */
    private function viewAccessLevel(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão     
        $pageElements = [
            'title_head' => 'Editar Nível de Acesso',
            'menu' => 'list-access-levels',
            'buttonPermission' => ['ListAccessLevels', 'ViewAccessLevel'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accessLevels/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o nível de acesso.
     *
     * Este método valida os dados do formulário, atualiza as informações do nível de acesso no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o nível de acesso é redirecionado para a página de visualização do nível de acesso.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editAccessLevel(): void
    {
        // Validar os dados do formulário
        $validationAccessLevel = new ValidationAccessLevelService();
        $this->data['errors'] = $validationAccessLevel->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewAccessLevel();
            return;
        }

        // Atualizar o nível de acesso
        $accessLevelsUpdate = new AccessLevelsRepository();
        $result = $accessLevelsUpdate->updateAccessLevel($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_access_levels',
                    'action' => 'edição',
                    'record_id' => $result,
                    'description' => $this->data['form']['name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }


            $_SESSION['success'] = "Nível de acesso editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-access-level/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Nível de acesso não editado!";
            $this->viewAccessLevel();
        }
    }
}

<?php

namespace App\adms\Controllers\groupsPages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationGroupPageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar grupos de página
 *
 * Esta classe é responsável por gerenciar a edição de informações de um grupo de página existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do grupo de página no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um grupo de página não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\groups
 * @author Rafael Mendes
 */
class UpdateGroupPage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o grupo de página.
     *
     * Este método gerencia o processo de edição de um grupo de página. Recebe os dados do formulário, valida o CSRF token e
     * a existência do grupo de página, e chama o método adequado para editar o grupo de página ou carregar a visualização de edição.
     *
     * @param int|string $id ID do grupo de página a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do grupo de página
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_group_page', $this->data['form']['csrf_token'])) 
        {
            // Editar o grupo de página
            $this->editGroupPage();
        } else {
            // Recuperar o registro do grupo de página
            $viewGroupPage = new GroupsPagesRepository();
            $this->data['form'] = $viewGroupPage->getGroupPage((int) $id);

            // Verificar se o grupo de página foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Grupo de página não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Grupo de página não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-groups-pages");
                return;
            }

            // Carregar a visualização para edição do grupo de página
            $this->viewGroupPage();
        }
    }

    /**
     * Carregar a visualização para edição do grupo de página.
     *
     * Este método define o título da página e carrega a visualização de edição do grupo de página com os dados necessários.
     * 
     * @return void
     */
    private function viewGroupPage(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Grupo de Página',
            'menu' => 'list-groups-pages',
            'buttonPermission' => ['ListGroupsPages', 'ViewGroupPage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/groupsPages/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o grupo de página.
     *
     * Este método valida os dados do formulário, atualiza as informações do grupo de página no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o grupo de página é redirecionado para a página de visualização do grupo de página.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editGroupPage(): void
    {
        // Validar os dados do formulário
        $validationGroupPage = new ValidationGroupPageService();
        $this->data['errors'] = $validationGroupPage->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewGroupPage();
            return;
        }

        // Atualizar o grupo de página
        $groupPageUpdate = new GroupsPagesRepository();
        $result = $groupPageUpdate->updateGroupPage($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Grupo de página editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-group-page/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Grupo de página não editado!";
            $this->viewGroupPage();
        }
    }
}

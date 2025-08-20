<?php

namespace App\adms\Controllers\groupsPages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationGroupPageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de grupos
 *
 * Esta classe é responsável pelo processo de criação de novos grupos de página. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do grupo de página no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\groups
 * @author Rafael Mendes
 */
class CreateGroupPage
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação de um grupo de página.
     *
     * Este método é chamado para processar a criação de um novo grupo de página. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o grupo de página. Caso contrário, carrega a
     * visualização de criação de grupo de página com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_group_page', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o grupo de página
            $this->addGroup();
        } else {
            // Chamar o método para carregar a view de criação de grupo de página
            $this->viewGroup();
        }
    }

    /**
     * Carregar a visualização de criação de grupo de página.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo grupo de página.
     * 
     * @return void
     */
    private function viewGroup(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Grupo de Página',
            'menu' => 'list-groups-pages',
            'buttonPermission' => ['ListGroupsPages'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/groupsPages/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo grupo de página ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationGroupService` e,
     * se não houver erros, cria o grupo de página no banco de dados usando o `GroupsRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addGroup(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationGroupPage = new ValidationGroupPageService();
        $this->data['errors'] = $validationGroupPage->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewGroup();
            return;
        }

        // Instanciar o Repository para criar o grupo de página
        $groupPageCreate = new GroupsPagesRepository();
        $result = $groupPageCreate->createGroupPage($this->data['form']);

        // Se a criação do grupo de página for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Grupo de página cadastrado com sucesso!";

            // Redirecionar para a página de visualização do grupo de página recém-criado
            header("Location: {$_ENV['URL_ADM']}view-group-page/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Grupo de página não cadastrado!";

            // Recarregar a view com erro
            $this->viewGroup();
        }
    }
}

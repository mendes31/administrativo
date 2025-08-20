<?php

namespace App\adms\Controllers\groupsPages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um grupo de página
 *
 * Esta classe é responsável por exibir as informações detalhadas de um grupo de página específico. Ela recupera os dados
 * do grupo de página a partir do repositório, valida se o grupo de página existe e carrega a visualização apropriada. Se o grupo de página
 * não for encontrado, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\groups
 * @author Rafael Mendes de Oliveira
 */
class ViewGroupPage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do grupo de página.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um grupo de página específico. Ele valida o ID fornecido,
     * recupera os dados do grupo de página do repositório e carrega a visualização. Se o grupo de página não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de grupos de página.
     *
     * @param int|string $id ID do grupo de página a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Grupo de página não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Grupo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-groups");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewGroupsPages = new GroupsPagesRepository();
        $this->data['groupPage'] = $viewGroupsPages->getGroupPage((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['groupPage']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Grupo de página não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Grupo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-groups-pages");
            return;
        }

        // Registrar a visualização do grupo de página
        GenerateLog::generateLog("info", "Visualizado o grupo de página .", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Grupo de Página',
            'menu' => 'list-groups-pages',
            'buttonPermission' => ['ListGroupsPages', 'UpdateGroupPage', 'DeleteGroupPage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/groupsPages/view", $this->data);
        $loadView->loadView();
    }
}

<?php

namespace App\adms\Controllers\pages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma página
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma página específica. Ela recupera os dados
 * da página a partir do repositório, valida se a página existe e carrega a visualização apropriada. Se a página
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\pages
 * @author Rafael Mendes de Oliveira
 */
class ViewPage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da página.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma página específica. Ele valida o ID fornecido,
     * recupera os dados da página do repositório e carrega a visualização. Se a página não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de páginas.
     *
     * @param int|string $id ID da página a ser visualizada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Página não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Página não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-pages");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewPages = new PagesRepository();
        $this->data['page'] = $viewPages->getPage((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['page']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Página não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Página não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-pages");
            return;
        }

        // Registrar a visualização da página
        GenerateLog::generateLog("info", "Visualizado a página.", ['id' => (int) $id]);

        // Definir o título da página
        $this->data['title_head'] = " Página";

        // Ativar o item de menu
        $this->data['menu'] = "list-pages";

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Página',
            'menu' => 'list-pages',
            'buttonPermission' => ['ListPages', 'UpdatePage', 'DeletePage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/pages/view", $this->data);
        $loadView->loadView();
    }
}

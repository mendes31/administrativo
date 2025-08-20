<?php

namespace App\adms\Controllers\pages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Models\Repository\PagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de página
 *
 * Esta classe é responsável pelo processo de criação de novas páginas. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos e criação da página no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\pages
 * @author Rafael Mendes
 */
class CreatePage
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação da página.
     *
     * Este método é chamado para processar a criação de uma nova página. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria a página. Caso contrário, carrega a
     * visualização de criação de página com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_page', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar a página
            $this->addPage();
        } else {
            // Chamar o método para carregar a view de criação de página
            $this->viewPage();
        }
    }

    /**
     * Carregar a visualização de criação de página.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de uma nova página.
     * 
     * @return void
     */
    private function viewPage(): void
    {        
        // Instanciar o repositório para recuperar os pacotes
        $listPackagesPages = new PackagesRepository();
        $this->data['listPackagesPages'] = $listPackagesPages->getAllPackagesSelect();

        // Instanciar o repositório para recuperar os grupos
        $listgroupsPages = new GroupsPagesRepository();
        $this->data['listgroupsPages'] = $listgroupsPages->getAllGroupsPagesSelect();

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Página',
            'menu' => 'list-pages',
            'buttonPermission' => ['ListPages'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/pages/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar uma nova página ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationPageService` e,
     * se não houver erros, cria a página no banco de dados usando o `PagesRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addPage(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationPage = new ValidationPageService();
        $this->data['errors'] = $validationPage->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewPage();
            return;
        }

        // Instanciar o Repository para criar a página
        $pageCreate = new PagesRepository();
        $result = $pageCreate->createPage($this->data['form']);

        // Se a criação da página for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Página cadastrada com sucesso!";

            // Redirecionar para a página de visualização da página recém-criada
            header("Location: {$_ENV['URL_ADM']}view-page/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Página não cadastrada!";

            // Recarregar a view com erro
            $this->viewPage();
        }
    }
}

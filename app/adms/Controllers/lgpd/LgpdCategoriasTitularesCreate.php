<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCategoriaTitularService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Categoria de Titular LGPD
 *
 * Esta classe é responsável pelo processo de criação de novas Categorias de Titular LGPD. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação da Categoria no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdCategoriasTitularesCreate
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação da Categoria de Titular LGPD
     *
     * Este método é chamado para processar a criação de uma nova Categoria de Titular LGPD. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria a Categoria. Caso contrário, carrega a
     * visualização de criação da Categoria com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_categoria_titular', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar a Categoria de Titular            
            $this->addCategoriaTitular();
        } else {
            // Chamar o método para carregar a view de criação de Categoria de Titular 
            $this->viewCategoriaTitular();
        }
    }

    /**
     * Carregar a visualização de criação da Categoria de Titular
     * 
     * Este método configura os dados necessários e carrega a view para a criação de uma nova Categoria de Titular
     * 
     * @return void
     */
    private function viewCategoriaTitular(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Categoria de Titular LGPD',
            'menu' => 'lgpd-categorias-titulares',
            'buttonPermission' => ['LgpdCategoriasTitulares'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/categorias-titulares/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar uma nova Categoria de Titular LGPD ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationCategoriaTitularService` e,
     * se não houver erros, cria a categoria no banco de dados usando o `LgpdCategoriasTitularesRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addCategoriaTitular(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationCategoriaTitular = new ValidationCategoriaTitularService();
        $this->data['errors'] = $validationCategoriaTitular->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewCategoriaTitular();
            return;
        }

        // Instanciar o Repository para criar a Categoria de Titular        
        $categoriaTitularCreate = new LgpdCategoriasTitularesRepository();
        $result = $categoriaTitularCreate->create($this->data['form']);

        // Se a criação da Categoria de Titular for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Categoria de Titular cadastrada com sucesso!";

            // Redirecionar para a página de visualização da categoria recém-criada
            header("Location: {$_ENV['URL_ADM']}view-lgpd-categorias-titulares/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Categoria de Titular não cadastrada!";

            // Recarregar a view com erro
            $this->viewCategoriaTitular();
        }
    }
} 
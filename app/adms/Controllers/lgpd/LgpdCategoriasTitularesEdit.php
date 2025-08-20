<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCategoriaTitularService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Categoria de Titular LGPD
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Categoria de Titular LGPD existente. Inclui a validação dos dados
 * do formulário, a atualização das informações da Categoria no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como uma Categoria não encontrada ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdCategoriasTitularesEdit
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a Categoria de Titular LGPD.
     *
     * Este método gerencia o processo de edição de uma Categoria de Titular LGPD. Recebe os dados do formulário, valida o CSRF token e
     * a existência da Categoria, e chama o método adequado para editar a Categoria ou carregar a visualização de edição.
     *
     * @param int|string $id ID da Categoria a ser editada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da categoria
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_edit_categoria_titular', $this->data['form']['csrf_token'])) 
        {
            // Editar a Categoria
            $this->editCategoriaTitular();
        } else {
            // Recuperar o registro da Categoria
            $viewCategoriaTitular = new LgpdCategoriasTitularesRepository();
            $this->data['form'] = $viewCategoriaTitular->getById((int) $id);

            // Verificar se a Categoria foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Categoria de Titular não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Categoria de Titular não encontrada!";
                header("Location: {$_ENV['URL_ADM']}lgpd-categorias-titulares");
                return;
            }

            // Carregar a visualização para edição da Categoria
            $this->viewCategoriaTitular();
        }
    }

    /**
     * Carregar a visualização para edição da Categoria de Titular.
     *
     * Este método define o título da página e carrega a visualização de edição da Categoria com os dados necessários.
     * 
     * @return void
     */
    private function viewCategoriaTitular(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Categoria de Titular LGPD',
            'menu' => 'lgpd-categorias-titulares',
            'buttonPermission' => ['LgpdCategoriasTitulares', 'ViewLgpdCategoriasTitulares'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/categorias-titulares/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a Categoria de Titular LGPD.
     *
     * Este método valida os dados do formulário, atualiza as informações da Categoria no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização da Categoria.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editCategoriaTitular(): void
    {
        // Validar os dados do formulário
        $validationCategoriaTitular = new ValidationCategoriaTitularService();
        $this->data['errors'] = $validationCategoriaTitular->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewCategoriaTitular();
            return;
        }

        // Atualizar a Categoria
        $categoriaTitularUpdate = new LgpdCategoriasTitularesRepository();
        $result = $categoriaTitularUpdate->update($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Categoria de Titular editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-lgpd-categorias-titulares/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Categoria de Titular não editada!";
            $this->viewCategoriaTitular();
        }
    }
} 
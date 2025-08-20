<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma Categoria de Titular LGPD
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma Categoria de Titular LGPD específica. Ela recupera os dados
 * da Categoria a partir do repositório, valida se a Categoria existe e carrega a visualização apropriada. Se a Categoria
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes de Oliveira
 */
class LgpdCategoriasTitularesView
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da Categoria de Titular LGPD.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma Categoria de Titular LGPD específica. Ele valida o ID fornecido,
     * recupera os dados da Categoria do repositório e carrega a visualização. Se a Categoria não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Categorias.
     *
     * @param int|string $id ID da Categoria a ser visualizada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Categoria de Titular não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Categoria de Titular não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-categorias-titulares");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewCategoriasTitulares = new LgpdCategoriasTitularesRepository();
        $this->data['categoria_titular'] = $viewCategoriasTitulares->getById((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['categoria_titular']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Categoria de Titular não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Categoria de Titular não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-categorias-titulares");
            return;
        }

        // Registrar a visualização da Categoria
        GenerateLog::generateLog("info", "Visualizada a Categoria de Titular.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Categoria de Titular LGPD',
            'menu' => 'lgpd-categorias-titulares',
            'buttonPermission' => ['LgpdCategoriasTitulares', 'UpdateLgpdCategoriasTitulares', 'DeleteLgpdCategoriasTitulares'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/categorias-titulares/view", $this->data);
        $loadView->loadView();
    }
} 
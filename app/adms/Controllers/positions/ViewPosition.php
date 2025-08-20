<?php

namespace App\adms\Controllers\positions;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Cargo
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Cargo específico. Ela recupera os dados
 * do Cargo a partir do repositório, valida se o Cargo existe e carrega a visualização apropriada. Se o Cargo
 * não for encontrado, uma mensagem de erro é exibida e o Cargo é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\departments
 * @author Rafael Mendes de Oliveira
 */
class ViewPosition
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Cargo.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Cargo específico. Ele valida o ID fornecido,
     * recupera os dados do Cargo do repositório e carrega a visualização. Se o Cargo não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Cargo.
     *
     * @param int|string $id ID do Cargo a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Cargo não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Cargo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-positions");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewPositions = new PositionsRepository();
        $this->data['positions'] = $viewPositions->getPosition((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['positions']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Cargo não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Cargo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-positions");
            return;
        }

        // Registrar a visualização do Cargo
        GenerateLog::generateLog("info", "Visualizado o Cargo.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Cargo',
            'menu' => 'list-positions',
            'buttonPermission' => ['ListPositions', 'UpdatePosition', 'DeletePosition'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/positions/view", $this->data);
        $loadView->loadView();
    }
}

<?php

namespace App\adms\Controllers\departments;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um departamento
 *
 * Esta classe é responsável por exibir as informações detalhadas de um departamento específico. Ela recupera os dados
 * do departamento a partir do repositório, valida se o departamento existe e carrega a visualização apropriada. Se o departamento
 * não for encontrado, uma mensagem de erro é exibida e o departamento é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\departments
 * @author Rafael Mendes de Oliveira
 */
class ViewDepartment
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do departamento.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um departamento específico. Ele valida o ID fornecido,
     * recupera os dados do departamento do repositório e carrega a visualização. Se o departamento não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de departamento.
     *
     * @param int|string $id ID do departamento a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Departamento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Departamento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-departments");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewDepartments = new DepartmentsRepository();
        $this->data['departments'] = $viewDepartments->getDepartment((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['departments']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Departamento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Departamento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-departments");
            return;
        }

        // Registrar a visualização do departamento
        GenerateLog::generateLog("info", "Visualizado o departamento.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Departamento',
            'menu' => 'list-departments',
            'buttonPermission' => ['ListDepartments', 'UpdateDepartments', 'DeleteDepartment'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/departments/view", $this->data);
        $loadView->loadView();
    }
}

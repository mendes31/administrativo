<?php

namespace App\adms\Controllers\departments;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationDepartmentService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Departamento
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Departamento existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Departamento no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Departamento não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\departments;
 * @author Rafael Mendes
 */
class UpdateDepartments
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Departamento.
     *
     * Este método gerencia o processo de edição de um Departamento. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Departamento, e chama o método adequado para editar o Departamento ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Departamento a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_departments', $this->data['form']['csrf_token'])) 
        {
            // Editar o Departamento
            $this->editDepartment();
        } else {
            // Recuperar o registro do Departamento
            $viewDepartment = new DepartmentsRepository();
            $this->data['form'] = $viewDepartment->getDepartment((int) $id);

            // Verificar se o Departamento foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Departamento não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Departamento não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-departments");
                return;
            }

            // Carregar a visualização para edição do Departamento
            $this->viewDepartment();
        }
    }

    /**
     * Carregar a visualização para edição do Departamento.
     *
     * Este método define o título da página e carrega a visualização de edição do Departamento com os dados necessários.
     * 
     * @return void
     */
    private function viewDepartment(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Departamento',
            'menu' => 'list-departments',
            'buttonPermission' => ['ListDepartments', 'ViewDepartment'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/departments/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Departamento.
     *
     * Este método valida os dados do formulário, atualiza as informações do Departamento no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Departamento é redirecionado para a página de visualização do Departamento.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editDepartment(): void
    {
        // Validar os dados do formulário
        $validationDepartment = new ValidationDepartmentService();
        $this->data['errors'] = $validationDepartment->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewDepartment();
            return;
        }

        // Atualizar o Departamento
        $departmentUpdate = new DepartmentsRepository();
        $result = $departmentUpdate->updateDepartment($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Departamento editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-department/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Departamento não editado!";
            $this->viewDepartment();
        }
    }
}

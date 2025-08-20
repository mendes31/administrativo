<?php

namespace App\adms\Controllers\departments;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationDepartmentService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de departamentos
 *
 * Esta classe é responsável pelo processo de criação de novos departamentos. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do departamentos no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\departments
 * @author Rafael Mendes
 */
class CreateDepartment
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do departamento.
     *
     * Este método é chamado para processar a criação de um novo departamento. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o departamento. Caso contrário, carrega a
     * visualização de criação do departamento com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_department', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Departamento
            $this->addDepartment();
        } else {
            // Chamar o método para carregar a view de criação de Departamento
            $this->viewDepartment();
        }
    }

    /**
     * Carregar a visualização de criação do Departamento.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Departamento.
     * 
     * @return void
     */
    private function viewDepartment(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Departamento',
            'menu' => 'list-departments',
            'buttonPermission' => ['ListDepartment'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/departments/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo departametno ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationDepartmentsService` e,
     * se não houver erros, cria o departametno no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addDepartment(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationDepartments = new ValidationDepartmentService();
        $this->data['errors'] = $validationDepartments->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewDepartment();
            return;
        }

        // Instanciar o Repository para criar o departamento
        $departmentCreate = new DepartmentsRepository();
        $result = $departmentCreate->createDepartment($this->data['form']);

        // Se a criação do departamento for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Departametno cadastrado com sucesso!";

            // Redirecionar para a página de visualização do departametno recém-criado
            header("Location: {$_ENV['URL_ADM']}view-department/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Departametno não cadastrado!";

            // Recarregar a view com erro
            $this->viewDepartment();
        }
    }
}

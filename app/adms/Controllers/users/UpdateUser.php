<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserRakitService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

// Reforço do carregamento do .env
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

//var_dump($_ENV);
// exit;

/**
 * Controller para editar usuário
 *
 * Esta classe é responsável por gerenciar a edição de informações de um usuário existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do usuário no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um usuário não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class UpdateUser
{
    // /** @var array|null $dataForm Recebe os dados do FORMULARIO */
    // private array|null $dataForm;

    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o usuário.
     *
     * Este método gerencia o processo de edição de um usuário. Recebe os dados do formulário, valida o CSRF token e
     * a existência do usuário, e chama o método adequado para editar o usuário ou carregar a visualização de edição.
     *
     * @param int|string $id ID do usuário a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {

        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for valido o CSRF
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_update_user', $this->data['form']['csrf_token'])) {

            // Chamar o método editar
            $this->editUser();
           

        } else {
            
            // Instanciar o Repository para recuperar o registro do banco de dados
            $viewUser = new UsersRepository();
            $this->data['form'] = $viewUser->getUser((int) $id);

            // Verificar se existe o registro no banco de dados
            if (!$this->data['form']) {

                // Chamar o método para salvar o log
                GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $id]);

                // Criar a mensagem de erro 
                $_SESSION['error'] = "Usuário não encontrado.";

                // Redirecionar o usuário para página listar
                header("Location: {$_ENV['URL_ADM']}list-users");
                return;
            }

            // Chamar método carregar a view
            $this->viewUser();
        }
    }

    /**
     * Carregar a visualização para edição do usuário.
     *
     * Este método define o título da página e carrega a visualização de edição do usuário com os dados necessários.
     * 
     * @return void
     */
    private function viewUser(): void
    {
        // Instanciar o repositório para recuperar os departamentos
        $listDepartments = new DepartmentsRepository();
        $this->data['listDepartments'] = $listDepartments->getAllDepartmentsSelect();

        // Instanciar o repositório para recuperar os cargos
        $listPositions = new PositionsRepository();
        $this->data['listPositions'] = $listPositions->getAllPositionsSelect();

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Usuário',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers', 'ViewUser'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/update", $this->data);
        $loadView->loadView();
    }

   /**
     * Editar o usuário.
     *
     * Este método valida os dados do formulário, atualiza as informações do usuário no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização do usuário.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editUser(): void 
    {
        // Instanciar a classe validar os dados do formulário
        $validationUser = new ValidationUserRakitService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Capturar dados antigos para verificar mudanças
        $userUpdate = new UsersRepository();
        $userAntigo = $userUpdate->getUser($this->data['form']['id']);
        $statusAnterior = $userAntigo['status'] ?? 'Ativo';
        $cargoAnterior = $userAntigo['user_position_id'] ?? null;

        // Instanciar Repository para editar o usuário
        $form = $this->data['form'];
        $form['data_nascimento'] = $_POST['data_nascimento'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($form['id'])) {
            $uploadDir = 'public/adms/uploads/users/' . $form['id'] . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('user_') . '.' . $ext;
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $form['image'] = 'users/' . $form['id'] . '/' . $fileName;
            }
        }
        $this->data['form'] = $form;
        // Normalização dos campos booleanos
        $form['status'] = isset($form['status']) && $form['status'] === 'Ativo' ? 'Ativo' : 'Inativo';
        $form['bloqueado'] = isset($form['bloqueado']) && $form['bloqueado'] === 'Sim' ? 'Sim' : 'Não';
        $form['senha_nunca_expira'] = isset($form['senha_nunca_expira']) && $form['senha_nunca_expira'] === 'Sim' ? 'Sim' : 'Não';
        $form['modificar_senha_proximo_logon'] = isset($form['modificar_senha_proximo_logon']) && $form['modificar_senha_proximo_logon'] === 'Sim' ? 'Sim' : 'Não';
        $this->data['form'] = $form;
        $result = $userUpdate->updateUser($this->data['form']);

        // Forçar logout se admin inativar ou bloquear o usuário
        if (
            (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Inativo') ||
            (isset($this->data['form']['bloqueado']) && $this->data['form']['bloqueado'] === 'Sim')
        ) {
            $securityService = new \App\adms\Controllers\Services\SecurityService();
            $securityService->forcarLogoutUsuario($this->data['form']['id']);
        }

        // Acessa o IF se o repository retornou TRUE
        if($result){
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            
            // Verificar mudanças de status do usuário
            $statusNovo = $form['status'];
            
            if ($statusAnterior === 'Ativo' && $statusNovo === 'Inativo') {
                // Usuário foi inativado - remover vínculos ativos
                $trainingUsersRepo = new \App\adms\Models\Repository\TrainingUsersRepository();
                $trainingUsersRepo->removeActiveLinksByUser($form['id']);
                
                // Log da ação
                \App\adms\Helpers\GenerateLog::generateLog(
                    "info", 
                    "Usuário inativado - vínculos removidos", 
                    [
                        'user_id' => $form['id'],
                        'user_name' => $userAntigo['name'] ?? '',
                        'admin_user_id' => $_SESSION['user_id'] ?? 0
                    ]
                );
            } elseif ($statusAnterior === 'Inativo' && $statusNovo === 'Ativo') {
                // Usuário foi reativado - recriar vínculos necessários
                $results = $matrixService->recreateLinksForReactivatedUser($form['id']);
                
                // Log da ação
                \App\adms\Helpers\GenerateLog::generateLog(
                    "info", 
                    "Usuário reativado - vínculos recriados", 
                    [
                        'user_id' => $form['id'],
                        'user_name' => $userAntigo['name'] ?? '',
                        'admin_user_id' => $_SESSION['user_id'] ?? 0,
                        'trainings_added' => $results['trainings_added'],
                        'trainings_skipped' => $results['trainings_skipped'],
                        'reciclagem_added' => $results['reciclagem_added']
                    ]
                );
            } else {
                // Verificar se o cargo foi alterado
                $cargoNovo = $form['user_position_id'] ?? null;
                
                if ($cargoAnterior !== $cargoNovo) {
                    // Cargo foi alterado - verificar e corrigir vínculos
                    $results = $matrixService->checkAndFixUserLinks($form['id']);
                    
                    // Log da ação
                    \App\adms\Helpers\GenerateLog::generateLog(
                        "info", 
                        "Cargo do usuário alterado - vínculos corrigidos", 
                        [
                            'user_id' => $form['id'],
                            'user_name' => $userAntigo['name'] ?? '',
                            'cargo_anterior' => $cargoAnterior,
                            'cargo_novo' => $cargoNovo,
                            'admin_user_id' => $_SESSION['user_id'] ?? 0,
                            'converted_to_cargo' => $results['converted_to_cargo'],
                            'kept_individual' => $results['kept_individual'],
                            'no_changes' => $results['no_changes']
                        ]
                    );
                } else {
                    // Atualização normal - atualizar matriz
                    $matrixService->updateMatrixForUser($form['id']);
                }
            }
            
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Usuário editado com suscesso!";

            // Redirecionar o usuário para a pagina view - visualizar usuario
            header("Location: {$_ENV['URL_ADM']}view-user/{$form['id']}");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Usuário não editado!";

            // Chamar método carregar a view
            $this->viewUser();
        }

    }
}

<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserRakitService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SuperUsuarioAccessLevelsSyncService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de usuário
 *
 * Esta classe é responsável pelo processo de criação de novos usuários. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do usuário no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class CreateUser
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do usuário.
     *
     * Este método é chamado para processar a criação de um novo usuário. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o usuário. Caso contrário, carrega a
     * visualização de criação de usuário com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é valido
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_create_user', $this->data['form']['csrf_token'])) {

            // Chamar o método para cadastrar o usuário 
            $this->addUser();
        } else {
            // Chamar método carregar a view de criação de usuário
            $this->viewUser();
        }
    }

    /**
     * Carregar a visualização de criação de usuário.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo usuário.
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
        
        // Lista de usuários ativos para selecionar como supervisor
        $usersRepo = new UsersRepository();
        $this->data['listSupervisors'] = $usersRepo->getAllUsersSelect();
 
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Usuários',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $this->data['can_manage_super_usuario_on_create'] = UserAccessHelper::canManageSuperUsuarioForOthers();

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo usuário ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationUserRakitService` e,
     * se não houver erros, cria o usuário no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addUser(): void
    {
        // Antes de validar, tratar geração automática de senha, se solicitado,
        // para que o validador enxergue os campos preenchidos.
        $form = $this->data['form'];

        // Salvar data de nascimento e admissão em $form (usados em outros pontos)
        $form['data_nascimento'] = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ($form['data_nascimento'] ?? null);
        $form['data_admissao']   = !empty($_POST['data_admissao'])   ? $_POST['data_admissao']   : ($form['data_admissao']   ?? null);

        $plainPassword = null;
        if (!empty($form['gerar_senha']) && $form['gerar_senha'] === '1' && !empty($form['data_nascimento'])) {
            $ts = strtotime($form['data_nascimento']);
            if ($ts !== false) {
                $plainPassword = date('dmY', $ts);
                $form['password'] = $plainPassword;
                $form['confirm_password'] = $plainPassword;
            }
        }

        $this->data['form'] = $form;

        // Instanciar a classe validar os dados do formulário com Rakit
        $validationUser = new ValidationUserRakitService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Instanciar o Repository para Criar o usuário
        $form = $this->data['form'];
        // Normalização dos campos booleanos
        $form['status'] = isset($form['status']) && $form['status'] === 'Ativo' ? 'Ativo' : 'Inativo';
        $form['bloqueado'] = isset($form['bloqueado']) && $form['bloqueado'] === 'Sim' ? 'Sim' : 'Não';
        $form['senha_nunca_expira'] = isset($form['senha_nunca_expira']) && $form['senha_nunca_expira'] === 'Sim' ? 'Sim' : 'Não';
        $form['modificar_senha_proximo_logon'] = isset($form['modificar_senha_proximo_logon']) && $form['modificar_senha_proximo_logon'] === 'Sim' ? 'Sim' : 'Não';
        if (!UserAccessHelper::canManageSuperUsuarioForOthers()) {
            if (!empty($form['super_usuario'])) {
                GenerateLog::generateLog('warning', 'CreateUser: POST super_usuario sem privilégio de gestão total (forçado a 0).', [
                    'actor_id' => (int)($_SESSION['user_id'] ?? 0),
                ]);
            }
            $form['super_usuario'] = 0;
        } else {
            $form['super_usuario'] = !empty($form['super_usuario']) ? 1 : 0;
        }
        $form['sexo'] = UserFormHelper::normalizeSexo($form['sexo'] ?? '');
        $form['filhos'] = UserFormHelper::normalizeFilhos($form['filhos'] ?? '');
        // $form['data_nascimento'] e $form['data_admissao'] já foram preenchidos antes da validação
        // Flags de mensagem de boas-vindas
        $form['enviar_boas_vindas_email'] = !empty($form['enviar_boas_vindas_email']) ? 1 : 0;
        $form['enviar_boas_vindas_whatsapp'] = !empty($form['enviar_boas_vindas_whatsapp']) ? 1 : 0;

        // Processar upload da imagem
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/adms/uploads/users/';
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('user_') . '.' . $ext;
            $destPath = $uploadDir . $fileName;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $form['image'] = 'users/' . $fileName;
            }
        }

        $this->data['form'] = $form;
        $userCreate = new UsersRepository();
        $result = $userCreate->createUser($this->data['form']);

        // Se houve upload de imagem e o usuário foi criado, mova a imagem para a subpasta do usuário
        if ($result && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/adms/uploads/users/' . $result . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('user_') . '.' . $ext;
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                // Atualize o caminho da imagem no banco para users/{id}/arquivo.png
                $userCreate->updateUser([
                    'id' => $result,
                    'image' => 'users/' . $result . '/' . $fileName,
                    // outros campos obrigatórios para updateUser
                    'name' => $this->data['form']['name'],
                    'email' => $this->data['form']['email'],
                    'username' => $this->data['form']['username'],
                    'user_department_id' => $this->data['form']['user_department_id'],
                    'user_position_id' => $this->data['form']['user_position_id'],
                ]);
            }
        }

        // Acessa o IF se o repository retornou TRUE (retorna ID do novo usuário)
        if ($result) {
            $newUserSuperFlag = ((int) ($form['super_usuario'] ?? 0) === 1) ? 1 : 0;
            $superLevelsSyncFailed = false;
            if ($newUserSuperFlag === 1) {
                try {
                    (new SuperUsuarioAccessLevelsSyncService())->sync((int) $result, 0, 1);
                } catch (\Throwable $e) {
                    $superLevelsSyncFailed = true;
                }
            }
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForUser($result);

            // Enviar mensagem de boas-vindas conforme flags
            try {
                $createdUser = $userCreate->getUser((int)$result);
                if ($createdUser) {
                    // Mesclar flags usados na criação (pois getUser não traz as colunas novas ainda em algumas versões)
                    $createdUser['enviar_boas_vindas_email'] = $form['enviar_boas_vindas_email'] ?? 0;
                    $createdUser['enviar_boas_vindas_whatsapp'] = $form['enviar_boas_vindas_whatsapp'] ?? 0;
                    $createdUser['celular'] = $form['celular'] ?? ($createdUser['celular'] ?? '');
                    \App\adms\Controllers\Services\WelcomeMessageService::sendForNewUser(
                        $createdUser,
                        isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
                        'welcome',
                        $plainPassword
                    );
                }
            } catch (\Throwable $e) {
                \App\adms\Helpers\GenerateLog::generateLog('error', 'Erro ao enviar mensagem de boas-vindas.', [
                    'user_id' => $result,
                    'exception' => $e->getMessage(),
                ]);
            }

            // Criar a mensagem de sucesso
            $_SESSION['success'] = $superLevelsSyncFailed
                ? 'Usuário cadastrado, porém falhou a sincronização dos níveis de acesso (super usuário). Ajuste manualmente na visualização do usuário ou contacte o suporte.'
                : 'Usuário cadastrado com sucesso!';

            // Redirecionar o usuário para a pagina listar
            header("Location: {$_ENV['URL_ADM']}view-user/$result");
            return;
        } else {
            // Criar a mensagem de erro
            // $_SESSION['error'] = "Usuário não cadastrado!";
            $this->data['errors'][] = "Usuário não cadastrado!";

            // Chamar método carregar a view
            $this->viewUser();
        }
    }
}

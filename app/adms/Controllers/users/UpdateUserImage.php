<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UserImageRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar imagem do usuário
 *
 * Esta classe é responsável por gerenciar a edição imagem de um usuário existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do usuário no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um usuário não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class UpdateUserImage
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a imagem do usuário.
     *
     * Este método gerencia o processo de edição da imagem de um usuário. Recebe os dados do formulário, valida o CSRF token e
     * a existência do usuário, e chama o método adequado para editar a imagem ou carregar a visualização de edição.
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
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_update_user_image', $this->data['form']['csrf_token'])) {
            
            // Chamar o método editar
            $this->editUser();

        } else {
            
            // Instanciar o Repository para recuperar o registro do banco de dados
            $viewUser = new \App\adms\Models\Repository\UsersRepository();
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
     * Carregar a visualização para edição da imagem do usuário.
     *
     * Este método define o título da página e carrega a visualização de edição da imagem com os dados necessários.
     * 
     * @return void
     */
    private function viewUser(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Imagem do Usuário',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers', 'ViewUser'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar informações do usuário para o cabeçalho
        $this->data['user_info'] = [
            'id' => $this->data['form']['id'],
            'name' => $this->data['form']['name'],
            'email' => $this->data['form']['email']
        ];

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/updateUserImage", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a imagem do usuário.
     *
     * Este método valida os dados do formulário, atualiza a imagem do usuário no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização do usuário.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editUser(): void 
    {
        // Adicionar a imagem aos dados do formulário
        $this->data['form']['image'] = $_FILES['image'] ?? null;

        // Validação da imagem
        error_log(
            'UPLOAD DEBUG - controller: type=' . ($_FILES['image']['type'] ?? 'null') .
            ' size=' . ($_FILES['image']['size'] ?? 'null') .
            ' error=' . ($_FILES['image']['error'] ?? 'null') .
            ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = "Erro: Necessário selecionar uma imagem válida!";
            $this->viewUser();
            return;
        }
        
        // Verificar tipo de arquivo (ampliado p/ maior compatibilidade em desktops)
        $allowedTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/pjpeg',
            'image/jfif',
            'image/webp',
            'image/gif',
            'image/heic',
            'image/heif',
            'image/avif',
        ];
        if (!in_array($_FILES['image']['type'], $allowedTypes, true)) {
            error_log('UPLOAD DEBUG - controller: tipo não suportado recebido=' . ($_FILES['image']['type'] ?? 'null'));
            $this->data['errors'][] = "Erro: Formato de imagem não suportado (" . htmlspecialchars($_FILES['image']['type']) . ")! Use PNG, JPG, JPEG, JFIF, WEBP, GIF, HEIC, HEIF ou AVIF.";
            $this->viewUser();
            return;
        }
        
        // Verificar tamanho (10MB para compatibilidade)
        if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
            error_log('UPLOAD DEBUG - controller: arquivo acima do limite size=' . ($_FILES['image']['size'] ?? 'null'));
            $this->data['errors'][] = "Erro: Imagem muito grande! Máximo 10MB.";
            $this->viewUser();
            return;
        }

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }
        
        // Instanciar Repository específico para imagem
        $updateUserImage = new UserImageRepository();
        $result = $updateUserImage->updateUserImage($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Imagem do usuário editada com sucesso!";

            // Redirecionar o usuário para a pagina view - visualizar usuario
            header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['id']}");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Imagem do usuário não foi editada!";

            // Chamar método carregar a view
            $this->viewUser();
        }
    }
}

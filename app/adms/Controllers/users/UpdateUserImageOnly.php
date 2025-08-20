<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UserImageRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller específico para alteração de imagem do usuário
 *
 * Esta classe é responsável exclusivamente por gerenciar a alteração de imagem de um usuário.
 * Desvinculada do updateUser para ter controle total sobre o processo de imagem.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class UpdateUserImageOnly
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Alterar a imagem do usuário.
     *
     * Este método gerencia exclusivamente o processo de alteração de imagem.
     * Valida o CSRF token, processa o upload e atualiza o banco.
     *
     * @param int|string $id ID do usuário a ser alterado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for valido o CSRF
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_update_user_image_only', $this->data['form']['csrf_token'])) {
            
            // Chamar o método para alterar a imagem
            $this->updateUserImage($id);

        } else {
            
            // Instanciar o Repository para recuperar o registro do banco de dados
            $userImageRepo = new UserImageRepository();
            $this->data['form'] = $userImageRepo->getUser((int) $id);

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
     * Carregar a visualização para alteração da imagem.
     *
     * Este método define o título da página e carrega a visualização específica para alteração de imagem.
     * 
     * @return void
     */
    private function viewUser(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Alterar Imagem do Usuário',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers', 'ViewUser'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/updateUserImageOnly", $this->data);
        $loadView->loadView();
    }

    /**
     * Alterar a imagem do usuário.
     *
     * Este método processa o upload da nova imagem, remove a antiga e atualiza o banco.
     * 
     * @param int|string $id ID do usuário.
     * @return void
     */
    private function updateUserImage(int|string $id): void 
    {
        // Adicionar o ID aos dados do formulário
        $this->data['form']['id'] = $id;
        
        // Adicionar a imagem aos dados do formulário
        $this->data['form']['image'] = $_FILES['image'] ?? null;

        // Instanciar Repository específico para imagem
        $userImageRepo = new UserImageRepository();
        $result = $userImageRepo->updateUserImage($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Imagem do usuário alterada com sucesso!";

            // Redirecionar o usuário para a pagina view - visualizar usuario
            header("Location: {$_ENV['URL_ADM']}view-user/{$id}");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Imagem do usuário não foi alterada!";

            // Chamar método carregar a view
            $this->viewUser();
        }
    }
}

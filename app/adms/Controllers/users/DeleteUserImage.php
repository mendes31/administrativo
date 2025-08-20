<?php

namespace App\adms\Controllers\users;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\UserImageRepository;

class DeleteUserImage
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar CSRF e ID
        if (!isset($this->data['form']['csrf_token']) || !CSRFHelper::validateCSRFToken('form_delete_user_image', $this->data['form']['csrf_token']) || !$id) {
            GenerateLog::generateLog("error", "Requisição inválida para deleção de imagem de usuário.", ['id' => $id]);
            $_SESSION['error'] = "Requisição inválida.";
            header("Location: {$_ENV['URL_ADM']}view-user/$id");
            return;
        }

        $userRepo = new UsersRepository();
        $user = $userRepo->getUser((int)$id);
        if (!$user) {
            GenerateLog::generateLog("error", "Usuário não encontrado ao tentar deletar imagem.", ['id' => $id]);
            $_SESSION['error'] = "Usuário não encontrado.";
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Remover arquivo físico se existir
        if (!empty($user['image']) && $user['image'] !== 'icon_user.png') {
            // Usar UserImageRepository para deletar a imagem
            $userImageRepo = new UserImageRepository();
            $userImageRepo->deleteUserImage((int)$id, $user['image']);
        }

        // Limpar campo image no banco (definir como icon_user.png)
        $result = $userRepo->updateUserImageOnly([
            'id' => $id,
            'image' => 'icon_user.png'
        ]);
        
        if (!$result) {
            error_log("DeleteUserImage: Erro ao atualizar banco para usuário ID: " . $id);
            $_SESSION['error'] = "Erro ao atualizar banco de dados.";
            header("Location: {$_ENV['URL_ADM']}view-user/$id");
            return;
        }

        GenerateLog::generateLog("info", "Imagem de usuário deletada com sucesso.", ['id' => $id]);
        $_SESSION['success'] = "Imagem removida com sucesso.";
        header("Location: {$_ENV['URL_ADM']}view-user/$id");
        return;
    }
} 
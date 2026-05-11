<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativosPermissionService;

class RemoveInformativoImagem
{
    public function index($id)
    {
        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);
        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $perm = new ButtonPermissionUserRepository();
        $btn = $perm->buttonPermission(['UpdateInformativo']);
        $canUpdate = is_array($btn) && count($btn) > 0;
        if (!$canUpdate) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você não tem permissão para alterar este informativo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-informativo/' . (int)$id);
            exit;
        }

        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você não tem permissão para alterar este informativo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        if ($informativo && !empty($informativo['imagem'])) {
            $basePath = dirname(__DIR__, 4);
            $imgPath = $basePath . '/public/adms/uploads/' . $informativo['imagem'];
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
            // Atualizar todos os campos, apenas 'imagem' como null
            $informativo['imagem'] = null;
            $repo->updateInformativo($id, $informativo);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Imagem removida com sucesso!</div>';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'update-informativo/' . $id);
        exit;
    }
} 
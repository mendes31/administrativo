<?php

namespace App\adms\Controllers\informativos;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativosPermissionService;

class DeleteInformativo
{
    public function index(string|int $id = null)
    {
        // Permitir receber o ID tanto pela URL quanto pelo POST (formulário da modal)
        if (!$id && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        // Validar CSRF quando vier via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (empty($csrf) || !CSRFHelper::validateCSRFToken('form_delete_informativo', $csrf)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token CSRF inválido ou expirado ao tentar excluir o informativo.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
                exit;
            }
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você não tem permissão para excluir este informativo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $baseUploads = dirname(__DIR__, 4) . '/public/adms/uploads/';

        try {
            $success = $repo->deleteInformativo((int)$id);
            
            if ($success) {
                // Remover arquivos físicos se existirem
                if (!empty($informativo['imagem'])) {
                    $imagemPath = $baseUploads . $informativo['imagem'];
                    if (file_exists($imagemPath)) {
                        unlink($imagemPath);
                    }
                }
                
                if (!empty($informativo['anexo'])) {
                    $anexoPath = $baseUploads . $informativo['anexo'];
                    if (file_exists($anexoPath)) {
                        unlink($anexoPath);
                    }
                }
                
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo excluído com sucesso!</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao excluir informativo!</div>';
            }
        } catch (\Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao excluir informativo.', [
                'informativo_id' => (int) $id,
                'exception' => $e->getMessage(),
            ]);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao excluir informativo: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
        exit;
    }
}
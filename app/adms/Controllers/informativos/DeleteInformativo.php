<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;

class DeleteInformativo
{
    public function index(string|int $id = null)
    {
        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        try {
            $success = $repo->deleteInformativo((int)$id);
            
            if ($success) {
                // Remover arquivos físicos se existirem
                if (!empty($informativo['imagem'])) {
                    $imagemPath = __DIR__ . '/../../../public/adms/' . $informativo['imagem'];
                    if (file_exists($imagemPath)) {
                        unlink($imagemPath);
                    }
                }
                
                if (!empty($informativo['anexo'])) {
                    $anexoPath = __DIR__ . '/../../../public/adms/' . $informativo['anexo'];
                    if (file_exists($anexoPath)) {
                        unlink($anexoPath);
                    }
                }
                
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo excluído com sucesso!</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao excluir informativo!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao excluir informativo: ' . $e->getMessage() . '</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
        exit;
    }
} 
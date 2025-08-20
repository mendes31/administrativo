<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;

class RemoveInformativoImagem
{
    public function index($id)
    {
        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);
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
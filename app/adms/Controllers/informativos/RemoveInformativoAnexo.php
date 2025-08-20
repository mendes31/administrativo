<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;

class RemoveInformativoAnexo
{
    public function index($id)
    {
        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);
        if ($informativo && !empty($informativo['anexo'])) {
            $basePath = dirname(__DIR__, 4);
            $anexoPath = $basePath . '/public/adms/uploads/' . $informativo['anexo'];
            if (file_exists($anexoPath)) {
                unlink($anexoPath);
            }
            // Atualizar todos os campos, apenas 'anexo' como null
            $informativo['anexo'] = null;
            $repo->updateInformativo($id, $informativo);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Anexo removido com sucesso!</div>';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'update-informativo/' . $id);
        exit;
    }
} 
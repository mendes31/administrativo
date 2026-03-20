<?php

namespace App\adms\Controllers\serveFile;

use App\adms\Controllers\Services\FileServer;

class ServeFile
{
    public function index()
    {
        $path = $_GET['path'] ?? '';
        // Não gravar log em disco a cada imagem — com várias miniaturas na dashboard isso deixa o carregamento muito lento.
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
            error_log('[ServeFile] path=' . $path);
        }
        $fileServer = new FileServer();
        $fileServer->serveFile($path);
    }
} 
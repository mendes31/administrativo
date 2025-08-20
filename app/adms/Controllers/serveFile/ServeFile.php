<?php

namespace App\adms\Controllers\serveFile;

use App\adms\Controllers\Services\FileServer;

class ServeFile
{
    public function index()
    {
        $path = $_GET['path'] ?? '';
        $logPath = __DIR__ . '/log_servefile.txt';
        file_put_contents($logPath, date('Y-m-d H:i:s') . " | PATH: $path\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/debug_path_param.txt', var_export($path, true) . PHP_EOL, FILE_APPEND);
        $fileServer = new FileServer();
        file_put_contents(__DIR__ . '/debug_path_service.txt', var_export($path, true) . PHP_EOL, FILE_APPEND);
        $fileServer->serveFile($path);
    }
} 
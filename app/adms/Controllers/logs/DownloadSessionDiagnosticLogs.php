<?php

namespace App\adms\Controllers\logs;

use App\adms\Helpers\CSRFHelper;

class DownloadSessionDiagnosticLogs
{
    public function index(): void
    {
        if (!CSRFHelper::validateCSRFToken('download_session_logs', (string)($_GET['token'] ?? ''), false)) {
            $_SESSION['msg'] = 'Token inválido para download dos logs de sessão.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        $baseDir = realpath(__DIR__ . '/../../../../logs');
        if ($baseDir === false) {
            $_SESSION['msg'] = 'Diretório de logs não encontrado.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        $files = [
            'php_session_config.log',
            'session_investigar.log',
            'session_debug2.log',
        ];

        $available = [];
        foreach ($files as $file) {
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($fullPath) && is_readable($fullPath)) {
                $available[$file] = $fullPath;
            }
        }

        if ($available === []) {
            $_SESSION['msg'] = 'Nenhum arquivo de diagnóstico de sessão disponível para download.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        $dateTag = date('Y-m-d_H-i-s');
        if (class_exists(\ZipArchive::class)) {
            $tmpZip = tempnam(sys_get_temp_dir(), 'adms_session_logs_');
            if ($tmpZip !== false) {
                $zip = new \ZipArchive();
                if ($zip->open($tmpZip, \ZipArchive::OVERWRITE) === true) {
                    foreach ($available as $name => $path) {
                        $zip->addFile($path, $name);
                    }
                    $zip->close();

                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="session_diagnostic_logs_' . $dateTag . '.zip"');
                    header('Content-Length: ' . filesize($tmpZip));
                    readfile($tmpZip);
                    @unlink($tmpZip);
                    exit;
                }
                @unlink($tmpZip);
            }
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="session_diagnostic_logs_' . $dateTag . '.txt"');
        echo "Arquivos de diagnóstico de sessão\n";
        echo "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";
        foreach ($available as $name => $path) {
            echo str_repeat('=', 90) . "\n";
            echo "ARQUIVO: {$name}\n";
            echo str_repeat('=', 90) . "\n";
            readfile($path);
            echo "\n\n";
        }
        exit;
    }
}

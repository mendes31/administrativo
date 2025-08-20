<?php

namespace App\adms\Controllers\lgpd;

use Exception;

class LgpdTiaExportPdfList
{
    public function index(): void
    {
        try {
            // Verificar se as variáveis de ambiente estão carregadas
            if (!isset($_ENV['URL_ADM'])) {
                throw new Exception("Variáveis de ambiente não carregadas");
            }
            
            // Verificar se o repositório existe
            if (!class_exists('\App\adms\Models\Repository\LgpdTiaRepository')) {
                throw new Exception("Repositório LgpdTiaRepository não encontrado");
            }
            
            // Verificar se o controller principal existe
            if (!class_exists('\App\adms\Controllers\lgpd\LgpdTiaExportPdf')) {
                throw new Exception("Controller LgpdTiaExportPdf não encontrado");
            }
            
            $controller = new LgpdTiaExportPdf();
            
            // Verificar se o método existe
            if (!method_exists($controller, 'exportTiaList')) {
                throw new Exception("Método exportTiaList não encontrado");
            }
            
            // Executar a exportação
            $controller->exportTiaList();
            
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro em LgpdTiaExportPdfList: " . $e->getMessage());
            
            // Exibir erro amigável
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Ocorreu um erro ao gerar o PDF da lista de TIA.</p>";
            echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
            exit;
        }
    }
}

<?php

namespace App\adms\Controllers\lgpd;

use Exception;

class LgpdTiaExportPdfView
{
    public function index(int $id): void
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
            
            // Validar se há um ID válido
            if ($id <= 0) {
                throw new Exception("ID do TIA inválido");
            }
            
            $controller = new LgpdTiaExportPdf();
            
            // Verificar se o método existe
            if (!method_exists($controller, 'exportTia')) {
                throw new Exception("Método exportTia não encontrado");
            }
            
            // Executar a exportação
            $controller->exportTia($id);
            
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro em LgpdTiaExportPdfView: " . $e->getMessage());
            
            // Exibir erro amigável
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Ocorreu um erro ao gerar o PDF do TIA.</p>";
            echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
        }
    }
}

<?php

namespace App\adms\Controllers\Services;

use Mpdf\Mpdf;
use Exception;

/**
 * Controller de teste para exportar Análise Completa do Projeto em PDF
 * Versão simplificada para debug
 */
class TestExportAnalysisPdf
{
    public function index(): void
    {
        // Limpar qualquer output anterior
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            // Caminho do arquivo Markdown (raiz do projeto)
            $markdownFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'ANALISE_COMPLETA_PROJETO.md';
            
            if (!file_exists($markdownFile)) {
                throw new Exception("Arquivo não encontrado: " . $markdownFile);
            }
            
            $markdownContent = file_get_contents($markdownFile);
            
            if (empty($markdownContent)) {
                throw new Exception("Arquivo está vazio");
            }
            
            // Converter apenas as primeiras 100 linhas para teste
            $lines = explode("\n", $markdownContent);
            $testContent = implode("\n", array_slice($lines, 0, 100));
            $testContent .= "\n\n... (documento truncado para teste) ...";
            
            $html = $this->simpleMarkdownToHtml($testContent);
            
            // Configurar mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'tempDir' => sys_get_temp_dir()
            ]);
            
            $mpdf->SetTitle("Análise Completa do Projeto - TESTE");
            
            // Suprimir warnings
            $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
            
            $mpdf->WriteHTML($html);
            
            error_reporting($oldErrorReporting);
            
            // Limpar output
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Teste_Analise_' . date('Y-m-d') . '.pdf"');
            
            $mpdf->Output("Teste_Analise_" . date('Y-m-d') . ".pdf", 'D');
            exit;
            
        } catch (\Throwable $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro</title></head><body>";
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($markdownFile ?? 'não definido') . "</p>";
            echo "<p><strong>Existe:</strong> " . (file_exists($markdownFile ?? '') ? 'Sim' : 'Não') . "</p>";
            echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
            echo "</body></html>";
            exit;
        }
    }
    
    private function simpleMarkdownToHtml(string $markdown): string
    {
        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10pt; }
            h1 { color: #2c3e50; font-size: 20pt; margin: 20px 0; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
            h2 { color: #34495e; font-size: 16pt; margin: 18px 0; border-bottom: 2px solid #ecf0f1; padding-bottom: 8px; }
            h3 { color: #555; font-size: 14pt; margin: 15px 0; }
            p { margin: 8px 0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 9pt; }
            th { background-color: #3498db; color: white; border: 1px solid #2980b9; padding: 10px; }
            td { border: 1px solid #ddd; padding: 8px; }
            ul, ol { margin: 10px 0; padding-left: 25px; }
            li { margin: 5px 0; }
        </style>';
        
        $html .= '<div style="padding: 10px;">';
        
        // Converter headers
        $markdown = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $markdown);
        $markdown = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $markdown);
        
        // Converter listas
        $markdown = preg_replace('/^- (.*)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $markdown);
        
        // Converter parágrafos
        $lines = explode("\n", $markdown);
        $processed = [];
        $inParagraph = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                if ($inParagraph) {
                    $processed[] = '</p>';
                    $inParagraph = false;
                }
                continue;
            }
            
            if (preg_match('/^<(h[1-6]|ul|ol|li|table|hr)/', $line)) {
                if ($inParagraph) {
                    $processed[] = '</p>';
                    $inParagraph = false;
                }
                $processed[] = $line;
            } else {
                if (!$inParagraph) {
                    $processed[] = '<p>';
                    $inParagraph = true;
                }
                $processed[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . ' ';
            }
        }
        
        if ($inParagraph) {
            $processed[] = '</p>';
        }
        
        $html .= implode("\n", $processed);
        $html .= '</div>';
        
        return $html;
    }
}


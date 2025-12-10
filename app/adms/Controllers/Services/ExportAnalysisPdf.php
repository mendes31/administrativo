<?php

namespace App\adms\Controllers\Services;

use Mpdf\Mpdf;
use Exception;

/**
 * Controller para exportar Análise Completa do Projeto em PDF
 */
class ExportAnalysisPdf
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
                throw new Exception("Arquivo de análise não encontrado em: " . $markdownFile);
            }
            
            $markdownContent = file_get_contents($markdownFile);
            
            if (empty($markdownContent)) {
                throw new Exception("Arquivo de análise está vazio");
            }
            
            $html = $this->markdownToHtml($markdownContent);
            
            // Validar se o HTML foi gerado
            if (empty($html)) {
                throw new Exception("Falha ao converter Markdown para HTML");
            }
            
            // Limitar tamanho do HTML para evitar problemas de memória (truncar se muito grande)
            $maxHtmlSize = 2000000; // 2MB
            if (strlen($html) > $maxHtmlSize) {
                $html = substr($html, 0, $maxHtmlSize) . '<p><em>... (documento truncado devido ao tamanho) ...</em></p>';
            }
            
            // Configurar mPDF com tratamento de erros
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'autoPageBreak' => true,
                'tempDir' => sys_get_temp_dir(),
                'debug' => false
            ]);
            
            $mpdf->SetTitle("Análise Completa do Projeto - Sistema Administrativo");
            $mpdf->SetAuthor("Sistema Administrativo");
            $mpdf->SetCreator("Sistema de Gestão");
            $mpdf->SetSubject("Análise Técnica do Projeto");
            
            // Adicionar header e footer
            $mpdf->SetHTMLHeader('
                <div style="text-align: right; font-size: 9pt; color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    Análise Completa do Projeto
                </div>
            ');
            
            $mpdf->SetHTMLFooter('
                <div style="text-align: center; font-size: 8pt; color: #666; border-top: 1px solid #ddd; padding-top: 5px;">
                    Página {PAGENO} de {nbpg} | Gerado em ' . date('d/m/Y H:i:s') . '
                </div>
            ');
            
            // Limpar qualquer output antes de escrever HTML
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Suprimir warnings e erros durante a escrita do HTML
            $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
            
            try {
                // Escrever HTML diretamente (mPDF gerencia memória internamente)
                // Usar @ para suprimir warnings do mPDF
                @$mpdf->WriteHTML($html);
            } catch (\Throwable $writeError) {
                error_reporting($oldErrorReporting);
                // Salvar HTML para debug se houver erro
                @file_put_contents(sys_get_temp_dir() . '/debug_analysis_html.html', $html);
                throw new Exception("Erro ao escrever HTML no PDF: " . $writeError->getMessage() . " | Tamanho HTML: " . strlen($html) . " bytes | HTML salvo em: " . sys_get_temp_dir() . '/debug_analysis_html.html');
            }
            
            // Restaurar error reporting
            error_reporting($oldErrorReporting);
            
            // Limpar qualquer output antes de enviar headers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Definir headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Analise_Completa_Projeto_' . date('Y-m-d') . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            $filename = "Analise_Completa_Projeto_" . date('Y-m-d') . ".pdf";
            
            // Gerar PDF (usar 'I' para inline no navegador)
            $mpdf->Output($filename, 'I'); // I = inline (abre no navegador)
            exit;
            
        } catch (\Throwable $e) {
            // Limpar qualquer output
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            error_log("Erro ao exportar Análise PDF: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Arquivo: " . ($markdownFile ?? 'não definido'));
            
            // Retornar erro em HTML
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro ao Gerar PDF</title></head><body>";
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            if (isset($markdownFile) && file_exists($markdownFile)) {
                echo "<p><strong>Arquivo encontrado em:</strong> " . htmlspecialchars($markdownFile) . "</p>";
            } else {
                echo "<p><strong>Arquivo não encontrado.</strong></p>";
                if (isset($markdownFile)) {
                    echo "<p><strong>Caminho tentado:</strong> " . htmlspecialchars($markdownFile) . "</p>";
                }
            }
            echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
            echo "</body></html>";
            exit;
        }
    }
    
    /**
     * Converte Markdown básico para HTML
     */
    private function markdownToHtml(string $markdown): string
    {
        $html = '<style>
            body { 
                font-family: Arial, sans-serif; 
                font-size: 10pt; 
                line-height: 1.6;
                color: #333;
            }
            h1 { 
                color: #2c3e50; 
                font-size: 20pt; 
                margin-top: 20px; 
                margin-bottom: 15px;
                border-bottom: 3px solid #3498db;
                padding-bottom: 10px;
            }
            h2 { 
                color: #34495e; 
                font-size: 16pt; 
                margin-top: 18px; 
                margin-bottom: 12px;
                border-bottom: 2px solid #ecf0f1;
                padding-bottom: 8px;
            }
            h3 { 
                color: #555; 
                font-size: 14pt; 
                margin-top: 15px; 
                margin-bottom: 10px;
            }
            h4 { 
                color: #666; 
                font-size: 12pt; 
                margin-top: 12px; 
                margin-bottom: 8px;
            }
            p { 
                margin: 8px 0;
                text-align: justify;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 15px 0;
                font-size: 9pt;
            }
            th { 
                background-color: #3498db; 
                color: white;
                border: 1px solid #2980b9; 
                padding: 10px; 
                text-align: left; 
                font-weight: bold;
            }
            td { 
                border: 1px solid #ddd; 
                padding: 8px; 
                vertical-align: top;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            ul, ol { 
                margin: 10px 0;
                padding-left: 25px;
            }
            li { 
                margin: 5px 0;
            }
            code { 
                background-color: #f4f4f4; 
                padding: 2px 6px; 
                border-radius: 3px;
                font-family: "Courier New", monospace;
                font-size: 9pt;
            }
            pre { 
                background-color: #f4f4f4; 
                padding: 10px; 
                border-radius: 5px;
                border-left: 4px solid #3498db;
                overflow-x: auto;
            }
            blockquote { 
                border-left: 4px solid #3498db; 
                padding-left: 15px; 
                margin: 15px 0;
                color: #555;
                font-style: italic;
            }
            hr { 
                border: none; 
                border-top: 2px solid #ecf0f1; 
                margin: 20px 0;
            }
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 8pt;
                font-weight: bold;
            }
            .badge-success { background-color: #27ae60; color: white; }
            .badge-warning { background-color: #f39c12; color: white; }
            .badge-danger { background-color: #e74c3c; color: white; }
            .badge-info { background-color: #3498db; color: white; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .mb-3 { margin-bottom: 15px; }
            .mt-3 { margin-top: 15px; }
        </style>';
        
        // Processar Markdown
        $html .= '<div style="padding: 10px;">';
        
        // Converter headers
        $markdown = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $markdown);
        $markdown = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $markdown);
        
        // Converter listas
        $markdown = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/^- (.*)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/^\d+\. (.*)$/m', '<li>$1</li>', $markdown);
        
        // Agrupar listas
        $markdown = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $markdown);
        
        // Converter tabelas Markdown
        $markdown = $this->convertMarkdownTables($markdown);
        
        // Converter código inline
        $markdown = preg_replace('/`([^`]+)`/', '<code>$1</code>', $markdown);
        
        // Converter negrito
        $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
        
        // Converter itálico
        $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
        
        // Converter emojis para texto
        $markdown = str_replace('✅', '[OK]', $markdown);
        $markdown = str_replace('⏳', '[PENDENTE]', $markdown);
        $markdown = str_replace('🔴', '[ALTA]', $markdown);
        $markdown = str_replace('🟡', '[MÉDIA]', $markdown);
        $markdown = str_replace('🟢', '[BAIXA]', $markdown);
        $markdown = str_replace('⭐', '[RECOMENDADO]', $markdown);
        $markdown = str_replace('⚠️', '[ATENÇÃO]', $markdown);
        $markdown = str_replace('📊', '', $markdown);
        $markdown = str_replace('🎯', '', $markdown);
        $markdown = str_replace('📈', '', $markdown);
        $markdown = str_replace('🏗️', '', $markdown);
        $markdown = str_replace('⏱️', '', $markdown);
        $markdown = str_replace('👥', '', $markdown);
        $markdown = str_replace('📋', '', $markdown);
        $markdown = str_replace('💰', '', $markdown);
        $markdown = str_replace('📝', '', $markdown);
        
        // Converter HR
        $markdown = preg_replace('/^---$/m', '<hr>', $markdown);
        
        // Converter parágrafos (linhas que não são headers, listas, etc)
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
            
            // Se já é um elemento HTML, adicionar direto
            if (preg_match('/^<(h[1-6]|ul|ol|li|table|hr|div|p)/', $line)) {
                if ($inParagraph) {
                    $processed[] = '</p>';
                    $inParagraph = false;
                }
                $processed[] = $line;
            } else {
                // Evitar criar parágrafos dentro de outros elementos
                if (!$inParagraph && !preg_match('/^<(h[1-6]|ul|ol|li|table|hr|div)/', $line)) {
                    $processed[] = '<p>';
                    $inParagraph = true;
                }
                if ($inParagraph) {
                    $processed[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . ' ';
                } else {
                    $processed[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                }
            }
        }
        
        if ($inParagraph) {
            $processed[] = '</p>';
        }
        
        $html .= implode("\n", $processed);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Converte tabelas Markdown para HTML
     */
    private function convertMarkdownTables(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $result = [];
        $inTable = false;
        $tableRows = [];
        
        foreach ($lines as $line) {
            // Detectar início de tabela (linha com |)
            if (preg_match('/^\|.*\|$/', $line)) {
                if (!$inTable) {
                    $inTable = true;
                    $tableRows = [];
                }
                $tableRows[] = $line;
            } else {
                // Se estava em uma tabela, processar
                if ($inTable && !empty($tableRows)) {
                    $result[] = $this->processTable($tableRows);
                    $tableRows = [];
                    $inTable = false;
                }
                $result[] = $line;
            }
        }
        
        // Processar última tabela se houver
        if ($inTable && !empty($tableRows)) {
            $result[] = $this->processTable($tableRows);
        }
        
        return implode("\n", $result);
    }
    
    /**
     * Processa linhas de tabela Markdown e converte para HTML
     */
    private function processTable(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }
        
        $html = '<table>';
        
        foreach ($rows as $index => $row) {
            $cells = array_map('trim', explode('|', $row));
            // Remover primeiro e último elemento vazio
            array_shift($cells);
            array_pop($cells);
            
            $html .= '<tr>';
            
            foreach ($cells as $cell) {
                $cell = trim($cell);
                
                // Segunda linha é geralmente o separador (---)
                if ($index === 1 && preg_match('/^[-:]+$/', $cell)) {
                    continue; // Pular linha de separador
                }
                
                $tag = ($index === 0) ? 'th' : 'td';
                $html .= "<$tag>" . htmlspecialchars($cell) . "</$tag>";
            }
            
            $html .= '</tr>';
            
            // Se era a linha de separador, não adicionar mais
            if ($index === 1 && preg_match('/^[-:]+$/', $row)) {
                break;
            }
        }
        
        $html .= '</table>';
        return $html;
    }
}


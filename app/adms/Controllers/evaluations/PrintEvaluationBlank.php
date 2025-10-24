<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\EvaluationLogService;
use Mpdf\Mpdf;

/**
 * Controller para imprimir modelo de avaliação em branco (para resposta física)
 */
class PrintEvaluationBlank
{
    private $modelsRepo;
    private $questionsRepo;

    public function __construct()
    {
        $this->modelsRepo = new EvaluationModelsRepository();
        $this->questionsRepo = new EvaluationQuestionsRepository();
    }

    /**
     * Gerar PDF do modelo em branco para impressão física
     */
    public function index($modelId = null): void
    {
        // Converter para int se for string
        if (is_string($modelId)) {
            $modelId = !empty($modelId) ? (int)$modelId : null;
        }

        if (!$modelId) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>ID do modelo não informado!</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        try {
            // Buscar dados do modelo
            $model = $this->modelsRepo->getModel($modelId);
            
            if (!$model) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Modelo não encontrado!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
                exit;
            }

            // Buscar questões
            $questions = $this->questionsRepo->getQuestionsByModel($modelId);
            
            if (empty($questions)) {
                $_SESSION['msg'] = "<div class='alert alert-warning'>Este modelo não possui questões cadastradas!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'view-evaluation-model/' . $modelId);
                exit;
            }

            // Gerar HTML do PDF
            $html = $this->generatePdfHtml($model, $questions);

            // Suprimir warnings do MPDF
            error_reporting(E_ERROR | E_PARSE);
            
            // Configurar MPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'debug' => false
            ]);

            // Configurações do PDF
            $mpdf->SetTitle('Avaliação - ' . $model['titulo']);
            $mpdf->SetAuthor('Sistema Administrativo');
            $mpdf->SetCreator('Sistema de Avaliações');

            // Escrever HTML
            @$mpdf->WriteHTML($html);
            
            // Restaurar error reporting
            error_reporting(E_ALL);

            // Nome do arquivo
            $nomeArquivo = 'Formulario_' . $this->sanitizeFileName($model['titulo']) . '_' . 
                          date('YmdHis') . '.pdf';

            // Log
            GenerateLog::generateLog('INFO', 'PDF de formulário em branco gerado', [
                'model_id' => $modelId,
                'model_titulo' => $model['titulo'],
                'user_id' => $_SESSION['user_id'] ?? 0,
                'file_name' => $nomeArquivo
            ]);

            // Saída do PDF
            $mpdf->Output($nomeArquivo, 'D'); // D = download
            exit;

        } catch (\Exception $e) {
            GenerateLog::generateLog('ERROR', 'Erro ao gerar PDF em branco', [
                'model_id' => $modelId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao gerar PDF: " . $e->getMessage() . "</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }
    }

    /**
     * Gerar HTML do PDF usando template em branco
     */
    private function generatePdfHtml($model, $questions): string
    {
        $dataImpressao = date('d/m/Y \à\s H:i');

        // Carregar template em branco
        ob_start();
        include __DIR__ . '/../../Views/evaluations/pdf_template_blank.php';
        return ob_get_clean();
    }

    /**
     * Sanitizar nome de arquivo
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Remover acentos
        $fileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fileName);
        
        // Remover caracteres especiais
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fileName);
        
        // Remover underscores duplicados
        $fileName = preg_replace('/_+/', '_', $fileName);
        
        // Limitar tamanho
        return substr($fileName, 0, 50);
    }
}


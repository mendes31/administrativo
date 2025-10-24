<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAttemptsRepository;
use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\EvaluationLogService;
use Mpdf\Mpdf;

/**
 * Controller para imprimir resultado da avaliação em PDF
 */
class PrintEvaluationResult
{
    private $attemptsRepo;
    private $assignmentsRepo;
    private $modelsRepo;
    private $questionsRepo;
    private $usersRepo;

    public function __construct()
    {
        $this->attemptsRepo = new EvaluationAttemptsRepository();
        $this->assignmentsRepo = new EvaluationAssignmentsRepository();
        $this->modelsRepo = new EvaluationModelsRepository();
        $this->questionsRepo = new EvaluationQuestionsRepository();
        $this->usersRepo = new UsersRepository();
    }

    /**
     * Gerar PDF do resultado da avaliação
     */
    public function index($attemptId = null): void
    {
        // Converter para int se for string
        if (is_string($attemptId)) {
            $attemptId = !empty($attemptId) ? (int)$attemptId : null;
        }

        if (!$attemptId) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>ID da tentativa não informado!</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
            exit;
        }

        try {
            // Buscar dados da tentativa
            $attempt = $this->attemptsRepo->getById($attemptId);
            
            if (!$attempt) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Tentativa não encontrada!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
                exit;
            }

            // Buscar dados da atribuição
            $assignment = $this->assignmentsRepo->getById($attempt['assignment_id']);
            
            if (!$assignment) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Atribuição não encontrada!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
                exit;
            }

            // Buscar dados do modelo
            $model = $this->modelsRepo->getModel($assignment['evaluation_model_id']);
            
            if (!$model) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Modelo não encontrado!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
                exit;
            }

            // Buscar dados do usuário
            $user = $this->usersRepo->getUser($assignment['adms_user_id']);
            
            if (!$user) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Usuário não encontrado!</div>";
                header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
                exit;
            }

            // Buscar questões
            $questions = $this->questionsRepo->getQuestionsByModel($assignment['evaluation_model_id']);
            
            // Decodificar respostas
            $respostas = json_decode($attempt['respostas'], true);

            // Gerar HTML do PDF
            $html = $this->generatePdfHtml($attempt, $assignment, $model, $user, $questions, $respostas);

            // Suprimir warnings do MPDF
            error_reporting(E_ERROR | E_PARSE);
            
            // Configurar MPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10,
                'debug' => false
            ]);

            // Configurações do PDF
            $mpdf->SetTitle('Resultado da Avaliação - ' . $model['titulo']);
            $mpdf->SetAuthor('Sistema Administrativo');
            $mpdf->SetCreator('Sistema de Avaliações');

            // Escrever HTML (suprimindo warnings)
            @$mpdf->WriteHTML($html);
            
            // Restaurar error reporting
            error_reporting(E_ALL);

            // Nome do arquivo
            $nomeArquivo = 'Avaliacao_' . $this->sanitizeFileName($model['titulo']) . '_' . 
                          $this->sanitizeFileName($user['name']) . '_' . 
                          date('YmdHis') . '.pdf';

            // Log
            EvaluationLogService::logPdfGenerated(
                $attemptId,
                $assignment['adms_user_id'],
                $nomeArquivo,
                $_SESSION['user_id'] ?? $assignment['adms_user_id']
            );

            // Saída do PDF
            $mpdf->Output($nomeArquivo, 'D'); // D = download
            exit; // Importante: parar execução após output do PDF

        } catch (\Exception $e) {
            GenerateLog::generateLog('ERROR', 'Erro ao gerar PDF da avaliação', [
                'attempt_id' => $attemptId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao gerar PDF: " . $e->getMessage() . "</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'my-evaluations');
            exit;
        }
    }

    /**
     * Gerar HTML do PDF usando template profissional
     */
    private function generatePdfHtml($attempt, $assignment, $model, $user, $questions, $respostas): string
    {
        $dataImpressao = date('d/m/Y \à\s H:i');
        $dataAvaliacao = date('d/m/Y \à\s H:i', strtotime($attempt['data_finalizacao'] ?? $attempt['created_at']));
        
        $aprovado = ($attempt['nota_obtida'] >= ($model['nota_minima_aprovacao'] ?? 7.00));
        $statusTexto = $aprovado ? 'APROVADO' : 'REPROVADO';
        $statusCor = $aprovado ? '#2d5f2e' : '#a81c1c';
        $statusCorClara = $aprovado ? '#e8f5e9' : '#ffebee';

        // Carregar template profissional
        ob_start();
        include __DIR__ . '/../../Views/evaluations/pdf_template_professional.php';
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


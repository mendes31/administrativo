<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationQuestionsRepository;

/**
 * Controller para buscar perguntas por modelo via AJAX.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class GetQuestionsByModel
{
    /**
     * Buscar perguntas por modelo de avaliação
     * @param int $modelId ID do modelo de avaliação
     * @return void
     */
    public function index(int $modelId): void
    {
        header('Content-Type: application/json');
        
        if (empty($modelId)) {
            echo json_encode(['success' => false, 'message' => 'ID do modelo não fornecido']);
            return;
        }

        try {
            $questionsRepository = new EvaluationQuestionsRepository();
            $questions = $questionsRepository->getQuestionsByModel($modelId);
            
            echo json_encode([
                'success' => true,
                'questions' => $questions
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar perguntas: ' . $e->getMessage()
            ]);
        }
    }
} 
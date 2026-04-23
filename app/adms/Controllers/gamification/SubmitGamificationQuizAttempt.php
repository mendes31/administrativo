<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Models\Services\GamificationAwardService;

class SubmitGamificationQuizAttempt
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['error'] = 'Sessão expirada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['SubmitGamificationQuizAttempt', 'TakeGamificationQuiz']);
        $canSubmit = is_array($perms) && in_array('SubmitGamificationQuizAttempt', $perms, true);
        if (!$canSubmit) {
            $_SESSION['error'] = 'Sem permissão para enviar respostas do quiz.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_submit_gamification_quiz', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $quizId = (int)($_POST['quiz_id'] ?? 0);
        if ($attemptId <= 0 || $quizId <= 0) {
            $_SESSION['error'] = 'Dados da tentativa inválidos.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $repo = new GamificationQuizRepository();
        $quiz = $repo->findPublishedById($quizId);
        if (!$quiz) {
            $_SESSION['error'] = 'Quiz não disponível.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $attempt = $repo->findAttempt($attemptId, $userId);
        if (!$attempt || (string)($attempt['status'] ?? '') !== 'in_progress' || (int)($attempt['quiz_id'] ?? 0) !== $quizId) {
            $_SESSION['error'] = 'Tentativa inválida ou já finalizada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $questions = $repo->listQuestionsForQuiz($quizId);
        $validQuestions = [];
        $maxScore = 0;
        foreach ($questions as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid <= 0) {
                continue;
            }
            $opts = $repo->listOptionsForQuestion($qid);
            if (count($opts) < 2) {
                continue;
            }
            $validQuestions[] = $q;
            $maxScore += max(0, (int)($q['points_correct'] ?? 0));
        }
        if ($validQuestions === [] || $maxScore <= 0) {
            $_SESSION['error'] = 'Quiz sem pontuação configurada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
            exit;
        }

        $answersPost = $_POST['answer'] ?? [];
        if (!is_array($answersPost)) {
            $answersPost = [];
        }

        $score = 0;
        $graded = [];

        foreach ($validQuestions as $q) {
            $qid = (int)($q['id'] ?? 0);
            $opts = $repo->listOptionsForQuestion($qid);
            $type = (string)($q['question_type'] ?? 'single');
            $raw = $answersPost[(string)$qid] ?? ($answersPost[$qid] ?? null);
            $selected = [];
            if ($type === 'multiple') {
                if (!is_array($raw)) {
                    $raw = $raw !== null && $raw !== '' ? [$raw] : [];
                }
                foreach ($raw as $rid) {
                    $selected[] = (int)$rid;
                }
            } else {
                if (is_array($raw)) {
                    $selected = $raw !== [] ? [(int)reset($raw)] : [];
                } else {
                    $selected = $raw !== null && $raw !== '' ? [(int)$raw] : [];
                }
            }
            $selected = array_values(array_unique(array_filter($selected, static fn ($v) => $v > 0)));
            if ($selected === []) {
                $_SESSION['error'] = 'Responda todas as questões antes de enviar.';
                header('Location: ' . $_ENV['URL_ADM'] . 'take-gamification-quiz/' . $quizId);
                exit;
            }

            $correctIds = [];
            foreach ($opts as $o) {
                if (!empty($o['is_correct'])) {
                    $correctIds[] = (int)($o['id'] ?? 0);
                }
            }
            $correctIds = array_values(array_filter($correctIds, static fn ($v) => $v > 0));
            sort($correctIds);
            $selSorted = $selected;
            sort($selSorted);
            $isCorrect = $correctIds !== [] && $correctIds === $selSorted;
            $pts = $isCorrect ? max(0, (int)($q['points_correct'] ?? 0)) : 0;
            $score += $pts;
            $graded[] = ['qid' => $qid, 'selected' => $selected, 'isCorrect' => $isCorrect, 'pts' => $pts];
        }

        $percent = (int)round(100 * $score / $maxScore);
        $passing = $quiz['passing_percent'] ?? null;
        $passed = $passing === null || $passing === '' || (int)$passing <= 0 || $percent >= (int)$passing;
        $pointsConfigured = max(0, (int)($quiz['points_on_completion'] ?? 0));
        $pointsToLedger = ($passed && $pointsConfigured > 0) ? $pointsConfigured : 0;

        $pdo = $repo->getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($graded as $row) {
                $repo->insertAttemptAnswer($attemptId, $row['qid'], $row['selected'], $row['isCorrect'], $row['pts']);
            }
            if (!$repo->finalizeAttempt($attemptId, $score, $percent, 0)) {
                throw new \RuntimeException('finalize');
            }
            $pdo->commit();
        } catch (\Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = 'Não foi possível finalizar a tentativa. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'take-gamification-quiz/' . $quizId);
            exit;
        }

        $awardSvc = new GamificationAwardService();
        $awarded = $pointsToLedger > 0 ? $awardSvc->awardQuizLedger($userId, $attemptId, $pointsToLedger) : 0;
        if ($awarded > 0) {
            $repo->updateAttemptPointsAwarded($attemptId, $awarded);
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Respostas enviadas. Pontuação: '
            . htmlspecialchars((string)$score) . ' / ' . htmlspecialchars((string)$maxScore)
            . ' (' . $percent . '%). '
            . ($awarded > 0 ? 'Você recebeu ' . $awarded . ' pontos no ranking.' : '')
            . '</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'gamification-quiz-catalog');
        exit;
    }
}

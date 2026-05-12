<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class GamificationQuizRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listAllForAdmin(): array
    {
        $sql = 'SELECT q.id, q.title, q.slug, q.status, q.passing_percent, q.max_attempts, q.points_on_completion,
                       q.available_from, q.available_until, q.created_at, q.updated_at,
                       (SELECT COUNT(*) FROM adms_gamification_quiz_questions qq WHERE qq.quiz_id = q.id) AS questions_count
                FROM adms_gamification_quizzes q
                ORDER BY q.updated_at DESC, q.id DESC';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublishedForCatalog(): array
    {
        $sql = 'SELECT id, title, slug, summary, passing_percent, max_attempts, points_on_completion, available_from, available_until
                FROM adms_gamification_quizzes
                WHERE status = \'published\'
                  AND (available_from IS NULL OR available_from <= NOW())
                  AND (available_until IS NULL OR available_until >= NOW())
                ORDER BY title ASC';
        $stmt = $this->getConnection()->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublishedAvailableForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $sql = 'SELECT q.id, q.title, q.slug, q.summary, q.passing_percent, q.max_attempts, q.points_on_completion, q.available_from, q.available_until
                FROM adms_gamification_quizzes q
                WHERE q.status = \'published\'
                  AND (q.available_from IS NULL OR q.available_from <= NOW())
                  AND (q.available_until IS NULL OR q.available_until >= NOW())
                  AND (
                        SELECT COUNT(*)
                        FROM adms_gamification_quiz_attempts a
                        WHERE a.quiz_id = q.id
                          AND a.user_id = :u
                          AND a.status = \'completed\'
                      ) < q.max_attempts
                ORDER BY q.title ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':u' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quizzes WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findPublishedById(int $id): ?array
    {
        $quiz = $this->findById($id);
        if (!$quiz || ($quiz['status'] ?? '') !== 'published') {
            return null;
        }
        $from = $quiz['available_from'] ?? null;
        $until = $quiz['available_until'] ?? null;
        if ($from !== null && $from !== '' && strtotime((string)$from) > time()) {
            return null;
        }
        if ($until !== null && $until !== '' && strtotime((string)$until) < time()) {
            return null;
        }

        return $quiz;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return true;
        }
        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $this->getConnection()->prepare(
                'SELECT id FROM adms_gamification_quizzes WHERE slug = :s AND id <> :id LIMIT 1'
            );
            $stmt->execute([':s' => $slug, ':id' => $excludeId]);
        } else {
            $stmt = $this->getConnection()->prepare(
                'SELECT id FROM adms_gamification_quizzes WHERE slug = :s LIMIT 1'
            );
            $stmt->execute([':s' => $slug]);
        }

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_gamification_quizzes
                (title, slug, summary, status, passing_percent, max_attempts, points_on_completion, available_from, available_until, created_by, created_at, updated_at)
                VALUES (:title, :slug, :summary, :status, :pp, :ma, :poc, :af, :au, :cb, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 191),
            ':slug' => strtolower(trim((string)($data['slug'] ?? ''))),
            ':summary' => ($data['summary'] ?? null) !== null && (string)$data['summary'] !== '' ? trim((string)$data['summary']) : null,
            ':status' => in_array($data['status'] ?? 'draft', ['draft', 'published', 'archived'], true) ? $data['status'] : 'draft',
            ':pp' => isset($data['passing_percent']) && $data['passing_percent'] !== '' && $data['passing_percent'] !== null
                ? max(0, min(100, (int)$data['passing_percent'])) : null,
            ':ma' => max(1, min(50, (int)($data['max_attempts'] ?? 1))),
            ':poc' => max(0, min(999999, (int)($data['points_on_completion'] ?? 0))),
            ':af' => !empty($data['available_from']) ? $data['available_from'] : null,
            ':au' => !empty($data['available_until']) ? $data['available_until'] : null,
            ':cb' => !empty($data['created_by']) ? (int)$data['created_by'] : null,
        ]);

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->findById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quizzes',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
        $oldRow = $this->findById($id);
        $sql = 'UPDATE adms_gamification_quizzes SET
                title = :title,
                slug = :slug,
                summary = :summary,
                status = :status,
                passing_percent = :pp,
                max_attempts = :ma,
                points_on_completion = :poc,
                available_from = :af,
                available_until = :au,
                updated_at = NOW()
                WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);

        $ok = $stmt->execute([
            ':id' => $id,
            ':title' => mb_substr(trim((string)($data['title'] ?? '')), 0, 191),
            ':slug' => strtolower(trim((string)($data['slug'] ?? ''))),
            ':summary' => ($data['summary'] ?? null) !== null && (string)$data['summary'] !== '' ? trim((string)$data['summary']) : null,
            ':status' => in_array($data['status'] ?? 'draft', ['draft', 'published', 'archived'], true) ? $data['status'] : 'draft',
            ':pp' => isset($data['passing_percent']) && $data['passing_percent'] !== '' && $data['passing_percent'] !== null
                ? max(0, min(100, (int)$data['passing_percent'])) : null,
            ':ma' => max(1, min(50, (int)($data['max_attempts'] ?? 1))),
            ':poc' => max(0, min(999999, (int)($data['points_on_completion'] ?? 0))),
            ':af' => !empty($data['available_from']) ? $data['available_from'] : null,
            ':au' => !empty($data['available_until']) ? $data['available_until'] : null,
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->findById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quizzes',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $oldRow = $this->findById($id);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_gamification_quizzes WHERE id = :id LIMIT 1');

        $ok = $stmt->execute([':id' => $id]);
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_gamification_quizzes',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    public function countCompletedAttempts(int $quizId, int $userId): int
    {
        if ($quizId <= 0 || $userId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_gamification_quiz_attempts
             WHERE quiz_id = :q AND user_id = :u AND status = \'completed\''
        );
        $stmt->execute([':q' => $quizId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    public function abandonStaleInProgressAttempts(int $quizId, int $userId, int $hours = 6): void
    {
        if ($quizId <= 0 || $userId <= 0) {
            return;
        }
        $hours = max(1, min(72, $hours));
        $stmt = $this->getConnection()->prepare(
            "UPDATE adms_gamification_quiz_attempts
             SET status = 'abandoned', finished_at = NOW()
             WHERE quiz_id = :q AND user_id = :u AND status = 'in_progress'
               AND started_at < DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
        );
        $stmt->execute([':q' => $quizId, ':u' => $userId]);
    }

    public function findOpenAttempt(int $quizId, int $userId): ?array
    {
        if ($quizId <= 0 || $userId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quiz_attempts
             WHERE quiz_id = :q AND user_id = :u AND status = \'in_progress\'
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':q' => $quizId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createAttempt(int $quizId, int $userId, int $maxScore): int
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_quiz_attempts (quiz_id, user_id, status, score, max_score, percent, points_awarded, started_at)
             VALUES (:q, :u, \'in_progress\', 0, :ms, 0, 0, NOW())'
        );
        $stmt->execute([':q' => $quizId, ':u' => $userId, ':ms' => max(0, $maxScore)]);

        return (int)$this->getConnection()->lastInsertId();
    }

    public function findAttempt(int $attemptId, int $userId): ?array
    {
        if ($attemptId <= 0 || $userId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quiz_attempts WHERE id = :id AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':id' => $attemptId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param list<array{question_id:int,selected:int[],is_correct:bool,points_earned:int}> $answers
     */
    public function finalizeAttempt(int $attemptId, int $score, int $percent, int $pointsAwarded): bool
    {
        if ($attemptId <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_quiz_attempts
             SET status = \'completed\', score = :sc, percent = :pc, points_awarded = :pa, finished_at = NOW()
             WHERE id = :id AND status = \'in_progress\' LIMIT 1'
        );

        return $stmt->execute([
            ':id' => $attemptId,
            ':sc' => max(0, $score),
            ':pc' => max(0, min(100, $percent)),
            ':pa' => max(0, $pointsAwarded),
        ]) && $stmt->rowCount() > 0;
    }

    public function updateAttemptPointsAwarded(int $attemptId, int $pointsAwarded): bool
    {
        if ($attemptId <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_quiz_attempts SET points_awarded = :p WHERE id = :id LIMIT 1'
        );

        return $stmt->execute([':id' => $attemptId, ':p' => max(0, $pointsAwarded)]);
    }

    public function countAttemptAnswers(int $attemptId): int
    {
        if ($attemptId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_gamification_quiz_attempt_answers WHERE attempt_id = :a'
        );
        $stmt->execute([':a' => $attemptId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    public function insertAttemptAnswer(int $attemptId, int $questionId, array $selectedIds, bool $isCorrect, int $pointsEarned): void
    {
        $json = json_encode(array_values(array_unique(array_map('intval', $selectedIds))));
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_quiz_attempt_answers (attempt_id, question_id, selected_option_ids_json, is_correct, points_earned, created_at)
             VALUES (:a, :q, :j, :ok, :pe, NOW())'
        );
        $stmt->execute([
            ':a' => $attemptId,
            ':q' => $questionId,
            ':j' => $json !== false ? $json : '[]',
            ':ok' => $isCorrect ? 1 : 0,
            ':pe' => max(0, $pointsEarned),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listQuestionsForQuiz(int $quizId): array
    {
        if ($quizId <= 0) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, quiz_id, body, question_type, sort_order, points_correct, created_at, updated_at
             FROM adms_gamification_quiz_questions WHERE quiz_id = :q ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':q' => $quizId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOptionsForQuestion(int $questionId): array
    {
        if ($questionId <= 0) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, question_id, label, is_correct, sort_order FROM adms_gamification_quiz_options
             WHERE question_id = :q ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':q' => $questionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function sumQuestionPoints(int $quizId): int
    {
        if ($quizId <= 0) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(SUM(points_correct), 0) AS s FROM adms_gamification_quiz_questions WHERE quiz_id = :q'
        );
        $stmt->execute([':q' => $quizId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['s'] ?? 0);
    }

    public function createQuestion(int $quizId, string $body, string $type, int $sortOrder, int $pointsCorrect): int
    {
        $type = $type === 'multiple' ? 'multiple' : 'single';
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_quiz_questions (quiz_id, body, question_type, sort_order, points_correct, created_at, updated_at)
             VALUES (:q, :b, :t, :o, :pc, NOW(), NOW())'
        );
        $stmt->execute([
            ':q' => $quizId,
            ':b' => $body,
            ':t' => $type,
            ':o' => max(0, $sortOrder),
            ':pc' => max(0, min(999999, $pointsCorrect)),
        ]);

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->findQuestionById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quiz_questions',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    public function updateQuestion(int $id, int $quizId, string $body, string $type, int $sortOrder, int $pointsCorrect): bool
    {
        $type = $type === 'multiple' ? 'multiple' : 'single';
        $oldRow = $this->findQuestion($id, $quizId);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_quiz_questions SET body = :b, question_type = :t, sort_order = :o, points_correct = :pc, updated_at = NOW()
             WHERE id = :id AND quiz_id = :q LIMIT 1'
        );

        $ok = $stmt->execute([
            ':id' => $id,
            ':q' => $quizId,
            ':b' => $body,
            ':t' => $type,
            ':o' => max(0, $sortOrder),
            ':pc' => max(0, min(999999, $pointsCorrect)),
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->findQuestion($id, $quizId);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quiz_questions',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function findQuestion(int $id, int $quizId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quiz_questions WHERE id = :id AND quiz_id = :q LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':q' => $quizId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findQuestionById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quiz_questions WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function deleteQuestion(int $id, int $quizId): bool
    {
        $oldRow = $this->findQuestion($id, $quizId);
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_gamification_quiz_questions WHERE id = :id AND quiz_id = :q LIMIT 1'
        );

        $ok = $stmt->execute([':id' => $id, ':q' => $quizId]);
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_gamification_quiz_questions',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    public function createOption(int $questionId, string $label, bool $isCorrect, int $sortOrder): int
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_gamification_quiz_options (question_id, label, is_correct, sort_order, created_at)
             VALUES (:q, :l, :c, :o, NOW())'
        );
        $stmt->execute([
            ':q' => $questionId,
            ':l' => mb_substr(trim($label), 0, 500),
            ':c' => $isCorrect ? 1 : 0,
            ':o' => max(0, $sortOrder),
        ]);

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->findOption($newId, $questionId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quiz_options',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    public function updateOption(int $id, int $questionId, string $label, bool $isCorrect, int $sortOrder): bool
    {
        $oldRow = $this->findOption($id, $questionId);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_gamification_quiz_options SET label = :l, is_correct = :c, sort_order = :o
             WHERE id = :id AND question_id = :q LIMIT 1'
        );

        $ok = $stmt->execute([
            ':id' => $id,
            ':q' => $questionId,
            ':l' => mb_substr(trim($label), 0, 500),
            ':c' => $isCorrect ? 1 : 0,
            ':o' => max(0, $sortOrder),
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->findOption($id, $questionId);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_gamification_quiz_options',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function deleteOptionsForQuestion(int $questionId): void
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_gamification_quiz_options WHERE question_id = :q');
        $stmt->execute([':q' => $questionId]);
        $n = $stmt->rowCount();
        if ($n > 0) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_gamification_quiz_options',
                $questionId,
                $usuarioId,
                'DELETE',
                ['bulk_deleted_for_question_id' => (string) $questionId, 'rows' => (string) $n],
                []
            );
        }
    }

    public function findOption(int $id, int $questionId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_gamification_quiz_options WHERE id = :id AND question_id = :q LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':q' => $questionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function deleteOption(int $id, int $questionId): bool
    {
        $oldRow = $this->findOption($id, $questionId);
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_gamification_quiz_options WHERE id = :id AND question_id = :q LIMIT 1'
        );

        $ok = $stmt->execute([':id' => $id, ':q' => $questionId]);
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_gamification_quiz_options',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    /**
     * @return array<int, list<int>>
     */
    public function getCorrectOptionIdsByQuestion(int $quizId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT q.id AS qid, o.id AS oid
             FROM adms_gamification_quiz_questions q
             INNER JOIN adms_gamification_quiz_options o ON o.question_id = q.id
             WHERE q.quiz_id = :quiz AND o.is_correct = 1
             ORDER BY q.id ASC, o.sort_order ASC'
        );
        $stmt->execute([':quiz' => $quizId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        if (!is_array($rows)) {
            return $out;
        }
        foreach ($rows as $r) {
            $qid = (int)($r['qid'] ?? 0);
            $oid = (int)($r['oid'] ?? 0);
            if ($qid <= 0 || $oid <= 0) {
                continue;
            }
            $out[$qid][] = $oid;
        }

        return $out;
    }

    public function findQuizIdForOptionId(int $optionId): ?int
    {
        if ($optionId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT q.quiz_id FROM adms_gamification_quiz_options o
             INNER JOIN adms_gamification_quiz_questions q ON q.id = o.question_id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $optionId]);
        $v = $stmt->fetchColumn();

        return $v !== false ? (int) $v : null;
    }
}

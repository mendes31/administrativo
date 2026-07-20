<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PulseResponsesRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pulse_responses
                (campaign_id, question_id, user_id, score, comment_text, answered_at)
                VALUES
                (:campaign_id, :question_id, :user_id, :score, :comment_text, :answered_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':campaign_id', (int) $data['campaign_id'], PDO::PARAM_INT);
        $stmt->bindValue(':question_id', (int) $data['question_id'], PDO::PARAM_INT);
        if (empty($data['user_id'])) {
            $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        }
        if ($data['score'] === null || $data['score'] === '') {
            $stmt->bindValue(':score', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':score', (int) $data['score'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':comment_text', $data['comment_text'] ?? null);
        $stmt->bindValue(':answered_at', $data['answered_at'] ?? date('Y-m-d H:i:s'));
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function hasUserAnsweredQuestion(int $questionId, int $userId): bool
    {
        $sql = 'SELECT 1 FROM adms_pulse_responses
                WHERE question_id = :question_id AND user_id = :user_id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function listScoresByCampaign(int $campaignId): array
    {
        $sql = 'SELECT score FROM adms_pulse_responses
                WHERE campaign_id = :campaign_id AND score IS NOT NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'score'));
    }

    public function countByCampaign(int $campaignId): int
    {
        $sql = 'SELECT COUNT(*) FROM adms_pulse_responses WHERE campaign_id = :campaign_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}

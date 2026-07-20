<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PulseQuestionsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pulse_questions (campaign_id, question_text, question_type, sort_order)
                VALUES (:campaign_id, :question_text, :question_type, :sort_order)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':campaign_id', (int) $data['campaign_id'], PDO::PARAM_INT);
        $stmt->bindValue(':question_text', $data['question_text']);
        $stmt->bindValue(':question_type', $data['question_type'] ?? 'nps');
        $stmt->bindValue(':sort_order', (int) ($data['sort_order'] ?? 1), PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function listByCampaign(int $campaignId): array
    {
        $sql = 'SELECT * FROM adms_pulse_questions
                WHERE campaign_id = :campaign_id
                ORDER BY sort_order ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

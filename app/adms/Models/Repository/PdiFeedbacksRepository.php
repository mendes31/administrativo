<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PdiFeedbacksRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pdi_feedbacks
                (pdi_plan_id, pdi_action_id, feedback_type, feedback_text, given_by, given_to)
                VALUES
                (:pdi_plan_id, :pdi_action_id, :feedback_type, :feedback_text, :given_by, :given_to)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        if (empty($data['pdi_action_id'])) {
            $stmt->bindValue(':pdi_action_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':pdi_action_id', (int) $data['pdi_action_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':feedback_type', $data['feedback_type'] ?? 'general');
        $stmt->bindValue(':feedback_text', $data['feedback_text']);
        $stmt->bindValue(':given_by', (int) $data['given_by'], PDO::PARAM_INT);
        $stmt->bindValue(':given_to', (int) $data['given_to'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByPlanId(int $planId): array
    {
        $sql = 'SELECT f.*,
                       gb.name AS given_by_name,
                       gt.name AS given_to_name,
                       a.title AS action_title
                FROM adms_pdi_feedbacks f
                INNER JOIN adms_users gb ON gb.id = f.given_by
                INNER JOIN adms_users gt ON gt.id = f.given_to
                LEFT JOIN adms_pdi_actions a ON a.id = f.pdi_action_id
                WHERE f.pdi_plan_id = :plan_id
                ORDER BY f.created_at DESC, f.id DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

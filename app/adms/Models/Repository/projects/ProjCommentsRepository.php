<?php

namespace App\adms\Models\Repository\projects;

use App\adms\Models\Services\DbConnection;
use PDO;

class ProjCommentsRepository extends DbConnection
{
    /**
     * Lista comentários do projeto, opcionalmente filtrados por etapa.
     *
     * @param int      $projectId
     * @param int|null $projProjectStageId null = todos
     * @return array
     */
    public function listByProject(int $projectId, ?int $projProjectStageId = null): array
    {
        $sql = 'SELECT c.id, c.project_id, c.proj_project_stage_id, c.user_id, c.body, c.created_at,
                       u.name AS user_name,
                       s.name AS stage_name
                FROM proj_comments c
                INNER JOIN adms_users u ON u.id = c.user_id
                LEFT JOIN proj_project_stages ps ON ps.id = c.proj_project_stage_id
                LEFT JOIN proj_stages s ON s.id = ps.stage_id
                WHERE c.project_id = :project_id';
        $params = [':project_id' => $projectId];
        if ($projProjectStageId !== null) {
            $sql .= ' AND (c.proj_project_stage_id = :stage_id OR c.proj_project_stage_id IS NULL)';
            $params[':stage_id'] = $projProjectStageId;
        }
        $sql .= ' ORDER BY c.created_at ASC';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($comments as &$c) {
            $c['mentions'] = $this->getMentionsForComment((int)$c['id']);
        }
        unset($c);

        return $comments;
    }

    public function getMentionsForComment(int $commentId): array
    {
        $sql = 'SELECT m.user_id, u.name AS user_name
                FROM proj_comment_mentions m
                INNER JOIN adms_users u ON u.id = m.user_id
                WHERE m.comment_id = :comment_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cria comentário e opcionalmente menções; dispara notificações para usuários mencionados.
     *
     * @param int       $projectId
     * @param int|null  $projProjectStageId
     * @param int       $userId
     * @param string    $body
     * @param int[]     $mentionedUserIds
     * @return int|false
     */
    public function create(int $projectId, ?int $projProjectStageId, int $userId, string $body, array $mentionedUserIds = [])
    {
        $sql = 'INSERT INTO proj_comments (project_id, proj_project_stage_id, user_id, body, created_at)
                VALUES (:project_id, :stage_id, :user_id, :body, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':stage_id', $projProjectStageId, $projProjectStageId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return false;
        }
        $commentId = (int)$this->getConnection()->lastInsertId();

        $mentionedUserIds = array_unique(array_filter(array_map('intval', $mentionedUserIds)));
        foreach ($mentionedUserIds as $mentionedId) {
            if ($mentionedId === $userId) {
                continue;
            }
            $stmtM = $this->getConnection()->prepare('INSERT INTO proj_comment_mentions (comment_id, user_id, created_at) VALUES (:comment_id, :user_id, NOW())');
            $stmtM->bindValue(':comment_id', $commentId, PDO::PARAM_INT);
            $stmtM->bindValue(':user_id', $mentionedId, PDO::PARAM_INT);
            $stmtM->execute();
        }

        return $commentId;
    }
}

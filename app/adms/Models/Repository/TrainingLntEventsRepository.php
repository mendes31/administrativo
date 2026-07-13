<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class TrainingLntEventsRepository extends DbConnection
{
    public const TYPE_NOVO_COLABORADOR = 'novo_colaborador';
    public const TYPE_NOVO_CARGO = 'novo_cargo';
    public const TYPE_COLABORADOR_DESLIGADO = 'colaborador_desligado';
    public const TYPE_ALTERACAO_CARGO = 'alteracao_cargo';

  /** Controllers que identificam equipe de treinamentos. */
    public const TRAINING_TEAM_CONTROLLERS = [
        'ListTrainings',
        'CreateTraining',
        'TrainingPositions',
        'LinkTrainingUsers',
        'MatrixByUser',
        'ListTrainingStatus',
        'ListTrainingLntEvents',
    ];

    public function insert(array $data): int
    {
        $sql = 'INSERT INTO adms_training_lnt_events
                (event_type, action_label, user_id, position_id, collaborator_name, collaborator_cpf,
                 department_name, position_name, data_admissao, data_desligamento, details_json, actor_user_id, created_at)
                VALUES
                (:event_type, :action_label, :user_id, :position_id, :collaborator_name, :collaborator_cpf,
                 :department_name, :position_name, :data_admissao, :data_desligamento, :details_json, :actor_user_id, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':event_type', $data['event_type'], PDO::PARAM_STR);
        $stmt->bindValue(':action_label', $data['action_label'], PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null, $data['user_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':position_id', $data['position_id'] ?? null, $data['position_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':collaborator_name', $data['collaborator_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':collaborator_cpf', $data['collaborator_cpf'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':department_name', $data['department_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':position_name', $data['position_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':data_admissao', $data['data_admissao'] ?? null, $data['data_admissao'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':data_desligamento', $data['data_desligamento'] ?? null, $data['data_desligamento'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':details_json', $data['details_json'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':actor_user_id', $data['actor_user_id'] ?? null, $data['actor_user_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['event_type'])) {
            $where[] = 'e.event_type = :event_type';
            $params[':event_type'] = $filters['event_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(e.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(e.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(e.collaborator_name LIKE :q OR e.collaborator_cpf LIKE :q OR e.department_name LIKE :q OR e.position_name LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT e.*, actor.name AS actor_name
                FROM adms_training_lnt_events e
                LEFT JOIN adms_users actor ON actor.id = e.actor_user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.created_at DESC, e.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['event_type'])) {
            $where[] = 'event_type = :event_type';
            $params[':event_type'] = $filters['event_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(collaborator_name LIKE :q OR collaborator_cpf LIKE :q OR department_name LIKE :q OR position_name LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM adms_training_lnt_events WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Datas com eventos pendentes de digest anteriores ao dia informado.
     *
     * @return array<int, string> Datas no formato Y-m-d, em ordem cronológica.
     */
    public function getPendingDigestDatesBefore(string $beforeDate): array
    {
        $sql = 'SELECT DISTINCT DATE(created_at) AS ref_date
                FROM adms_training_lnt_events
                WHERE digest_sent_at IS NULL
                  AND DATE(created_at) < :before_date
                ORDER BY ref_date ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':before_date', $beforeDate, PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_values(array_map(
            static fn (array $row): string => (string)($row['ref_date'] ?? ''),
            $rows
        ));
    }

    /**
     * Eventos do dia anterior ainda não enviados no digest.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendingDigestForDate(string $referenceDate): array
    {
        $sql = 'SELECT *
                FROM adms_training_lnt_events
                WHERE DATE(created_at) = :ref_date
                  AND digest_sent_at IS NULL
                ORDER BY created_at ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ref_date', $referenceDate, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int> $ids
     */
    public function markDigestSent(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE adms_training_lnt_events SET digest_sent_at = NOW() WHERE id IN ({$in})";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    public function markInappNotified(int $id): void
    {
        $sql = 'UPDATE adms_training_lnt_events SET inapp_notified_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Usuários ativos com permissão explícita de treinamentos e e-mail válido.
     * Exclui superadministradores (nível 1 ou flag super_usuario) e, opcionalmente, o autor da ação.
     *
     * @return array<int, array{id:int,name:string,email:string}>
     */
    public function getTrainingTeamRecipients(?int $excludeActorUserId = null): array
    {
        $controllers = self::TRAINING_TEAM_CONTROLLERS;
        $controllerParams = [];
        foreach ($controllers as $i => $controller) {
            $controllerParams[] = ':ctrl_' . $i;
        }
        $controllerIn = implode(',', $controllerParams);

        $sql = "SELECT DISTINCT u.id, u.name, u.email
                FROM adms_users u
                WHERE u.status = 'Ativo'
                  AND u.email IS NOT NULL
                  AND TRIM(u.email) <> ''
                  AND COALESCE(u.super_usuario, 0) <> 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM adms_users_access_levels ual_super
                      WHERE ual_super.adms_user_id = u.id
                        AND ual_super.adms_access_level_id = :super_admin_level_id
                  )
                  AND EXISTS (
                      SELECT 1
                      FROM adms_users_access_levels ual
                      INNER JOIN adms_access_levels_pages alp
                              ON alp.adms_access_level_id = ual.adms_access_level_id
                             AND alp.permission = 1
                      INNER JOIN adms_pages p
                              ON p.id = alp.adms_page_id
                             AND p.page_status = 1
                      WHERE ual.adms_user_id = u.id
                        AND p.controller IN ({$controllerIn})
                  )";

        if ($excludeActorUserId !== null && $excludeActorUserId > 0) {
            $sql .= ' AND u.id <> :exclude_actor_user_id';
        }

        $sql .= ' ORDER BY u.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':super_admin_level_id', UserAccessHelper::SUPER_ADMIN_LEVEL_ID, PDO::PARAM_INT);
        foreach ($controllers as $i => $controller) {
            $stmt->bindValue(':ctrl_' . $i, $controller, PDO::PARAM_STR);
        }
        if ($excludeActorUserId !== null && $excludeActorUserId > 0) {
            $stmt->bindValue(':exclude_actor_user_id', $excludeActorUserId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

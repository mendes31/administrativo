<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Etapas configuráveis por tipo de solicitação (fonte de verdade do fluxo).
 */
class RequestTypeStagesRepository extends DbConnection
{
    public const KINDS = ['immediate', 'hr', 'fixed_user'];

    /**
     * @return list<array<string, mixed>>
     */
    public function listByTypeId(int $requestTypeId): array
    {
        if ($requestTypeId <= 0 || !$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT *
                FROM adms_request_type_stages
                WHERE request_type_id = :type_id
                  AND is_active = 1
                ORDER BY stage_order ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':type_id', $requestTypeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByTypeCode(string $typeCode): array
    {
        if ($typeCode === '' || !$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT s.*
                FROM adms_request_type_stages s
                INNER JOIN adms_request_types t ON t.id = s.request_type_id
                WHERE t.code = :code
                  AND s.is_active = 1
                ORDER BY s.stage_order ASC, s.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $typeCode);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Substitui todas as etapas do tipo pela lista enviada (editor do fluxo).
     *
     * @param list<array{
     *   approver_kind: string,
     *   stage_label?: string,
     *   fixed_user_id?: int|null,
     *   hierarchy_level?: int,
     *   escalate_after_hours?: int,
     *   escalate_policy?: string,
     *   max_escalation_levels?: int
     * }> $stages
     */
    public function replaceStagesForType(int $requestTypeId, array $stages): bool
    {
        if ($requestTypeId <= 0 || !$this->tableExists()) {
            return false;
        }

        $normalized = [];
        $order = 1;
        foreach ($stages as $row) {
            $kind = (string) ($row['approver_kind'] ?? '');
            if (!in_array($kind, self::KINDS, true)) {
                continue;
            }
            $hierarchyLevel = max(1, min(10, (int) ($row['hierarchy_level'] ?? 1)));
            if ($kind !== 'immediate') {
                $hierarchyLevel = 1;
            }

            $label = trim((string) ($row['stage_label'] ?? ''));
            if ($label === '') {
                $label = match ($kind) {
                    'immediate' => $hierarchyLevel === 1
                        ? $order . 'ª Etapa — Gestor do solicitante'
                        : $order . 'ª Etapa — ' . $hierarchyLevel . 'º nível acima do solicitante',
                    'hr' => $order . 'ª Etapa — RH',
                    'fixed_user' => $order . 'ª Etapa — Pessoa específica',
                    default => $order . 'ª Etapa',
                };
            }
            $fixedUserId = isset($row['fixed_user_id']) && (int) $row['fixed_user_id'] > 0
                ? (int) $row['fixed_user_id']
                : null;
            if ($kind === 'fixed_user' && $fixedUserId === null) {
                continue;
            }

            $code = $kind . '_' . $order;
            if ($kind === 'immediate' && $hierarchyLevel > 1) {
                $code = 'hierarchy_' . $hierarchyLevel . '_' . $order;
            }
            $hours = max(0, (int) ($row['escalate_after_hours'] ?? ($kind === 'immediate' ? 72 : 0)));
            $policy = (string) ($row['escalate_policy'] ?? ($kind === 'immediate' ? 'next_level' : 'none'));
            if (!in_array($policy, ['next_level', 'hr', 'none', 'next_stage'], true)) {
                $policy = 'next_level';
            }
            $maxLevels = max(0, min(10, (int) ($row['max_escalation_levels'] ?? ($kind === 'immediate' ? 1 : 0))));

            $normalized[] = [
                'order' => $order,
                'code' => $code,
                'label' => $label,
                'kind' => $kind,
                'hierarchy_level' => $hierarchyLevel,
                'fixed_user_id' => $fixedUserId,
                'hours' => $hours,
                'policy' => $policy,
                'max_levels' => $maxLevels,
            ];
            $order++;
        }

        if ($normalized === []) {
            return false;
        }

        $pdo = $this->getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM adms_request_type_stages WHERE request_type_id = :id')
                ->execute([':id' => $requestTypeId]);

            foreach ($normalized as $stage) {
                $this->insertStageRow($requestTypeId, $stage);
            }
            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();

            return false;
        }
    }

    /**
     * @return array{requires_manager: bool, requires_hr: bool}
     */
    public function deriveFlagsFromStages(array $stages): array
    {
        $requiresManager = false;
        $requiresHr = false;
        foreach ($stages as $stage) {
            $kind = (string) ($stage['approver_kind'] ?? $stage['kind'] ?? '');
            if ($kind === 'immediate' || $kind === 'fixed_user') {
                $requiresManager = true;
            }
            if ($kind === 'hr') {
                $requiresHr = true;
            }
        }

        return ['requires_manager' => $requiresManager, 'requires_hr' => $requiresHr];
    }

    /** @deprecated Prefer replaceStagesForType */
    public function syncDefaultStagesForType(
        int $requestTypeId,
        bool $requiresManager,
        int $escalateHours = 72,
        int $maxEscalationLevels = 1,
        string $escalatePolicy = 'next_level',
        bool $requiresHr = true
    ): void {
        $stages = [];
        if ($requiresManager) {
            $stages[] = [
                'approver_kind' => 'immediate',
                'stage_label' => '1ª Etapa — Gestor imediato',
                'escalate_after_hours' => $escalateHours,
                'escalate_policy' => $escalatePolicy,
                'max_escalation_levels' => $maxEscalationLevels,
            ];
        }
        if ($requiresHr || $stages === []) {
            $stages[] = [
                'approver_kind' => 'hr',
                'stage_label' => (count($stages) + 1) . 'ª Etapa — RH',
                'escalate_after_hours' => 0,
                'escalate_policy' => 'none',
                'max_escalation_levels' => 0,
            ];
        }
        $this->replaceStagesForType($requestTypeId, $stages);
    }

    public function ensureStagesForType(int $requestTypeId, bool $requiresManager = true, bool $requiresHr = true): void
    {
        if ($this->listByTypeId($requestTypeId) !== []) {
            return;
        }
        $this->syncDefaultStagesForType($requestTypeId, $requiresManager, 72, 1, 'next_level', $requiresHr);
    }

    /**
     * @param array{
     *   order: int,
     *   code: string,
     *   label: string,
     *   kind: string,
     *   hierarchy_level?: int,
     *   fixed_user_id: ?int,
     *   hours: int,
     *   policy: string,
     *   max_levels: int
     * } $stage
     */
    private function insertStageRow(int $typeId, array $stage): void
    {
        $hasMax = $this->hasMaxEscalationColumn();
        $hasHierarchy = $this->hasHierarchyLevelColumn();
        $hierarchyLevel = max(1, (int) ($stage['hierarchy_level'] ?? 1));

        if ($hasMax && $hasHierarchy) {
            $sql = 'INSERT INTO adms_request_type_stages
                        (request_type_id, stage_order, stage_code, stage_label, approver_kind, hierarchy_level,
                         fixed_user_id, escalate_after_hours, escalate_policy, max_escalation_levels,
                         is_active, created_at, updated_at)
                    VALUES
                        (:type_id, :ord, :code, :label, :kind, :hierarchy_level, :fixed_user, :hours, :policy, :max_levels,
                         1, NOW(), NOW())';
            $this->getConnection()->prepare($sql)->execute([
                ':type_id' => $typeId,
                ':ord' => $stage['order'],
                ':code' => $stage['code'],
                ':label' => $stage['label'],
                ':kind' => $stage['kind'],
                ':hierarchy_level' => $hierarchyLevel,
                ':fixed_user' => $stage['fixed_user_id'],
                ':hours' => $stage['hours'],
                ':policy' => $stage['policy'],
                ':max_levels' => $stage['max_levels'],
            ]);
            return;
        }

        if ($hasMax) {
            $sql = 'INSERT INTO adms_request_type_stages
                        (request_type_id, stage_order, stage_code, stage_label, approver_kind,
                         fixed_user_id, escalate_after_hours, escalate_policy, max_escalation_levels,
                         is_active, created_at, updated_at)
                    VALUES
                        (:type_id, :ord, :code, :label, :kind, :fixed_user, :hours, :policy, :max_levels,
                         1, NOW(), NOW())';
            $this->getConnection()->prepare($sql)->execute([
                ':type_id' => $typeId,
                ':ord' => $stage['order'],
                ':code' => $stage['code'],
                ':label' => $stage['label'],
                ':kind' => $stage['kind'],
                ':fixed_user' => $stage['fixed_user_id'],
                ':hours' => $stage['hours'],
                ':policy' => $stage['policy'],
                ':max_levels' => $stage['max_levels'],
            ]);
            return;
        }

        $sql = 'INSERT INTO adms_request_type_stages
                    (request_type_id, stage_order, stage_code, stage_label, approver_kind,
                     fixed_user_id, escalate_after_hours, escalate_policy, is_active, created_at, updated_at)
                VALUES
                    (:type_id, :ord, :code, :label, :kind, :fixed_user, :hours, :policy, 1, NOW(), NOW())';
        $this->getConnection()->prepare($sql)->execute([
            ':type_id' => $typeId,
            ':ord' => $stage['order'],
            ':code' => $stage['code'],
            ':label' => $stage['label'],
            ':kind' => $stage['kind'],
            ':fixed_user' => $stage['fixed_user_id'],
            ':hours' => $stage['hours'],
            ':policy' => $stage['policy'],
        ]);
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_request_type_stages LIMIT 1');
            $exists = true;
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    private function hasMaxEscalationColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query(
                "SHOW COLUMNS FROM adms_request_type_stages LIKE 'max_escalation_levels'"
            );
            $has = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $has = false;
        }

        return $has;
    }

    private function hasHierarchyLevelColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query(
                "SHOW COLUMNS FROM adms_request_type_stages LIKE 'hierarchy_level'"
            );
            $has = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $has = false;
        }

        return $has;
    }
}

<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CriticalPositionsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\SuccessionSuccessorsRepository;
use App\adms\Models\Repository\UsersRepository;
use PDOException;

class SuccessionService
{
    public const RISK_LEVELS = ['high', 'medium', 'low'];
    public const STATUSES = ['active', 'inactive'];
    public const READINESS = ['ready_now', 'ready_1_2y', 'ready_3y', 'emergency'];

    public function __construct(
        private readonly ?CriticalPositionsRepository $critical = null,
        private readonly ?SuccessionSuccessorsRepository $successors = null,
        private readonly ?PositionsRepository $positions = null,
        private readonly ?UsersRepository $users = null,
    ) {
    }

    public function createCritical(array $input, int $createdBy): array
    {
        $positionId = (int) ($input['position_id'] ?? 0);
        $risk = (string) ($input['risk_level'] ?? 'medium');
        $status = (string) ($input['status'] ?? 'active');
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($positionId <= 0) {
            return ['ok' => false, 'error' => 'Cargo é obrigatório.'];
        }
        if (!$this->positionsRepo()->getPosition($positionId)) {
            return ['ok' => false, 'error' => 'Cargo informado não existe.'];
        }
        if (!in_array($risk, self::RISK_LEVELS, true)) {
            return ['ok' => false, 'error' => 'Nível de risco inválido.'];
        }
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        if ($this->criticalRepo()->getByPositionId($positionId)) {
            return ['ok' => false, 'error' => 'Este cargo já está marcado como crítico.'];
        }

        try {
            $id = $this->criticalRepo()->create([
                'position_id' => $positionId,
                'risk_level' => $risk,
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null,
                'created_by' => $createdBy,
            ]);
        } catch (PDOException) {
            return ['ok' => false, 'error' => 'Não foi possível criar o cargo crítico.'];
        }

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar cargo crítico.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function updateCritical(int $id, array $input): array
    {
        if (!$this->criticalRepo()->getById($id)) {
            return ['ok' => false, 'error' => 'Cargo crítico não encontrado.'];
        }

        $risk = (string) ($input['risk_level'] ?? 'medium');
        $status = (string) ($input['status'] ?? 'active');
        $notes = trim((string) ($input['notes'] ?? ''));

        if (!in_array($risk, self::RISK_LEVELS, true)) {
            return ['ok' => false, 'error' => 'Nível de risco inválido.'];
        }
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }

        if (!$this->criticalRepo()->update($id, [
            'risk_level' => $risk,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
        ])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function addSuccessor(int $criticalPositionId, array $input, int $nominatedBy): array
    {
        if (!$this->criticalRepo()->getById($criticalPositionId)) {
            return ['ok' => false, 'error' => 'Cargo crítico não encontrado.'];
        }

        $userId = (int) ($input['user_id'] ?? 0);
        $readiness = (string) ($input['readiness'] ?? 'ready_1_2y');
        $order = (int) ($input['priority_order'] ?? 1);
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($userId <= 0 || !$this->usersRepo()->getUser($userId)) {
            return ['ok' => false, 'error' => 'Sucessor inválido.'];
        }
        if (!in_array($readiness, self::READINESS, true)) {
            return ['ok' => false, 'error' => 'Readiness inválido.'];
        }
        if ($order < 1 || $order > 99) {
            return ['ok' => false, 'error' => 'Ordem deve ser entre 1 e 99.'];
        }

        try {
            $id = $this->successorsRepo()->create([
                'critical_position_id' => $criticalPositionId,
                'user_id' => $userId,
                'readiness' => $readiness,
                'priority_order' => $order,
                'notes' => $notes !== '' ? $notes : null,
                'nominated_by' => $nominatedBy,
            ]);
        } catch (PDOException) {
            return ['ok' => false, 'error' => 'Sucessor já vinculado a este cargo.'];
        }

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao adicionar sucessor.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function updateSuccessor(int $successorId, int $criticalPositionId, array $input): array
    {
        $row = $this->successorsRepo()->getById($successorId);
        if (!$row || (int) $row['critical_position_id'] !== $criticalPositionId) {
            return ['ok' => false, 'error' => 'Sucessor não encontrado.'];
        }

        $readiness = (string) ($input['readiness'] ?? 'ready_1_2y');
        $order = (int) ($input['priority_order'] ?? 1);
        $notes = trim((string) ($input['notes'] ?? ''));

        if (!in_array($readiness, self::READINESS, true)) {
            return ['ok' => false, 'error' => 'Readiness inválido.'];
        }
        if ($order < 1 || $order > 99) {
            return ['ok' => false, 'error' => 'Ordem deve ser entre 1 e 99.'];
        }

        if (!$this->successorsRepo()->update($successorId, [
            'critical_position_id' => $criticalPositionId,
            'readiness' => $readiness,
            'priority_order' => $order,
            'notes' => $notes !== '' ? $notes : null,
        ])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar sucessor.'];
        }

        return ['ok' => true, 'id' => $successorId];
    }

    public function removeSuccessor(int $successorId, int $criticalPositionId): array
    {
        $row = $this->successorsRepo()->getById($successorId);
        if (!$row || (int) $row['critical_position_id'] !== $criticalPositionId) {
            return ['ok' => false, 'error' => 'Sucessor não encontrado.'];
        }

        if (!$this->successorsRepo()->delete($successorId, $criticalPositionId)) {
            return ['ok' => false, 'error' => 'Erro ao remover sucessor.'];
        }

        return ['ok' => true];
    }

    private function criticalRepo(): CriticalPositionsRepository
    {
        return $this->critical ?? new CriticalPositionsRepository();
    }

    private function successorsRepo(): SuccessionSuccessorsRepository
    {
        return $this->successors ?? new SuccessionSuccessorsRepository();
    }

    private function positionsRepo(): PositionsRepository
    {
        return $this->positions ?? new PositionsRepository();
    }

    private function usersRepo(): UsersRepository
    {
        return $this->users ?? new UsersRepository();
    }
}

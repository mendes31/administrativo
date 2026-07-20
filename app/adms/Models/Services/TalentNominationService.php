<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\TalentNominationsRepository;
use App\adms\Models\Repository\UsersRepository;
use PDOException;

/**
 * Regras de nomeação HiPo / talent pool (Fase 5 Expand).
 */
class TalentNominationService
{
    public const STATUSES = ['active', 'removed'];

    public function __construct(
        private readonly ?TalentNominationsRepository $repository = null,
        private readonly ?PerformanceCyclesRepository $cycles = null,
        private readonly ?UsersRepository $users = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validatePayload(array $input, bool $forUpdate = false): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $cycleId = (int) ($input['performance_cycle_id'] ?? 0);
        $status = (string) ($input['status'] ?? 'active');
        $nineBox = isset($input['nine_box']) && $input['nine_box'] !== ''
            ? (int) $input['nine_box']
            : null;
        $notes = trim((string) ($input['notes'] ?? ''));

        if (!$forUpdate) {
            if ($userId <= 0) {
                return ['ok' => false, 'error' => 'Colaborador é obrigatório.'];
            }
            if ($cycleId <= 0) {
                return ['ok' => false, 'error' => 'Ciclo é obrigatório.'];
            }
            if (!$this->cyclesRepo()->getById($cycleId)) {
                return ['ok' => false, 'error' => 'Ciclo informado não existe.'];
            }
            $user = $this->usersRepo()->getUser($userId);
            if (!$user) {
                return ['ok' => false, 'error' => 'Colaborador não encontrado.'];
            }
        }

        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        if ($nineBox !== null && ($nineBox < 1 || $nineBox > 9)) {
            return ['ok' => false, 'error' => 'Box Nine Box deve ser entre 1 e 9.'];
        }

        $data = [
            'status' => $status,
            'nine_box' => $nineBox,
            'notes' => $notes !== '' ? $notes : null,
        ];

        if (!$forUpdate) {
            $data['user_id'] = $userId;
            $data['performance_cycle_id'] = $cycleId;
        }

        return ['ok' => true, 'data' => $data];
    }

    public function create(array $input, int $nominatedBy): array
    {
        $validated = $this->validatePayload($input);
        if (!$validated['ok']) {
            return $validated;
        }

        $data = $validated['data'];
        $existing = $this->repo()->getByUserAndCycle(
            (int) $data['user_id'],
            (int) $data['performance_cycle_id']
        );

        if ($existing) {
            if (($existing['status'] ?? '') === 'active') {
                return ['ok' => false, 'error' => 'Colaborador já está no talent pool deste ciclo.'];
            }
            // Reativar nomeação removida
            $update = [
                'status' => 'active',
                'nine_box' => $data['nine_box'] ?? $existing['nine_box'],
                'notes' => $data['notes'] ?? $existing['notes'],
            ];
            if (!$this->repo()->update((int) $existing['id'], $update)) {
                return ['ok' => false, 'error' => 'Erro ao reativar nomeação.'];
            }

            return ['ok' => true, 'id' => (int) $existing['id'], 'reactivated' => true];
        }

        $data['nominated_by'] = $nominatedBy;
        $data['status'] = 'active';

        try {
            $id = $this->repo()->create($data);
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => 'Não foi possível criar a nomeação (possível duplicidade).'];
        }

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar nomeação.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input): array
    {
        $current = $this->repo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Nomeação não encontrada.'];
        }

        $validated = $this->validatePayload($input, true);
        if (!$validated['ok']) {
            return $validated;
        }

        if (!$this->repo()->update($id, $validated['data'])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar nomeação.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    private function repo(): TalentNominationsRepository
    {
        return $this->repository ?? new TalentNominationsRepository();
    }

    private function cyclesRepo(): PerformanceCyclesRepository
    {
        return $this->cycles ?? new PerformanceCyclesRepository();
    }

    private function usersRepo(): UsersRepository
    {
        return $this->users ?? new UsersRepository();
    }
}

<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CareerLevelsRepository;
use App\adms\Models\Repository\CareerPromotionsRepository;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;

class CareerService
{
    public const TRACK_STATUSES = ['active', 'inactive'];
    public const PROMO_STATUSES = ['draft', 'approved', 'applied', 'cancelled'];

    public function __construct(
        private readonly ?CareerTracksRepository $tracks = null,
        private readonly ?CareerLevelsRepository $levels = null,
        private readonly ?CareerPromotionsRepository $promotions = null,
        private readonly ?PositionsRepository $positions = null,
        private readonly ?UsersRepository $users = null,
    ) {
    }

    public function createTrack(array $input, int $createdBy): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $status = (string) ($input['status'] ?? 'active');
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome da trilha é obrigatório.'];
        }
        if (!in_array($status, self::TRACK_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        $id = $this->tracksRepo()->create([
            'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
            'created_by' => $createdBy,
        ]);
        return $id > 0 ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'Erro ao criar trilha.'];
    }

    public function updateTrack(int $id, array $input): array
    {
        if (!$this->tracksRepo()->getById($id)) {
            return ['ok' => false, 'error' => 'Trilha não encontrada.'];
        }
        $name = trim((string) ($input['name'] ?? ''));
        $status = (string) ($input['status'] ?? 'active');
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome da trilha é obrigatório.'];
        }
        if (!in_array($status, self::TRACK_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        $ok = $this->tracksRepo()->update($id, [
            'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'status' => $status,
        ]);
        return $ok ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'Erro ao atualizar.'];
    }

    public function addLevel(int $trackId, array $input): array
    {
        if (!$this->tracksRepo()->getById($trackId)) {
            return ['ok' => false, 'error' => 'Trilha não encontrada.'];
        }
        $name = trim((string) ($input['name'] ?? ''));
        $order = (int) ($input['level_order'] ?? 1);
        $positionId = !empty($input['position_id']) ? (int) $input['position_id'] : null;
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome do nível é obrigatório.'];
        }
        if ($order < 1 || $order > 99) {
            return ['ok' => false, 'error' => 'Ordem deve ser entre 1 e 99.'];
        }
        if ($positionId !== null && !$this->positionsRepo()->getPosition($positionId)) {
            return ['ok' => false, 'error' => 'Cargo do nível inválido.'];
        }
        $id = $this->levelsRepo()->create([
            'career_track_id' => $trackId,
            'name' => $name,
            'level_order' => $order,
            'position_id' => $positionId,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ]);
        return $id > 0 ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'Erro ao adicionar nível.'];
    }

    public function updateLevel(int $levelId, int $trackId, array $input): array
    {
        $level = $this->levelsRepo()->getById($levelId);
        if (!$level || (int) $level['career_track_id'] !== $trackId) {
            return ['ok' => false, 'error' => 'Nível não encontrado.'];
        }
        $name = trim((string) ($input['name'] ?? ''));
        $order = (int) ($input['level_order'] ?? 1);
        $positionId = !empty($input['position_id']) ? (int) $input['position_id'] : null;
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome do nível é obrigatório.'];
        }
        if ($order < 1 || $order > 99) {
            return ['ok' => false, 'error' => 'Ordem deve ser entre 1 e 99.'];
        }
        $ok = $this->levelsRepo()->update($levelId, [
            'career_track_id' => $trackId,
            'name' => $name,
            'level_order' => $order,
            'position_id' => $positionId,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ]);
        return $ok ? ['ok' => true, 'id' => $levelId] : ['ok' => false, 'error' => 'Erro ao atualizar nível.'];
    }

    public function removeLevel(int $levelId, int $trackId): array
    {
        $level = $this->levelsRepo()->getById($levelId);
        if (!$level || (int) $level['career_track_id'] !== $trackId) {
            return ['ok' => false, 'error' => 'Nível não encontrado.'];
        }
        return $this->levelsRepo()->delete($levelId, $trackId)
            ? ['ok' => true]
            : ['ok' => false, 'error' => 'Erro ao remover nível.'];
    }

    public function createPromotion(array $input, int $createdBy): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $toPos = (int) ($input['to_position_id'] ?? 0);
        $fromPos = !empty($input['from_position_id']) ? (int) $input['from_position_id'] : null;
        $trackId = !empty($input['career_track_id']) ? (int) $input['career_track_id'] : null;
        $levelId = !empty($input['career_level_id']) ? (int) $input['career_level_id'] : null;
        $date = trim((string) ($input['effective_date'] ?? ''));
        $status = (string) ($input['status'] ?? 'draft');

        if ($userId <= 0 || !$this->usersRepo()->getUser($userId)) {
            return ['ok' => false, 'error' => 'Colaborador inválido.'];
        }
        if ($toPos <= 0 || !$this->positionsRepo()->getPosition($toPos)) {
            return ['ok' => false, 'error' => 'Cargo de destino inválido.'];
        }
        if ($date === '' || strtotime($date) === false) {
            return ['ok' => false, 'error' => 'Data efetiva inválida.'];
        }
        if (!in_array($status, self::PROMO_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        if ($fromPos === null) {
            $user = $this->usersRepo()->getUser($userId);
            if (is_array($user) && !empty($user['user_position_id'])) {
                $fromPos = (int) $user['user_position_id'];
            }
        }

        $id = $this->promotionsRepo()->create([
            'user_id' => $userId,
            'from_position_id' => $fromPos,
            'to_position_id' => $toPos,
            'career_track_id' => $trackId,
            'career_level_id' => $levelId,
            'effective_date' => $date,
            'status' => $status === 'applied' ? 'draft' : $status,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);
        return $id > 0 ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'Erro ao criar promoção.'];
    }

    public function updatePromotion(int $id, array $input, int $actorId): array
    {
        $current = $this->promotionsRepo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Promoção não encontrada.'];
        }
        if (($current['status'] ?? '') === 'applied') {
            return ['ok' => false, 'error' => 'Promoção já aplicada não pode ser editada.'];
        }

        $toPos = (int) ($input['to_position_id'] ?? $current['to_position_id']);
        $fromPos = isset($input['from_position_id']) && $input['from_position_id'] !== ''
            ? (int) $input['from_position_id'] : ($current['from_position_id'] ?? null);
        $trackId = isset($input['career_track_id']) && $input['career_track_id'] !== ''
            ? (int) $input['career_track_id'] : ($current['career_track_id'] ?? null);
        $levelId = isset($input['career_level_id']) && $input['career_level_id'] !== ''
            ? (int) $input['career_level_id'] : ($current['career_level_id'] ?? null);
        $date = trim((string) ($input['effective_date'] ?? $current['effective_date']));
        $status = (string) ($input['status'] ?? $current['status']);
        $notes = array_key_exists('notes', $input)
            ? (trim((string) $input['notes']) ?: null)
            : ($current['notes'] ?? null);

        if ($toPos <= 0 || !$this->positionsRepo()->getPosition($toPos)) {
            return ['ok' => false, 'error' => 'Cargo de destino inválido.'];
        }
        if ($date === '' || strtotime($date) === false) {
            return ['ok' => false, 'error' => 'Data efetiva inválida.'];
        }
        if (!in_array($status, self::PROMO_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }

        $approvedBy = $current['approved_by'] ?? null;
        $approvedAt = $current['approved_at'] ?? null;
        $appliedAt = $current['applied_at'] ?? null;

        if ($status === 'approved' && ($current['status'] ?? '') === 'draft') {
            $approvedBy = $actorId;
            $approvedAt = date('Y-m-d H:i:s');
        }
        if ($status === 'applied') {
            if (!in_array($current['status'] ?? '', ['approved', 'draft'], true)) {
                return ['ok' => false, 'error' => 'Só é possível aplicar promoção em rascunho ou aprovada.'];
            }
            if (($current['status'] ?? '') === 'draft') {
                $approvedBy = $actorId;
                $approvedAt = date('Y-m-d H:i:s');
            }
            if (!$this->promotionsRepo()->applyUserPosition((int) $current['user_id'], $toPos)) {
                return ['ok' => false, 'error' => 'Erro ao aplicar cargo no colaborador.'];
            }
            $appliedAt = date('Y-m-d H:i:s');
        }

        $ok = $this->promotionsRepo()->update($id, [
            'from_position_id' => $fromPos,
            'to_position_id' => $toPos,
            'career_track_id' => $trackId,
            'career_level_id' => $levelId,
            'effective_date' => $date,
            'status' => $status,
            'notes' => $notes,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'applied_at' => $appliedAt,
        ]);
        return $ok ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'Erro ao atualizar promoção.'];
    }

    private function tracksRepo(): CareerTracksRepository
    {
        return $this->tracks ?? new CareerTracksRepository();
    }

    private function levelsRepo(): CareerLevelsRepository
    {
        return $this->levels ?? new CareerLevelsRepository();
    }

    private function promotionsRepo(): CareerPromotionsRepository
    {
        return $this->promotions ?? new CareerPromotionsRepository();
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

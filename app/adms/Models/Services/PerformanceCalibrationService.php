<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use Throwable;

/**
 * Regras de sessão de calibração (básica + avançada).
 */
class PerformanceCalibrationService
{
    public const STATUSES = ['draft', 'open', 'locked'];

    public function __construct(
        private readonly ?PerformanceCalibrationsRepository $repository = null,
        private readonly ?PerformanceCyclesRepository $cyclesRepository = null,
        private readonly ?PerformanceReviewsRepository $reviewsRepository = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int, data?: array<string, mixed>}
     */
    public function create(array $input, int $createdBy): array
    {
        $cycleId = (int) ($input['performance_cycle_id'] ?? 0);
        if ($cycleId <= 0) {
            return ['ok' => false, 'error' => 'Selecione o ciclo.'];
        }

        $cycle = $this->cycles()->getById($cycleId);
        if (!$cycle) {
            return ['ok' => false, 'error' => 'Ciclo não encontrado.'];
        }
        if (($cycle['status'] ?? '') === 'closed') {
            return ['ok' => false, 'error' => 'Não é possível abrir calibração em ciclo fechado.'];
        }

        if ($this->repo()->getByCycleId($cycleId)) {
            return ['ok' => false, 'error' => 'Já existe calibração para este ciclo.'];
        }

        $status = (string) ($input['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'open'], true)) {
            $status = 'draft';
        }

        $id = $this->repo()->create([
            'performance_cycle_id' => $cycleId,
            'status' => $status,
            'session_notes' => trim((string) ($input['session_notes'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar calibração.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function update(int $id, array $input, int $actorId): array
    {
        $current = $this->repo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Calibração não encontrada.'];
        }

        $status = (string) ($input['status'] ?? $current['status']);
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }

        if (($current['status'] ?? '') === 'locked') {
            return ['ok' => false, 'error' => 'Calibração travada (somente leitura).'];
        }

        if (isset($input['scores']) && is_array($input['scores'])) {
            $scoresResult = $this->applyScores($id, $input['scores']);
            if (!$scoresResult['ok']) {
                return $scoresResult;
            }
        }

        $payload = [
            'session_notes' => trim((string) ($input['session_notes'] ?? '')) ?: null,
            'status' => $status,
        ];

        if ($status === 'locked') {
            $payload['locked_at'] = date('Y-m-d H:i:s');
            $payload['locked_by'] = $actorId;
        }

        if (!$this->repo()->update($id, $payload)) {
            return ['ok' => false, 'error' => 'Erro ao atualizar calibração.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Avaliações do ciclo com calibração travada não podem ser editadas fora da sessão.
     *
     * @param array<string, mixed> $review
     * @return array{ok: bool, error?: string}
     */
    public function assertReviewEditable(array $review): array
    {
        $cycleId = (int) ($review['performance_cycle_id'] ?? 0);
        if ($cycleId <= 0) {
            return ['ok' => true];
        }
        if ($this->isCycleReviewsLocked($cycleId)) {
            return [
                'ok' => false,
                'error' => 'Ciclo com calibração travada — avaliações somente leitura.',
            ];
        }

        return ['ok' => true];
    }

    public function isCycleReviewsLocked(int $cycleId): bool
    {
        if ($cycleId <= 0) {
            return false;
        }
        $cal = $this->repo()->getByCycleId($cycleId);

        return is_array($cal) && ($cal['status'] ?? '') === 'locked';
    }

    /**
     * Valida um par de notas (sem I/O).
     *
     * @return array{ok: bool, error?: string, overall?: float|null, potential?: float|null}
     */
    public function validateScorePair(mixed $overallRaw, mixed $potentialRaw): array
    {
        $overall = $this->parseScore($overallRaw);
        if ($overall === false) {
            return ['ok' => false, 'error' => 'Nota de desempenho inválida (use 0 a 10).'];
        }
        $potential = $this->parseScore($potentialRaw);
        if ($potential === false) {
            return ['ok' => false, 'error' => 'Nota de potencial inválida (use 0 a 10).'];
        }

        return ['ok' => true, 'overall' => $overall, 'potential' => $potential];
    }

    /**
     * @param array<int|string, mixed> $scoresByReviewId
     * @return array{ok: bool, error?: string, updated?: int}
     */
    private function applyScores(int $calibrationId, array $scoresByReviewId): array
    {
        $cal = $this->repo()->getById($calibrationId);
        if (!$cal) {
            return ['ok' => false, 'error' => 'Calibração não encontrada.'];
        }
        if (($cal['status'] ?? '') === 'locked') {
            return ['ok' => false, 'error' => 'Calibração travada (somente leitura).'];
        }

        $cycleId = (int) $cal['performance_cycle_id'];
        $updated = 0;

        try {
            foreach ($scoresByReviewId as $reviewId => $pair) {
                if (!is_array($pair)) {
                    continue;
                }
                $rid = (int) $reviewId;
                if ($rid <= 0) {
                    continue;
                }

                $review = $this->reviews()->getById($rid);
                if (!$review || (int) ($review['performance_cycle_id'] ?? 0) !== $cycleId) {
                    return ['ok' => false, 'error' => "Avaliação #{$rid} não pertence a este ciclo."];
                }
                if (($review['status'] ?? '') === 'cancelled') {
                    continue;
                }

                $parsed = $this->validateScorePair(
                    $pair['overall_score'] ?? null,
                    $pair['potential_score'] ?? null
                );
                if (!$parsed['ok']) {
                    return $parsed;
                }

                $payload = [
                    'overall_score' => $parsed['overall'],
                    'potential_score' => $parsed['potential'],
                ];

                if (
                    ($review['overall_score_pre_calibration'] ?? null) === null
                    && ($review['potential_score_pre_calibration'] ?? null) === null
                ) {
                    $payload['overall_score_pre_calibration'] = $review['overall_score'] ?? null;
                    $payload['potential_score_pre_calibration'] = $review['potential_score'] ?? null;
                }

                if (!$this->reviews()->update($rid, $payload)) {
                    return ['ok' => false, 'error' => "Falha ao salvar notas da avaliação #{$rid}."];
                }
                $updated++;
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Falha ao salvar notas: ' . $e->getMessage()];
        }

        return ['ok' => true, 'updated' => $updated];
    }

    /**
     * @return float|null|false false = inválido
     */
    private function parseScore(mixed $raw): float|null|false
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (!is_numeric($raw)) {
            return false;
        }
        $value = (float) $raw;
        if ($value < 0 || $value > 10) {
            return false;
        }

        return round($value, 2);
    }

    private function repo(): PerformanceCalibrationsRepository
    {
        return $this->repository ?? new PerformanceCalibrationsRepository();
    }

    private function cycles(): PerformanceCyclesRepository
    {
        return $this->cyclesRepository ?? new PerformanceCyclesRepository();
    }

    private function reviews(): PerformanceReviewsRepository
    {
        return $this->reviewsRepository ?? new PerformanceReviewsRepository();
    }
}

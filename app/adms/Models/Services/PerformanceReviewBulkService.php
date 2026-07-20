<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\UsersRepository;
use Throwable;

/**
 * Geração em massa de avaliações a partir do ciclo (Expand Fase 5).
 */
class PerformanceReviewBulkService
{
    public const REVIEW_TYPES = ['90', '180', '360', 'annual'];
    public const SCOPES = ['all_active', 'department', 'manager'];
    public const REVIEWER_MODES = ['supervisor', 'fixed'];

    public function __construct(
        private readonly ?PerformanceCyclesRepository $cyclesRepo = null,
        private readonly ?PerformanceReviewsRepository $reviewsRepo = null,
        private readonly ?UsersRepository $usersRepo = null,
        private readonly ?PerformanceCycleService $cycleService = null,
    ) {
    }

    /**
     * Valida opções do formulário (sem I/O de banco além do necessário no generate).
     *
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validateOptions(array $input): array
    {
        $reviewType = (string) ($input['review_type'] ?? '180');
        $scope = (string) ($input['scope'] ?? 'all_active');
        $reviewerMode = (string) ($input['reviewer_mode'] ?? 'supervisor');
        $departmentId = (int) ($input['department_id'] ?? 0);
        $managerId = (int) ($input['manager_id'] ?? 0);
        $reviewerId = (int) ($input['reviewer_id'] ?? 0);
        $skipExisting = isset($input['skip_existing'])
            && in_array((string) $input['skip_existing'], ['1', 'on', 'true'], true);

        if (!in_array($reviewType, self::REVIEW_TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de avaliação inválido.'];
        }
        if (!in_array($scope, self::SCOPES, true)) {
            return ['ok' => false, 'error' => 'Escopo inválido.'];
        }
        if (!in_array($reviewerMode, self::REVIEWER_MODES, true)) {
            return ['ok' => false, 'error' => 'Modo de avaliador inválido.'];
        }
        if ($scope === 'department' && $departmentId <= 0) {
            return ['ok' => false, 'error' => 'Selecione o departamento.'];
        }
        if ($scope === 'manager' && $managerId <= 0) {
            return ['ok' => false, 'error' => 'Selecione o gestor.'];
        }
        if ($reviewerMode === 'fixed' && $reviewerId <= 0) {
            return ['ok' => false, 'error' => 'Selecione o avaliador fixo.'];
        }

        return [
            'ok' => true,
            'data' => [
                'review_type' => $reviewType,
                'scope' => $scope,
                'reviewer_mode' => $reviewerMode,
                'department_id' => $departmentId,
                'manager_id' => $managerId,
                'reviewer_id' => $reviewerId,
                'skip_existing' => $skipExisting,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   ok: bool,
     *   error?: string,
     *   created?: int,
     *   skipped?: int,
     *   errors?: int,
     *   message?: string
     * }
     */
    public function generateForCycle(int $cycleId, array $input, int $actorId, bool $fullAccess): array
    {
        if ($actorId <= 0) {
            return ['ok' => false, 'error' => 'Usuário não autenticado.'];
        }

        $link = $this->cycles()->assertMayLink($cycleId);
        if (!$link['ok']) {
            return ['ok' => false, 'error' => $link['error'] ?? 'Ciclo inválido.'];
        }

        $cycle = $this->cyclesRepo()->getById($cycleId);
        if (!$cycle) {
            return ['ok' => false, 'error' => 'Ciclo não encontrado.'];
        }

        $validated = $this->validateOptions($input);
        if (!$validated['ok']) {
            return $validated;
        }
        /** @var array<string, mixed> $opts */
        $opts = $validated['data'];

        if (!$fullAccess) {
            if ($opts['scope'] === 'manager' && (int) $opts['manager_id'] !== $actorId) {
                return ['ok' => false, 'error' => 'Gestores só podem gerar para os próprios subordinados.'];
            }
            if (in_array($opts['scope'], ['all_active', 'department'], true)) {
                $opts['scope'] = 'manager';
                $opts['manager_id'] = $actorId;
            }
        }

        $candidates = $this->resolveCandidates($opts, $actorId, $fullAccess);
        if ($candidates === []) {
            return ['ok' => false, 'error' => 'Nenhum colaborador encontrado para o escopo informado.'];
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;
        $periodStart = (string) $cycle['period_start'];
        $periodEnd = (string) $cycle['period_end'];
        $reviewDate = date('Y-m-d');
        $reviewType = (string) $opts['review_type'];
        $skipExisting = (bool) $opts['skip_existing'];

        $pdo = $this->reviewsRepo()->getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($candidates as $user) {
                $employeeId = (int) ($user['id'] ?? 0);
                if ($employeeId <= 0) {
                    $errors++;
                    continue;
                }

                if ($skipExisting && $this->reviewsRepo()->existsForEmployeeCycleType($employeeId, $cycleId, $reviewType)) {
                    $skipped++;
                    continue;
                }

                $reviewerId = $this->resolveReviewerId($opts, $user, $actorId);
                if ($reviewerId <= 0) {
                    $errors++;
                    continue;
                }

                $id = $this->reviewsRepo()->create([
                    'employee_id' => $employeeId,
                    'reviewer_id' => $reviewerId,
                    'review_type' => $reviewType,
                    'performance_cycle_id' => $cycleId,
                    'review_period_start' => $periodStart,
                    'review_period_end' => $periodEnd,
                    'review_date' => $reviewDate,
                    'status' => 'draft',
                    'created_by' => $actorId,
                ]);

                if ($id > 0) {
                    $created++;
                } else {
                    $errors++;
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'Falha ao gerar avaliações: ' . $e->getMessage()];
        }

        return [
            'ok' => true,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(
                'Geração concluída: %d criada(s), %d pulada(s), %d erro(s).',
                $created,
                $skipped,
                $errors
            ),
        ];
    }

    /**
     * @param array<string, mixed> $opts
     * @return list<array<string, mixed>>
     */
    private function resolveCandidates(array $opts, int $actorId, bool $fullAccess): array
    {
        $scope = (string) $opts['scope'];
        if ($scope === 'manager') {
            return $this->usersRepo()->getSubordinates((int) $opts['manager_id']);
        }

        if ($scope === 'department') {
            return $this->usersRepo()->listActiveByDepartment((int) $opts['department_id']);
        }

        // all_active
        if ($fullAccess) {
            return $this->usersRepo()->getAllUsersForSelect();
        }

        return $this->usersRepo()->getSubordinates($actorId);
    }

    /**
     * @param array<string, mixed> $opts
     * @param array<string, mixed> $user
     */
    private function resolveReviewerId(array $opts, array $user, int $actorId): int
    {
        if (($opts['reviewer_mode'] ?? '') === 'fixed') {
            return (int) $opts['reviewer_id'];
        }

        $supervisorId = (int) ($user['immediate_supervisor_id'] ?? 0);
        if ($supervisorId > 0) {
            return $supervisorId;
        }

        // getSubordinates / select podem não trazer supervisor — buscar se necessário
        $full = $this->usersRepo()->getUser((int) ($user['id'] ?? 0));
        if (is_array($full)) {
            $supervisorId = (int) ($full['immediate_supervisor_id'] ?? 0);
            if ($supervisorId > 0) {
                return $supervisorId;
            }
        }

        return $actorId;
    }

    private function cycles(): PerformanceCycleService
    {
        return $this->cycleService ?? new PerformanceCycleService($this->cyclesRepo());
    }

    private function cyclesRepo(): PerformanceCyclesRepository
    {
        return $this->cyclesRepo ?? new PerformanceCyclesRepository();
    }

    private function reviewsRepo(): PerformanceReviewsRepository
    {
        return $this->reviewsRepo ?? new PerformanceReviewsRepository();
    }

    private function usersRepo(): UsersRepository
    {
        return $this->usersRepo ?? new UsersRepository();
    }
}

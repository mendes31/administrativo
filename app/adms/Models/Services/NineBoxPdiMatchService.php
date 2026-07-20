<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Matching Nine Box → PDI (1º incremento): cria rascunho a partir do quadrante.
 */
class NineBoxPdiMatchService
{
    /** @var array<int, array{title: string, description: string}> */
    public const BOX_GUIDANCE = [
        1 => [
            'title' => 'Reposicionar',
            'description' => 'Reposicionar em função mais adequada ou iniciar processo de desligamento. Defina ações concretas de realocação ou offboarding.',
        ],
        2 => [
            'title' => 'Manter',
            'description' => 'Manter na função atual e monitorar desempenho. Inclua ações de acompanhamento periódico.',
        ],
        3 => [
            'title' => 'Desenvolver',
            'description' => 'Investir em desenvolvimento — pode crescer. Priorize competências e treinamentos.',
        ],
        4 => [
            'title' => 'Monitorar',
            'description' => 'Monitorar de perto e definir plano de ação. Foque em gaps de desempenho.',
        ],
        5 => [
            'title' => 'Manter',
            'description' => 'Manter na função — está adequado. Reforce consistência e prepare próximos desafios leves.',
        ],
        6 => [
            'title' => 'Desenvolver',
            'description' => 'Desenvolver para crescimento. Combine projetos desafiadores e mentoria.',
        ],
        7 => [
            'title' => 'Desenvolver',
            'description' => 'Alto potencial — desenvolver competências. Acelere exposição e feedback.',
        ],
        8 => [
            'title' => 'Promover',
            'description' => 'Promover para posição de maior responsabilidade. Alinhe trilha de carreira e prontidão.',
        ],
        9 => [
            'title' => 'Estrela',
            'description' => 'Estrela: promover e reter — alto valor estratégico. Foque retenção, sucessão e desafios estratégicos.',
        ],
    ];

    public function __construct(
        private readonly ?PdiPlanService $pdi = null,
        private readonly ?PdiPlansRepository $plans = null,
        private readonly ?PerformanceCyclesRepository $cycles = null,
        private readonly ?UsersRepository $users = null,
    ) {
    }

    /**
     * @return array{title: string, description: string}|null
     */
    public static function guidanceForBox(int $box): ?array
    {
        return self::BOX_GUIDANCE[$box] ?? null;
    }

    /**
     * Monta payload de PDI sugerido (sem gravar).
     *
     * @param array{name?: string, period_start?: string, period_end?: string} $cycle
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public static function buildSuggestedPlanPayload(
        int $userId,
        int $cycleId,
        int $nineBox,
        array $cycle,
        ?int $managerId = null
    ): array {
        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Colaborador é obrigatório.'];
        }
        if ($cycleId <= 0) {
            return ['ok' => false, 'error' => 'Ciclo é obrigatório.'];
        }
        $guide = self::guidanceForBox($nineBox);
        if ($guide === null) {
            return ['ok' => false, 'error' => 'Box Nine Box deve ser entre 1 e 9.'];
        }

        $periodStart = trim((string) ($cycle['period_start'] ?? ''));
        $periodEnd = trim((string) ($cycle['period_end'] ?? ''));
        if ($periodStart === '' || $periodEnd === '') {
            return ['ok' => false, 'error' => 'Ciclo sem período definido; ajuste as datas do ciclo.'];
        }

        $cycleName = trim((string) ($cycle['name'] ?? ''));
        $title = sprintf('PDI Box %d — %s', $nineBox, $guide['title']);
        if ($cycleName !== '') {
            $title .= ' (' . $cycleName . ')';
        }

        return [
            'ok' => true,
            'data' => [
                'user_id' => $userId,
                'manager_id' => ($managerId !== null && $managerId > 0) ? $managerId : null,
                'performance_cycle_id' => $cycleId,
                'title' => $title,
                'description' => $guide['description'] . ' (origem: Matriz 9BOX, box ' . $nineBox . ')',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'draft',
                'career_goal' => $guide['title'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int, reused?: bool}
     */
    public function createFromMatrix(array $input, int $createdBy): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $cycleId = (int) ($input['performance_cycle_id'] ?? 0);
        $nineBox = (int) ($input['nine_box'] ?? 0);

        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Colaborador é obrigatório.'];
        }
        if ($cycleId <= 0) {
            return ['ok' => false, 'error' => 'Ciclo é obrigatório para criar PDI a partir da 9BOX.'];
        }
        if (self::guidanceForBox($nineBox) === null) {
            return ['ok' => false, 'error' => 'Box Nine Box deve ser entre 1 e 9.'];
        }

        $cycle = $this->cyclesRepo()->getById($cycleId);
        if (!$cycle) {
            return ['ok' => false, 'error' => 'Ciclo informado não existe.'];
        }

        $user = $this->usersRepo()->getUser($userId);
        if (!$user || !is_array($user)) {
            return ['ok' => false, 'error' => 'Colaborador não encontrado.'];
        }

        $existing = $this->plansRepo()->findOpenByUserAndCycle($userId, $cycleId);
        if ($existing !== null) {
            return [
                'ok' => true,
                'id' => (int) $existing['id'],
                'reused' => true,
            ];
        }

        $managerId = !empty($user['immediate_supervisor_id'])
            ? (int) $user['immediate_supervisor_id']
            : null;

        $suggested = self::buildSuggestedPlanPayload($userId, $cycleId, $nineBox, $cycle, $managerId);
        if (!$suggested['ok']) {
            return $suggested;
        }

        $result = $this->pdiService()->create($suggested['data'], $createdBy);
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'id' => (int) $result['id'],
            'reused' => false,
        ];
    }

    private function pdiService(): PdiPlanService
    {
        return $this->pdi ?? new PdiPlanService();
    }

    private function plansRepo(): PdiPlansRepository
    {
        return $this->plans ?? new PdiPlansRepository();
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

<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use DateTimeImmutable;

/**
 * Gera vistorias no dia 01 conforme periodicidade de cada equipamento.
 */
final class SstEquipamentoVistoriaGeneratorService
{
    public function __construct(
        private ?SstEquipamentosRepository $equipamentosRepo = null,
        private ?SstEquipamentoVistoriasRepository $vistoriasRepo = null,
        private ?SstEquipamentoTiposRepository $tiposRepo = null,
    ) {
        $this->equipamentosRepo = $equipamentosRepo ?? new SstEquipamentosRepository();
        $this->vistoriasRepo = $vistoriasRepo ?? new SstEquipamentoVistoriasRepository();
        $this->tiposRepo = $tiposRepo ?? new SstEquipamentoTiposRepository();
    }

    /**
     * @return array{created: int, skipped: int, overdue_marked: int, competencia: string, errors: list<string>}
     */
    public function run(?DateTimeImmutable $referenceDate = null): array
    {
        $ref = $referenceDate ?? new DateTimeImmutable('today');
        $competencia = $ref->format('Y-m');
        $dataPrevista = $ref->format('Y-m-01');
        $today = $ref->format('Y-m-d');

        $result = [
            'created' => 0,
            'skipped' => 0,
            'overdue_marked' => $this->vistoriasRepo->markOverdue($today),
            'competencia' => $competencia,
            'errors' => [],
        ];

        // Só gera novas vistorias no dia 01
        if ($ref->format('d') !== '01') {
            return $result;
        }

        foreach ($this->equipamentosRepo->getActiveForGeneration() as $equipamento) {
            $equipamentoId = (int) $equipamento['id'];
            $interval = (int) ($equipamento['periodicidade_meses'] ?? 1);
            if (!SstEquipamentoPeriodicidadeHelper::isValid($interval)) {
                $interval = 1;
            }

            if ($this->vistoriasRepo->existsForCompetencia($equipamentoId, $competencia)) {
                $result['skipped']++;
                continue;
            }

            if (!$this->isDueThisMonth($equipamento, $competencia, $interval)) {
                $result['skipped']++;
                continue;
            }

            $tipoId = (int) $equipamento['adms_sst_equipamento_tipo_id'];
            $checklist = $this->tiposRepo->getChecklistItens($tipoId, true);
            if ($checklist === []) {
                $result['errors'][] = "Equipamento {$equipamento['codigo']}: tipo sem itens de checklist.";
                $result['skipped']++;
                continue;
            }

            $created = $this->vistoriasRepo->createWithRespostas(
                $equipamentoId,
                $competencia,
                $dataPrevista,
                $checklist
            );
            if ($created) {
                $result['created']++;
            } else {
                $result['errors'][] = "Falha ao criar vistoria para {$equipamento['codigo']}.";
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $equipamento */
    private function isDueThisMonth(array $equipamento, string $competencia, int $intervalMonths): bool
    {
        $lastCompleted = $this->vistoriasRepo->getLastCompletedCompetencia((int) $equipamento['id']);
        if ($lastCompleted !== null) {
            $nextDue = $this->addMonthsToCompetencia($lastCompleted, $intervalMonths);

            return $competencia >= $nextDue;
        }

        $anchor = $equipamento['data_referencia_inspecao']
            ?? substr((string) ($equipamento['created_at'] ?? ''), 0, 10)
            ?: date('Y-m-d');
        $anchorComp = substr($anchor, 0, 7);
        $monthsDiff = $this->diffCompetenciaMonths($anchorComp, $competencia);

        return $monthsDiff >= 0 && ($monthsDiff % $intervalMonths) === 0;
    }

    private function addMonthsToCompetencia(string $competencia, int $months): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $competencia . '-01');
        if (!$dt) {
            return $competencia;
        }

        return $dt->modify('+' . $months . ' months')->format('Y-m');
    }

    private function diffCompetenciaMonths(string $fromComp, string $toComp): int
    {
        $from = DateTimeImmutable::createFromFormat('Y-m-d', $fromComp . '-01');
        $to = DateTimeImmutable::createFromFormat('Y-m-d', $toComp . '-01');
        if (!$from || !$to) {
            return 0;
        }

        return ((int) $to->format('Y') - (int) $from->format('Y')) * 12
            + ((int) $to->format('m') - (int) $from->format('m'));
    }
}

<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use DateTimeImmutable;

/**
 * Gera vistorias no dia previsto de cada equipamento (ou padrão do módulo).
 */
final class SstEquipamentoVistoriaGeneratorService
{
    public function __construct(
        private ?SstEquipamentosRepository $equipamentosRepo = null,
        private ?SstEquipamentoVistoriasRepository $vistoriasRepo = null,
        private ?SstEquipamentoTiposRepository $tiposRepo = null,
        private ?SstEquipamentoSettingsRepository $settingsRepo = null,
    ) {
        $this->equipamentosRepo = $equipamentosRepo ?? new SstEquipamentosRepository();
        $this->vistoriasRepo = $vistoriasRepo ?? new SstEquipamentoVistoriasRepository();
        $this->tiposRepo = $tiposRepo ?? new SstEquipamentoTiposRepository();
        $this->settingsRepo = $settingsRepo ?? new SstEquipamentoSettingsRepository();
    }

    /**
     * @return array{created: int, skipped: int, overdue_marked: int, competencia: string, errors: list<string>}
     */
    public function run(?DateTimeImmutable $referenceDate = null): array
    {
        $ref = $referenceDate ?? new DateTimeImmutable('today');
        $settings = $this->settingsRepo->get();
        $competencia = $ref->format('Y-m');
        $today = $ref->format('Y-m-d');
        $tolerancia = (int) ($settings['dias_tolerancia_vencimento'] ?? 0);

        $result = [
            'created' => 0,
            'skipped' => 0,
            'overdue_marked' => $this->vistoriasRepo->markOverdue($today, $tolerancia),
            'competencia' => $competencia,
            'errors' => [],
        ];

        foreach ($this->equipamentosRepo->getActiveForGeneration() as $equipamento) {
            $equipamentoId = (int) $equipamento['id'];
            $diaPrevisto = SstEquipamentoPeriodicidadeHelper::resolveDiaPrevisto($equipamento, $settings);

            if (!$this->shouldGenerateToday($ref, $diaPrevisto, $competencia, $equipamentoId)) {
                $result['skipped']++;
                continue;
            }

            $outcome = $this->tryCreateScheduledVistoria($equipamento, $competencia, $settings, $result['errors']);
            if ($outcome === true) {
                $result['created']++;
            } elseif ($outcome === false) {
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * Gera no dia previsto do equipamento; se o cron falhar, recupera nos dias seguintes do mesmo mês.
     */
    private function shouldGenerateToday(
        DateTimeImmutable $ref,
        int $diaPrevisto,
        string $competencia,
        int $equipamentoId,
    ): bool {
        if ($this->getCompetenciaBlockReason($equipamentoId, $competencia) !== null) {
            return false;
        }

        return (int) $ref->format('d') >= $diaPrevisto;
    }

    public function tryGenerateOnEquipamentoCreate(int $equipamentoId): bool
    {
        $settings = $this->settingsRepo->get();
        if (empty($settings['gerar_vistoria_na_criacao'])) {
            return false;
        }

        $equipamento = $this->equipamentosRepo->getById($equipamentoId);
        if (!$equipamento || ($equipamento['status'] ?? '') !== 'Ativo') {
            return false;
        }
        if (!$this->isVistoriaAutomatica($equipamento)) {
            return false;
        }

        $competencia = (new DateTimeImmutable('today'))->format('Y-m');
        if ($this->vistoriasRepo->existsForCompetencia($equipamentoId, $competencia)) {
            return false;
        }

        $errors = [];
        $created = $this->tryCreateScheduledVistoria($equipamento, $competencia, $settings, $errors, skipPeriodicidadeCheck: true);

        return $created === true;
    }

    /**
     * Geração manual: ignora dia do mês e periodicidade, mas respeita vistoria existente/concluída.
     *
     * @return array{ok: bool, code: string, message: string, vistoria_id?: int}
     */
    public function tryGenerateManual(int $equipamentoId, ?string $competencia = null): array
    {
        $equipamento = $this->equipamentosRepo->getById($equipamentoId);
        if (!$equipamento) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'Equipamento não encontrado.'];
        }
        if (($equipamento['status'] ?? '') !== 'Ativo') {
            return ['ok' => false, 'code' => 'inactive', 'message' => 'Só é possível gerar vistoria para equipamento ativo.'];
        }

        $competencia = $competencia ?? (new DateTimeImmutable('today'))->format('Y-m');
        $block = $this->getCompetenciaBlockReason($equipamentoId, $competencia);
        if ($block !== null) {
            return $block;
        }

        $settings = $this->settingsRepo->get();
        $errors = [];
        $vistoriaId = $this->createScheduledVistoria($equipamento, $competencia, $settings, $errors);
        if ($vistoriaId === false) {
            $msg = $errors[0] ?? 'Não foi possível gerar a vistoria.';

            return ['ok' => false, 'code' => 'error', 'message' => $msg];
        }

        return [
            'ok' => true,
            'code' => 'created',
            'message' => "Vistoria gerada para a competência {$competencia}.",
            'vistoria_id' => $vistoriaId,
        ];
    }

    /**
     * @return array{ok: false, code: string, message: string, vistoria_id?: int}|null
     */
    private function getCompetenciaBlockReason(int $equipamentoId, string $competencia): ?array
    {
        $existing = $this->vistoriasRepo->getByEquipamentoCompetencia($equipamentoId, $competencia);
        if (!$existing) {
            return null;
        }

        $status = (string) ($existing['status'] ?? '');
        $vistoriaId = (int) $existing['id'];

        if ($status === 'Concluída') {
            return [
                'ok' => false,
                'code' => 'completed',
                'message' => "Já existe vistoria concluída para {$competencia}. Não é possível gerar outra.",
                'vistoria_id' => $vistoriaId,
            ];
        }

        return [
            'ok' => false,
            'code' => 'exists',
            'message' => "Já existe vistoria ({$status}) para {$competencia}.",
            'vistoria_id' => $vistoriaId,
        ];
    }

    /**
     * @param list<string> $errors
     * @return bool|null true=criada, false=ignorada, null=erro
     */
    private function tryCreateScheduledVistoria(
        array $equipamento,
        string $competencia,
        array $settings,
        array &$errors,
        bool $skipPeriodicidadeCheck = false,
    ): ?bool {
        if (!$this->isVistoriaAutomatica($equipamento)) {
            return false;
        }

        $equipamentoId = (int) $equipamento['id'];

        if ($this->getCompetenciaBlockReason($equipamentoId, $competencia) !== null) {
            return false;
        }

        $interval = (int) ($equipamento['periodicidade_meses'] ?? 1);
        if (!SstEquipamentoPeriodicidadeHelper::isValid($interval)) {
            $interval = 1;
        }

        if (!$skipPeriodicidadeCheck && !$this->isDueThisMonth($equipamento, $competencia, $interval)) {
            return false;
        }

        $vistoriaId = $this->createScheduledVistoria($equipamento, $competencia, $settings, $errors);
        if ($vistoriaId === false) {
            return null;
        }

        return true;
    }

    /**
     * @param list<string> $errors
     * @return int|false ID da vistoria criada
     */
    private function createScheduledVistoria(
        array $equipamento,
        string $competencia,
        array $settings,
        array &$errors,
    ): int|false {
        $equipamentoId = (int) $equipamento['id'];
        $tipoId = (int) $equipamento['adms_sst_equipamento_tipo_id'];
        $checklist = $this->tiposRepo->getChecklistItens($tipoId, true);
        if ($checklist === []) {
            $errors[] = "Equipamento {$equipamento['codigo']}: tipo sem itens de checklist.";

            return false;
        }

        $diaPrevisto = SstEquipamentoPeriodicidadeHelper::resolveDiaPrevisto($equipamento, $settings);
        $dataPrevista = SstEquipamentoPeriodicidadeHelper::buildDataPrevista($competencia, $diaPrevisto);

        $created = $this->vistoriasRepo->createWithRespostas(
            $equipamentoId,
            $competencia,
            $dataPrevista,
            $checklist
        );
        if ($created) {
            return (int) $created;
        }

        $errors[] = "Falha ao criar vistoria para {$equipamento['codigo']}.";

        return false;
    }

    /** @param array<string, mixed> $equipamento */
    private function isVistoriaAutomatica(array $equipamento): bool
    {
        if (!array_key_exists('vistoria_automatica', $equipamento)) {
            return true;
        }

        return !empty($equipamento['vistoria_automatica']);
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

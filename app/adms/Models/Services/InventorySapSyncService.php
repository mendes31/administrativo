<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Helpers\InvCostProjectHelper;
use App\adms\Models\Repository\inventory\InvInventorySapSyncRunsRepository;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvPharmaFormsRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use Throwable;

class InventorySapSyncService
{
    /** Prefixos de grupo exibidos no SAP (OITB.ItmsGrpNam), ex.: "400 - PROD ACABADO". */
    private const SAP_ALLOWED_ITEM_GROUP_PREFIXES = ['100', '1000', '200', '300', '400', '600', '700'];

    /** Códigos SAP (OITB.ItmsGrpCod) resolvidos na execução da sync. */
    private ?array $allowedSapGroupCodes = null;

    /** Mapa código → descrição de U_FormaFarma (UFD1). */
    private ?array $sapPharmaFormMap = null;

    /** Cache forma/linha por ItemCode (API não permite SELECT em U_*). */
    private array $sapUdfFormaByItem = [];

    private array $sapUdfLinhaByItem = [];

    /** @var array<string, true> */
    private array $sapUdfPreloadedGroups = [];

    private int $activeSyncRunId = 0;

    private bool $deferRunFinalization = false;

    private int $trackedProgressTotal = 0;

    private int $trackedProgressExamined = 0;

    private int $trackedProgressExaminedBase = 0;

    private int $trackedRowsCreatedBase = 0;

    private int $trackedRowsUpdatedBase = 0;

    private int $trackedRowsUnchangedBase = 0;

    private int $trackedSyncPass = 1;

    /** @var list<array{erp_code: string, phase: string, reason: string, at: string}> */
    private array $syncFailureEntries = [];

    /** null = ainda não testado; false = API não aceita UDF no SELECT. */
    private ?bool $sapUnifiedSelectSupported = null;

    private const SAP_FORMA_FIELD_ID = 99;

    private const SAP_LINHA_FIELD_ID = 101;

    private const SAP_UDF_EMPTY = '__empty__';

    /** Descrições conhecidas quando UFD1 falha ou demora. */
    private const SAP_PHARMA_FORM_FALLBACK = [
        '2' => 'Capsula Dura',
        '3' => 'Capsula Mole',
        '4' => 'Comprimido',
        '5' => 'Líquido',
        '6' => 'Pós (Frasco)',
        '7' => 'Sachê',
        '8' => 'Stick',
    ];

    /**
     * Sincroniza itens de estoque com SAP e atualiza custos de comprados.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   synced: int,
     *   created: int,
     *   updated: int,
     *   unchanged: int,
     *   recalculated: int,
     *   removed: int,
     *   remove_skipped: int,
     *   sync_mode: string,
     *   examined: int,
     *   partial: bool
     * }
     */
    public function syncItemsAndCosts(
        bool $fullSync = false,
        ?string $filterItemCode = null,
        ?string $filterGroupPrefix = null
    ): array {
        @set_time_limit(0);

        $filterItemCode = trim((string)($filterItemCode ?? ''));
        $filterGroupPrefix = trim((string)($filterGroupPrefix ?? ''));
        $isScopedItem = $filterItemCode !== '';
        $isScopedGroup = !$isScopedItem && $filterGroupPrefix !== '';
        $isScoped = $isScopedItem || $isScopedGroup;

        $stats = [
            'success' => false,
            'message' => '',
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'recalculated' => 0,
            'removed' => 0,
            'remove_skipped' => 0,
            'sync_mode' => 'diff',
            'examined' => 0,
            'partial' => false,
            'failed' => 0,
        ];

        $runsRepo = new InvInventorySapSyncRunsRepository();

        $useFullSync = $fullSync && !$isScopedItem;
        if ($isScopedItem) {
            $stats['sync_mode'] = 'item';
        } elseif ($isScopedGroup) {
            $stats['sync_mode'] = 'group-' . $filterGroupPrefix;
        } else {
            $stats['sync_mode'] = $useFullSync ? 'full' : 'diff';
        }

        $scopeLabel = $isScopedItem
            ? ('item:' . $filterItemCode)
            : ($isScopedGroup ? ('group:' . $filterGroupPrefix) : null);

        $runId = $this->activeSyncRunId > 0
            ? $this->activeSyncRunId
            : $runsRepo->create([
                'sync_type' => 'items',
                'sync_mode' => $stats['sync_mode'],
                'filter_from_date' => $scopeLabel,
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);

        try {
            $sap = new SapReportApiService();
            $this->resolveAllowedSapGroupCodes($sap);

            $pharmaFormsRepo = new InvPharmaFormsRepository();
            try {
                $this->syncPharmaFormsCatalogFromSap($sap, $pharmaFormsRepo);
            } catch (Throwable) {
                $this->ensurePharmaFormFallbackMap($pharmaFormsRepo);
            }

            $itemsRepo = new InvItemsRepository();
            $categoriesRepo = new InvCategoriesRepository();
            $unitsRepo = new InvUnitsRepository();
            $localSnapshot = $itemsRepo->getSapSyncSnapshot();

            $this->sapUdfFormaByItem = [];
            $this->sapUdfLinhaByItem = [];
            $this->sapUdfPreloadedGroups = [];

            $defaultUnitId = $unitsRepo->findIdByCode('UN');
            if ($defaultUnitId === null) {
                $firstUnit = $unitsRepo->getAll(1, 1);
                $defaultUnitId = (int)($firstUnit[0]['id'] ?? 0);
            }

            $purchaseCostsChanged = false;
            $allowedErpCodes = [];
            $groupMap = $this->fetchSapItemGroupsMap($sap);
            usleep(300000);

            $partialError = null;
            $resumeFromCode = '';
            $lastCode = '';

            if ($isScopedItem) {
                $purchaseCostsChanged = $this->syncSingleItemFromSap(
                    $sap,
                    $filterItemCode,
                    $groupMap,
                    $itemsRepo,
                    $categoriesRepo,
                    $unitsRepo,
                    $pharmaFormsRepo,
                    $defaultUnitId,
                    $localSnapshot,
                    $stats,
                    $allowedErpCodes
                );
            } elseif ($isScopedGroup) {
                $scopedGroupCodes = $this->resolveGroupCodesForPrefix($groupMap, $filterGroupPrefix);
                if ($scopedGroupCodes === []) {
                    throw new \InvalidArgumentException(
                        'Grupo SAP ' . $filterGroupPrefix . ' não encontrado ou fora dos grupos permitidos.'
                    );
                }
                $purchaseCostsChanged = $this->runCatalogSyncBatches(
                    $sap,
                    $groupMap,
                    $itemsRepo,
                    $categoriesRepo,
                    $unitsRepo,
                    $pharmaFormsRepo,
                    $defaultUnitId,
                    $localSnapshot,
                    $stats,
                    $allowedErpCodes,
                    $lastCode,
                    $partialError,
                    $useFullSync,
                    $scopedGroupCodes
                );
            } else {
                $lastCode = $this->resolveCatalogSyncStartCode($runsRepo, $useFullSync);
                $purchaseCostsChanged = $this->runCatalogSyncBatches(
                    $sap,
                    $groupMap,
                    $itemsRepo,
                    $categoriesRepo,
                    $unitsRepo,
                    $pharmaFormsRepo,
                    $defaultUnitId,
                    $localSnapshot,
                    $stats,
                    $allowedErpCodes,
                    $lastCode,
                    $partialError,
                    $useFullSync
                );
            }

            if ($stats['examined'] === 0) {
                if ($isScopedItem) {
                    throw new \RuntimeException(
                        'Item ' . $filterItemCode . ' não encontrado no SAP ou fora dos grupos permitidos.'
                    );
                }
                if ($isScopedGroup && !$useFullSync) {
                    $stats['success'] = true;
                    $stats['message'] = 'Nenhum item pendente no grupo SAP ' . $filterGroupPrefix
                        . '. O cadastro local já está atualizado para itens alterados recentemente no SAP.';
                    if ($this->activeSyncRunId > 0) {
                        $this->publishSyncProgress(
                            ['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0],
                            $stats['message'],
                            0,
                            true
                        );
                    }

                    return $stats;
                }
                if (!$useFullSync && !$isScoped) {
                    $stats['success'] = true;
                    $stats['message'] = 'Nenhum item pendente para sincronizar. O cadastro local já está atualizado em relação ao SAP.';
                    if ($this->activeSyncRunId > 0) {
                        $this->publishSyncProgress(
                            ['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0],
                            'Nenhum item pendente para sincronizar.',
                            0,
                            true
                        );
                    }

                    return $stats;
                }
                throw new \RuntimeException(
                    'Nenhum item retornado do SAP nos grupos permitidos. Verifique a conexão com a API SAP e tente novamente.'
                );
            }

            if (!$isScoped && !$stats['partial']) {
                $runsRepo->clearPartialCheckpoints('items');
            }

            if ($useFullSync && !$stats['partial']) {
                $purgeResult = $this->purgeLocalSapItemsOutsideAllowedCatalog(
                    $itemsRepo,
                    $this->fetchAllowedSapItemCodeSet($sap)
                );
                $stats['removed'] = $purgeResult['removed'];
                $stats['remove_skipped'] = $purgeResult['skipped'];
            }

            if ($purchaseCostsChanged || ($useFullSync && !$stats['partial'])) {
                $recalcIds = $itemsRepo->getIdsByCategoryNameKeywords(['PROD. ACABADO', 'PROD. INTERMED', 'ACABADO', 'INTERMED']);
                foreach ($recalcIds as $itemId) {
                    if ($itemId <= 0) {
                        continue;
                    }
                    InventoryCostService::recalculateStandardCost($itemId);
                    $stats['recalculated']++;
                }
            }

            $checkpointCode = $stats['checkpoint_code'] ?? $lastCode;
            if ($this->deferRunFinalization) {
                $progressLabel = $stats['partial']
                    ? ('Pausa temporária após ' . ($checkpointCode !== '' ? $checkpointCode : 'lote'))
                    : 'Lote concluído';
                $this->publishSyncProgress($stats, $progressLabel, $this->trackedProgressTotal);
                $runsRepo->update($runId, [
                    'status' => 'running',
                    'rows_created' => $stats['created'],
                    'rows_updated' => $stats['updated'],
                    'rows_unchanged' => $stats['unchanged'],
                    'error_log' => $stats['partial'] && $checkpointCode !== ''
                        ? ('checkpoint:' . $checkpointCode . '|' . ($partialError ?? ''))
                        : ($stats['partial'] ? ('checkpoint:|' . ($partialError ?? '')) : null),
                    'finished_at' => null,
                    'progress_examined' => $this->trackedProgressExamined,
                    'progress_total' => $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null,
                    'progress_label' => $progressLabel,
                ]);
            } else {
                $runsRepo->update($runId, [
                    'rows_created' => $stats['created'],
                    'rows_updated' => $stats['updated'],
                    'rows_unchanged' => $stats['unchanged'],
                    'status' => 'completed',
                    'error_log' => $stats['partial'] && $checkpointCode !== ''
                        ? ('checkpoint:' . $checkpointCode . '|' . ($partialError ?? ''))
                        : ($stats['partial'] ? ('checkpoint:|' . ($partialError ?? '')) : null),
                    'finished_at' => date('Y-m-d H:i:s'),
                    'result_message' => null,
                ]);
            }

            $stats['success'] = true;
            if ($isScopedItem) {
                $modeLabel = 'do item ' . $filterItemCode;
            } elseif ($isScopedGroup) {
                $modeLabel = 'do grupo ' . $filterGroupPrefix;
            } else {
                $modeLabel = $useFullSync ? 'completa' : 'por diff';
            }
            $stats['message'] = sprintf(
                'Sincronização %s concluída. SAP analisados: %d | Novos: %d | Alterados: %d | Sem mudança: %d | Removidos (grupo SAP fora do catálogo): %d | PA/PI recalculados: %d.',
                $modeLabel,
                $stats['examined'],
                $stats['created'],
                $stats['updated'],
                $stats['unchanged'],
                $stats['removed'],
                $stats['recalculated']
            );
            if ($stats['remove_skipped'] > 0) {
                $stats['message'] .= sprintf(
                    ' %d item(ns) fora do catálogo não puderam ser excluídos (vínculos em movimentação ou BOM de outro item).',
                    $stats['remove_skipped']
                );
            }
            if ((int)($stats['failed'] ?? 0) > 0) {
                $stats['message'] .= sprintf(
                    ' %d item(ns) ignorado(s) porque a API SAP não retornou o cadastro completo.',
                    (int)$stats['failed']
                );
                $failureDetail = $this->formatSyncFailureSummaryText();
                if ($failureDetail !== '') {
                    $stats['message'] .= ' Itens: ' . $failureDetail . '.';
                }
            }
            if ($stats['partial']) {
                $syncedTotal = $itemsRepo->countWithSapSyncDate();
                $stats['message'] .= sprintf(
                    ' A API SAP interrompeu antes do fim do catálogo (~%d itens já sincronizados).',
                    $syncedTotal
                );
                if ($checkpointCode !== '') {
                    $stats['message'] .= ' Retomará após o código ' . $checkpointCode . ' — clique Sincronizar novamente (ou use Grupo SAP / código unitário).';
                } else {
                    $stats['message'] .= ' Clique Sincronizar novamente para continuar.';
                }
                if ($partialError !== null && $partialError !== '') {
                    $stats['message'] .= ' Detalhe: ' . $partialError;
                }
            } elseif (!$isScoped && $resumeFromCode !== '') {
                $stats['message'] .= sprintf(' (retomado após %s)', $resumeFromCode);
            }

            return $stats;
        } catch (Throwable $e) {
            $checkpointCode = $lastCode ?? ($stats['checkpoint_code'] ?? '');
            if ($stats['examined'] > 0) {
                if ($this->deferRunFinalization) {
                    $this->publishSyncProgress($stats, 'Erro temporário — retomando…', $this->trackedProgressTotal);
                    $runsRepo->update($runId, [
                        'status' => 'running',
                        'rows_created' => $stats['created'],
                        'rows_updated' => $stats['updated'],
                        'rows_unchanged' => $stats['unchanged'],
                        'error_log' => $checkpointCode !== ''
                            ? ('checkpoint:' . $checkpointCode . '|' . $e->getMessage())
                            : ('Parcial: ' . $e->getMessage()),
                        'finished_at' => null,
                        'progress_examined' => $this->trackedProgressExamined,
                        'progress_total' => $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null,
                        'progress_label' => 'Erro temporário — retomando…',
                    ]);
                } else {
                    $runsRepo->update($runId, [
                        'rows_created' => $stats['created'],
                        'rows_updated' => $stats['updated'],
                        'rows_unchanged' => $stats['unchanged'],
                        'status' => 'completed',
                        'error_log' => $checkpointCode !== ''
                            ? ('checkpoint:' . $checkpointCode . '|' . $e->getMessage())
                            : ('Parcial: ' . $e->getMessage()),
                        'finished_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $stats['success'] = true;
                $stats['partial'] = true;
                $stats['message'] = sprintf(
                    'Sincronização parcial concluída. SAP analisados: %d | Novos: %d | Alterados: %d. Erro ao continuar: %s',
                    $stats['examined'],
                    $stats['created'],
                    $stats['updated'],
                    $this->formatSyncErrorMessage($e->getMessage())
                );

                return $stats;
            }

            $runsRepo->update($runId, [
                'rows_created' => $stats['created'],
                'rows_updated' => $stats['updated'],
                'rows_unchanged' => $stats['unchanged'],
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $stats['message'] = 'Falha na sincronização SAP: ' . $this->formatSyncErrorMessage($e->getMessage());

            return $stats;
        }
    }

    /**
     * Sincronização pela interface web: retentativas automáticas e backfill de forma/linha.
     *
     * @return array<string, mixed>
     */
    public function syncItemsAndCostsInteractive(
        bool $fullSync = false,
        ?string $filterItemCode = null,
        ?string $filterGroupPrefix = null,
        bool $autoContinue = true,
        int $maxLoops = 40,
        ?int $trackRunId = null,
        bool $appendStructuresPhase = false
    ): array {
        @set_time_limit(0);

        $filterItemCode = trim((string)($filterItemCode ?? ''));
        $filterGroupPrefix = trim((string)($filterGroupPrefix ?? ''));
        $isScopedItem = $filterItemCode !== '';
        $maxLoops = max(1, min(80, $maxLoops));

        $runsRepo = new InvInventorySapSyncRunsRepository();
        if ($trackRunId === null || $trackRunId <= 0) {
            $trackRunId = $runsRepo->create([
                'sync_type' => 'items',
                'sync_mode' => $isScopedItem ? 'item' : ($filterGroupPrefix !== '' ? 'group-' . $filterGroupPrefix : ($fullSync ? 'full' : 'diff')),
                'filter_from_date' => $isScopedItem ? ('item:' . $filterItemCode) : ($filterGroupPrefix !== '' ? ('group:' . $filterGroupPrefix) : null),
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->beginTrackedSync($trackRunId, true);
        (new InvInventorySapSyncRunsRepository())->setCurrentPhase($trackRunId, 'items');
        $this->publishSyncProgress(['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0], 'Preparando sincronização SAP…', 0);

        $aggregate = [
            'success' => false,
            'message' => '',
            'examined' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'partial' => false,
        ];

        try {
            if ($isScopedItem) {
            $fullSync = false;
            $filterGroupPrefix = '';
            $result = $this->syncItemsAndCosts(false, $filterItemCode, null);
            $this->mergeSyncStats($aggregate, $result);
        } else {
            $loop = 0;
            $result = ['success' => false, 'partial' => true, 'message' => ''];
            do {
                $loop++;
                if ($loop > 1) {
                    $this->beginNewSyncPass($loop);
                    $this->syncProgressCountersFromAggregate($aggregate);
                    $this->publishSyncProgress(
                        $aggregate,
                        sprintf('Passagem %d/%d — retomada automática…', $loop, $maxLoops),
                        $this->trackedProgressTotal,
                        true
                    );
                    sleep(empty($result['success']) ? 8 : 3);
                }
                $result = $this->syncItemsAndCosts(
                    $fullSync,
                    null,
                    $filterGroupPrefix !== '' ? $filterGroupPrefix : null
                );
                $this->mergeSyncStats($aggregate, $result);
                $this->syncProgressCountersFromAggregate($aggregate);
                if (!empty($result['success']) && empty($result['partial'])) {
                    break;
                }
                if (empty($result['success'])) {
                    break;
                }
                if (!$autoContinue) {
                    break;
                }
            } while ($loop < $maxLoops);
            }

            $aggregate['success'] = !empty($result['success']);
            $aggregate['message'] = (string)($result['message'] ?? '');
            $aggregate['partial'] = !empty($result['partial']);

            if (
                $appendStructuresPhase
                && !empty($aggregate['success'])
                && empty($aggregate['partial'])
            ) {
                (new InvInventorySapSyncRunsRepository())->setCurrentPhase($trackRunId, 'structures');
                $this->beginNewSyncPass(1);
                $this->trackedProgressTotal = 0;

                if ($isScopedItem && $filterItemCode !== '') {
                    $structureStats = $this->syncStructuresForScopedItem($trackRunId, $filterItemCode, true);
                } else {
                    $structureStats = $this->syncAllItemStructures($trackRunId, true, true);
                }

                if (!empty($structureStats['message'])) {
                    $aggregate['message'] .= ' | Estruturas: ' . (string)$structureStats['message'];
                }
                if (empty($structureStats['success'])) {
                    $aggregate['success'] = false;
                }
            }

            $this->finalizeTrackedSync(
                $aggregate['success'],
                $aggregate['message'],
                [
                    'rows_created' => $aggregate['created'],
                    'rows_updated' => $aggregate['updated'],
                    'rows_unchanged' => $aggregate['unchanged'],
                    'rows_failed' => (int)($aggregate['failed'] ?? 0),
                    'error_log' => $aggregate['partial']
                        ? InvInventorySapSyncRunsRepository::mergeErrorLogWithFailures(
                            ($result['checkpoint_code'] ?? null) && ($result['checkpoint_code'] ?? '') !== ''
                                ? ('checkpoint:' . ($result['checkpoint_code'] ?? '') . '|' . ($result['message'] ?? ''))
                                : null,
                            $this->syncFailureEntries
                        )
                        : InvInventorySapSyncRunsRepository::mergeErrorLogWithFailures(null, $this->syncFailureEntries),
                ]
            );
        } catch (SyncCancelledException $e) {
            $aggregate['success'] = false;
            $aggregate['message'] = $e->getMessage();
            $this->finalizeTrackedSync(false, $e->getMessage(), [
                'status' => 'cancelled',
                'error_log' => InvInventorySapSyncRunsRepository::mergeErrorLogWithFailures(null, $this->syncFailureEntries),
                'rows_created' => $aggregate['created'],
                'rows_updated' => $aggregate['updated'],
                'rows_unchanged' => $aggregate['unchanged'],
                'rows_failed' => (int)($aggregate['failed'] ?? 0),
            ]);
        }

        $aggregate['run_id'] = $trackRunId;

        return $aggregate;
    }

    /**
     * Sincronização unificada: itens + estruturas pendentes (uma execução, um run).
     *
     * @return array<string, mixed>
     */
    public function syncSapUnifiedInteractive(
        bool $fullSync = false,
        ?string $filterItemCode = null,
        ?string $filterGroupPrefix = null,
        bool $autoContinue = true,
        int $maxLoops = 40,
        ?int $trackRunId = null
    ): array {
        return $this->syncItemsAndCostsInteractive(
            $fullSync,
            $filterItemCode,
            $filterGroupPrefix,
            $autoContinue,
            $maxLoops,
            $trackRunId,
            true
        );
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $chunk
     */
    private function mergeSyncStats(array &$target, array $chunk): void
    {
        foreach (['examined', 'created', 'updated', 'unchanged', 'failed'] as $key) {
            $target[$key] = (int)($target[$key] ?? 0) + (int)($chunk[$key] ?? 0);
        }
        if (!empty($chunk['partial'])) {
            $target['partial'] = true;
        }
        if (!empty($chunk['message'])) {
            $target['message'] = (string)$chunk['message'];
        }
    }

    public function beginTrackedSync(int $runId, bool $deferFinalization = false): void
    {
        $this->activeSyncRunId = max(0, $runId);
        $this->deferRunFinalization = $deferFinalization;
        $this->trackedProgressTotal = 0;
        $this->trackedProgressExamined = 0;
        $this->trackedProgressExaminedBase = 0;
        $this->trackedRowsCreatedBase = 0;
        $this->trackedRowsUpdatedBase = 0;
        $this->trackedRowsUnchangedBase = 0;
        $this->trackedSyncPass = 1;
        $this->sapUnifiedSelectSupported = null;
        $this->syncFailureEntries = [];
        $failureLogPath = InvInventorySapSyncRunsRepository::failureLogFilePath($runId);
        if (is_file($failureLogPath)) {
            @unlink($failureLogPath);
        }
    }

    public function beginNewSyncPass(int $pass): void
    {
        $this->trackedSyncPass = max(1, $pass);
        $this->trackedProgressExaminedBase = 0;
        $this->trackedProgressExamined = 0;
    }

    /**
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $finalizeData
     */
    public function finalizeTrackedSync(bool $success, string $message, array $finalizeData = []): void
    {
        if ($this->activeSyncRunId <= 0) {
            return;
        }

        $runsRepo = new InvInventorySapSyncRunsRepository();
        $existingErrorLog = null;
        if ($this->activeSyncRunId > 0) {
            $existingRun = $runsRepo->getById($this->activeSyncRunId);
            $existingErrorLog = is_array($existingRun) ? (string)($existingRun['error_log'] ?? '') : '';
        }

        $runsRepo->update($this->activeSyncRunId, array_merge([
            'status' => $success ? 'completed' : 'failed',
            'finished_at' => date('Y-m-d H:i:s'),
            'result_message' => $message,
            'progress_examined' => $this->trackedProgressExamined,
            'progress_total' => $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null,
            'progress_label' => $success ? 'Concluído' : 'Interrompido',
            'error_log' => InvInventorySapSyncRunsRepository::mergeErrorLogWithFailures(
                $existingErrorLog !== '' ? $existingErrorLog : null,
                $this->syncFailureEntries
            ),
        ], $finalizeData));

        $this->activeSyncRunId = 0;
        $this->deferRunFinalization = false;
    }

    private function throwIfSyncCancelled(): void
    {
        if ($this->activeSyncRunId <= 0) {
            return;
        }

        $run = (new InvInventorySapSyncRunsRepository())->getById($this->activeSyncRunId);
        $status = is_array($run) ? (string)($run['status'] ?? '') : '';
        if ($status === 'cancelled') {
            throw new SyncCancelledException('Sincronização cancelada pelo usuário.');
        }
        if (in_array($status, ['failed'], true)) {
            throw new SyncCancelledException('Sincronização encerrada no servidor.');
        }
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function publishSyncProgress(array $stats, string $label, ?int $total = null, bool $statsAreAbsolute = false): void
    {
        if ($this->activeSyncRunId <= 0) {
            return;
        }

        $this->throwIfSyncCancelled();

        if ($statsAreAbsolute) {
            $examined = (int)($stats['examined'] ?? 0);
            $rowsCreated = (int)($stats['created'] ?? 0);
            $rowsUpdated = (int)($stats['updated'] ?? 0);
            $rowsUnchanged = (int)($stats['unchanged'] ?? 0);
        } else {
            $examined = $this->trackedProgressExaminedBase + (int)($stats['examined'] ?? 0);
            $rowsCreated = $this->trackedRowsCreatedBase + (int)($stats['created'] ?? 0);
            $rowsUpdated = $this->trackedRowsUpdatedBase + (int)($stats['updated'] ?? 0);
            $rowsUnchanged = $this->trackedRowsUnchangedBase + (int)($stats['unchanged'] ?? 0);
        }
        $this->trackedProgressExamined = $examined;
        if ($total !== null && $total > 0) {
            $this->trackedProgressTotal = $total;
        }

        (new InvInventorySapSyncRunsRepository())->updateProgress($this->activeSyncRunId, [
            'progress_examined' => $examined,
            'progress_total' => $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null,
            'progress_label' => $this->formatProgressLabel($label),
            'rows_created' => $rowsCreated,
            'rows_updated' => $rowsUpdated,
            'rows_unchanged' => $rowsUnchanged,
            'rows_failed' => (int)($stats['failed'] ?? $stats['structures_failed'] ?? 0),
        ]);
    }

    private function recordSyncFailure(string $erpCode, string $reason, string $phase = 'items'): void
    {
        $erpCode = trim($erpCode);
        $reason = trim($reason);
        if ($erpCode === '') {
            return;
        }

        $entry = [
            'erp_code' => $erpCode,
            'phase' => $phase !== '' ? $phase : 'items',
            'reason' => $reason !== '' ? $reason : 'Falha na sincronização SAP.',
            'at' => date('Y-m-d H:i:s'),
        ];
        $this->syncFailureEntries[] = $entry;
        $this->appendSyncFailureLogLine($entry);
    }

    /**
     * @param array{erp_code: string, phase: string, reason: string, at: string} $entry
     */
    private function appendSyncFailureLogLine(array $entry): void
    {
        if ($this->activeSyncRunId <= 0) {
            return;
        }

        $path = InvInventorySapSyncRunsRepository::failureLogFilePath($this->activeSyncRunId);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            '[%s] %s | %s | %s',
            $entry['at'],
            $entry['phase'],
            $entry['erp_code'],
            $entry['reason']
        );
        @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function formatSyncFailureSummaryText(): string
    {
        if ($this->syncFailureEntries === []) {
            return '';
        }

        $codes = [];
        foreach ($this->syncFailureEntries as $entry) {
            $code = trim((string)($entry['erp_code'] ?? ''));
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return $codes === [] ? '' : implode(', ', array_values(array_unique($codes)));
    }

    private function formatProgressLabel(string $label): string
    {
        $label = trim($label);
        if ($this->trackedSyncPass <= 1) {
            return $label;
        }

        if (preg_match('/^Passagem \d+/u', $label) === 1) {
            return $label;
        }

        return sprintf('Passagem %d — %s', $this->trackedSyncPass, $label);
    }

    /**
     * @param array<string, mixed> $aggregate
     */
    private function syncProgressCountersFromAggregate(array $aggregate): void
    {
        $this->trackedRowsCreatedBase = (int)($aggregate['created'] ?? 0);
        $this->trackedRowsUpdatedBase = (int)($aggregate['updated'] ?? 0);
        $this->trackedRowsUnchangedBase = (int)($aggregate['unchanged'] ?? 0);
    }

    /**
     * @param array<string, mixed> $aggregate
     */
    private function syncProgressRowBasesFromAggregate(array $aggregate): void
    {
        $this->syncProgressCountersFromAggregate($aggregate);
        $this->trackedProgressExaminedBase = (int)($aggregate['examined'] ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getSyncRunStatus(int $runId): ?array
    {
        return InvInventorySapSyncRunsRepository::buildStatusPayload(
            (new InvInventorySapSyncRunsRepository())->getById($runId)
        );
    }

    /**
     * Atualiza forma farmacêutica e linha de produção em itens PA/PI já cadastrados.
     *
     * @return array{success: bool, message: string, updated: int, skipped: int, examined: int}
     */
    public function syncMissingUdfFields(?string $filterGroupPrefix = null): array
    {
        @set_time_limit(0);

        $filterGroupPrefix = trim((string)($filterGroupPrefix ?? ''));
        $stats = [
            'success' => false,
            'message' => '',
            'updated' => 0,
            'skipped' => 0,
            'examined' => 0,
        ];

        try {
            $sap = new SapReportApiService();
            $this->resolveAllowedSapGroupCodes($sap);
            $pharmaFormsRepo = new InvPharmaFormsRepository();
            try {
                $this->syncPharmaFormsCatalogFromSap($sap, $pharmaFormsRepo);
            } catch (Throwable) {
                $this->ensurePharmaFormFallbackMap($pharmaFormsRepo);
            }

            $itemsRepo = new InvItemsRepository();
            $categoriesRepo = new InvCategoriesRepository();
            $unitsRepo = new InvUnitsRepository();
            $defaultUnitId = $unitsRepo->findIdByCode('UN');
            if ($defaultUnitId === null) {
                $firstUnit = $unitsRepo->getAll(1, 1);
                $defaultUnitId = (int)($firstUnit[0]['id'] ?? 0);
            }

            $groupMap = $this->fetchSapItemGroupsMap($sap);
            $this->sapUdfFormaByItem = [];
            $this->sapUdfLinhaByItem = [];
            $this->sapUdfPreloadedGroups = [];

            $allowedErpCodes = [];
            $candidates = $itemsRepo->getPaPiItemsMissingUdf();
            foreach ($candidates as $existing) {
                $erpCode = trim((string)($existing['erp_code'] ?? ''));
                if ($erpCode === '') {
                    continue;
                }
                if ($filterGroupPrefix !== '') {
                    $cat = mb_strtoupper((string)($existing['category_name'] ?? ''), 'UTF-8');
                    if (!str_contains($cat, $filterGroupPrefix)) {
                        continue;
                    }
                }

                $complete = $this->fetchSapItemCompleteRow($sap, $erpCode, $groupMap);
                if ($complete === null) {
                    continue;
                }

                $stats['examined']++;
                $rowStats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0];
                $this->processSapItemRow(
                    $complete,
                    $itemsRepo,
                    $categoriesRepo,
                    $unitsRepo,
                    $pharmaFormsRepo,
                    $defaultUnitId,
                    $rowStats,
                    $allowedErpCodes,
                    $existing
                );
                if ((int)($rowStats['updated'] ?? 0) > 0) {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            }

            $stats['success'] = true;
            $stats['message'] = sprintf(
                'UDF (forma/linha) atualizados: %d de %d PA/PI analisados (%d sem valor no SAP).',
                $stats['updated'],
                $stats['examined'],
                $stats['skipped']
            );
        } catch (Throwable $e) {
            $stats['message'] = 'Falha ao atualizar UDF: ' . $this->formatSyncErrorMessage($e->getMessage());
        }

        return $stats;
    }

    /**
     * Sincroniza BOM/rota de um item após sync por código ERP (não varre toda a fila pendente).
     *
     * @return array<string, mixed>
     */
    private function syncStructuresForScopedItem(int $trackRunId, string $erpCode, bool $continueTracking = false): array
    {
        $erpCode = trim($erpCode);
        $stats = [
            'success' => false,
            'message' => '',
            'structures_synced' => 0,
            'structures_skipped' => 0,
            'structures_unchanged' => 0,
            'structures_failed' => 0,
            'examined' => 0,
        ];

        if ($erpCode === '') {
            $stats['message'] = 'Código ERP não informado para sincronizar estrutura.';

            return $stats;
        }

        $itemsRepo = new InvItemsRepository();
        $item = $itemsRepo->findByErpCode($erpCode);
        if (!is_array($item)) {
            $stats['success'] = true;
            $stats['message'] = 'Item não encontrado localmente após sync.';

            return $stats;
        }

        $itemId = (int)($item['id'] ?? 0);
        if ($itemId <= 0) {
            $stats['message'] = 'Item inválido para sincronizar estrutura.';

            return $stats;
        }

        $this->publishSyncProgress(
            ['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0],
            'Consultando SAP — estrutura de ' . $erpCode,
            1,
            true
        );

        $result = $this->syncItemStructureById($itemId, false, new SapReportApiService());
        $stats['examined'] = 1;

        if (!empty($result['skipped'])) {
            $stats['structures_skipped']++;
        } elseif (!empty($result['unchanged'])) {
            $stats['structures_unchanged']++;
        } elseif (!empty($result['success'])) {
            $stats['structures_synced']++;
        } else {
            $stats['structures_failed']++;
            $this->recordSyncFailure(
                $erpCode,
                (string)($result['message'] ?? 'Falha ao importar estrutura do SAP.'),
                'structures'
            );
        }

        $stats['success'] = !empty($result['success']);
        $stats['message'] = (string)($result['message'] ?? '');

        $this->publishSyncProgress(
            [
                'examined' => 1,
                'created' => $stats['structures_skipped'],
                'updated' => $stats['structures_synced'],
                'unchanged' => $stats['structures_unchanged'],
                'failed' => $stats['structures_failed'],
            ],
            $stats['message'] !== '' ? $stats['message'] : 'Estrutura concluída.',
            1,
            true
        );

        $this->finalizeStructureRunIfNeeded($continueTracking, $stats['success'], $stats['message'], [
            'rows_created' => $stats['structures_skipped'],
            'rows_updated' => $stats['structures_synced'],
            'rows_unchanged' => $stats['structures_unchanged'],
            'rows_failed' => $stats['structures_failed'],
        ]);

        $stats['run_id'] = $trackRunId;

        return $stats;
    }

    /**
     * Sincroniza BOM/rota de todos os itens elegíveis (PA/PI, códigos 43/40 ou com estrutura local).
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   structures_synced: int,
     *   structures_skipped: int,
     *   structures_unchanged: int,
     *   structures_failed: int
     * }
     */
    public function syncAllItemStructures(?int $trackRunId = null, bool $pendingOnly = false, bool $continueTracking = false): array
    {
        @set_time_limit(0);

        $stats = [
            'success' => false,
            'message' => '',
            'structures_synced' => 0,
            'structures_skipped' => 0,
            'structures_unchanged' => 0,
            'structures_failed' => 0,
            'examined' => 0,
        ];

        $runsRepo = new InvInventorySapSyncRunsRepository();
        if ($trackRunId === null || $trackRunId <= 0) {
            $trackRunId = $runsRepo->create([
                'sync_type' => $pendingOnly ? 'all' : 'structures',
                'sync_mode' => $pendingOnly ? 'incremental' : 'full',
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($continueTracking) {
            $this->activeSyncRunId = $trackRunId;
        } else {
            $this->beginTrackedSync($trackRunId, false);
        }

        $runsRepo->setCurrentPhase($trackRunId, 'structures');

        try {
            $itemsRepo = new InvItemsRepository();
            $ids = $pendingOnly
                ? $itemsRepo->getIdsPendingStructureSync()
                : $itemsRepo->getIdsEligibleForStructureSync();
            $total = count($ids);
            if ($total === 0) {
                $stats['success'] = true;
                $stats['message'] = $pendingOnly
                    ? 'Nenhuma estrutura pendente. O cadastro local já está atualizado em relação ao SAP.'
                    : 'Nenhum item elegível para sincronizar estruturas.';
                $this->publishSyncProgress(
                    ['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0],
                    $stats['message'],
                    0,
                    true
                );
                $this->finalizeStructureRunIfNeeded($continueTracking, true, $stats['message'], [
                    'rows_updated' => 0,
                    'rows_unchanged' => 0,
                    'rows_failed' => 0,
                ]);
                $stats['run_id'] = $trackRunId;

                return $stats;
            }

            $this->publishSyncProgress(
                ['examined' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0],
                sprintf(
                    $pendingOnly ? 'Preparando %d estrutura(s) pendente(s)…' : 'Preparando %d estrutura(s)…',
                    $total
                ),
                $total
            );

            $itemsRepo = new InvItemsRepository();
            $sap = new SapReportApiService();

            foreach ($ids as $index => $itemId) {
                if ($itemId <= 0) {
                    continue;
                }

                $this->throwIfSyncCancelled();

                $item = $itemsRepo->getOne($itemId);
                $erpLabel = is_array($item) ? trim((string)($item['erp_code'] ?? '')) : '';

                $this->publishSyncProgress(
                    [
                        'examined' => $index,
                        'created' => $stats['structures_skipped'],
                        'updated' => $stats['structures_synced'],
                        'unchanged' => $stats['structures_unchanged'],
                        'failed' => $stats['structures_failed'],
                    ],
                    sprintf(
                        'Consultando SAP — estrutura %d/%d%s',
                        $index + 1,
                        $total,
                        $erpLabel !== '' ? ' (' . $erpLabel . ')' : ''
                    ),
                    $total,
                    true
                );

                $result = $this->syncItemStructureById($itemId, false, $sap);
                if (!empty($result['skipped'])) {
                    $stats['structures_skipped']++;
                } elseif (!empty($result['unchanged'])) {
                    $stats['structures_unchanged']++;
                } elseif (!empty($result['success'])) {
                    $stats['structures_synced']++;
                } else {
                    $stats['structures_failed']++;
                    $failErp = $erpLabel !== '' ? $erpLabel : ('id:' . $itemId);
                    $this->recordSyncFailure(
                        $failErp,
                        (string)($result['message'] ?? 'Falha ao importar estrutura do SAP.'),
                        'structures'
                    );
                }
                $stats['examined'] = $index + 1;

                $this->publishSyncProgress(
                    [
                        'examined' => $stats['examined'],
                        'created' => $stats['structures_skipped'],
                        'updated' => $stats['structures_synced'],
                        'unchanged' => $stats['structures_unchanged'],
                        'failed' => $stats['structures_failed'],
                    ],
                    sprintf('Estruturas: %d/%d concluída(s)', $stats['examined'], $total),
                    $total,
                    true
                );

                usleep(150000);
            }

            if (
                $stats['structures_synced'] === 0
                && $stats['structures_failed'] === 0
                && $stats['examined'] > 0
            ) {
                $stats['message'] = 'Nenhuma estrutura alterada. O cadastro local já está atualizado em relação ao SAP.';
            } else {
                $stats['message'] = sprintf(
                    'Estruturas alteradas: %d | Sem alteração: %d | Sem BOM/rota no SAP: %d | Falhas: %d.',
                    $stats['structures_synced'],
                    $stats['structures_unchanged'],
                    $stats['structures_skipped'],
                    $stats['structures_failed']
                );
            }
            $stats['success'] = true;

            $this->finalizeStructureRunIfNeeded($continueTracking, true, $stats['message'], [
                'rows_created' => $stats['structures_skipped'],
                'rows_updated' => $stats['structures_synced'],
                'rows_unchanged' => $stats['structures_unchanged'],
                'rows_failed' => $stats['structures_failed'],
            ]);

            $stats['run_id'] = $trackRunId;

            return $stats;
        } catch (SyncCancelledException $e) {
            $stats['message'] = $e->getMessage();
            $this->finalizeStructureRunIfNeeded($continueTracking, false, $stats['message'], [
                'status' => 'cancelled',
                'error_log' => null,
                'rows_created' => $stats['structures_skipped'],
                'rows_updated' => $stats['structures_synced'],
                'rows_unchanged' => $stats['structures_unchanged'],
                'rows_failed' => $stats['structures_failed'],
            ]);

            return $stats;
        } catch (Throwable $e) {
            $stats['message'] = 'Falha na sincronização de estruturas: ' . $this->formatSyncErrorMessage($e->getMessage());
            $this->finalizeStructureRunIfNeeded($continueTracking, false, $stats['message'], [
                'rows_created' => $stats['structures_skipped'],
                'rows_updated' => $stats['structures_synced'],
                'rows_unchanged' => $stats['structures_unchanged'],
                'rows_failed' => $stats['structures_failed'],
                'error_log' => $e->getMessage(),
            ]);

            return $stats;
        }
    }

    /**
     * Sincroniza estrutura completa (lista + rota) de um item específico.
     *
     * @return array{success: bool, message: string, lines: int, routes: int, skipped: bool, unchanged: bool}
     */
    public function syncItemStructureById(int $invItemId, bool $replaceWhenEmpty = true, ?SapReportApiService $sap = null): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'lines' => 0,
            'routes' => 0,
            'skipped' => false,
            'unchanged' => false,
        ];

        try {
            $itemsRepo = new InvItemsRepository();
            $item = $itemsRepo->getOne($invItemId);
            if (!is_array($item)) {
                $result['message'] = 'Item não encontrado para sincronizar estrutura.';
                return $result;
            }

            if (InvCostProjectHelper::isProjectItem($item)) {
                $result['message'] = 'Itens da categoria PA - PROJETO não são sincronizados com o SAP. Edite a lista de materiais manualmente.';
                return $result;
            }

            $parentErpCode = trim((string)($item['erp_code'] ?? ''));
            if ($parentErpCode === '') {
                $result['message'] = 'Item sem Código ERP. Informe o Código ERP antes de sincronizar estrutura.';
                return $result;
            }

            $sap = $sap ?? new SapReportApiService();
            $rows = $this->fetchStructureRowsFromSap($sap, $parentErpCode);
            [$materialSapRows, $routeSapRows] = $this->partitionStructureRowsByTipo($rows);
            $bomHash = $this->computeBomHashFromSapRows($materialSapRows);
            $routeHash = $this->computeRouteHashFromSapRows($routeSapRows);
            $storedItem = $itemsRepo->getOne($invItemId);
            $storedBomHash = is_array($storedItem) ? (string)($storedItem['sap_bom_hash'] ?? '') : '';
            $storedRouteHash = is_array($storedItem) ? (string)($storedItem['sap_route_hash'] ?? '') : '';
            if (
                $storedBomHash !== ''
                && $storedRouteHash !== ''
                && hash_equals($storedBomHash, $bomHash)
                && hash_equals($storedRouteHash, $routeHash)
            ) {
                $this->persistStructureSapMetadata($itemsRepo, $invItemId, $bomHash, $routeHash);
                $result['success'] = true;
                $result['unchanged'] = true;
                $result['lines'] = count($materialSapRows);
                $result['routes'] = count($routeSapRows);
                $result['message'] = 'Estrutura idêntica ao cadastro local (hash); nenhuma gravação necessária.';

                return $result;
            }

            $categoriesRepo = new InvCategoriesRepository();
            $unitsRepo = new InvUnitsRepository();
            $operationsRepo = new InvOperationsRepository();
            $resourcesRepo = new InvProductionResourcesRepository();
            $defaultUnitId = $unitsRepo->findIdByCode('UN');
            if ($defaultUnitId === null) {
                $firstUnit = $unitsRepo->getAll(1, 1);
                $defaultUnitId = (int)($firstUnit[0]['id'] ?? 0);
            }

            $bomLines = [];
            $routeLines = [];
            foreach ($rows as $row) {
                $tipo = strtoupper(trim((string)($row['tipo'] ?? $row['TIPO'] ?? '')));
                if ($tipo === '') {
                    continue;
                }

                if ($tipo === 'MATERIAIS') {
                    $componentErpCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
                    if ($componentErpCode === '') {
                        continue;
                    }

                    $component = $itemsRepo->findByErpCode($componentErpCode);
                    $sapComponentCost = $this->extractComponentCostFromRow($row);
                    if ($component === null) {
                        $componentId = $this->createLocalItemFromSapMaterialRow(
                            $row,
                            $itemsRepo,
                            $categoriesRepo,
                            $unitsRepo,
                            $defaultUnitId
                        );
                        if ($componentId <= 0) {
                            continue;
                        }
                        $component = $itemsRepo->getOne($componentId);
                    } elseif ($sapComponentCost > 0) {
                        $this->updateItemCostFromSap($itemsRepo, $component, $sapComponentCost);
                        $component = $itemsRepo->getOne((int)($component['id'] ?? 0)) ?: $component;
                    }

                    if (!is_array($component)) {
                        continue;
                    }

                    $qty = $this->toFloat($row['quantidade'] ?? $row['QUANTIDADE'] ?? 0);
                    if ($qty <= 0) {
                        $qty = $this->toFloat($row['menge_verbrauch'] ?? $row['MENGE_VERBRAUCH'] ?? 0);
                    }
                    if ($qty <= 0) {
                        continue;
                    }

                    $bomLines[] = [
                        'component_item_id' => (int)$component['id'],
                        'quantity_per_batch' => $qty,
                        'scrap_percent' => 0.0,
                    ];
                    continue;
                }

                if ($tipo === 'ROTA') {
                    $operationCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
                    $operationName = trim((string)($row['descricao'] ?? $row['DESCRICAO'] ?? ''));
                    if ($operationCode === '' && $operationName === '') {
                        continue;
                    }
                    if ($operationName === '') {
                        $operationName = $operationCode;
                    }
                    if ($operationCode === '') {
                        $operationCode = preg_replace('/\s+/', '_', strtoupper($operationName)) ?? $operationName;
                    }

                    $operation = $operationsRepo->findByCode($operationCode);
                    if ($operation === null) {
                        $operation = $operationsRepo->findByName($operationName);
                    }
                    if ($operation === null) {
                        $newId = $operationsRepo->create([
                            'code' => $operationCode,
                            'name' => $operationName,
                            'description' => 'Importado da rota SAP',
                            'default_cost_per_hour' => 0,
                            'active' => 1,
                        ]);
                        if (!is_int($newId) || $newId <= 0) {
                            continue;
                        }
                        $operation = $operationsRepo->getOne($newId);
                    }
                    if (!is_array($operation)) {
                        continue;
                    }

                    $sequence = (int)$this->toFloat($row['pos_id'] ?? $row['POS_ID'] ?? 0);
                    if ($sequence <= 0) {
                        $sequence = count($routeLines) + 1;
                    }

                    $tempo = $this->resolveRouteTimeMinutes($row);
                    $resource = trim((string)($row['recurso'] ?? $row['RECURSO'] ?? ''));
                    $routeCosts = $resourcesRepo->resolveRouteCosts($resource, $operationName);
                    $resources = [];
                    if (!empty($routeCosts['resource_id'])) {
                        $resources[] = [
                            'inv_production_resource_id' => (int)$routeCosts['resource_id'],
                            'qty' => 1,
                            'machine_cost_per_min' => $routeCosts['machine_cost_per_min'],
                            'energy_cost_per_min' => $routeCosts['energy_cost_per_min'],
                        ];
                    }
                    $routeLines[] = [
                        'inv_operation_id' => (int)($operation['id'] ?? 0),
                        'sequence' => $sequence,
                        'time_per_batch_hours' => max(0, $tempo),
                        'time_unit' => 'MIN',
                        'operators_qty' => 1,
                        'labor_cost_per_min' => $routeCosts['labor_cost_per_min'],
                        'machine_cost_per_min' => $routeCosts['machine_cost_per_min'],
                        'energy_cost_per_min' => $routeCosts['energy_cost_per_min'],
                        'notes' => $resource !== '' ? ('Recurso SAP: ' . $resource) : null,
                        'resources' => $resources,
                    ];
                }
            }

            if ($bomLines === [] && $routeLines === []) {
                $bomRepo = new InvItemBomRepository();
                $opsRepo = new InvItemOperationsRepository();
                $hasLocal = $bomRepo->getByItem($invItemId) !== [] || $opsRepo->getByItem($invItemId) !== [];
                $this->persistStructureSapMetadata($itemsRepo, $invItemId, $bomHash, $routeHash);
                $result['success'] = true;
                $result['skipped'] = true;
                $result['unchanged'] = $hasLocal;
                $inactive = empty($item['active']);
                $result['message'] = $hasLocal
                    ? 'Sem estrutura no SAP; cadastro local mantido.'
                    : ($inactive
                        ? 'Sem BOM/rota no SAP para este item inativo (validFor=N) ou versão BEAS não encontrada.'
                        : 'Sem BOM/rota no SAP para este item na versão BEAS atual.');

                return $result;
            }

            $bomRepo = new InvItemBomRepository();
            $opsRepo = new InvItemOperationsRepository();
            if ($bomRepo->structureLinesMatch($invItemId, $bomLines) && $opsRepo->structureLinesMatch($invItemId, $routeLines)) {
                $this->persistStructureSapMetadata($itemsRepo, $invItemId, $bomHash, $routeHash);
                $result['success'] = true;
                $result['unchanged'] = true;
                $result['lines'] = count($bomLines);
                $result['routes'] = count($routeLines);
                $result['message'] = 'Estrutura idêntica ao cadastro local; nenhuma gravação necessária.';

                return $result;
            }

            $ok = $bomRepo->replaceForItem($invItemId, $bomLines);
            if (!$ok) {
                $result['message'] = 'Falha ao gravar lista de materiais no banco local.';
                return $result;
            }
            $okRoute = $opsRepo->replaceForItem($invItemId, $routeLines);
            if (!$okRoute) {
                $result['message'] = 'Falha ao gravar rota no banco local.';
                return $result;
            }

            InventoryCostService::recalculateStandardCost($invItemId);
            $this->persistStructureSapMetadata($itemsRepo, $invItemId, $bomHash, $routeHash);
            $result['success'] = true;
            $result['lines'] = count($bomLines);
            $result['routes'] = count($routeLines);
            $result['message'] = "Estrutura sincronizada com sucesso. Componentes: {$result['lines']} | Operações: {$result['routes']}.";
            return $result;
        } catch (Throwable $e) {
            $result['message'] = 'Falha na sincronização da estrutura SAP: ' . $this->formatSyncErrorMessage($e->getMessage());
            return $result;
        }
    }

    private function formatSyncErrorMessage(string $rawMessage): string
    {
        $message = trim($rawMessage);
        $lower = mb_strtolower($message, 'UTF-8');

        if (
            str_contains($lower, 'could not resolve host') ||
            str_contains($lower, 'getaddrinfo failed') ||
            str_contains($lower, 'name or service not known')
        ) {
            return 'Host da API SAP não foi resolvido. Verifique se o túnel (ngrok/trycloudflare) expirou e atualize a URL em Configurações -> Configuração SAP API.';
        }

        if (
            str_contains($lower, 'failed to connect') ||
            str_contains($lower, 'connection refused') ||
            str_contains($lower, 'timed out')
        ) {
            return 'Não foi possível conectar na API SAP. Confirme se a API está online e se a URL/porta em Configurações -> Configuração SAP API está correta.';
        }

        if (
            str_contains($lower, 'apenas queries select') ||
            str_contains($lower, 'sql deve começar com select')
        ) {
            return 'A API SAP recusou a consulta de estrutura. Atualize o sistema para a versão mais recente e tente novamente. Se o erro persistir, verifique se a API intermediária aceita consultas com UNION ALL.';
        }

        if (
            str_contains($lower, 'invalid number') ||
            str_contains($lower, '__typecast__')
        ) {
            return 'A API SAP não conseguiu converter um valor numérico vazio na estrutura do item. Tente sincronizar novamente; se persistir, revise os tempos/quantidades no BEAS para o item.';
        }

        if (
            str_contains($lower, 'http 500') ||
            str_contains($lower, 'sem resposta') ||
            str_contains($lower, 'transfer closed')
        ) {
            return 'A API SAP retornou erro interno (HTTP 500), geralmente por consulta pesada ou instabilidade momentânea. '
                . 'Aguarde alguns segundos e tente novamente. Na sincronização completa o sistema consulta o SAP em lotes leves; '
                . 'não feche a aba — o processo pode levar vários minutos. '
                . 'Detalhe: ' . $message;
        }

        return $message !== '' ? $message : 'Erro desconhecido ao sincronizar com SAP.';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSapItemsPaginated(SapReportApiService $sap, ?string $updatedSince = null): array
    {
        if ($updatedSince === null) {
            return $this->fetchSapItemsFullCatalog($sap);
        }

        return $this->fetchSapItemsIncremental($sap, $updatedSince);
    }

    /**
     * Incremental: filtro UpdateDate + paginação OFFSET (poucos registros).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchSapItemsIncremental(SapReportApiService $sap, string $updatedSince): array
    {
        $batchSize = 50;
        $offset = 0;
        $all = [];

        for ($batch = 0; $batch < 40; $batch++) {
            $rows = $this->executeSapQueryWithRetry(
                $sap,
                $this->getSapItemsQuery($updatedSince, $batchSize, $offset)
            );
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $all[] = $row;
            }

            if (count($rows) < $batchSize) {
                break;
            }

            $offset += count($rows);
            usleep(600000);
        }

        return $all;
    }

    /**
     * Completa: catálogo inteiro em lotes leves (OITM + custo por lote), evita JOIN pesado único.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchSapItemsFullCatalog(SapReportApiService $sap): array
    {
        $groupMap = $this->fetchSapItemGroupsMap($sap);
        usleep(800000);
        $batchSize = 50;
        $lastCode = '';
        $all = [];

        for ($batch = 0; $batch < 150; $batch++) {
            $oitmRows = $this->fetchOitmKeysetBatch($sap, $lastCode, $batchSize);
            if ($oitmRows === []) {
                break;
            }

            foreach ($oitmRows as $row) {
                $code = trim((string)($row['ItemCode'] ?? $row['ITEMCODE'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $grpCod = $row['ItmsGrpCod'] ?? $row['ITMSGRPCOD'] ?? '';
                $all[] = [
                    'ItemCode' => $code,
                    'ItemName' => $row['ItemName'] ?? $row['ITEMNAME'] ?? '',
                    'InvntryUom' => $row['InvntryUom'] ?? $row['INVNTYUOM'] ?? '',
                    'validFor' => $row['validFor'] ?? $row['VALIDFOR'] ?? 'Y',
                    'AvgPrice' => $row['AvgPrice'] ?? $row['AVGPRICE'] ?? 0,
                    'ItemGroupName' => $groupMap[(string)$grpCod] ?? 'Geral',
                    'U_FormaFarma' => $row['U_FormaFarma'] ?? $row['U_FORMAFARMA'] ?? '',
                    'U_LinhaProduto' => $row['U_LinhaProduto'] ?? $row['U_LINHAPRODUTO'] ?? '',
                ];
            }

            $lastRow = $oitmRows[count($oitmRows) - 1];
            $lastCode = trim((string)($lastRow['ItemCode'] ?? $lastRow['ITEMCODE'] ?? $lastCode));

            if (count($oitmRows) < $batchSize) {
                break;
            }

            usleep(2500000);
        }

        return $all;
    }

    /**
     * @return array<string, string>
     */
    private function fetchSapItemGroupsMap(SapReportApiService $sap): array
    {
        $map = [];
        foreach ($this->executeSapQueryWithRetry($sap, 'SELECT "ItmsGrpCod", "ItmsGrpNam" FROM OITB') as $row) {
            $cod = trim((string)($row['ItmsGrpCod'] ?? $row['ITMSGRPCOD'] ?? ''));
            $name = trim((string)($row['ItmsGrpNam'] ?? $row['ITMSGRPNAM'] ?? ''));
            if ($cod !== '') {
                $map[$cod] = $name !== '' ? $name : 'Geral';
            }
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function executeSapQueryWithRetry(SapReportApiService $sap, string $sql, int $maxAttempts = 3): array
    {
        $last = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) {
                usleep(1000000 * min($attempt, 6));
            }

            try {
                return $this->extractSapRows($sap->execute($sql));
            } catch (Throwable $e) {
                $last = $e;
                if (!$this->isRetryableSapError($e) || $attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }

        if ($last instanceof Throwable) {
            throw $last;
        }

        return [];
    }

    private function isRetryableSapError(Throwable $e): bool
    {
        $msg = mb_strtolower($e->getMessage(), 'UTF-8');

        return str_contains($msg, 'http 500')
            || str_contains($msg, 'http 502')
            || str_contains($msg, 'http 503')
            || str_contains($msg, 'http 504')
            || str_contains($msg, 'sem resposta')
            || str_contains($msg, 'timed out')
            || str_contains($msg, 'timeout')
            || str_contains($msg, 'transfer closed')
            || str_contains($msg, 'empty reply')
            || str_contains($msg, 'connection reset')
            || str_contains($msg, 'failed to connect');
    }

    /**
     * Colunas padrão do catálogo OITM numa única consulta.
     * Campos nulos: COALESCE + TO_NVARCHAR em UpdateDate evita HTTP 500 da API (igual ao SAP Query Manager).
     */
    private function sapOitmCatalogSelectColumns(string $alias = 'T0'): string
    {
        return $alias . '."ItemCode", '
            . $this->sapCoalesceStringSelectExpression($alias, 'ItemName') . ', '
            . $this->sapCoalesceStringSelectExpression($alias, 'InvntryUom') . ', '
            . 'COALESCE(' . $alias . '."validFor", \'Y\') AS "validFor", '
            . $alias . '."ItmsGrpCod", '
            . $alias . '."AvgPrice", '
            . $this->sapOitmUpdateDateSelectExpression($alias) . ', '
            . 'COALESCE(' . $alias . '."U_FormaFarma", \'\') AS "U_FormaFarma", '
            . 'COALESCE(' . $alias . '."U_LinhaProduto", \'\') AS "U_LinhaProduto", '
            . 'COALESCE(' . $alias . '."U_beas_ver", \'\') AS "U_beas_ver", '
            . $alias . '."MinOrdrQty", '
            . $alias . '."MinLevel", '
            . $alias . '."MaxLevel", '
            . $this->sapCoalesceStringSelectExpression($alias, 'ManBtchNum') . ', '
            . $this->sapCoalesceStringSelectExpression($alias, 'ManSerNum');
    }

    /** UpdateDate NULL na OITM: API 5007 falha sem TO_NVARCHAR; no SAP o campo aparece vazio. */
    private function sapOitmUpdateDateSelectExpression(string $alias = 'T0'): string
    {
        return 'COALESCE(TO_NVARCHAR(' . $alias . '."UpdateDate"), \'\') AS "UpdateDate"';
    }

    private function sapCoalesceStringSelectExpression(string $alias, string $column): string
    {
        return 'COALESCE(' . $alias . '."' . $column . '", \'\') AS "' . $column . '"';
    }

    /**
     * NULL, string vazia, espaços e literal "null" → valor padrão (geralmente '').
     */
    private function sapEmptyAsString(mixed $value, string $default = ''): string
    {
        if ($value === null || $value === false) {
            return $default;
        }

        $text = trim((string)$value);

        return ($text === '' || strcasecmp($text, 'null') === 0) ? $default : $text;
    }

    /**
     * Igual a sapEmptyAsString, mas retorna null quando o resultado seria vazio.
     */
    private function sapEmptyAsNull(mixed $value): ?string
    {
        $text = $this->sapEmptyAsString($value);

        return $text === '' ? null : $text;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function sapRowString(array $row, string $field, string $default = ''): string
    {
        $value = $row[$field] ?? $row[strtoupper($field)] ?? null;

        return $this->sapEmptyAsString($value, $default);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function sapRowNumericOrNull(array $row, string $field): ?float
    {
        $value = $row[$field] ?? $row[strtoupper($field)] ?? null;
        if ($value === null || $value === false) {
            return null;
        }
        if (is_string($value) && strcasecmp(trim($value), 'null') === 0) {
            return null;
        }
        if ($value === '') {
            return null;
        }

        return round($this->toFloat($value), 6);
    }

    /**
     * Grupos de colunas para fallback quando o SELECT completo retorna HTTP 500.
     *
     * @return list<string>
     */
    private function sapOitmCatalogFallbackSelectGroups(string $alias = 'T0'): array
    {
        return [
            $alias . '."ItemCode", '
                . $this->sapCoalesceStringSelectExpression($alias, 'ItemName') . ', '
                . $this->sapCoalesceStringSelectExpression($alias, 'InvntryUom') . ', '
                . 'COALESCE(' . $alias . '."validFor", \'Y\') AS "validFor", '
                . $alias . '."ItmsGrpCod", '
                . $alias . '."AvgPrice", '
                . $alias . '."MinOrdrQty", '
                . $alias . '."MinLevel", '
                . $alias . '."MaxLevel", '
                . $this->sapCoalesceStringSelectExpression($alias, 'ManBtchNum') . ', '
                . $this->sapCoalesceStringSelectExpression($alias, 'ManSerNum'),
            $this->sapOitmUpdateDateSelectExpression($alias) . ', '
                . 'COALESCE(' . $alias . '."U_FormaFarma", \'\') AS "U_FormaFarma", '
                . 'COALESCE(' . $alias . '."U_LinhaProduto", \'\') AS "U_LinhaProduto", '
                . 'COALESCE(' . $alias . '."U_beas_ver", \'\') AS "U_beas_ver"',
        ];
    }

    /**
     * @param array<string, string> $groupMap
     * @return array<string, mixed>|null
     */
    private function fetchOitmRowWithColumnFallback(
        SapReportApiService $sap,
        string $itemCode,
        array $groupMap
    ): ?array {
        $safeCode = str_replace("'", "''", trim($itemCode));
        if ($safeCode === '') {
            return null;
        }

        $where = 'FROM OITM T0 WHERE T0."ItemCode" = \'' . $safeCode . '\''
            . $this->sapAllowedGroupsWhereSql('T0');

        try {
            $sql = 'SELECT ' . $this->sapOitmCatalogSelectColumns('T0') . ' ' . $where . ' LIMIT 1';
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
            if (is_array($rows[0] ?? null)) {
                return $this->normalizeOitmCatalogRow($rows[0], $groupMap);
            }
        } catch (Throwable) {
            // tenta grupos parciais abaixo
        }

        $merged = [];
        foreach ($this->sapOitmCatalogFallbackSelectGroups('T0') as $selectList) {
            try {
                $sql = 'SELECT ' . $selectList . ' ' . $where . ' LIMIT 1';
                $rows = $this->executeSapQueryWithRetry($sap, $sql, 1);
                if (is_array($rows[0] ?? null)) {
                    $merged = array_merge($merged, $rows[0]);
                }
            } catch (Throwable) {
                continue;
            }
        }

        $erp = trim((string)($merged['ItemCode'] ?? $merged['ITEMCODE'] ?? $itemCode));
        if ($erp === '') {
            return null;
        }

        return $this->normalizeOitmCatalogRow($merged, $groupMap);
    }

    /**
     * @param list<string>|null $restrictGroupCodes
     */
    private function fetchNextSapItemCodeAfter(
        SapReportApiService $sap,
        string $afterItemCode,
        ?array $restrictGroupCodes = null
    ): ?string {
        $afterClause = '';
        if ($afterItemCode !== '') {
            $afterClause = ' AND T0."ItemCode" > \'' . str_replace("'", "''", $afterItemCode) . '\'';
        }

        $groupFilter = '';
        if ($restrictGroupCodes !== null && $restrictGroupCodes !== []) {
            $quoted = array_map(
                static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'",
                $restrictGroupCodes
            );
            $groupFilter = ' AND T0."ItmsGrpCod" IN (' . implode(', ', $quoted) . ')';
        }

        $sql = 'SELECT T0."ItemCode" FROM OITM T0 '
            . 'WHERE 1=1' . $this->sapAllowedGroupsWhereSql('T0') . $groupFilter . $afterClause . ' '
            . 'ORDER BY T0."ItemCode" '
            . 'LIMIT 1';

        try {
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
        } catch (Throwable) {
            return null;
        }

        $code = trim((string)($rows[0]['ItemCode'] ?? $rows[0]['ITEMCODE'] ?? ''));

        return $code !== '' ? $code : null;
    }

    /** Inclui itens com UpdateDate NULL (comum no SAP) na sync incremental. */
    private function sapIncrementalUpdateDateFilterSql(string $tableAlias, string $sinceDate): string
    {
        $safeDate = str_replace("'", "''", substr($sinceDate, 0, 10));

        return ' AND (' . $tableAlias . '."UpdateDate" IS NULL OR ' . $tableAlias . '."UpdateDate" >= \'' . $safeDate . '\')';
    }

    private function getSapItemsQuery(?string $updatedSince = null, int $limit = 0, int $offset = 0): string
    {
        $where = 'WHERE 1=1' . $this->sapAllowedGroupsWhereSql('T0');
        if ($updatedSince !== null && $updatedSince !== '') {
            $where .= $this->sapIncrementalUpdateDateFilterSql('T0', $updatedSince);
        }

        $sql = 'SELECT '
            . 'T0."ItemCode", '
            . 'COALESCE(T0."ItemName", \'\') AS "ItemName", '
            . 'COALESCE(T0."InvntryUom", \'\') AS "InvntryUom", '
            . 'T0."AvgPrice", '
            . 'COALESCE(T0."validFor", \'Y\') AS "validFor", '
            . 'COALESCE(T0."U_FormaFarma", \'\') AS "U_FormaFarma", '
            . 'COALESCE(T0."U_LinhaProduto", \'\') AS "U_LinhaProduto", '
            . 'T1."ItmsGrpNam" AS "ItemGroupName" '
            . 'FROM OITM T0 '
            . 'LEFT JOIN OITB T1 ON T1."ItmsGrpCod" = T0."ItmsGrpCod" '
            . $where
            . ' ORDER BY T0."ItemCode"';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(200, $limit));
            $sql .= ' OFFSET ' . max(0, $offset);
        }

        return $sql;
    }

    /**
     * @param array<string, string> $groupMap
     * @param array<string, array<string, mixed>> $localSnapshot
     * @param array<string, mixed> $stats
     * @param array<string, true> $allowedErpCodes
     * @param list<string>|null $restrictGroupCodes
     */
    private function runCatalogSyncBatches(
        SapReportApiService $sap,
        array $groupMap,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        InvPharmaFormsRepository $pharmaFormsRepo,
        int $defaultUnitId,
        array &$localSnapshot,
        array &$stats,
        array &$allowedErpCodes,
        string &$lastCode,
        ?string &$partialError,
        bool $useFullSync,
        ?array $restrictGroupCodes = null
    ): bool {
        $purchaseCostsChanged = false;
        $batchSize = 50;
        $batchFailures = 0;
        $maxBatchFailures = 5;
        $stuckAtCode = '';
        $stuckFailures = 0;
        $incrementalSince = $useFullSync ? null : $this->resolveIncrementalSinceDate();

        for ($batch = 0; $batch < 400; $batch++) {
            $this->throwIfSyncCancelled();

            if ($batch === 0 && $this->activeSyncRunId > 0 && $this->trackedProgressTotal === 0) {
                $estimated = $this->estimateSapCatalogCount($sap, $restrictGroupCodes);
                if ($estimated > 0) {
                    $this->trackedProgressTotal = $estimated;
                    $this->publishSyncProgress($stats, 'Consultando catálogo SAP…', $estimated);
                }
            }

            try {
                $oitmRows = $this->fetchOitmRichKeysetBatch($sap, $lastCode, $batchSize, $restrictGroupCodes, $incrementalSince);
            } catch (Throwable $e) {
                if ($lastCode !== '' && $lastCode === $stuckAtCode) {
                    $stuckFailures++;
                } else {
                    $stuckAtCode = $lastCode;
                    $stuckFailures = 1;
                }

                if (
                    $stuckFailures >= $maxBatchFailures
                    && $stats['examined'] === 0
                    && $lastCode !== ''
                ) {
                    throw new \RuntimeException(
                        'API SAP indisponível após o código ' . $lastCode
                        . '. A sincronização foi interrompida para evitar retentativas em loop.'
                        . ' Verifique o serviço SAP (porta 5007) e tente novamente.'
                    );
                }

                if ($this->isRetryableSapError($e) && $batchFailures < $maxBatchFailures) {
                    $batchFailures++;
                    if ($batchSize > 1) {
                        $batchSize = max(1, (int) floor($batchSize / 2));
                        if ($this->activeSyncRunId > 0) {
                            $this->publishSyncProgress(
                                $stats,
                                sprintf(
                                    'Instabilidade na API SAP — tentativa %d/%d (lote %d itens)…',
                                    $batchFailures,
                                    $maxBatchFailures,
                                    $batchSize
                                ),
                                $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null
                            );
                        }
                        usleep(800000 * min($batchFailures, 4));
                        $batch--;
                        continue;
                    }

                    $skipCode = $this->fetchNextSapItemCodeAfter($sap, $lastCode, $restrictGroupCodes);
                    if ($skipCode === null || $skipCode === $lastCode) {
                        throw $e;
                    }

                    $fallbackRow = $this->fetchOitmRowWithColumnFallback($sap, $skipCode, $groupMap);
                    if ($fallbackRow !== null) {
                        if ($this->processSapBatchRows(
                            $sap,
                            [$fallbackRow],
                            $groupMap,
                            $itemsRepo,
                            $categoriesRepo,
                            $unitsRepo,
                            $pharmaFormsRepo,
                            $defaultUnitId,
                            $localSnapshot,
                            $stats,
                            $allowedErpCodes,
                            $useFullSync,
                            true
                        )) {
                            $purchaseCostsChanged = true;
                        }
                        $lastCode = $skipCode;
                        $batchFailures = 0;
                        $stuckAtCode = '';
                        $stuckFailures = 0;
                        if ($this->activeSyncRunId > 0) {
                            $this->publishSyncProgress(
                                $stats,
                                'Item ' . $skipCode . ' sincronizado (fallback parcial SAP)…',
                                $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null
                            );
                        }
                        $batch--;
                        continue;
                    }

                    $stats['failed'] = (int)($stats['failed'] ?? 0) + 1;
                    $this->recordSyncFailure(
                        $skipCode,
                        'A API SAP não retornou o cadastro completo do item.',
                        'items'
                    );
                    $lastCode = $skipCode;
                    $batchFailures = 0;
                    $stuckAtCode = '';
                    $stuckFailures = 0;
                    $partialError = 'Item ' . $skipCode . ' ignorado: a API SAP não retornou o cadastro completo.';
                    if ($this->activeSyncRunId > 0) {
                        $this->publishSyncProgress(
                            $stats,
                            'Item ' . $skipCode . ' ignorado (erro SAP) — continuando…',
                            $this->trackedProgressTotal > 0 ? $this->trackedProgressTotal : null
                        );
                    }
                    $batch--;
                    continue;
                }
                if ($stats['examined'] > 0) {
                    $stats['partial'] = true;
                    $stats['checkpoint_code'] = $lastCode;
                    $partialError = $e->getMessage();
                    break;
                }
                throw $e;
            }

            $batchFailures = 0;
            if ($oitmRows === []) {
                break;
            }

            if ($this->processSapBatchRows(
                $sap,
                $oitmRows,
                $groupMap,
                $itemsRepo,
                $categoriesRepo,
                $unitsRepo,
                $pharmaFormsRepo,
                $defaultUnitId,
                $localSnapshot,
                $stats,
                $allowedErpCodes,
                $useFullSync,
                false
            )) {
                $purchaseCostsChanged = true;
            }

            $lastRow = $oitmRows[count($oitmRows) - 1];
            $lastCode = trim((string)($lastRow['ItemCode'] ?? $lastRow['ITEMCODE'] ?? $lastCode));

            if ($this->activeSyncRunId > 0) {
                $this->publishSyncProgress(
                    $stats,
                    $lastCode !== '' ? ('Analisando item ' . $lastCode . '…') : 'Processando lote…',
                    $this->trackedProgressTotal
                );
            }

            if (count($oitmRows) < $batchSize) {
                break;
            }

            usleep(800000);
        }

        return $purchaseCostsChanged;
    }

    private function resolveSyncResumeAfterCode(
        InvInventorySapSyncRunsRepository $runsRepo,
        InvItemsRepository $itemsRepo
    ): string {
        $fromRun = $runsRepo->getLastPartialCheckpoint('items');
        if ($fromRun !== null && $fromRun !== '') {
            return $fromRun;
        }

        return $itemsRepo->getMaxSyncedErpCode() ?? '';
    }

    private function resolveCatalogSyncStartCode(
        InvInventorySapSyncRunsRepository $runsRepo,
        bool $useFullSync
    ): string {
        $checkpoint = $runsRepo->getLastPartialCheckpoint('items');
        if ($checkpoint !== null && $checkpoint !== '') {
            return $checkpoint;
        }

        if ($useFullSync) {
            return '';
        }

        return $this->resolveSyncResumeAfterCode($runsRepo, new InvItemsRepository());
    }

    /**
     * @param array<string, string> $groupMap
     * @param array<string, array<string, mixed>> $localSnapshot
     * @param array<string, mixed> $stats
     * @param array<string, true> $allowedErpCodes
     */
    private function syncSingleItemFromSap(
        SapReportApiService $sap,
        string $itemCode,
        array $groupMap,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        InvPharmaFormsRepository $pharmaFormsRepo,
        int $defaultUnitId,
        array &$localSnapshot,
        array &$stats,
        array &$allowedErpCodes
    ): bool {
        $row = $this->fetchSapItemCompleteRow($sap, $itemCode, $groupMap);
        if ($row === null) {
            return false;
        }

        if ($this->activeSyncRunId > 0) {
            $this->publishSyncProgress($stats, 'Sincronizando item ' . $itemCode . '…', 1);
        }

        return $this->processSapBatchRows(
            $sap,
            [$row],
            $groupMap,
            $itemsRepo,
            $categoriesRepo,
            $unitsRepo,
            $pharmaFormsRepo,
            $defaultUnitId,
            $localSnapshot,
            $stats,
            $allowedErpCodes,
            true,
            true
        );
    }

    /**
     * @param list<array<string, mixed>> $oitmRows
     * @param array<string, string> $groupMap
     * @param array<string, array<string, mixed>> $localSnapshot
     * @param array<string, mixed> $stats
     * @param array<string, true> $allowedErpCodes
     */
    private function processSapBatchRows(
        SapReportApiService $sap,
        array $oitmRows,
        array $groupMap,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        InvPharmaFormsRepository $pharmaFormsRepo,
        int $defaultUnitId,
        array &$localSnapshot,
        array &$stats,
        array &$allowedErpCodes,
        bool $useFullSync,
        bool $forceSync
    ): bool {
        $changed = false;

        foreach ($oitmRows as $lightRow) {
            $stats['examined']++;
            $erpCode = trim((string)($lightRow['ItemCode'] ?? $lightRow['ITEMCODE'] ?? ''));
            if ($erpCode !== '') {
                $allowedErpCodes[$erpCode] = true;
            }

            $local = $localSnapshot[mb_strtoupper($erpCode, 'UTF-8')] ?? null;
            $complete = $this->normalizeOitmCatalogRow($lightRow, $groupMap);

            if (!$forceSync && !$useFullSync && $local !== null) {
                $rowHash = $this->computeItemHashFromSapRow($complete);
                $storedHash = (string)($local['sap_item_hash'] ?? '');
                $differs = $this->sapRowDiffersFromLocalItem($complete, $local, $pharmaFormsRepo);
                if (!$differs && ($storedHash === '' || hash_equals($storedHash, $rowHash))) {
                    $stats['unchanged']++;
                    if ($storedHash === '') {
                        $categoryName = $this->normalizeCategoryName((string)($complete['ItemGroupName'] ?? 'Geral'));
                        $isPaPi = in_array($this->inferItemTypeFromCategory($categoryName), ['PA', 'PI'], true);
                        $this->persistItemSapMetadata($itemsRepo, (int)$local['id'], $complete, $local, $rowHash, $isPaPi);
                    }
                    continue;
                }
            }

            if ($this->processSapItemRow(
                $complete,
                $itemsRepo,
                $categoriesRepo,
                $unitsRepo,
                $pharmaFormsRepo,
                $defaultUnitId,
                $stats,
                $allowedErpCodes,
                $local
            )) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param array<string, string> $groupMap
     * @return list<string>
     */
    private function resolveGroupCodesForPrefix(array $groupMap, string $prefix): array
    {
        if (!in_array($prefix, self::SAP_ALLOWED_ITEM_GROUP_PREFIXES, true)) {
            return [];
        }

        $codes = [];
        foreach ($groupMap as $cod => $name) {
            if (preg_match('/^(\d+)/', trim($name), $matches) === 1 && $matches[1] === $prefix) {
                $codes[] = (string)$cod;
            }
        }

        return $codes;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSapItemLightByCode(SapReportApiService $sap, string $itemCode): ?array
    {
        $safeCode = str_replace("'", "''", trim($itemCode));
        if ($safeCode === '') {
            return null;
        }

        $sql = 'SELECT ' . $this->sapOitmCatalogSelectColumns('T0') . ' '
            . 'FROM OITM T0 '
            . 'WHERE T0."ItemCode" = \'' . $safeCode . '\''
            . $this->sapAllowedGroupsWhereSql('T0')
            . ' LIMIT 1';

        try {
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 3);
        } catch (Throwable) {
            return null;
        }

        if (is_array($rows[0] ?? null)) {
            return $rows[0];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $stats
     * @param array<string, true> $allowedErpCodes
     */
    private function processSapItemRow(
        array $row,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        InvPharmaFormsRepository $pharmaFormsRepo,
        int $defaultUnitId,
        array &$stats,
        array &$allowedErpCodes,
        ?array $existing = null
    ): bool {
        $erpCode = trim((string)($row['ItemCode'] ?? ''));
        if ($erpCode !== '') {
            $allowedErpCodes[$erpCode] = true;
        }

        $description = trim((string)($row['ItemName'] ?? ''));
        if ($erpCode === '') {
            return false;
        }
        if ($description === '') {
            $description = $erpCode;
        }

        $categoryName = $this->normalizeCategoryName((string)($row['ItemGroupName'] ?? 'Geral'));
        $categoryId = $categoriesRepo->findOrCreateByName($categoryName);
        if ($categoryId === null) {
            return false;
        }

        $uomCode = strtoupper(trim((string)($row['InvntryUom'] ?? '')));
        $unitId = $unitsRepo->findIdByCode($uomCode) ?? $defaultUnitId;
        if (!$unitId) {
            return false;
        }

        $itemType = $this->inferItemTypeFromCategory($categoryName);
        $isPaPi = in_array($itemType, ['PA', 'PI'], true);
        $sapCost = $this->extractSapCost($row);
        $active = strtoupper((string)($row['validFor'] ?? 'Y')) === 'Y' ? 1 : 0;
        $sapUpdateDate = $this->normalizeSapUpdateDate($row['UpdateDate'] ?? $row['UPDATEDATE'] ?? null);
        $pharmaFormId = $isPaPi
            ? $this->resolvePharmaFormId($pharmaFormsRepo, $row['U_FormaFarma'] ?? $row['U_FORMAFARMA'] ?? null)
            : null;
        $productionLine = $isPaPi
            ? InvCostProductionLineHelper::fromSapLinhaProduto($row['U_LinhaProduto'] ?? $row['U_LINHAPRODUTO'] ?? null)
            : null;

        if ($existing === null) {
            $existing = $itemsRepo->findByErpCode($erpCode);
        }

        $minLevel = $this->extractSapNumericField($row, 'MinLevel');
        $maxLevel = $this->extractSapNumericField($row, 'MaxLevel');
        $minOrderQty = $this->extractSapNumericField($row, 'MinOrdrQty');
        $adminType = $this->resolveSapAdminType($row);

        $itemHash = $this->computeItemHashFromSapRow($row);
        if ($existing !== null) {
            $storedHash = (string)($existing['sap_item_hash'] ?? '');
            if ($storedHash !== '' && hash_equals($storedHash, $itemHash)) {
                if ($this->reconcileUnchangedSapItem(
                    $itemsRepo,
                    $existing,
                    $row,
                    $itemHash,
                    $isPaPi,
                    $pharmaFormId,
                    $productionLine,
                    $active,
                    $sapUpdateDate,
                    (int)$unitId,
                    (int)$categoryId,
                    $adminType,
                    $minLevel,
                    $maxLevel,
                    $minOrderQty,
                    $stats
                )) {
                    return in_array($itemType, ['MP', 'EMB'], true);
                }

                return false;
            }
        }

        if ($existing !== null) {
            $shouldUpdateCost = in_array($itemType, ['MP', 'EMB'], true);
            $payload = [
                'code' => (string)($existing['code'] ?? $erpCode),
                'erp_code' => $erpCode,
                'description' => $description,
                'inv_unit_id' => (int)$unitId,
                'inv_category_id' => (int)$categoryId,
                'admin_type' => $adminType,
                'average_cost' => $shouldUpdateCost ? $sapCost : (float)($existing['average_cost'] ?? 0),
                'last_cost' => $shouldUpdateCost ? $sapCost : (float)($existing['last_cost'] ?? 0),
                'min_stock' => $minLevel ?? (float)($existing['min_stock'] ?? 0),
                'max_stock' => $maxLevel ?? (float)($existing['max_stock'] ?? 0),
                'standard_batch_size' => $minOrderQty ?? (float)($existing['standard_batch_size'] ?? 1),
                'production_line' => $isPaPi ? $productionLine : ($existing['production_line'] ?? null),
                'inv_pharma_form_id' => $isPaPi ? $pharmaFormId : ($existing['inv_pharma_form_id'] ?? null),
                'sap_update_date' => $sapUpdateDate,
                'active' => $active,
            ];

            if (!$this->itemPayloadDiffersFromExisting($existing, $payload)) {
                $this->persistItemSapMetadata($itemsRepo, (int)$existing['id'], $row, $existing, $itemHash, $isPaPi);
                $stats['unchanged']++;

                return false;
            }

            if ($itemsRepo->update((int)$existing['id'], $payload)) {
                $this->persistItemSapMetadata($itemsRepo, (int)$existing['id'], $row, $existing, $itemHash, $isPaPi);
                $stats['updated']++;
                $stats['synced']++;

                return $shouldUpdateCost;
            }

            return false;
        }

        $created = $itemsRepo->create([
            'code' => $erpCode,
            'erp_code' => $erpCode,
            'description' => $description,
            'inv_unit_id' => (int)$unitId,
            'inv_category_id' => (int)$categoryId,
            'admin_type' => $adminType,
            'average_cost' => $sapCost,
            'last_cost' => $sapCost,
            'min_stock' => $minLevel ?? 0,
            'max_stock' => $maxLevel ?? 0,
            'standard_batch_size' => max(0.000001, $minOrderQty ?? 1),
            'production_line' => $productionLine,
            'inv_pharma_form_id' => $pharmaFormId,
            'sap_update_date' => $sapUpdateDate,
            'active' => $active,
        ]);
        if (is_int($created) && $created > 0) {
            $this->persistItemSapMetadata($itemsRepo, $created, $row, null, $itemHash, $isPaPi);
            $stats['created']++;
            $stats['synced']++;

            return in_array($itemType, ['MP', 'EMB'], true);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $groupMap
     * @return array<string, mixed>
     */
    private function normalizeOitmCatalogRow(array $row, array $groupMap): array
    {
        $grpCod = $this->sapRowString($row, 'ItmsGrpCod');

        return [
            'ItemCode' => $this->sapRowString($row, 'ItemCode'),
            'ItemName' => $this->sapRowString($row, 'ItemName'),
            'InvntryUom' => $this->sapRowString($row, 'InvntryUom'),
            'validFor' => $this->sapEmptyAsString($row['validFor'] ?? $row['VALIDFOR'] ?? null, 'Y'),
            'AvgPrice' => $row['AvgPrice'] ?? $row['AVGPRICE'] ?? 0,
            'UpdateDate' => $this->sapRowString($row, 'UpdateDate'),
            'ItmsGrpCod' => $grpCod,
            'ItemGroupName' => $groupMap[$grpCod] ?? 'Geral',
            'U_FormaFarma' => $this->sapRowString($row, 'U_FormaFarma'),
            'U_LinhaProduto' => $this->sapRowString($row, 'U_LinhaProduto'),
            'U_beas_ver' => $this->sapRowString($row, 'U_beas_ver'),
            'MinOrdrQty' => $this->sapRowNumericOrNull($row, 'MinOrdrQty'),
            'MinLevel' => $this->sapRowNumericOrNull($row, 'MinLevel'),
            'MaxLevel' => $this->sapRowNumericOrNull($row, 'MaxLevel'),
            'ManBtchNum' => $this->sapRowString($row, 'ManBtchNum'),
            'ManSerNum' => $this->sapRowString($row, 'ManSerNum'),
        ];
    }

    /**
     * Compara registro SAP com item local pelo código ERP (não pela descrição).
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $local
     */
    private function sapRowDiffersFromLocalItem(
        array $row,
        ?array $local,
        ?InvPharmaFormsRepository $pharmaFormsRepo = null
    ): bool {
        if ($local === null) {
            return true;
        }

        $localErp = mb_strtoupper(trim((string)($local['erp_code'] ?? '')), 'UTF-8');
        $sapErp = mb_strtoupper(trim((string)($row['ItemCode'] ?? '')), 'UTF-8');
        if ($localErp !== '' && $sapErp !== '' && $localErp !== $sapErp) {
            return true;
        }

        $categoryName = $this->normalizeCategoryName((string)($row['ItemGroupName'] ?? 'Geral'));
        $itemType = $this->inferItemTypeFromCategory($categoryName);
        $active = strtoupper((string)($row['validFor'] ?? 'Y')) === 'Y' ? 1 : 0;
        $sapUpdateDate = $this->normalizeSapUpdateDate($row['UpdateDate'] ?? null);
        $localUpdateDate = $this->normalizeSapUpdateDate($local['sap_update_date'] ?? null);

        if ($sapUpdateDate !== $localUpdateDate) {
            return true;
        }

        if ($this->sapEmptyAsString($local['description'] ?? null) !== $this->sapEmptyAsString($row['ItemName'] ?? null)) {
            return true;
        }

        if ((int)($local['active'] ?? 1) !== $active) {
            return true;
        }

        if (mb_strtoupper(trim((string)($local['category_name'] ?? ''))) !== mb_strtoupper($categoryName)) {
            return true;
        }

        $sapUom = mb_strtoupper(trim((string)($row['InvntryUom'] ?? '')));
        $localUom = mb_strtoupper(trim((string)($local['unit_code'] ?? '')));
        if ($sapUom !== '' && $localUom !== '' && $sapUom !== $localUom) {
            return true;
        }

        if (in_array($itemType, ['MP', 'EMB'], true)) {
            $sapCost = $this->extractSapCost($row);
            if (round((float)($local['average_cost'] ?? 0), 6) !== round($sapCost, 6)) {
                return true;
            }
        }

        $minLevel = $this->extractSapNumericField($row, 'MinLevel');
        $maxLevel = $this->extractSapNumericField($row, 'MaxLevel');
        $minOrderQty = $this->extractSapNumericField($row, 'MinOrdrQty');
        $sapAdminType = $this->resolveSapAdminType($row);

        if ($minLevel !== null && round((float)($local['min_stock'] ?? 0), 6) !== $minLevel) {
            return true;
        }
        if ($maxLevel !== null && round((float)($local['max_stock'] ?? 0), 6) !== $maxLevel) {
            return true;
        }
        if ($minOrderQty !== null && round((float)($local['standard_batch_size'] ?? 1), 6) !== $minOrderQty) {
            return true;
        }
        if ((string)($local['admin_type'] ?? 'none') !== $sapAdminType) {
            return true;
        }

        if ($this->itemTypeNeedsUdfFields($row) && $pharmaFormsRepo !== null) {
            $sapPharmaId = $this->resolvePharmaFormId(
                $pharmaFormsRepo,
                $this->sapRowString($row, 'U_FormaFarma')
            );
            $localPharmaId = !empty($local['inv_pharma_form_id']) ? (int)$local['inv_pharma_form_id'] : null;
            if ($sapPharmaId !== $localPharmaId) {
                return true;
            }

            $sapLine = InvCostProductionLineHelper::fromSapLinhaProduto(
                $this->sapEmptyAsNull($row['U_LinhaProduto'] ?? $row['U_LINHAPRODUTO'] ?? null)
            );
            $localLine = InvCostProductionLineHelper::normalize($local['production_line'] ?? null);
            if ($sapLine !== $localLine) {
                return true;
            }
        } elseif ($this->itemTypeNeedsUdfFields($row)) {
            if (empty($local['inv_pharma_form_id'])
                || InvCostProductionLineHelper::normalize($local['production_line'] ?? null) === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function itemTypeNeedsUdfFields(array $row): bool
    {
        $categoryName = $this->normalizeCategoryName((string)($row['ItemGroupName'] ?? 'Geral'));
        $itemType = $this->inferItemTypeFromCategory($categoryName);

        return in_array($itemType, ['PA', 'PI'], true);
    }

    /**
     * Registro SAP completo por item — todos os campos de cadastro numa única leitura lógica.
     *
     * @param array<string, mixed>|null $lightRow linha do lote keyset (evita reconsulta dos campos básicos)
     * @return array<string, mixed>|null
     */
    private function fetchSapItemCompleteRow(
        SapReportApiService $sap,
        string $itemCode,
        array $groupMap,
        ?array $lightRow = null
    ): ?array {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return null;
        }

        if ($lightRow !== null) {
            $normalizedLight = $this->normalizeOitmCatalogRow($lightRow, $groupMap);
            if (!$this->itemTypeNeedsUdfFields($normalizedLight)) {
                return $normalizedLight;
            }
        }

        $unified = $this->tryFetchSapItemRowUnifiedSelect($sap, $itemCode, $groupMap);
        if ($unified !== null) {
            return $unified;
        }

        if ($lightRow === null) {
            $lightRow = $this->fetchSapItemLightByCode($sap, $itemCode);
        }
        if ($lightRow === null) {
            return $this->fetchOitmRowWithColumnFallback($sap, $itemCode, $groupMap);
        }

        $normalized = $this->normalizeOitmCatalogRow($lightRow, $groupMap);
        $needsUdf = $this->itemTypeNeedsUdfFields($normalized);
        $udf = $this->resolveSapUdfValuesForItem($sap, $itemCode, $needsUdf);
        $normalized['U_FormaFarma'] = $udf['U_FormaFarma'];
        $normalized['U_LinhaProduto'] = $udf['U_LinhaProduto'];

        return $normalized;
    }

    /**
     * SELECT único com todos os campos (quando a API SAP aceita UDF no SELECT).
     *
     * @param array<string, string> $groupMap
     * @return array<string, mixed>|null
     */
    private function tryFetchSapItemRowUnifiedSelect(
        SapReportApiService $sap,
        string $itemCode,
        array $groupMap
    ): ?array {
        if ($this->sapUnifiedSelectSupported === false) {
            return null;
        }

        $safeCode = str_replace("'", "''", trim($itemCode));
        if ($safeCode === '') {
            return null;
        }

        $sql = 'SELECT ' . $this->sapOitmCatalogSelectColumns('T0') . ' '
            . 'FROM OITM T0 WHERE T0."ItemCode" = \'' . $safeCode . '\''
            . $this->sapAllowedGroupsWhereSql('T0')
            . ' LIMIT 1';

        try {
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
        } catch (Throwable) {
            return $this->fetchOitmRowWithColumnFallback($sap, $itemCode, $groupMap);
        }

        if (!is_array($rows[0] ?? null)) {
            return null;
        }

        $this->sapUnifiedSelectSupported = true;

        return $this->normalizeOitmCatalogRow($rows[0], $groupMap);
    }

    /**
     * @return array{U_FormaFarma: string, U_LinhaProduto: string}
     */
    private function resolveSapUdfValuesForItem(SapReportApiService $sap, string $erpCode, bool $needed): array
    {
        if (!$needed) {
            return ['U_FormaFarma' => '', 'U_LinhaProduto' => ''];
        }

        $this->probeSapUdfForSingleItem($sap, $erpCode);
        $key = $this->normalizeErpKey($erpCode);
        $forma = $this->sapUdfFormaByItem[$key] ?? self::SAP_UDF_EMPTY;
        $linha = $this->sapUdfLinhaByItem[$key] ?? self::SAP_UDF_EMPTY;

        return [
            'U_FormaFarma' => $forma === self::SAP_UDF_EMPTY ? '' : $forma,
            'U_LinhaProduto' => $linha === self::SAP_UDF_EMPTY ? '' : $linha,
        ];
    }

    private function normalizeErpKey(string $erpCode): string
    {
        return mb_strtoupper(trim($erpCode), 'UTF-8');
    }

    /**
     * @return list<string>
     */
    private function sapPharmaFormCodes(): array
    {
        return array_values(array_unique(array_merge(['1'], array_keys(self::SAP_PHARMA_FORM_FALLBACK))));
    }

    private function probeSapUdfForSingleItem(SapReportApiService $sap, string $erpCode): void
    {
        $key = $this->normalizeErpKey($erpCode);
        if (isset($this->sapUdfFormaByItem[$key]) && isset($this->sapUdfLinhaByItem[$key])) {
            return;
        }

        $safeCode = str_replace("'", "''", trim($erpCode));
        if ($safeCode === '') {
            return;
        }

        if (!isset($this->sapUdfFormaByItem[$key])) {
            $forma = self::SAP_UDF_EMPTY;
            foreach ($this->sapPharmaFormCodes() as $formaCode) {
                $sql = 'SELECT "ItemCode" FROM OITM WHERE "ItemCode" = \'' . $safeCode
                    . '\' AND "U_FormaFarma" = \'' . str_replace("'", "''", $formaCode) . '\'';
                try {
                    $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
                } catch (Throwable) {
                    break;
                }
                if ($rows !== []) {
                    $forma = $formaCode;
                    break;
                }
                usleep(150000);
            }
            $this->sapUdfFormaByItem[$key] = $forma;
        }

        if (!isset($this->sapUdfLinhaByItem[$key])) {
            $linha = self::SAP_UDF_EMPTY;
            foreach (['P', 'T'] as $linhaCode) {
                $sql = 'SELECT "ItemCode" FROM OITM WHERE "ItemCode" = \'' . $safeCode
                    . '\' AND "U_LinhaProduto" = \'' . $linhaCode . '\'';
                try {
                    $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
                } catch (Throwable) {
                    break;
                }
                if ($rows !== []) {
                    $linha = $linhaCode;
                    break;
                }
                usleep(150000);
            }
            $this->sapUdfLinhaByItem[$key] = $linha;
        }
    }

    private function normalizeSapUpdateDate(mixed $value): ?string
    {
        $raw = $this->sapEmptyAsString($value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $matches) === 1) {
            return $matches[1];
        }

        $ts = strtotime($raw);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function ensurePharmaFormFallbackMap(InvPharmaFormsRepository $repo): void
    {
        $map = self::SAP_PHARMA_FORM_FALLBACK;
        foreach ($map as $description) {
            $repo->findOrCreateByName($description);
        }
        $this->sapPharmaFormMap = $map;
    }

    /**
     * @param list<string>|null $restrictGroupCodes
     */
    private function estimateSapCatalogCount(SapReportApiService $sap, ?array $restrictGroupCodes = null): int
    {
        try {
            $groupFilter = '';
            if ($restrictGroupCodes !== null && $restrictGroupCodes !== []) {
                $quoted = array_map(
                    static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'",
                    $restrictGroupCodes
                );
                $groupFilter = ' AND T0."ItmsGrpCod" IN (' . implode(', ', $quoted) . ')';
            }

            $sql = 'SELECT COUNT(*) AS "cnt" FROM OITM T0 WHERE 1=1'
                . $this->sapAllowedGroupsWhereSql('T0') . $groupFilter;
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 3);
            if ($rows === []) {
                return 0;
            }

            $row = $rows[0];

            return (int)($row['cnt'] ?? $row['CNT'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Catálogo leve (sem UDF) — estável na API SAP.
     *
     * @param list<string>|null $restrictGroupCodes
     * @return list<array<string, mixed>>
     */
    private function fetchOitmLightKeysetBatch(
        SapReportApiService $sap,
        string $afterItemCode,
        int $limit,
        ?array $restrictGroupCodes = null
    ): array {
        $limit = max(1, min(100, $limit));
        $afterClause = '';
        if ($afterItemCode !== '') {
            $afterClause = ' AND T0."ItemCode" > \'' . str_replace("'", "''", $afterItemCode) . '\'';
        }

        $groupFilter = '';
        if ($restrictGroupCodes !== null && $restrictGroupCodes !== []) {
            $quoted = array_map(
                static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'",
                $restrictGroupCodes
            );
            $groupFilter = ' AND T0."ItmsGrpCod" IN (' . implode(', ', $quoted) . ')';
        }

        $sql = 'SELECT T0."ItemCode", T0."ItemName", T0."InvntryUom", T0."validFor", T0."ItmsGrpCod", '
            . 'T0."AvgPrice", ' . $this->sapOitmUpdateDateSelectExpression('T0') . ' '
            . 'FROM OITM T0 '
            . 'WHERE 1=1' . $this->sapAllowedGroupsWhereSql('T0') . $groupFilter . $afterClause . ' '
            . 'ORDER BY T0."ItemCode" '
            . 'LIMIT ' . $limit;

        return $this->executeSapQueryWithRetry($sap, $sql, 3);
    }

    /**
     * Catálogo completo (UDF + BEAS) em lote — query principal da sync.
     *
     * @param list<string>|null $restrictGroupCodes
     * @return list<array<string, mixed>>
     */
    private function fetchOitmRichKeysetBatch(
        SapReportApiService $sap,
        string $afterItemCode,
        int $limit,
        ?array $restrictGroupCodes = null,
        ?string $incrementalSinceDate = null
    ): array {
        $limit = max(1, min(500, $limit));
        $afterClause = '';
        if ($afterItemCode !== '') {
            $afterClause = ' AND T0."ItemCode" > \'' . str_replace("'", "''", $afterItemCode) . '\'';
        }

        $groupFilter = '';
        if ($restrictGroupCodes !== null && $restrictGroupCodes !== []) {
            $quoted = array_map(
                static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'",
                $restrictGroupCodes
            );
            $groupFilter = ' AND T0."ItmsGrpCod" IN (' . implode(', ', $quoted) . ')';
        }

        $dateFilter = '';
        if ($incrementalSinceDate !== null && $incrementalSinceDate !== '') {
            $dateFilter = $this->sapIncrementalUpdateDateFilterSql('T0', $incrementalSinceDate);
        }

        $sql = 'SELECT ' . $this->sapOitmCatalogSelectColumns('T0') . ' '
            . 'FROM OITM T0 '
            . 'WHERE 1=1' . $this->sapAllowedGroupsWhereSql('T0') . $groupFilter . $dateFilter . $afterClause . ' '
            . 'ORDER BY T0."ItemCode" '
            . 'LIMIT ' . $limit;

        return $this->executeSapQueryWithRetry($sap, $sql, 3);
    }

    /** @deprecated Usar fetchOitmRichKeysetBatch */
    private function fetchOitmKeysetBatch(SapReportApiService $sap, string $afterItemCode, int $limit): array
    {
        return $this->fetchOitmLightKeysetBatch($sap, $afterItemCode, $limit);
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $payload
     */
    private function itemPayloadDiffersFromExisting(array $existing, array $payload): bool
    {
        $compare = static function (mixed $a, mixed $b): bool {
            if (is_numeric($a) || is_numeric($b)) {
                return round((float)$a, 6) !== round((float)$b, 6);
            }

            return trim((string)$a) !== trim((string)$b);
        };

        foreach (['description', 'inv_unit_id', 'inv_category_id', 'average_cost', 'last_cost', 'active', 'production_line', 'inv_pharma_form_id', 'sap_update_date', 'admin_type', 'min_stock', 'max_stock', 'standard_batch_size'] as $field) {
            if ($compare($existing[$field] ?? null, $payload[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function sapAllowedGroupsWhereSql(string $tableAlias = 'T0'): string
    {
        $codes = $this->allowedSapGroupCodes ?? [];
        if ($codes === []) {
            return '';
        }

        $quoted = array_map(
            static fn(string $code): string => "'" . str_replace("'", "''", $code) . "'",
            $codes
        );

        return ' AND ' . $tableAlias . '."ItmsGrpCod" IN (' . implode(', ', $quoted) . ')';
    }

    /**
     * Resolve OITB.ItmsGrpCod a partir do prefixo numérico do nome (ex.: "400 - PROD ACABADO" → 104).
     */
    private function resolveAllowedSapGroupCodes(SapReportApiService $sap): void
    {
        if ($this->allowedSapGroupCodes !== null) {
            return;
        }

        $allowedPrefixes = array_flip(self::SAP_ALLOWED_ITEM_GROUP_PREFIXES);
        $codes = [];

        foreach ($this->executeSapQueryWithRetry($sap, 'SELECT "ItmsGrpCod", "ItmsGrpNam" FROM OITB') as $row) {
            $cod = trim((string)($row['ItmsGrpCod'] ?? $row['ITMSGRPCOD'] ?? ''));
            $name = trim((string)($row['ItmsGrpNam'] ?? $row['ITMSGRPNAM'] ?? ''));
            if ($cod === '' || $name === '') {
                continue;
            }

            if (preg_match('/^(\d+)/', $name, $matches) !== 1) {
                continue;
            }

            if (isset($allowedPrefixes[$matches[1]])) {
                $codes[] = $cod;
            }
        }

        $this->allowedSapGroupCodes = array_values(array_unique($codes));
    }

    private function syncPharmaFormsCatalogFromSap(SapReportApiService $sap, InvPharmaFormsRepository $repo): void
    {
        $map = $this->fetchSapPharmaFormMap($sap);
        foreach ($map as $description) {
            $repo->findOrCreateByName($description);
        }
    }

    /**
     * @return array<string, string> código U_FormaFarma → descrição
     */
    private function fetchSapPharmaFormMap(SapReportApiService $sap): array
    {
        if ($this->sapPharmaFormMap !== null) {
            return $this->sapPharmaFormMap;
        }

        $map = [];
        foreach (self::SAP_PHARMA_FORM_FALLBACK as $code => $fallbackDescription) {
            $description = $this->fetchSapPharmaFormDescription($sap, (string)$code) ?? $fallbackDescription;
            $map[(string)$code] = $description;
            usleep(200000);
        }

        $this->sapPharmaFormMap = $map;

        return $map;
    }

    private function fetchSapPharmaFormDescription(SapReportApiService $sap, string $code): ?string
    {
        $safeCode = str_replace("'", "''", $code);
        $sql = 'SELECT "Descr" FROM UFD1 WHERE "TableID" = \'OITM\' '
            . 'AND "FieldID" = ' . self::SAP_FORMA_FIELD_ID . ' '
            . 'AND "FldValue" = \'' . $safeCode . '\'';

        try {
            $rows = $this->executeSapQueryWithRetry($sap, $sql, 2);
        } catch (Throwable) {
            return null;
        }

        $description = trim((string)($rows[0]['Descr'] ?? $rows[0]['DESCR'] ?? ''));
        if ($description === '') {
            return null;
        }

        return $description;
    }

    private function resolvePharmaFormId(InvPharmaFormsRepository $repo, mixed $rawCode): ?int
    {
        $code = $this->sapEmptyAsString($rawCode);
        if ($code === '') {
            return null;
        }

        $map = $this->sapPharmaFormMap ?? [];
        $name = $map[$code] ?? null;
        if ($name === null || $name === '') {
            return null;
        }

        return $repo->findOrCreateByName($name);
    }

    /**
     * Códigos ERP presentes no SAP nos grupos permitidos (consulta leve, só ItemCode).
     *
     * @return array<string, true>
     */
    private function fetchAllowedSapItemCodeSet(SapReportApiService $sap): array
    {
        $codes = [];
        $batchSize = 100;
        $lastCode = '';

        for ($batch = 0; $batch < 200; $batch++) {
            $afterClause = '';
            if ($lastCode !== '') {
                $afterClause = ' AND T0."ItemCode" > \'' . str_replace("'", "''", $lastCode) . '\'';
            }

            $sql = 'SELECT T0."ItemCode" FROM OITM T0 '
                . 'WHERE 1=1' . $this->sapAllowedGroupsWhereSql('T0') . $afterClause . ' '
                . 'ORDER BY T0."ItemCode" '
                . 'LIMIT ' . $batchSize;

            $rows = $this->executeSapQueryWithRetry($sap, $sql, 5);
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $code = trim((string)($row['ItemCode'] ?? $row['ITEMCODE'] ?? ''));
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }

            $lastRow = $rows[count($rows) - 1];
            $lastCode = trim((string)($lastRow['ItemCode'] ?? $lastRow['ITEMCODE'] ?? $lastCode));
            if (count($rows) < $batchSize) {
                break;
            }

            usleep(500000);
        }

        return $codes;
    }

    /**
     * Remove itens locais com código ERP que não pertencem aos grupos SAP permitidos.
     *
     * @param array<string, true> $allowedErpCodes
     * @return array{removed: int, skipped: int}
     */
    private function purgeLocalSapItemsOutsideAllowedCatalog(InvItemsRepository $itemsRepo, array $allowedErpCodes): array
    {
        if ($allowedErpCodes === []) {
            return ['removed' => 0, 'skipped' => 0];
        }

        $removed = 0;
        $skipped = 0;

        foreach ($itemsRepo->getSapLinkedItemsForPurge() as $item) {
            if (InvCostProjectHelper::isProjectItem($item)) {
                continue;
            }

            $erpCode = trim((string)($item['erp_code'] ?? ''));
            if ($erpCode === '' || isset($allowedErpCodes[$erpCode])) {
                continue;
            }

            if ($itemsRepo->delete((int)$item['id'])) {
                $removed++;
                continue;
            }

            $skipped++;
        }

        return ['removed' => $removed, 'skipped' => $skipped];
    }
    private function sapWarehouseCaseExpression(string $defaultWhColumn): string
    {
        return 'CASE '
            . 'WHEN ' . $defaultWhColumn . " = 'TJQP' THEN 'TJQR' "
            . 'WHEN ' . $defaultWhColumn . " = 'APQP' THEN 'APQR' "
            . 'ELSE ' . $defaultWhColumn . ' '
            . 'END';
    }

    private function sapLastCostJoinSql(string $itemCodeColumn): string
    {
        return 'LEFT JOIN ('
            . 'SELECT X."ItemCode", X."LastCost" '
            . 'FROM ('
            . 'SELECT N."ItemCode", '
            . 'CASE WHEN N."InQty" <> 0 THEN N."TransValue" / N."InQty" END AS "LastCost", '
            . 'ROW_NUMBER() OVER (PARTITION BY N."ItemCode" ORDER BY N."DocDate" DESC, N."TransNum" DESC) AS "RN" '
            . 'FROM OINM N '
            . 'WHERE N."InQty" > 0'
            . ') X '
            . 'WHERE X."RN" = 1'
            . ') LC ON LC."ItemCode" = ' . $itemCodeColumn . ' ';
    }

    private function sapOitwJoinSql(string $itemCodeColumn, string $defaultWhColumn): string
    {
        $warehouseCase = $this->sapWarehouseCaseExpression($defaultWhColumn);

        return 'LEFT JOIN OITW W '
            . 'ON W."ItemCode" = ' . $itemCodeColumn . ' '
            . 'AND W."WhsCode" = ' . $warehouseCase . ' ';
    }

    private function sapCostJoinsSql(string $itemCodeColumn, string $defaultWhColumn): string
    {
        return $this->sapOitwJoinSql($itemCodeColumn, $defaultWhColumn) . ' '
            . $this->sapLastCostJoinSql($itemCodeColumn);
    }

    /**
     * @param string $itemCodeColumn ex.: S."ART1_ID"
     * @param string $fallbackAvgPriceColumn ex.: T2."AvgPrice"
     */
    private function sapCostSelectExpression(
        string $itemCodeColumn,
        string $defaultWhColumn,
        string $fallbackAvgPriceColumn = ''
    ): string {
        $fallback = $fallbackAvgPriceColumn !== ''
            ? ", {$fallbackAvgPriceColumn}"
            : '';

        return 'TO_DECIMAL(COALESCE(LC."LastCost", W."AvgPrice"' . $fallback . '), 19, 4)';
    }

    /**
     * Busca estrutura em duas consultas simples (BOM + rota), evitando CAST/UNION no HANA.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchStructureRowsFromSap(SapReportApiService $sap, string $parentErpCode): array
    {
        $materialRows = $this->executeSapQueryWithRetry($sap, $this->getSapMaterialsQueryByParentErpCode($parentErpCode), 5);
        $routeRows = $this->executeSapQueryWithRetry($sap, $this->getSapRouteQueryByParentErpCode($parentErpCode), 5);

        $rows = [];
        foreach ($materialRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['tipo'] = 'MATERIAIS';
            $rows[] = $row;
        }
        foreach ($routeRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['tipo'] = 'ROTA';
            $rows[] = $row;
        }

        return $this->sortStructureRows($rows);
    }

    private function sapBeasVersionMatchSql(string $itemAlias = 'I'): string
    {
        return ' AND V."Version" = COALESCE(NULLIF(TRIM(' . $itemAlias . '."U_beas_ver"), \'\'), ('
            . 'SELECT MAX(VX."Version") FROM BEAS_ITEM_VERSION VX WHERE VX."ItemCode" = ' . $itemAlias . '."ItemCode"'
            . ')) ';
    }

    private function getSapMaterialsQueryByParentErpCode(string $parentErpCode, bool $includeLastCost = false): string
    {
        $safeCode = str_replace("'", "''", trim($parentErpCode));
        if ($includeLastCost) {
            $costSelect = $this->sapCostSelectExpression('S."ART1_ID"', 'I."DfltWH"', 'T2."AvgPrice"');
            $costJoins = $this->sapCostJoinsSql('S."ART1_ID"', 'I."DfltWH"');
        } else {
            $costSelect = 'TO_DECIMAL(COALESCE(NULLIF(W."AvgPrice", 0), NULLIF(T2."AvgPrice", 0), 0), 19, 4)';
            $costJoins = $this->sapOitwJoinSql('S."ART1_ID"', 'I."DfltWH"');
        }

        return 'SELECT '
            . 'S."POS_ID" AS "pos_id", '
            . 'S."ART1_ID" AS "codigo", '
            . 'S."DESCRIPTION" AS "descricao", '
            . 'S."INPUT_QTY" AS "quantidade", '
            . 'S."MENGE_VERBRAUCH" AS "menge_verbrauch", '
            . 'S."INPUT_UNIT" AS "unidade_medida", '
            . 'T2."ItemName" AS "component_item_name", '
            . 'T2."InvntryUom" AS "component_uom", '
            . $costSelect . ' AS "component_avg_price", '
            . 'T3."ItmsGrpNam" AS "component_group_name" '
            . 'FROM BEAS_STL S '
            . 'INNER JOIN BEAS_ITEM_VERSION V ON S."ItemCode" = V."StlItemCode" '
            . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
            . $this->sapBeasVersionMatchSql('I')
            . "AND I.\"ItemCode\" = '{$safeCode}' "
            . 'LEFT JOIN OITM T2 ON T2."ItemCode" = S."ART1_ID" '
            . 'LEFT JOIN OITB T3 ON T3."ItmsGrpCod" = T2."ItmsGrpCod" '
            . $costJoins
            . ' WHERE UPPER(S."DESCRIPTION") NOT LIKE \'%GERADOR DE LOTE%\'';
    }

    private function getSapRouteQueryByParentErpCode(string $parentErpCode): string
    {
        $safeCode = str_replace("'", "''", trim($parentErpCode));

        return 'SELECT '
            . 'A."POS_ID" AS "pos_id", '
            . 'A."AG_ID" AS "codigo", '
            . 'A."BEZ" AS "descricao", '
            . 'A."APLATZ_ID" AS "recurso", '
            . 'A."THAPLATZ" AS "tempo_th", '
            . 'A."TNAPLATZ" AS "tempo_tn", '
            . 'A."TEAPLATZ" AS "tempo_te" '
            . 'FROM BEAS_APL A '
            . 'INNER JOIN BEAS_ITEM_VERSION V ON A."ItemCode" = V."RoutingId" '
            . 'INNER JOIN OITM I ON V."ItemCode" = I."ItemCode" '
            . $this->sapBeasVersionMatchSql('I')
            . "AND I.\"ItemCode\" = '{$safeCode}'";
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveRouteTimeMinutes(array $row): float
    {
        $total = $this->toFloat($row['tempo'] ?? $row['TEMPO'] ?? 0);
        if ($total > 0) {
            return $total;
        }

        return $this->toFloat($row['tempo_th'] ?? $row['THAPLATZ'] ?? 0)
            + $this->toFloat($row['tempo_tn'] ?? $row['TNAPLATZ'] ?? 0)
            + $this->toFloat($row['tempo_te'] ?? $row['TEAPLATZ'] ?? 0);
    }

    /**
     * @param array<string, mixed> $response
     * @return list<array<string, mixed>>
     */
    private function extractSapRows(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return array_values($response['data']);
        }

        if (isset($response[0]) && is_array($response[0])) {
            return array_values($response);
        }

        return [];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function sortStructureRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $tipoA = strtoupper(trim((string)($a['tipo'] ?? $a['TIPO'] ?? '')));
            $tipoB = strtoupper(trim((string)($b['tipo'] ?? $b['TIPO'] ?? '')));
            $orderA = $tipoA === 'MATERIAIS' ? 1 : 2;
            $orderB = $tipoB === 'MATERIAIS' ? 1 : 2;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            $posA = (int)preg_replace('/\D+/', '', (string)($a['pos_id'] ?? $a['POS_ID'] ?? '0'));
            $posB = (int)preg_replace('/\D+/', '', (string)($b['pos_id'] ?? $b['POS_ID'] ?? '0'));

            return $posA <=> $posB;
        });

        return $rows;
    }

    private function createLocalItemFromSapMaterialRow(
        array $row,
        InvItemsRepository $itemsRepo,
        InvCategoriesRepository $categoriesRepo,
        InvUnitsRepository $unitsRepo,
        int $defaultUnitId
    ): int {
        $erpCode = trim((string)($row['codigo'] ?? $row['CODIGO'] ?? ''));
        if ($erpCode === '') {
            return 0;
        }

        $existing = $itemsRepo->findByErpCode($erpCode);
        if ($existing !== null) {
            return (int)($existing['id'] ?? 0);
        }

        $description = trim((string)($row['component_item_name'] ?? $row['COMPONENT_ITEM_NAME'] ?? $erpCode));
        $categoryName = $this->normalizeCategoryName((string)($row['component_group_name'] ?? $row['COMPONENT_GROUP_NAME'] ?? 'Geral'));
        $categoryId = $categoriesRepo->findOrCreateByName($categoryName);
        if ($categoryId === null) {
            return 0;
        }

        $uomCode = strtoupper(trim((string)($row['unidade_medida'] ?? $row['UNIDADE_MEDIDA'] ?? $row['component_uom'] ?? $row['COMPONENT_UOM'] ?? '')));
        $unitId = $unitsRepo->findIdByCode($uomCode) ?? $defaultUnitId;
        if ($unitId <= 0) {
            return 0;
        }

        $sapCost = $this->extractComponentCostFromRow($row);
        $created = $itemsRepo->create([
            'code' => $erpCode,
            'erp_code' => $erpCode,
            'description' => $description,
            'inv_unit_id' => $unitId,
            'inv_category_id' => $categoryId,
            'admin_type' => 'none',
            'average_cost' => $sapCost,
            'last_cost' => $sapCost,
            'min_stock' => 0,
            'max_stock' => 0,
            'active' => 1,
        ]);

        return is_int($created) && $created > 0 ? $created : 0;
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float)$value, 6);
        }

        $text = trim((string)$value);
        if ($text === '') {
            return 0.0;
        }

        // Suporta formatos com separador decimal em vírgula ou ponto
        // e remove separadores de milhar preservando até 6 casas decimais.
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');
            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    // 1.234,567890 -> 1234.567890
                    $text = str_replace('.', '', $text);
                    $text = str_replace(',', '.', $text);
                } else {
                    // 1,234.567890 -> 1234.567890
                    $text = str_replace(',', '', $text);
                }
            }
        } elseif (str_contains($text, ',')) {
            // 1234,567890 -> 1234.567890
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        if (!is_numeric($text)) {
            return 0.0;
        }

        return round((float)$text, 6);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractComponentCostFromRow(array $row): float
    {
        $cost = $this->toFloat(
            $row['component_avg_price']
                ?? $row['COMPONENT_AVG_PRICE']
                ?? $row['Custo Médio']
                ?? $row['Custo Medio']
                ?? 0
        );

        return $cost > 0 ? $cost : 0.0;
    }

    private function extractSapCost(array $row): float
    {
        $cost = $this->toFloat(
            $row['AvgPrice']
                ?? $row['AVGPRICE']
                ?? $row['Custo Médio']
                ?? $row['Custo Medio']
                ?? 0
        );

        return $cost > 0 ? $cost : 0.0;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function updateItemCostFromSap(InvItemsRepository $itemsRepo, array $item, float $sapCost): void
    {
        $itemId = (int)($item['id'] ?? 0);
        if ($itemId <= 0 || $sapCost <= 0) {
            return;
        }

        $currentCost = (float)($item['average_cost'] ?? 0);
        $categoryName = (string)($item['category_name'] ?? '');
        $itemType = $this->inferItemTypeFromCategory($categoryName);
        $shouldUpdate = $currentCost <= 0 || in_array($itemType, ['MP', 'EMB'], true);
        if (!$shouldUpdate) {
            return;
        }

        $itemsRepo->update($itemId, [
            'code' => (string)($item['code'] ?? ''),
            'erp_code' => $item['erp_code'] ?? null,
            'description' => (string)($item['description'] ?? ''),
            'inv_unit_id' => (int)($item['inv_unit_id'] ?? 0),
            'inv_category_id' => $item['inv_category_id'] ?? null,
            'admin_type' => (string)($item['admin_type'] ?? 'none'),
            'average_cost' => $sapCost,
            'last_cost' => $sapCost,
            'min_stock' => (float)($item['min_stock'] ?? 0),
            'max_stock' => (float)($item['max_stock'] ?? 0),
            'active' => (int)($item['active'] ?? 1),
        ]);
    }

    private function normalizeCategoryName(string $sapGroupName): string
    {
        $name = trim($sapGroupName);
        if ($name === '') {
            return 'Geral';
        }
        return mb_strtoupper($name, 'UTF-8');
    }

    private function finalizeStructureRunIfNeeded(bool $continueTracking, bool $success, string $message, array $finalizeData): void
    {
        if ($continueTracking) {
            return;
        }

        $this->finalizeTrackedSync($success, $message, $finalizeData);
    }

    private function resolveIncrementalSinceDate(): ?string
    {
        $finishedAt = (new InvInventorySapSyncRunsRepository())->getLastSuccessfulItemsSyncDate();
        if ($finishedAt === null) {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $finishedAt, $matches) === 1) {
            return $matches[1];
        }

        $ts = strtotime($finishedAt);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function computeItemHashFromSapRow(array $row): string
    {
        $payload = [
            'ItemName' => $this->sapEmptyAsString($row['ItemName'] ?? null),
            'InvntryUom' => strtoupper($this->sapEmptyAsString($row['InvntryUom'] ?? null)),
            'validFor' => strtoupper($this->sapEmptyAsString($row['validFor'] ?? null, 'Y')),
            'ItmsGrpCod' => $this->sapEmptyAsString($row['ItmsGrpCod'] ?? null),
            'AvgPrice' => round((float)($row['AvgPrice'] ?? 0), 6),
            'U_FormaFarma' => $this->sapEmptyAsString($row['U_FormaFarma'] ?? null),
            'U_LinhaProduto' => $this->sapEmptyAsString($row['U_LinhaProduto'] ?? null),
            'U_beas_ver' => $this->sapEmptyAsString($row['U_beas_ver'] ?? null),
            'MinOrdrQty' => round((float)($this->sapRowNumericOrNull($row, 'MinOrdrQty') ?? 0), 6),
            'MinLevel' => round((float)($this->sapRowNumericOrNull($row, 'MinLevel') ?? 0), 6),
            'MaxLevel' => round((float)($this->sapRowNumericOrNull($row, 'MaxLevel') ?? 0), 6),
            'ManBtchNum' => strtoupper($this->sapEmptyAsString($row['ManBtchNum'] ?? null)),
            'ManSerNum' => strtoupper($this->sapEmptyAsString($row['ManSerNum'] ?? null)),
        ];

        return $this->encodeHashPayload($payload);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function computeBomHashFromSapRows(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = [
                'pos_id' => trim((string)($row['pos_id'] ?? $row['POS_ID'] ?? '')),
                'codigo' => trim((string)($row['codigo'] ?? $row['CODIGO'] ?? '')),
                'descricao' => trim((string)($row['descricao'] ?? $row['DESCRIPTION'] ?? '')),
                'quantidade' => round($this->toFloat($row['quantidade'] ?? $row['INPUT_QTY'] ?? 0), 6),
                'menge_verbrauch' => round($this->toFloat($row['menge_verbrauch'] ?? $row['MENGE_VERBRAUCH'] ?? 0), 6),
                'unidade_medida' => trim((string)($row['unidade_medida'] ?? $row['INPUT_UNIT'] ?? '')),
            ];
        }

        usort($lines, static function (array $a, array $b): int {
            $pos = strcmp((string)$a['pos_id'], (string)$b['pos_id']);
            if ($pos !== 0) {
                return $pos;
            }

            return strcmp((string)$a['codigo'], (string)$b['codigo']);
        });

        return $this->encodeHashPayload($lines);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function computeRouteHashFromSapRows(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = [
                'pos_id' => trim((string)($row['pos_id'] ?? $row['POS_ID'] ?? '')),
                'codigo' => trim((string)($row['codigo'] ?? $row['AG_ID'] ?? '')),
                'descricao' => trim((string)($row['descricao'] ?? $row['BEZ'] ?? '')),
                'recurso' => trim((string)($row['recurso'] ?? $row['APLATZ_ID'] ?? '')),
                'tempo_th' => round($this->toFloat($row['tempo_th'] ?? $row['THAPLATZ'] ?? 0), 6),
                'tempo_tn' => round($this->toFloat($row['tempo_tn'] ?? $row['TNAPLATZ'] ?? 0), 6),
                'tempo_te' => round($this->toFloat($row['tempo_te'] ?? $row['TEAPLATZ'] ?? 0), 6),
            ];
        }

        usort($lines, static function (array $a, array $b): int {
            $pos = strcmp((string)$a['pos_id'], (string)$b['pos_id']);
            if ($pos !== 0) {
                return $pos;
            }

            return strcmp((string)$a['codigo'], (string)$b['codigo']);
        });

        return $this->encodeHashPayload($lines);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function partitionStructureRowsByTipo(array $rows): array
    {
        $materials = [];
        $routes = [];
        foreach ($rows as $row) {
            $tipo = strtoupper(trim((string)($row['tipo'] ?? $row['TIPO'] ?? '')));
            if ($tipo === 'MATERIAIS') {
                $materials[] = $row;
            } elseif ($tipo === 'ROTA') {
                $routes[] = $row;
            }
        }

        return [$materials, $routes];
    }

    /**
     * Hash igual ao SAP, mas ainda pode faltar forma/linha ou metadados de estrutura.
     *
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $row
     * @param array<string, mixed> $stats
     */
    private function reconcileUnchangedSapItem(
        InvItemsRepository $itemsRepo,
        array $existing,
        array $row,
        string $itemHash,
        bool $isPaPi,
        ?int $pharmaFormId,
        ?string $productionLine,
        int $active,
        ?string $sapUpdateDate,
        int $unitId,
        int $categoryId,
        string $adminType,
        ?float $minLevel,
        ?float $maxLevel,
        ?float $minOrderQty,
        array &$stats
    ): bool {
        $payload = [
            'code' => (string)($existing['code'] ?? $row['ItemCode'] ?? ''),
            'erp_code' => (string)($existing['erp_code'] ?? $row['ItemCode'] ?? ''),
            'description' => trim((string)($row['ItemName'] ?? $existing['description'] ?? '')),
            'inv_unit_id' => $unitId,
            'inv_category_id' => $categoryId,
            'admin_type' => $adminType,
            'average_cost' => (float)($existing['average_cost'] ?? 0),
            'last_cost' => (float)($existing['last_cost'] ?? 0),
            'min_stock' => $minLevel ?? (float)($existing['min_stock'] ?? 0),
            'max_stock' => $maxLevel ?? (float)($existing['max_stock'] ?? 0),
            'standard_batch_size' => $minOrderQty ?? (float)($existing['standard_batch_size'] ?? 1),
            'production_line' => $isPaPi ? $productionLine : ($existing['production_line'] ?? null),
            'inv_pharma_form_id' => $isPaPi ? $pharmaFormId : ($existing['inv_pharma_form_id'] ?? null),
            'sap_update_date' => $sapUpdateDate,
            'active' => $active,
        ];

        $changed = false;
        if ($this->itemPayloadDiffersFromExisting($existing, $payload)) {
            $changed = $itemsRepo->update((int)$existing['id'], $payload);
            if ($changed) {
                $stats['updated']++;
                $stats['synced']++;
            }
        } else {
            $stats['unchanged']++;
        }

        $this->persistItemSapMetadata($itemsRepo, (int)$existing['id'], $row, $existing, $itemHash, $isPaPi);

        return $changed;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $existing
     */
    private function persistItemSapMetadata(
        InvItemsRepository $itemsRepo,
        int $itemId,
        array $row,
        ?array $existing,
        string $itemHash,
        bool $isPaPi
    ): void {
        $beasVer = $this->sapEmptyAsString($row['U_beas_ver'] ?? $row['U_BEAS_VER'] ?? null);
        $queueStructure = $this->shouldQueueStructureSync($row, $existing, $itemHash, $isPaPi);
        $itemsRepo->updateSapSyncMetadata($itemId, [
            'sap_item_hash' => $itemHash,
            'sap_beas_version' => $beasVer !== '' ? $beasVer : null,
            'sap_structure_pending' => $queueStructure,
            'sap_route_pending' => $queueStructure,
            'sap_last_synced_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function persistStructureSapMetadata(
        InvItemsRepository $itemsRepo,
        int $itemId,
        string $bomHash,
        string $routeHash
    ): void {
        $itemsRepo->updateSapSyncMetadata($itemId, [
            'sap_bom_hash' => $bomHash !== '' ? $bomHash : null,
            'sap_route_hash' => $routeHash !== '' ? $routeHash : null,
            'sap_structure_pending' => false,
            'sap_route_pending' => false,
            'sap_last_synced_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $existing
     */
    private function shouldQueueStructureSync(array $row, ?array $existing, string $itemHash, bool $isPaPi): bool
    {
        if (!$this->itemEligibleForStructureQueue($row, $isPaPi)) {
            return false;
        }

        if ($existing === null) {
            return true;
        }

        if (!empty($existing['sap_structure_pending']) || !empty($existing['sap_route_pending'])) {
            return true;
        }

        if (
            trim((string)($existing['sap_bom_hash'] ?? '')) === ''
            && trim((string)($existing['sap_route_hash'] ?? '')) === ''
        ) {
            return true;
        }

        $oldHash = (string)($existing['sap_item_hash'] ?? '');
        if ($oldHash === '' || !hash_equals($oldHash, $itemHash)) {
            return true;
        }

        $newVer = $this->sapEmptyAsString($row['U_beas_ver'] ?? $row['U_BEAS_VER'] ?? null);
        $oldVer = $this->sapEmptyAsString($existing['sap_beas_version'] ?? null);

        return $newVer !== '' && $newVer !== $oldVer;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function itemEligibleForStructureQueue(array $row, bool $isPaPi): bool
    {
        if ($isPaPi) {
            return true;
        }

        $erp = trim((string)($row['ItemCode'] ?? ''));

        return str_starts_with($erp, '43') || str_starts_with($erp, '40');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractSapNumericField(array $row, string $field): ?float
    {
        return $this->sapRowNumericOrNull($row, $field);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $existing
     */
    private function resolveSapAdminType(array $row): string
    {
        $batchFlag = strtoupper($this->sapRowString($row, 'ManBtchNum'));
        if ($batchFlag === 'Y') {
            return 'lot';
        }

        $serialFlag = strtoupper($this->sapRowString($row, 'ManSerNum'));
        if ($serialFlag === 'Y') {
            return 'serial';
        }

        return 'none';
    }

    /**
     * @param array<mixed> $payload
     */
    private function encodeHashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function inferItemTypeFromCategory(string $categoryName): string
    {
        $name = mb_strtoupper($categoryName, 'UTF-8');
        if (str_contains($name, 'MATER') || str_contains($name, 'MATÉRIA')) {
            return 'MP';
        }
        if (str_contains($name, 'EMBAL')) {
            return 'EMB';
        }
        if (str_contains($name, 'INTERMED')) {
            return 'PI';
        }
        if (str_contains($name, 'ACABADO') || str_contains($name, 'PROD')) {
            return 'PA';
        }
        return 'OTHER';
    }
}


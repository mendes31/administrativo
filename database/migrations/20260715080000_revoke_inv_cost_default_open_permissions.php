<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Páginas de custeio (Estoque) foram registradas com default_page = 1 e/ou
 * grants que copiaram permission=1 para todos os níveis.
 *
 * Esta migration:
 * - define default_page = 0 (não nascem mais “padrão” na matriz);
 * - zera permission em todos os níveis (liberação passa a ser manual);
 * - Super Administrador continua com acesso total via hasFullSystemAccess().
 */
final class RevokeInvCostDefaultOpenPermissions extends AbstractMigration
{
    /** Controllers do módulo de custeio / simulação de custo */
    private const CONTROLLERS = [
        'SaveInventoryCostSimulation',
        'ExportInventoryCostSimulationPdf',
        'ListInvCostProductionBatches',
        'ListInvCostPeriods',
        'CreateInvCostPeriod',
        'ViewInvCostPeriod',
        'UpdateInvCostPeriod',
        'DeleteInvCostPeriod',
        'ImportInvCostDre',
        'SaveInvCostAllocationRules',
        'DownloadInvCostDreTemplate',
        'SaveInvCostPeriodScenarioProduction',
        'ImportInvCostRhDistribution',
        'SaveInvCostRhDistribution',
        'DownloadInvCostRhDistributionTemplate',
        'SimulateInventoryCost',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $inList = $this->controllersInList();

        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0,
                 public_page = 0,
                 updated_at = '{$now}'
             WHERE controller IN ({$inList})"
        );

        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute(
                "UPDATE adms_access_levels_pages alp
                 INNER JOIN adms_pages p ON p.id = alp.adms_page_id
                 SET alp.permission = 0,
                     alp.updated_at = '{$now}'
                 WHERE p.controller IN ({$inList})"
            );
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        // Não restaura grants em massa (evita reabrir o módulo para todos os níveis).
    }

    private function controllersInList(): string
    {
        $quoted = array_map(
            static fn (string $c): string => "'" . str_replace("'", "''", $c) . "'",
            self::CONTROLLERS
        );

        return implode(', ', $quoted);
    }
}

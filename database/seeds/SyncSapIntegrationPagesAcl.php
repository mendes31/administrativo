<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Alinha a matriz `adms_access_levels_pages` das páginas «API SAP (integração)» às permissões
 * por nível já definidas para as páginas de configuração SAP API (lista / gravar / testar).
 *
 * Deve executar após {@see SyncAccessLevelsPages} (fluxo em {@see AAADatabaseSeeder}).
 * Reexecutar a seed reaplica o espelhamento (útil após novos níveis de acesso).
 */
class SyncSapIntegrationPagesAcl extends AbstractSeed
{
    /** @var array<string, string> controller destino => controller de referência */
    private const ACL_MAP = [
        'SapServiceLayerConnections' => 'SapApiConfig',
        'SaveSapServiceLayerConnection' => 'SaveSapApiConfig',
        'DeleteSapServiceLayerConnection' => 'SaveSapApiConfig',
        'TestSapServiceLayerConnection' => 'TestSapApiConfig',
    ];

    public function getDependencies(): array
    {
        return [
            'AddAdmsPages',
            'SyncAccessLevelsPages',
        ];
    }

    public function run(): void
    {
        if (!$this->hasTable('adms_access_levels_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        foreach (self::ACL_MAP as $targetController => $refController) {
            $te = $this->esc($targetController);
            $re = $this->esc($refController);
            $this->execute(
                "UPDATE adms_access_levels_pages AS t
                 INNER JOIN adms_pages AS pt ON pt.id = t.adms_page_id AND pt.controller = '{$te}'
                 INNER JOIN adms_access_levels_pages AS r
                   ON r.adms_access_level_id = t.adms_access_level_id
                 INNER JOIN adms_pages AS pr ON pr.id = r.adms_page_id AND pr.controller = '{$re}'
                 SET t.permission = r.permission, t.updated_at = NOW()"
            );
        }

        echo "✅ SyncSapIntegrationPagesAcl: permissões por nível espelhadas a partir da SAP API.\n";
    }

    private function esc(string $s): string
    {
        return str_replace("'", "''", $s);
    }
}

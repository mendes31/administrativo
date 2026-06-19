<?php

declare(strict_types=1);

use App\adms\Helpers\SstExameResultadoHelper;
use Phinx\Migration\AbstractMigration;

/**
 * Remove conclusões de ASO (Apto/Inapto) do catálogo de resultados de exames complementares.
 */
final class SstExamesStripAsoConclusoesResultados extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_exames')) {
            return;
        }

        $rows = $this->fetchAll('SELECT id, resultados_permitidos FROM adms_sst_exames WHERE resultados_permitidos IS NOT NULL AND resultados_permitidos <> \'\'');
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $json = (string) ($row['resultados_permitidos'] ?? '');
            $clean = SstExameResultadoHelper::encodeCatalog(
                SstExameResultadoHelper::decodeCatalog($json)
            );
            if ($clean === $json || ($clean === null && $json === 'null')) {
                continue;
            }
            $this->execute(
                'UPDATE adms_sst_exames SET resultados_permitidos = '
                . ($clean === null ? 'NULL' : $this->getAdapter()->getConnection()->quote($clean))
                . ', updated_at = NOW() WHERE id = ' . $id
            );
        }
    }

    public function down(): void
    {
        // Não restaura valores removidos.
    }
}

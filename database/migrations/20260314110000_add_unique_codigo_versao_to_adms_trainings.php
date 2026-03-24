<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueCodigoVersaoToAdmsTrainings extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        // Guardrail: não aplicar unique se ainda houver duplicidade.
        $duplicates = $this->fetchAll(
            "SELECT TRIM(codigo) AS codigo_norm,
                    COALESCE(TRIM(versao), '') AS versao_norm,
                    COUNT(*) AS qtd
             FROM adms_trainings
             GROUP BY TRIM(codigo), COALESCE(TRIM(versao), '')
             HAVING COUNT(*) > 1
             LIMIT 1"
        );
        if (!empty($duplicates)) {
            throw new \RuntimeException(
                'Não foi possível criar UNIQUE(codigo, versao): ainda existem duplicidades em adms_trainings.'
            );
        }

        $indexes = $this->fetchAll("SHOW INDEX FROM adms_trainings WHERE Key_name = 'uk_adms_trainings_codigo_versao'");
        if (empty($indexes)) {
            $this->table('adms_trainings')
                ->addIndex(['codigo', 'versao'], ['unique' => true, 'name' => 'uk_adms_trainings_codigo_versao'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        $indexes = $this->fetchAll("SHOW INDEX FROM adms_trainings WHERE Key_name = 'uk_adms_trainings_codigo_versao'");
        if (!empty($indexes)) {
            $this->table('adms_trainings')
                ->removeIndexByName('uk_adms_trainings_codigo_versao')
                ->update();
        }
    }
}


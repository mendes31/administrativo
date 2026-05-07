<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * @method bool hasTable(string $tableName)
 * @method object table(string $tableName)
 * @method void execute(string $sql)
 * @method array<string, mixed>|false fetchRow(string $sql)
 */
final class SanitizeTrainingCurrentVersionFlags extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        // Backup de segurança (somente primeira execução).
        if (!$this->hasTable('adms_trainings_backup_before_version_sanitize')) {
            $this->execute(
                'CREATE TABLE adms_trainings_backup_before_version_sanitize AS
                 SELECT * FROM adms_trainings'
            );
        }

        // Tabela de log para auditoria da execução.
        if (!$this->hasTable('adms_training_version_sanitization_log')) {
            $this->table('adms_training_version_sanitization_log')
                ->addColumn('notes', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('total_trainings', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('invalid_families_before', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('invalid_families_after', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }

        $countInvalidFamiliesSql = 'SELECT COUNT(*) AS total_invalid
            FROM (
                SELECT COALESCE(NULLIF(TRIM(training_family_key), ""), TRIM(codigo)) AS familia
                FROM adms_trainings
                GROUP BY COALESCE(NULLIF(TRIM(training_family_key), ""), TRIM(codigo))
                HAVING SUM(CASE WHEN is_current_version = 1 THEN 1 ELSE 0 END) <> 1
            ) x';

        $beforeRow = $this->fetchRow($countInvalidFamiliesSql);
        $invalidBefore = (int)($beforeRow['total_invalid'] ?? 0);

        // Saneamento sem perda de dados: apenas normaliza flag lógica.
        $this->execute('START TRANSACTION');
        try {
            $this->execute('UPDATE adms_trainings SET is_current_version = 0');
            $this->execute(
                'UPDATE adms_trainings t
                 JOIN (
                     SELECT
                         fam.familia,
                         COALESCE(
                             MAX(CASE WHEN fam.ativo = 1 THEN CAST(fam.versao AS UNSIGNED) END),
                             MAX(CAST(fam.versao AS UNSIGNED))
                         ) AS versao_escolhida
                     FROM (
                         SELECT
                             COALESCE(NULLIF(TRIM(training_family_key), ""), TRIM(codigo)) AS familia,
                             versao,
                             ativo
                         FROM adms_trainings
                     ) fam
                     GROUP BY fam.familia
                 ) x
                   ON COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) = x.familia
                  AND CAST(t.versao AS UNSIGNED) = x.versao_escolhida
                 SET t.is_current_version = 1'
            );
            $this->execute('COMMIT');
        } catch (\Throwable $e) {
            $this->execute('ROLLBACK');
            throw $e;
        }

        $afterRow = $this->fetchRow($countInvalidFamiliesSql);
        $invalidAfter = (int)($afterRow['total_invalid'] ?? 0);
        $totalTrainingsRow = $this->fetchRow('SELECT COUNT(*) AS total_trainings FROM adms_trainings');
        $totalTrainings = (int)($totalTrainingsRow['total_trainings'] ?? 0);

        $notes = sprintf('Sanitizacao de is_current_version (antes=%d, depois=%d)', $invalidBefore, $invalidAfter);
        $notesEscaped = str_replace("'", "''", $notes);
        $this->execute(
            "INSERT INTO adms_training_version_sanitization_log
            (notes, total_trainings, invalid_families_before, invalid_families_after, created_at)
            VALUES ('{$notesEscaped}', {$totalTrainings}, {$invalidBefore}, {$invalidAfter}, NOW())"
        );
    }

    public function down(): void
    {
        // Migração de saneamento: rollback automático não é aplicado para evitar
        // reintroduzir inconsistências de versionamento.
    }
}


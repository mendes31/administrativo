<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * @method bool hasTable(string $tableName)
 * @method object table(string $tableName)
 * @method void execute(string $sql)
 * @method array<string, mixed>|false fetchRow(string $sql)
 */
final class BackfillTrainingParentLinks extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        if (!$this->hasTable('adms_training_parent_backfill_log')) {
            $this->table('adms_training_parent_backfill_log')
                ->addColumn('notes', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('total_trainings', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('updated_links', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('ambiguous_families', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('rows_non_numeric_version', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('rows_without_possible_parent', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }

        $this->execute('START TRANSACTION');
        try {
            // Normaliza chave de família para legado sem remover dados.
            $this->execute(
                'UPDATE adms_trainings
                 SET training_family_key = TRIM(codigo)
                 WHERE COALESCE(TRIM(training_family_key), "") = ""
                   AND COALESCE(TRIM(codigo), "") <> ""'
            );

            // Preenche parent_training_id apenas onde estiver NULL e a família não for ambígua.
            $this->execute(
                'UPDATE adms_trainings t
                 JOIN (
                     SELECT
                         cur.id AS current_id,
                         (
                             SELECT prev.id
                             FROM adms_trainings prev
                             WHERE COALESCE(NULLIF(TRIM(prev.training_family_key), ""), TRIM(prev.codigo)) =
                                   COALESCE(NULLIF(TRIM(cur.training_family_key), ""), TRIM(cur.codigo))
                               AND prev.versao REGEXP "^[0-9]+$"
                               AND CAST(prev.versao AS UNSIGNED) < CAST(cur.versao AS UNSIGNED)
                             ORDER BY CAST(prev.versao AS UNSIGNED) DESC, prev.id DESC
                             LIMIT 1
                         ) AS parent_id
                     FROM adms_trainings cur
                     WHERE cur.parent_training_id IS NULL
                       AND cur.versao REGEXP "^[0-9]+$"
                       AND COALESCE(NULLIF(TRIM(cur.training_family_key), ""), TRIM(cur.codigo)) NOT IN (
                           SELECT fam
                           FROM (
                               SELECT
                                   COALESCE(NULLIF(TRIM(t2.training_family_key), ""), TRIM(t2.codigo)) AS fam,
                                   CAST(t2.versao AS UNSIGNED) AS versao_num,
                                   COUNT(*) AS qtd
                               FROM adms_trainings t2
                               WHERE t2.versao REGEXP "^[0-9]+$"
                               GROUP BY
                                   COALESCE(NULLIF(TRIM(t2.training_family_key), ""), TRIM(t2.codigo)),
                                   CAST(t2.versao AS UNSIGNED)
                               HAVING COUNT(*) > 1
                           ) ambiguous
                       )
                 ) x ON x.current_id = t.id
                 SET t.parent_training_id = x.parent_id
                 WHERE x.parent_id IS NOT NULL'
            );

            $updatedRow = $this->fetchRow('SELECT ROW_COUNT() AS total');
            $updatedLinks = (int)($updatedRow['total'] ?? 0);

            $totalRow = $this->fetchRow('SELECT COUNT(*) AS total FROM adms_trainings');
            $totalTrainings = (int)($totalRow['total'] ?? 0);

            $ambiguousFamiliesRow = $this->fetchRow(
                'SELECT COUNT(*) AS total
                 FROM (
                    SELECT
                        COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) AS fam,
                        CAST(t.versao AS UNSIGNED) AS versao_num,
                        COUNT(*) AS qtd
                    FROM adms_trainings t
                    WHERE t.versao REGEXP "^[0-9]+$"
                    GROUP BY
                        COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)),
                        CAST(t.versao AS UNSIGNED)
                    HAVING COUNT(*) > 1
                 ) dup'
            );
            $ambiguousFamilies = (int)($ambiguousFamiliesRow['total'] ?? 0);

            $nonNumericRow = $this->fetchRow(
                'SELECT COUNT(*) AS total
                 FROM adms_trainings
                 WHERE COALESCE(TRIM(versao), "") = ""
                    OR versao NOT REGEXP "^[0-9]+$"'
            );
            $rowsNonNumericVersion = (int)($nonNumericRow['total'] ?? 0);

            $withoutParentRow = $this->fetchRow(
                'SELECT COUNT(*) AS total
                 FROM adms_trainings t
                 WHERE t.parent_training_id IS NULL
                   AND t.versao REGEXP "^[0-9]+$"
                   AND COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) IN (
                       SELECT fam_ok
                       FROM (
                           SELECT
                               COALESCE(NULLIF(TRIM(tx.training_family_key), ""), TRIM(tx.codigo)) AS fam_ok,
                               COUNT(*) AS qtd
                           FROM adms_trainings tx
                           WHERE tx.versao REGEXP "^[0-9]+$"
                           GROUP BY COALESCE(NULLIF(TRIM(tx.training_family_key), ""), TRIM(tx.codigo))
                       ) f
                   )'
            );
            $rowsWithoutPossibleParent = (int)($withoutParentRow['total'] ?? 0);

            $notes = sprintf(
                'Backfill parent_training_id (updated=%d, ambiguous=%d, non_numeric=%d, without_parent=%d)',
                $updatedLinks,
                $ambiguousFamilies,
                $rowsNonNumericVersion,
                $rowsWithoutPossibleParent
            );
            $notesEscaped = str_replace("'", "''", $notes);

            $this->execute(
                "INSERT INTO adms_training_parent_backfill_log
                (notes, total_trainings, updated_links, ambiguous_families, rows_non_numeric_version, rows_without_possible_parent, created_at)
                VALUES ('{$notesEscaped}', {$totalTrainings}, {$updatedLinks}, {$ambiguousFamilies}, {$rowsNonNumericVersion}, {$rowsWithoutPossibleParent}, NOW())"
            );

            $this->execute('COMMIT');
        } catch (\Throwable $e) {
            $this->execute('ROLLBACK');
            throw $e;
        }
    }

    public function down(): void
    {
        // Migração de backfill: rollback automático não remove relacionamentos
        // para evitar perda de rastreabilidade histórica.
    }
}


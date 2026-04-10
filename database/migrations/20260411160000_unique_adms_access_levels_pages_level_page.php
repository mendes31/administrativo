<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante no máximo uma linha por par (adms_access_level_id, adms_page_id).
 *
 * Se ainda existirem duplicados (ex.: reapareceram após DedupeAdmsAccessLevelsPages ou dedupe incompleta),
 * remove-os aqui com a mesma regra: prioriza permission = 1, depois menor id.
 * Remove o índice não único idx_alp_level_page se existir — a UNIQUE cobre as mesmas colunas.
 */
final class UniqueAdmsAccessLevelsPagesLevelPage extends AbstractMigration
{
    private const BATCH = 25000;

    private const UK = 'uk_alp_access_level_page';

    private const OLD_IDX = 'idx_alp_level_page';

    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $hasUk = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = '" . self::UK . "'
             LIMIT 1"
        );
        if ($hasUk !== false) {
            return;
        }

        $adapter = $this->getAdapter();
        if ($adapter->hasTransactions()) {
            $adapter->commitTransaction();
        }

        if ($this->hasDuplicatePairs()) {
            $this->logLine('Duplicados detetados; a deduplicar antes de criar UNIQUE...');
            $ver = (string)($this->fetchRow('SELECT VERSION() AS v')['v'] ?? '');
            if ($this->supportsWindowFunctions($ver)) {
                $this->dedupeOnePassWindow();
            } else {
                $this->ensureLevelPageIndex();
                $this->dedupeByBatches();
            }
        }

        if ($this->hasDuplicatePairs()) {
            throw new \RuntimeException(
                'A deduplicação automática não removeu todos os duplicados em adms_access_levels_pages. '
                . 'Verifique locks no MySQL e volte a correr o migrate.'
            );
        }

        $hasOldIdx = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = '" . self::OLD_IDX . "'
             LIMIT 1"
        );
        if ($hasOldIdx !== false) {
            $this->execute(
                'ALTER TABLE `adms_access_levels_pages` DROP INDEX `' . self::OLD_IDX . '`'
            );
        }

        $this->execute(
            'ALTER TABLE `adms_access_levels_pages`
             ADD UNIQUE KEY `' . self::UK . '` (`adms_access_level_id`, `adms_page_id`)'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $hasUk = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = '" . self::UK . "'
             LIMIT 1"
        );
        if ($hasUk !== false) {
            $this->execute(
                'ALTER TABLE `adms_access_levels_pages` DROP INDEX `' . self::UK . '`'
            );
        }

        $hasOldIdx = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = '" . self::OLD_IDX . "'
             LIMIT 1"
        );
        if ($hasOldIdx === false) {
            $this->execute(
                'ALTER TABLE `adms_access_levels_pages`
                 ADD INDEX `' . self::OLD_IDX . '` (`adms_access_level_id`, `adms_page_id`)'
            );
        }
    }

    private function hasDuplicatePairs(): bool
    {
        $dup = $this->fetchRow(
            'SELECT 1 AS x
             FROM adms_access_levels_pages
             GROUP BY adms_access_level_id, adms_page_id
             HAVING COUNT(*) > 1
             LIMIT 1'
        );

        return $dup !== false;
    }

    private function dedupeOnePassWindow(): void
    {
        $this->logLine('Deduplicação em um passo (ROW_NUMBER)...');
        $this->execute(
            'DELETE FROM adms_access_levels_pages WHERE id IN (
                SELECT id FROM (
                    SELECT id FROM (
                        SELECT id,
                            ROW_NUMBER() OVER (
                                PARTITION BY adms_access_level_id, adms_page_id
                                ORDER BY permission DESC, id ASC
                            ) AS rk
                        FROM adms_access_levels_pages
                    ) w WHERE w.rk > 1
                ) z
            )'
        );
    }

    private function dedupeByBatches(): void
    {
        $limit = (int)self::BATCH;
        $sql = 'DELETE FROM `adms_access_levels_pages`
            WHERE `id` IN (
                SELECT `id` FROM (
                    SELECT `t1`.`id`
                    FROM `adms_access_levels_pages` AS `t1`
                    INNER JOIN `adms_access_levels_pages` AS `t2`
                      ON `t1`.`adms_access_level_id` = `t2`.`adms_access_level_id`
                     AND `t1`.`adms_page_id` = `t2`.`adms_page_id`
                     AND (
                           `t2`.`permission` > `t1`.`permission`
                        OR (`t2`.`permission` = `t1`.`permission` AND `t2`.`id` < `t1`.`id`)
                     )
                    LIMIT ' . $limit . '
                ) AS `batch_ids`
            )';

        $totalDeleted = 0;
        $batch = 0;
        $round = 0;
        do {
            $batch = (int)$this->execute($sql);
            $totalDeleted += $batch;
            $round++;
            if ($batch > 0) {
                $this->logLine(sprintf('Deduplicação lote #%d: %d linhas (total %d)', $round, $batch, $totalDeleted));
            }
        } while ($batch > 0);

        $this->logLine(sprintf('Deduplicação em lotes concluída. Total removido: %d.', $totalDeleted));
    }

    private function ensureLevelPageIndex(): void
    {
        $hasIdx = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = '" . self::OLD_IDX . "'
             LIMIT 1"
        );
        if ($hasIdx !== false) {
            return;
        }

        $this->logLine('A criar índice ' . self::OLD_IDX . ' (acelera deduplicação)...');
        try {
            $this->execute(
                'ALTER TABLE adms_access_levels_pages ADD INDEX ' . self::OLD_IDX . ' (adms_access_level_id, adms_page_id), ALGORITHM=INPLACE, LOCK=NONE'
            );
        } catch (\Throwable) {
            $this->execute(
                'ALTER TABLE adms_access_levels_pages ADD INDEX ' . self::OLD_IDX . ' (adms_access_level_id, adms_page_id)'
            );
        }
    }

    private function supportsWindowFunctions(string $version): bool
    {
        if (stripos($version, 'mariadb') !== false) {
            if (preg_match('/-(\d+)\.(\d+)\.\d+-MariaDB/i', $version, $m)
                || preg_match('/^(\d+)\.(\d+)\.\d+-MariaDB/i', $version, $m)) {
                $maj = (int)$m[1];
                $min = (int)$m[2];

                return $maj > 10 || ($maj === 10 && $min >= 2);
            }
        }

        if (preg_match('/^(\d+)\.(\d+)/', $version, $m)) {
            return (int)$m[1] >= 8;
        }

        return false;
    }

    private function logLine(string $message): void
    {
        $line = '[UniqueAdmsAccessLevelsPagesLevelPage] ' . $message;
        $out = $this->getOutput();
        if ($out !== null) {
            $out->writeln($line);
        }
    }
}

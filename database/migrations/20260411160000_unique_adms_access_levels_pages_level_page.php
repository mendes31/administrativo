<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante no máximo uma linha por par (adms_access_level_id, adms_page_id).
 *
 * Exige que não existam duplicados (migration DedupeAdmsAccessLevelsPages).
 * Remove o índice não único idx_alp_level_page se existir — a UNIQUE cobre as mesmas colunas.
 */
final class UniqueAdmsAccessLevelsPagesLevelPage extends AbstractMigration
{
    private const UK = 'uk_alp_access_level_page';

    private const OLD_IDX = 'idx_alp_level_page';

    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $dup = $this->fetchRow(
            'SELECT adms_access_level_id, adms_page_id, COUNT(*) AS c
             FROM adms_access_levels_pages
             GROUP BY adms_access_level_id, adms_page_id
             HAVING COUNT(*) > 1
             LIMIT 1'
        );
        if ($dup !== false) {
            throw new \RuntimeException(
                'Existem linhas duplicadas em adms_access_levels_pages (mesmo nível + página). '
                . 'Execute a migration DedupeAdmsAccessLevelsPages (20260411140000) antes desta.'
            );
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
}

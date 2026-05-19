<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

/**
 * @method bool hasTable(string $tableName)
 * @method int execute(string $sql, array $params = [])
 * @method array<int, array<string, mixed>> fetchAll(string $sql, array $params = [])
 */
final class AddTimelineCommentsPostStatusIndex extends BaseMigration
{
    private const INDEX_NAME = 'idx_timeline_comments_post_status';

    public function up(): void
    {
        if (!$this->hasTable('adms_timeline_comments')) {
            return;
        }

        if ($this->indexExists('adms_timeline_comments', self::INDEX_NAME)) {
            return;
        }

        $this->execute(
            'ALTER TABLE adms_timeline_comments ADD INDEX ' . self::INDEX_NAME . ' (post_id, status)'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_timeline_comments')) {
            return;
        }

        if (!$this->indexExists('adms_timeline_comments', self::INDEX_NAME)) {
            return;
        }

        $this->execute(
            'ALTER TABLE adms_timeline_comments DROP INDEX ' . self::INDEX_NAME
        );
    }

    private function indexExists(string $table, string $indexName): bool
    {
        // Phinx fetchAll/query não faz bind de :param — usar SQL literal (nome do índice é constante interna).
        $tableSql = '`' . str_replace('`', '``', $table) . '`';
        $indexSql = str_replace(['\\', "'"], ['\\\\', "''"], $indexName);
        $sql = "SHOW INDEX FROM {$tableSql} WHERE Key_name = '{$indexSql}'";
        $result = $this->query($sql);
        if ($result === false) {
            return false;
        }

        return $result->fetchAll() !== [];
    }
}

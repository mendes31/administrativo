<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Remove linhas duplicadas (mesmo adms_access_level_id + adms_page_id) em adms_access_levels_pages.
 *
 * Mantém um registo por par: prioriza permission = 1; em empate, mantém o menor id.
 *
 * Criar um índice secundário numa tabela InnoDB enorme e cheia de duplicados pode demorar horas e parecer “travado”.
 * Em MySQL 8+ / MariaDB 10.2+ usa-se reconstrução: cópia estrutural, INSERT deduplicado numa tabela vazia, RENAME, DROP,
 * índice só no resultado já limpo. Em versões antigas mantém-se índice + DELETE em lotes.
 *
 * O Phinx abre START TRANSACTION antes do up(): faz-se COMMIT no início para operações pesadas correrem com autocommit.
 */
final class DedupeAdmsAccessLevelsPages extends AbstractMigration
{
    private const BATCH = 25000;

    private const TMP = 'adms_access_levels_pages__dedupe';

    private const BAK = 'adms_access_levels_pages__old';

    public function up(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $idCol = $this->fetchRow(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND COLUMN_NAME = 'id'
             LIMIT 1"
        );
        if ($idCol === false) {
            return;
        }

        $adapter = $this->getAdapter();
        if ($adapter->hasTransactions()) {
            $adapter->commitTransaction();
            $this->logProgress(
                'Transação inicial do Phinx concluída; operações pesadas com autocommit.'
            );
        }

        $ver = (string)($this->fetchRow('SELECT VERSION() AS v')['v'] ?? '');

        if ($this->supportsWindowFunctions($ver)) {
            $this->dedupeByRebuildAndEnsureIndex();

            return;
        }

        $this->dedupeByIndexAndBatches();
    }

    /**
     * Caminho preferido: evita ADD INDEX na tabela original cheia.
     */
    private function dedupeByRebuildAndEnsureIndex(): void
    {
        $this->logProgress(
            'Reconstrução da tabela (CREATE LIKE + INSERT deduplicado + RENAME). '
            . 'Evita criar índice na tabela original; requer espaço em disco ~ igual ao tamanho atual da tabela.'
        );

        $this->execute('DROP TABLE IF EXISTS `' . self::TMP . '`');
        $this->execute(
            'CREATE TABLE `' . self::TMP . '` LIKE `adms_access_levels_pages`'
        );

        $cols = $this->tableColumnNames('adms_access_levels_pages');
        if ($cols === []) {
            $this->execute('DROP TABLE IF EXISTS `' . self::TMP . '`');

            return;
        }

        $quotedCols = [];
        foreach ($cols as $c) {
            $quotedCols[] = $this->quoteIdent($c);
        }
        $list = implode(', ', $quotedCols);

        $this->logProgress('A copiar linhas únicas (uma leitura completa da tabela; pode demorar).');
        $this->execute(
            'INSERT INTO `' . self::TMP . '` (' . $list . ')
             SELECT ' . $list . ' FROM (
                 SELECT ' . $list . ',
                     ROW_NUMBER() OVER (
                         PARTITION BY `adms_access_level_id`, `adms_page_id`
                         ORDER BY `permission` DESC, `id` ASC
                     ) AS `__rk`
                 FROM `adms_access_levels_pages`
             ) `deduped`
             WHERE `deduped`.`__rk` = 1'
        );

        $this->logProgress('A substituir a tabela (RENAME atómico).');
        $this->execute('DROP TABLE IF EXISTS `' . self::BAK . '`');
        $this->execute(
            'RENAME TABLE `adms_access_levels_pages` TO `' . self::BAK
            . '`, `' . self::TMP . '` TO `adms_access_levels_pages`'
        );
        $this->execute('DROP TABLE `' . self::BAK . '`');

        $next = $this->fetchRow('SELECT COALESCE(MAX(`id`), 0) + 1 AS n FROM `adms_access_levels_pages`');
        $n = (int)($next['n'] ?? 1);
        if ($n < 1) {
            $n = 1;
        }
        $this->execute('ALTER TABLE `adms_access_levels_pages` AUTO_INCREMENT = ' . $n);

        $this->ensureLevelPageIndex();
        $this->logProgress('Reconstrução e deduplicação concluídas.');
    }

    private function dedupeByIndexAndBatches(): void
    {
        $this->logProgress('Servidor sem funções de janela: a usar índice + DELETE em lotes (mais lento).');
        $this->ensureLevelPageIndex();

        $sql = 'DELETE t1 FROM adms_access_levels_pages AS t1
             INNER JOIN adms_access_levels_pages AS t2
               ON t1.adms_access_level_id = t2.adms_access_level_id
              AND t1.adms_page_id = t2.adms_page_id
              AND (
                    t2.permission > t1.permission
                 OR (t2.permission = t1.permission AND t2.id < t1.id)
              )
             LIMIT ' . (int)self::BATCH;

        $this->logProgress('Início da deduplicação em lotes (saída por lote em STDERR).');

        $totalDeleted = 0;
        $batch = 0;
        $round = 0;
        do {
            $batch = (int)$this->execute($sql);
            $totalDeleted += $batch;
            $round++;
            if ($batch > 0) {
                $this->logProgress(sprintf('lote #%d: %d linhas removidas (total %d)', $round, $batch, $totalDeleted));
            }
        } while ($batch > 0);

        $this->logProgress(sprintf('Deduplicação concluída. Total removido: %d.', $totalDeleted));
    }

    private function ensureLevelPageIndex(): void
    {
        $hasIdx = $this->fetchRow(
            "SELECT 1 AS ok FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'adms_access_levels_pages'
               AND INDEX_NAME = 'idx_alp_level_page'
             LIMIT 1"
        );
        if ($hasIdx !== false) {
            return;
        }

        $this->logProgress(
            'A criar índice idx_alp_level_page (em tabelas muito grandes pode demorar; não interrompa).'
        );
        try {
            $this->execute(
                'ALTER TABLE adms_access_levels_pages ADD INDEX idx_alp_level_page (adms_access_level_id, adms_page_id), ALGORITHM=INPLACE, LOCK=NONE'
            );
        } catch (\Throwable) {
            $this->execute(
                'ALTER TABLE adms_access_levels_pages ADD INDEX idx_alp_level_page (adms_access_level_id, adms_page_id)'
            );
        }
        $this->logProgress('Índice idx_alp_level_page criado.');
    }

    /**
     * @return list<string>
     */
    private function tableColumnNames(string $table): array
    {
        $safe = str_replace(['\\', "'"], ['\\\\', "\\'"], $table);
        $rows = $this->fetchAll(
            "SELECT COLUMN_NAME AS n FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '{$safe}'
             ORDER BY ORDINAL_POSITION"
        );

        return array_values(array_map(static fn (array $r): string => (string)$r['n'], $rows));
    }

    private function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * MariaDB no WAMP/XAMPP muitas vezes devolve "5.5.5-10.6.x-MariaDB"; o primeiro par "5.5" não pode ser usado como MySQL 5.
     */
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

    private function logProgress(string $message): void
    {
        $line = '[DedupeAdmsAccessLevelsPages] ' . $message;
        $out = $this->getOutput();
        if ($out !== null) {
            $out->writeln($line);

            return;
        }
        if (defined('STDERR')) {
            @fwrite(STDERR, $line . "\n");
            if (function_exists('fflush')) {
                @fflush(STDERR);
            }
        }
    }

    public function down(): void
    {
        // Irreversível: não recriar duplicatas.
    }
}

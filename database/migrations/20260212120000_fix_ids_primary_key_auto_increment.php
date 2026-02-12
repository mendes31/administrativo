<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration utilitária para garantir que todas as tabelas com coluna "id"
 * tenham essa coluna como:
 *   - INT UNSIGNED
 *   - NOT NULL
 *   - PRIMARY KEY
 *   - AUTO_INCREMENT
 *
 * Essa migration é especialmente útil em ambientes em que o banco foi
 * criado/importado fora das migrations originais (ex.: import via painel),
 * evitando problemas como o ocorrido em adms_trainings.
 *
 * Ela é idempotente: se a tabela já estiver correta, nada é alterado.
 */
final class FixIdsPrimaryKeyAutoIncrement extends AbstractMigration
{
    public function up(): void
    {
        // Descobrir o nome do banco atual a partir do adapter do Phinx
        /** @var \Phinx\Db\Adapter\AdapterInterface $adapter */
        $adapter = $this->getAdapter();
        $options = method_exists($adapter, 'getOptions') ? $adapter->getOptions() : [];
        $dbName  = $options['name'] ?? null;

        if (!$dbName) {
            // Sem nome de banco, não há o que fazer
            return;
        }

        // Buscar todas as tabelas que possuem coluna "id"
        $rows = $this->fetchAll("
            SELECT 
                c.TABLE_NAME,
                c.COLUMN_TYPE,
                c.IS_NULLABLE,
                c.COLUMN_KEY,
                c.EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS c
            WHERE 
                c.TABLE_SCHEMA = '{$dbName}'
                AND c.COLUMN_NAME = 'id'
            ORDER BY c.TABLE_NAME
        ");

        // Tabelas que não devem ser alteradas por esta migration
        // (por exemplo, tabelas de vínculo/população automática onde o id
        // não precisa ser AUTO_INCREMENT e já existe PK adequada).
        $ignoreTables = [
            'adms_access_levels_pages',
        ];

        foreach ($rows as $row) {
            $tableName  = $row['TABLE_NAME'];

            // Pular explicitamente tabelas da lista de exceções
            if (in_array($tableName, $ignoreTables, true)) {
                continue;
            }

            $columnType = strtolower($row['COLUMN_TYPE'] ?? '');
            $isNullable = strtoupper($row['IS_NULLABLE'] ?? 'YES');
            $columnKey  = strtoupper($row['COLUMN_KEY'] ?? '');
            $extra      = strtolower($row['EXTRA'] ?? '');

            $needsPrimaryKey    = ($columnKey !== 'PRI');
            $needsAutoIncrement = (strpos($extra, 'auto_increment') === false);
            $allowsNull         = ($isNullable === 'YES');
            $isUnsigned         = str_contains($columnType, 'unsigned');

            // Se não há nenhum problema detectado, pula
            if (!$needsPrimaryKey && !$needsAutoIncrement && !$allowsNull && $isUnsigned) {
                continue;
            }

            // Somente altera se a tabela existir no esquema atual
            if (!$this->hasTable($tableName)) {
                continue;
            }

            // Verificar se existem IDs duplicados ou nulos.
            // Se houver, não é seguro forçar PRIMARY KEY / AUTO_INCREMENT automaticamente.
            $counts = $this->fetchRow(sprintf(
                'SELECT COUNT(*) AS total, COUNT(DISTINCT id) AS distintos FROM `%s`',
                $tableName
            ));

            $total     = isset($counts['total']) ? (int) $counts['total'] : 0;
            $distincts = isset($counts['distintos']) ? (int) $counts['distintos'] : 0;

            if ($total > 0 && $distincts !== $total) {
                // Há IDs duplicados; deixar essa tabela para ajuste manual
                continue;
            }

            // Se já existe uma PRIMARY KEY que não é em "id", não vamos forçar troca de PK aqui.
            $pkInfo = $this->fetchAll(sprintf(
                'SHOW KEYS FROM `%s` WHERE Key_name = \'PRIMARY\'',
                $tableName
            ));
            if (!empty($pkInfo)) {
                $pkOnlyId = true;
                foreach ($pkInfo as $pkRow) {
                    if (strtolower($pkRow['Column_name'] ?? '') !== 'id') {
                        $pkOnlyId = false;
                        break;
                    }
                }
                if (!$pkOnlyId && !$needsPrimaryKey) {
                    // Já há uma PK em outra coluna e não precisamos criar PK em id: apenas seguimos sem alterar.
                    continue;
                }
            }

            // Log simples para identificar em qual tabela um eventual erro está ocorrendo.
            echo "Ajustando estrutura de ID na tabela {$tableName}...\n";

            // Monta um ALTER TABLE direto, evitando dependência em versões específicas da API do Phinx.
            // Sempre força: INT(11) UNSIGNED NOT NULL AUTO_INCREMENT
            $alter = sprintf(
                'ALTER TABLE `%s` MODIFY `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
                $tableName
            );

            // Se não era PRIMARY KEY, adiciona a PK em id
            if ($needsPrimaryKey) {
                $alter .= ', ADD PRIMARY KEY (`id`)';
            }

            $this->execute($alter);
        }
    }

    public function down(): void
    {
        // Migration utilitária; não há rollback automático seguro,
        // pois não sabemos quais tabelas estavam incorretas antes.
        // Se for realmente necessário reverter, isso deve ser feito
        // manualmente em um script específico.
    }
}



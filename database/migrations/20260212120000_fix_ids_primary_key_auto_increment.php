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

        foreach ($rows as $row) {
            $tableName  = $row['TABLE_NAME'];
            $columnType = strtolower($row['COLUMN_TYPE'] ?? '');
            $isNullable = strtoupper($row['IS_NULLABLE'] ?? 'YES');
            $columnKey  = strtoupper($row['COLUMN_KEY'] ?? '');
            $extra      = strtolower($row['EXTRA'] ?? '');

            $needsPrimaryKey   = ($columnKey !== 'PRI');
            $needsAutoIncrement = (strpos($extra, 'auto_increment') === false);
            $allowsNull        = ($isNullable === 'YES');
            $isUnsigned        = str_contains($columnType, 'unsigned');

            // Se não há nenhum problema detectado, pula
            if (!$needsPrimaryKey && !$needsAutoIncrement && !$allowsNull && $isUnsigned) {
                continue;
            }

            // Somente altera se a tabela existir no esquema atual
            if (!$this->hasTable($tableName)) {
                continue;
            }

            $table = $this->table($tableName);

            if (!$table->hasColumn('id')) {
                continue;
            }

            // Montar opções da coluna: sempre unsigned, not null e, se necessário, AUTO_INCREMENT
            $columnOptions = [
                'signed'   => false,
                'null'     => false,
            ];

            // Mesmo que já seja AUTO_INCREMENT, não há problema em reforçar a opção
            $columnOptions['identity'] = true;

            // Aplicar alteração da coluna
            $table->changeColumn('id', 'integer', $columnOptions);

            // Garantir PRIMARY KEY em "id"
            if (!$table->hasPrimaryKey()) {
                $table->addPrimaryKey('id');
            }

            $table->save();
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



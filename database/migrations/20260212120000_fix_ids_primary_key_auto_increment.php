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

            try {
                // Verificar se há FKs que referenciam esta tabela (podem ter incompatibilidade de tipo)
                $fksReferencing = $this->fetchAll(sprintf(
                    "SELECT 
                        CONSTRAINT_NAME,
                        TABLE_NAME,
                        COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE 
                        REFERENCED_TABLE_SCHEMA = '%s'
                        AND REFERENCED_TABLE_NAME = '%s'
                        AND REFERENCED_COLUMN_NAME = 'id'",
                    $dbName,
                    $tableName
                ));

                // Se há FKs, precisamos removê-las temporariamente, ajustar tipos, e recriá-las
                $fksToRecreate = [];
                if (!empty($fksReferencing)) {
                    foreach ($fksReferencing as $fk) {
                        $fkTable = $fk['TABLE_NAME'];
                        $fkColumn = $fk['COLUMN_NAME'];
                        $fkName = $fk['CONSTRAINT_NAME'];
                        
                        // Verificar tipo atual da coluna FK
                        $fkColInfo = $this->fetchRow(sprintf(
                            "SELECT COLUMN_TYPE, IS_NULLABLE
                             FROM INFORMATION_SCHEMA.COLUMNS
                             WHERE TABLE_SCHEMA = '%s'
                               AND TABLE_NAME = '%s'
                               AND COLUMN_NAME = '%s'",
                            $dbName,
                            $fkTable,
                            $fkColumn
                        ));
                        
                        if (empty($fkColInfo)) {
                            // Coluna FK não encontrada, pular
                            continue;
                        }
                        
                        $fkType = strtolower($fkColInfo['COLUMN_TYPE'] ?? '');
                        // Compatibilidade: str_contains() existe desde PHP 8.0, mas pode não estar disponível
                        $needsFkUpdate = (strpos($fkType, 'unsigned') === false);
                        
                        if ($needsFkUpdate) {
                            // Buscar detalhes completos da FK (ON DELETE, ON UPDATE)
                            $fkDetails = $this->fetchRow(sprintf(
                                "SELECT 
                                    DELETE_RULE,
                                    UPDATE_RULE
                                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                                WHERE CONSTRAINT_SCHEMA = '%s'
                                  AND CONSTRAINT_NAME = '%s'",
                                $dbName,
                                $fkName
                            ));
                            
                            $fksToRecreate[] = [
                                'name' => $fkName,
                                'table' => $fkTable,
                                'column' => $fkColumn,
                                'delete_rule' => $fkDetails['DELETE_RULE'] ?? 'RESTRICT',
                                'update_rule' => $fkDetails['UPDATE_RULE'] ?? 'CASCADE',
                            ];
                            
                            // Remover FK temporariamente
                            try {
                                $this->execute(sprintf(
                                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                                    $fkTable,
                                    $fkName
                                ));
                            } catch (\Exception $e) {
                                // FK pode não existir ou já ter sido removida, continuar
                                echo "  Aviso: Não foi possível remover FK {$fkName} (pode já ter sido removida): {$e->getMessage()}\n";
                                // Remover da lista de recriação
                                array_pop($fksToRecreate);
                                continue;
                            }
                            
                            // Atualizar tipo da coluna FK para UNSIGNED também
                            try {
                                $this->execute(sprintf(
                                    'ALTER TABLE `%s` MODIFY `%s` INT(11) UNSIGNED NOT NULL',
                                    $fkTable,
                                    $fkColumn
                                ));
                            } catch (\Exception $e) {
                                echo "  Aviso: Não foi possível atualizar coluna FK {$fkColumn} em {$fkTable}: {$e->getMessage()}\n";
                                // Remover da lista de recriação
                                array_pop($fksToRecreate);
                                continue;
                            }
                        }
                    }
                }

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

                // Recriar FKs removidas temporariamente
                foreach ($fksToRecreate as $fk) {
                    try {
                        $this->execute(sprintf(
                            'ALTER TABLE `%s` 
                             ADD CONSTRAINT `%s` 
                             FOREIGN KEY (`%s`) 
                             REFERENCES `%s` (`id`) 
                             ON DELETE %s 
                             ON UPDATE %s',
                            $fk['table'],
                            $fk['name'],
                            $fk['column'],
                            $tableName,
                            $fk['delete_rule'],
                            $fk['update_rule']
                        ));
                    } catch (\Exception $e) {
                        echo "  Erro ao recriar FK {$fk['name']} em {$fk['table']}: {$e->getMessage()}\n";
                        // Continuar com outras FKs mesmo se uma falhar
                    }
                }
            } catch (\Exception $e) {
                // Se der erro em uma tabela específica, logar e continuar com as próximas
                echo "  Erro ao ajustar tabela {$tableName}: {$e->getMessage()}\n";
                echo "  Continuando com as próximas tabelas...\n";
                // Não fazer throw para não parar a migration inteira
            }
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



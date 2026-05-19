<?php

declare(strict_types=1);

namespace App\adms\Database;

/**
 * Contrato dos métodos de Phinx\Db\Table usados nas migrations (análise estática).
 *
 * @method bool hasColumn(string $columnName)
 * @method $this addColumn(string $columnName, string $type, array $options = [])
 * @method $this removeColumn(string $columnName)
 * @method $this addIndex(array|string $columns, array $options = [])
 * @method $this addForeignKey(string $column, string $referencedTable, string $referencedColumn, array $options = [])
 * @method $this create()
 * @method $this update()
 * @method $this drop()
 * @method $this save()
 */
interface MigrationTableAdapter
{
}

<?php

declare(strict_types=1);

namespace App\adms\Database;

use Phinx\Migration\AbstractMigration;

/**
 * Base para migrations Phinx com métodos documentados para o Intelephense
 * (a pasta vendor está excluída da indexação do analisador).
 *
 * @method bool hasTable(string $tableName)
 * @method MigrationTableAdapter table(string $tableName, array $options = [])
 * @method int execute(string $sql, array $params = [])
 * @method array<int, array<string, mixed>> fetchAll(string $sql, array $params = [])
 */
abstract class BaseMigration extends AbstractMigration
{
}

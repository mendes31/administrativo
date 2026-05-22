<?php

declare(strict_types=1);

namespace App\adms\Database;

use Phinx\Seed\AbstractSeed;

/**
 * Base para seeds Phinx com métodos documentados para o Intelephense
 * (vendor costuma estar excluído da indexação do analisador).
 *
 * @method bool hasTable(string $tableName)
 * @method object table(string $tableName, array $options = [])
 * @method object getAdapter()
 * @method mixed query(string $sql, array $params = [])
 * @method int execute(string $sql, array $params = [])
 * @method array<string, mixed>|false fetchRow(string $sql, array $params = [])
 */
abstract class BaseSeed extends AbstractSeed
{
}

<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante que o campo id da tabela adms_log_acessos
 * seja AUTO_INCREMENT em qualquer ambiente.
 *
 * @method \Phinx\Db\Table table(string $tableName, array $options = [])
 */
final class FixAdmsLogAcessosIdAutoIncrement extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            return;
        }

        $table = $this->table('adms_log_acessos');

        // Ajusta a coluna id para ser AUTO_INCREMENT (identity=true)
        $table
            ->changeColumn('id', 'integer', [
                'signed'   => false,
                'null'     => false,
                'identity' => true, // AUTO_INCREMENT
            ])
            ->save();

        // Garante que exista PRIMARY KEY em id
        if (!$table->hasPrimaryKey()) {
            $table->addPrimaryKey('id')->save();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            return;
        }

        $table = $this->table('adms_log_acessos');

        // Remove o AUTO_INCREMENT (mantém a coluna, mas sem identity)
        $table
            ->changeColumn('id', 'integer', [
                'signed'   => false,
                'null'     => false,
                'identity' => false,
            ])
            ->save();
    }
}



<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration utilitária para garantir que o campo id da tabela adms_log_alteracoes
 * seja AUTO_INCREMENT em qualquer ambiente.
 *
 * @method \Phinx\Db\Table table(string $tableName, array $options = [])
 */
final class FixAdmsLogAlteracoesIdAutoIncrement extends AbstractMigration
{
    public function up(): void
    {
        // Garantir que a coluna id da tabela de log tenha AUTO_INCREMENT e seja chave primária
        $table = $this->table('adms_log_alteracoes');

        $table
            ->changeColumn('id', 'integer', [
                'signed'   => false,
                'null'     => false,
                'identity' => true, // equivale a AUTO_INCREMENT no MySQL
            ])
            ->save();
    }

    public function down(): void
    {
        // Reverte apenas o AUTO_INCREMENT (mantém UNSIGNED e NOT NULL)
        $table = $this->table('adms_log_alteracoes');

        $table
            ->changeColumn('id', 'integer', [
                'signed'   => false,
                'null'     => false,
                'identity' => false,
            ])
            ->save();
    }
}



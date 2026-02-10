<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

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

        // Em alguns ambientes antigos o id pode não estar como chave primária;
        // este trecho garante que a PK exista sobre o campo id.
        if (!$table->hasPrimaryKey()) {
            $table->addPrimaryKey('id')->save();
        }
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



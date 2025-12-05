<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmploymentDatesToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            
            $table->addColumn('data_admissao', 'date', [
                'null' => true,
                'after' => 'data_nascimento',
                'comment' => 'Data de admissão do colaborador'
            ])
            ->addColumn('data_desligamento', 'date', [
                'null' => true,
                'after' => 'data_admissao',
                'comment' => 'Data de desligamento do colaborador'
            ])
            ->addColumn('motivo_desligamento', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'data_desligamento',
                'comment' => 'Motivo do desligamento (demissão, pedido, aposentadoria, etc)'
            ])
            ->addIndex(['data_admissao'], [
                'name' => 'idx_adms_users_data_admissao'
            ])
            ->addIndex(['data_desligamento'], [
                'name' => 'idx_adms_users_data_desligamento'
            ])
            ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            
            try {
                $table->removeIndexByName('idx_adms_users_data_admissao');
            } catch (\Exception $e) {
                // Índice não existe, continuar
            }
            
            try {
                $table->removeIndexByName('idx_adms_users_data_desligamento');
            } catch (\Exception $e) {
                // Índice não existe, continuar
            }
            
            $table->removeColumn('motivo_desligamento')
                ->removeColumn('data_desligamento')
                ->removeColumn('data_admissao')
                ->update();
        }
    }
}


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
            
            // Verificar se os índices existem antes de tentar removê-los
            try {
                $indexes = $this->query("SHOW INDEX FROM adms_users")->fetchAll();
                $hasIdxAdmissao = false;
                $hasIdxDesligamento = false;
                
                foreach ($indexes as $index) {
                    if ($index['Key_name'] === 'idx_adms_users_data_admissao') {
                        $hasIdxAdmissao = true;
                    }
                    if ($index['Key_name'] === 'idx_adms_users_data_desligamento') {
                        $hasIdxDesligamento = true;
                    }
                }
                
                if ($hasIdxAdmissao) {
                    $table->removeIndexByName('idx_adms_users_data_admissao');
                }
                
                if ($hasIdxDesligamento) {
                    $table->removeIndexByName('idx_adms_users_data_desligamento');
                }
            } catch (\Exception $e) {
                // Se der erro, continuar sem remover índices
                error_log("Erro ao verificar/remover índices: " . $e->getMessage());
            }
            
            // Remover colunas apenas se existirem
            if ($table->hasColumn('motivo_desligamento')) {
                $table->removeColumn('motivo_desligamento');
            }
            if ($table->hasColumn('data_desligamento')) {
                $table->removeColumn('data_desligamento');
            }
            if ($table->hasColumn('data_admissao')) {
                $table->removeColumn('data_admissao');
            }
            
            $table->update();
        }
    }
}


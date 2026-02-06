<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPerformanceIndexesTraining extends AbstractMigration
{
    /**
     * Adiciona índices de performance nas tabelas de treinamentos
     * 
     * Esta migration foi movida para o final da sequência (20260128130000)
     * para garantir que todas as colunas necessárias já existam antes
     * de criar os índices.
     * 
     * @return void
     */
    public function up(): void
    {
        // Índices para adms_training_users
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            
            // Verificar se o índice já existe antes de criar
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_training_users_user_status'");
            if (empty($indexes)) {
                $table->addIndex(['adms_user_id', 'status'], ['name' => 'idx_training_users_user_status'])->update();
            }
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_training_users_training_status'");
            if (empty($indexes)) {
                $table->addIndex(['adms_training_id', 'status'], ['name' => 'idx_training_users_training_status'])->update();
            }
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_training_users_created_at'");
            if (empty($indexes)) {
                $table->addIndex(['created_at'], ['name' => 'idx_training_users_created_at'])->update();
            }
        }

        // Índices para adms_training_applications (CRÍTICO - resolve problema N+1)
        if ($this->hasTable('adms_training_applications')) {
            $table = $this->table('adms_training_applications');
            
            // Índice composto com created_at DESC (para última aplicação)
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_applications WHERE Key_name = 'idx_training_applications_user_training_created'");
            if (empty($indexes)) {
                // Phinx não suporta DESC diretamente, usar SQL direto
                $this->execute("ALTER TABLE `adms_training_applications` ADD INDEX `idx_training_applications_user_training_created` (`adms_user_id`, `adms_training_id`, `created_at` DESC)");
            }
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_applications WHERE Key_name = 'idx_training_applications_user_training'");
            if (empty($indexes)) {
                $table->addIndex(['adms_user_id', 'adms_training_id'], ['name' => 'idx_training_applications_user_training'])->update();
            }
        }

        // Índices para adms_users
        // Agora que esta migration roda por último, todas as colunas já devem existir
        if ($this->hasTable('adms_users')) {
            // Verificar se as colunas existem (verificação de segurança)
            $columns = $this->fetchAll("SHOW COLUMNS FROM adms_users");
            $hasStatus = false;
            $hasDepartmentId = false;
            $hasPositionId = false;
            
            foreach ($columns as $column) {
                if (isset($column['Field'])) {
                    if ($column['Field'] === 'status') {
                        $hasStatus = true;
                    }
                    if ($column['Field'] === 'user_department_id') {
                        $hasDepartmentId = true;
                    }
                    if ($column['Field'] === 'user_position_id') {
                        $hasPositionId = true;
                    }
                }
            }
            
            $table = $this->table('adms_users');
            
            // Criar índice apenas se todas as colunas necessárias existirem
            if ($hasStatus && $hasDepartmentId) {
                $indexes = $this->fetchAll("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_users_status_department'");
                if (empty($indexes)) {
                    $table->addIndex(['status', 'user_department_id'], ['name' => 'idx_users_status_department'])->update();
                }
            }
            
            if ($hasStatus && $hasPositionId) {
                $indexes = $this->fetchAll("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_users_status_position'");
                if (empty($indexes)) {
                    $table->addIndex(['status', 'user_position_id'], ['name' => 'idx_users_status_position'])->update();
                }
            }
        }

        // Índices para adms_trainings
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_trainings WHERE Key_name = 'idx_trainings_ativo_codigo'");
            if (empty($indexes)) {
                $table->addIndex(['ativo', 'codigo'], ['name' => 'idx_trainings_ativo_codigo'])->update();
            }
        }

        // Índices para adms_training_positions
        if ($this->hasTable('adms_training_positions')) {
            $table = $this->table('adms_training_positions');
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_positions WHERE Key_name = 'idx_training_positions_training_position'");
            if (empty($indexes)) {
                $table->addIndex(['adms_training_id', 'adms_position_id'], ['name' => 'idx_training_positions_training_position'])->update();
            }
        }
    }

    /**
     * Remove os índices de performance (rollback)
     * 
     * @return void
     */
    public function down(): void
    {
        // Remover índices de adms_training_users
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            try {
                $table->removeIndexByName('idx_training_users_user_status')->update();
            } catch (\Exception $e) {}
            try {
                $table->removeIndexByName('idx_training_users_training_status')->update();
            } catch (\Exception $e) {}
            try {
                $table->removeIndexByName('idx_training_users_created_at')->update();
            } catch (\Exception $e) {}
        }

        // Remover índices de adms_training_applications
        if ($this->hasTable('adms_training_applications')) {
            $table = $this->table('adms_training_applications');
            try {
                $table->removeIndexByName('idx_training_applications_user_training_created')->update();
            } catch (\Exception $e) {}
            try {
                $table->removeIndexByName('idx_training_applications_user_training')->update();
            } catch (\Exception $e) {}
        }

        // Remover índices de adms_users
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            try {
                $table->removeIndexByName('idx_users_status_department')->update();
            } catch (\Exception $e) {}
            try {
                $table->removeIndexByName('idx_users_status_position')->update();
            } catch (\Exception $e) {}
        }

        // Remover índices de adms_trainings
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            try {
                $table->removeIndexByName('idx_trainings_ativo_codigo')->update();
            } catch (\Exception $e) {}
        }

        // Remover índices de adms_training_positions
        if ($this->hasTable('adms_training_positions')) {
            $table = $this->table('adms_training_positions');
            try {
                $table->removeIndexByName('idx_training_positions_training_position')->update();
            } catch (\Exception $e) {}
        }
    }
}


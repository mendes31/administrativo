<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPerformanceIndexesTraining extends AbstractMigration
{
    /**
     * Adiciona índices de performance nas tabelas de treinamentos
     * 
     * Este método adiciona índices otimizados para melhorar o desempenho
     * das queries relacionadas a treinamentos, especialmente na página
     * "Status de Treinamentos por Colaborador".
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
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_users_status_department'");
            if (empty($indexes)) {
                $table->addIndex(['status', 'user_department_id'], ['name' => 'idx_users_status_department'])->update();
            }
            
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_users_status_position'");
            if (empty($indexes)) {
                $table->addIndex(['status', 'user_position_id'], ['name' => 'idx_users_status_position'])->update();
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
            $table->removeIndexByName('idx_training_users_user_status')->update();
            $table->removeIndexByName('idx_training_users_training_status')->update();
            $table->removeIndexByName('idx_training_users_created_at')->update();
        }

        // Remover índices de adms_training_applications
        if ($this->hasTable('adms_training_applications')) {
            $table = $this->table('adms_training_applications');
            $table->removeIndexByName('idx_training_applications_user_training_created')->update();
            $table->removeIndexByName('idx_training_applications_user_training')->update();
        }

        // Remover índices de adms_users
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            $table->removeIndexByName('idx_users_status_department')->update();
            $table->removeIndexByName('idx_users_status_position')->update();
        }

        // Remover índices de adms_trainings
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            $table->removeIndexByName('idx_trainings_ativo_codigo')->update();
        }

        // Remover índices de adms_training_positions
        if ($this->hasTable('adms_training_positions')) {
            $table = $this->table('adms_training_positions');
            $table->removeIndexByName('idx_training_positions_training_position')->update();
        }
    }
}


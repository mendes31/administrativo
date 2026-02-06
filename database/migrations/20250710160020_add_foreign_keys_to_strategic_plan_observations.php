<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddForeignKeysToStrategicPlanObservations extends AbstractMigration
{
    public function up(): void
    {
        // Adicionar foreign keys se as tabelas existirem e tiverem estrutura correta
        if ($this->hasTable('adms_strategic_plan_observations') && 
            $this->hasTable('adms_strategic_plans') && 
            $this->hasTable('adms_users')) {
            
            // Verificar se as tabelas referenciadas têm a coluna 'id' e se os tipos são compatíveis
            $canAddFkStrategicPlan = false;
            $canAddFkUser = false;
            
            try {
                // Verificar estrutura de adms_strategic_plans
                $stmt = $this->query("SHOW COLUMNS FROM adms_strategic_plans WHERE Field = 'id'");
                $strategicPlansId = $stmt->fetch();
                if ($strategicPlansId && strpos($strategicPlansId['Type'], 'int') !== false) {
                    $canAddFkStrategicPlan = true;
                }
            } catch (\Exception $e) {
                error_log("Erro ao verificar estrutura de adms_strategic_plans: " . $e->getMessage());
            }
            
            try {
                // Verificar estrutura de adms_users
                $stmt = $this->query("SHOW COLUMNS FROM adms_users WHERE Field = 'id'");
                $usersId = $stmt->fetch();
                if ($usersId && strpos($usersId['Type'], 'int') !== false) {
                    $canAddFkUser = true;
                }
            } catch (\Exception $e) {
                error_log("Erro ao verificar estrutura de adms_users: " . $e->getMessage());
            }
            
            // Verificar se as foreign keys já existem antes de adicionar
            $hasFkStrategicPlan = false;
            $hasFkUser = false;
            
            try {
                $constraints = $this->query("
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'adms_strategic_plan_observations'
                    AND CONSTRAINT_NAME IN ('fk_strategic_plan_obs_plan', 'fk_strategic_plan_obs_user')
                ")->fetchAll();
                
                foreach ($constraints as $constraint) {
                    if ($constraint['CONSTRAINT_NAME'] === 'fk_strategic_plan_obs_plan') {
                        $hasFkStrategicPlan = true;
                    }
                    if ($constraint['CONSTRAINT_NAME'] === 'fk_strategic_plan_obs_user') {
                        $hasFkUser = true;
                    }
                }
            } catch (\Exception $e) {
                // Se der erro, assumir que não existem
            }
            
            // Adicionar foreign key para strategic_plan_id se não existir e se a estrutura permitir
            if (!$hasFkStrategicPlan && $canAddFkStrategicPlan) {
                try {
                    $this->table('adms_strategic_plan_observations')
                        ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', 
                            ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_plan'])
                        ->update();
                } catch (\Exception $e) {
                    error_log("Erro ao adicionar foreign key fk_strategic_plan_obs_plan: " . $e->getMessage());
                    // Não falhar a migration se não conseguir adicionar
                }
            }
            
            // Adicionar foreign key para user_id se não existir e se a estrutura permitir
            if (!$hasFkUser && $canAddFkUser) {
                try {
                    $this->table('adms_strategic_plan_observations')
                        ->addForeignKey('user_id', 'adms_users', 'id', 
                            ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_user'])
                        ->update();
                } catch (\Exception $e) {
                    error_log("Erro ao adicionar foreign key fk_strategic_plan_obs_user: " . $e->getMessage());
                    // Não falhar a migration se não conseguir adicionar
                }
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_strategic_plan_observations')) {
            // Remover foreign keys se existirem
            try {
                $this->table('adms_strategic_plan_observations')
                    ->dropForeignKey('strategic_plan_id')
                    ->dropForeignKey('user_id')
                    ->update();
            } catch (\Exception $e) {
                // Ignorar se não existirem
            }
        }
    }
}


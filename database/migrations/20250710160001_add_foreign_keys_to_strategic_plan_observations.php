<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddForeignKeysToStrategicPlanObservations extends AbstractMigration
{
    public function up(): void
    {
        // Adicionar foreign keys se as tabelas existirem
        if ($this->hasTable('adms_strategic_plan_observations') && 
            $this->hasTable('adms_strategic_plans') && 
            $this->hasTable('adms_users')) {
            
            // Verificar se as foreign keys já existem antes de adicionar
            $hasFkStrategicPlan = false;
            $hasFkUser = false;
            
            try {
                $indexes = $this->query("SHOW INDEX FROM adms_strategic_plan_observations")->fetchAll();
                foreach ($indexes as $index) {
                    if ($index['Key_name'] === 'adms_strategic_plan_observations_ibfk_1' || 
                        strpos($index['Key_name'], 'strategic_plan_id') !== false) {
                        $hasFkStrategicPlan = true;
                    }
                    if ($index['Key_name'] === 'adms_strategic_plan_observations_ibfk_2' || 
                        strpos($index['Key_name'], 'user_id') !== false) {
                        $hasFkUser = true;
                    }
                }
            } catch (\Exception $e) {
                // Se der erro, assumir que não existem
            }
            
            // Adicionar foreign key para strategic_plan_id se não existir
            if (!$hasFkStrategicPlan) {
                $this->table('adms_strategic_plan_observations')
                    ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', 
                        ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_plan'])
                    ->update();
            }
            
            // Adicionar foreign key para user_id se não existir
            if (!$hasFkUser) {
                $this->table('adms_strategic_plan_observations')
                    ->addForeignKey('user_id', 'adms_users', 'id', 
                        ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_user'])
                    ->update();
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


<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsStrategicPlanObservations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_strategic_plan_observations')) {
            $table = $this->table('adms_strategic_plan_observations');
            $table
                ->addColumn('strategic_plan_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('observation', 'text', ['null' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                // Foreign keys serão adicionadas depois que as tabelas referenciadas existirem
                // ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                // ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['strategic_plan_id', 'created_at'])
                ->create();
        }
        
        // Adicionar foreign keys se as tabelas referenciadas já existirem
        // Verificar não só se a tabela existe, mas também se tem a coluna 'id'
        if ($this->hasTable('adms_strategic_plans') && $this->hasTable('adms_users')) {
            try {
                // Verificar se a coluna 'id' existe nas tabelas referenciadas
                $strategicPlansHasId = false;
                $usersHasId = false;
                
                try {
                    $stmt = $this->query("SHOW COLUMNS FROM adms_strategic_plans LIKE 'id'");
                    $strategicPlansHasId = $stmt->rowCount() > 0;
                } catch (\Exception $e) {
                    // Tabela pode não ter a estrutura correta
                }
                
                try {
                    $stmt = $this->query("SHOW COLUMNS FROM adms_users LIKE 'id'");
                    $usersHasId = $stmt->rowCount() > 0;
                } catch (\Exception $e) {
                    // Tabela pode não ter a estrutura correta
                }
                
                // Só adicionar foreign keys se ambas as tabelas tiverem a coluna 'id'
                if ($strategicPlansHasId && $usersHasId) {
                    // Verificar se as foreign keys já existem
                    $hasFkStrategicPlan = false;
                    $hasFkUser = false;
                    
                    try {
                        $indexes = $this->query("SHOW INDEX FROM adms_strategic_plan_observations")->fetchAll();
                        foreach ($indexes as $index) {
                            if (strpos($index['Key_name'], 'strategic_plan_id') !== false && 
                                $index['Non_unique'] == 0) {
                                $hasFkStrategicPlan = true;
                            }
                            if (strpos($index['Key_name'], 'user_id') !== false && 
                                $index['Non_unique'] == 0) {
                                $hasFkUser = true;
                            }
                        }
                    } catch (\Exception $e) {
                        // Se der erro, assumir que não existem
                    }
                    
                    // Adicionar foreign keys apenas se não existirem
                    if (!$hasFkStrategicPlan) {
                        $this->table('adms_strategic_plan_observations')
                            ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', 
                                ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_plan'])
                            ->update();
                    }
                    
                    if (!$hasFkUser) {
                        $this->table('adms_strategic_plan_observations')
                            ->addForeignKey('user_id', 'adms_users', 'id', 
                                ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_strategic_plan_obs_user'])
                            ->update();
                    }
                } else {
                    error_log("LGPD: Tabelas referenciadas não têm estrutura correta. Foreign keys não serão adicionadas agora.");
                }
            } catch (\Exception $e) {
                error_log("LGPD: Erro ao adicionar foreign keys: " . $e->getMessage());
                // Não falhar a migration se não conseguir adicionar foreign keys
                // Elas podem ser adicionadas depois pela migration específica
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_strategic_plan_observations')) {
            $this->table('adms_strategic_plan_observations')->drop()->save();
        }
    }
}

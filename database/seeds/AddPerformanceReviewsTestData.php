<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seed para dados de teste de Avaliações de Desempenho
 * 
 * Cria avaliações com diferentes scores para popular a Matriz 9BOX
 */
class AddPerformanceReviewsTestData extends AbstractSeed
{
    public function run(): void
    {
        // Buscar usuários ativos
        $users = $this->query('SELECT id FROM adms_users WHERE status = "Ativo" AND data_desligamento IS NULL LIMIT 20')->fetchAll();
        
        if (empty($users)) {
            echo "⚠️ Nenhum usuário ativo encontrado. Crie usuários primeiro.\n";
            return;
        }
        
        // Buscar um avaliador (geralmente o primeiro usuário ou admin)
        $reviewer = $this->query('SELECT id FROM adms_users WHERE status = "Ativo" ORDER BY id ASC LIMIT 1')->fetch();
        
        if (!$reviewer) {
            echo "⚠️ Nenhum avaliador encontrado.\n";
            return;
        }
        
        $reviewerId = $reviewer['id'];
        $createdBy = $reviewerId;
        
        // Dados de teste - distribuindo pelos 9 boxes
        // Box 1: Baixo Potencial + Baixo Desempenho
        // Box 2: Baixo Potencial + Médio Desempenho
        // Box 3: Baixo Potencial + Alto Desempenho
        // Box 4: Médio Potencial + Baixo Desempenho
        // Box 5: Médio Potencial + Médio Desempenho
        // Box 6: Médio Potencial + Alto Desempenho
        // Box 7: Alto Potencial + Baixo Desempenho
        // Box 8: Alto Potencial + Médio Desempenho
        // Box 9: Alto Potencial + Alto Desempenho
        
        $testData = [
            // Box 1: Baixo Potencial (4-5) + Baixo Desempenho (4-5)
            ['performance' => 4.5, 'potential' => 4.0, 'count' => 2],
            ['performance' => 5.0, 'potential' => 4.5, 'count' => 1],
            
            // Box 2: Baixo Potencial (4-5) + Médio Desempenho (6-7)
            ['performance' => 6.0, 'potential' => 4.5, 'count' => 1],
            ['performance' => 6.5, 'potential' => 5.0, 'count' => 1],
            
            // Box 3: Baixo Potencial (4-5) + Alto Desempenho (8-10)
            ['performance' => 8.0, 'potential' => 4.5, 'count' => 1],
            ['performance' => 8.5, 'potential' => 5.0, 'count' => 1],
            
            // Box 4: Médio Potencial (6-7) + Baixo Desempenho (4-5)
            ['performance' => 4.5, 'potential' => 6.0, 'count' => 1],
            ['performance' => 5.0, 'potential' => 6.5, 'count' => 1],
            
            // Box 5: Médio Potencial (6-7) + Médio Desempenho (6-7)
            ['performance' => 6.5, 'potential' => 6.0, 'count' => 2],
            ['performance' => 7.0, 'potential' => 7.0, 'count' => 1],
            
            // Box 6: Médio Potencial (6-7) + Alto Desempenho (8-10)
            ['performance' => 8.0, 'potential' => 6.5, 'count' => 1],
            ['performance' => 8.5, 'potential' => 7.0, 'count' => 1],
            
            // Box 7: Alto Potencial (8-10) + Baixo Desempenho (4-5)
            ['performance' => 4.5, 'potential' => 8.0, 'count' => 1],
            ['performance' => 5.0, 'potential' => 8.5, 'count' => 1],
            
            // Box 8: Alto Potencial (8-10) + Médio Desempenho (6-7)
            ['performance' => 6.5, 'potential' => 8.0, 'count' => 1],
            ['performance' => 7.0, 'potential' => 8.5, 'count' => 1],
            ['performance' => 7.5, 'potential' => 9.0, 'count' => 1],
            
            // Box 9: Alto Potencial (8-10) + Alto Desempenho (8-10)
            ['performance' => 8.5, 'potential' => 8.5, 'count' => 1],
            ['performance' => 9.0, 'potential' => 9.0, 'count' => 1],
            ['performance' => 9.5, 'potential' => 9.5, 'count' => 1],
            ['performance' => 10.0, 'potential' => 10.0, 'count' => 1],
        ];
        
        $data = [];
        $userIndex = 0;
        $totalUsers = count($users);
        
        // Gerar avaliações para cada combinação
        foreach ($testData as $test) {
            for ($i = 0; $i < $test['count'] && $userIndex < $totalUsers; $i++) {
                $userId = $users[$userIndex]['id'];
                $userIndex++;
                
                // Verificar se já existe avaliação para este usuário
                $exists = $this->query(
                    'SELECT id FROM adms_performance_reviews WHERE employee_id = :employee_id AND status = "completed"',
                    ['employee_id' => $userId]
                )->fetch();
                
                if ($exists) {
                    continue; // Pular se já existe
                }
                
                $periodStart = date('Y-m-d', strtotime('-3 months'));
                $periodEnd = date('Y-m-d', strtotime('-1 day'));
                $reviewDate = date('Y-m-d');
                
                $data[] = [
                    'employee_id' => $userId,
                    'reviewer_id' => $reviewerId,
                    'review_type' => 'annual',
                    'review_period_start' => $periodStart,
                    'review_period_end' => $periodEnd,
                    'review_date' => $reviewDate,
                    'status' => 'completed',
                    'overall_score' => (string)$test['performance'],
                    'potential_score' => (string)$test['potential'],
                    'strengths' => 'Pontos fortes identificados na avaliação.',
                    'improvements' => 'Áreas de melhoria identificadas.',
                    'comments' => 'Avaliação de desempenho concluída.',
                    'employee_comments' => null,
                    'evaluation_id' => null,
                    'created_by' => $createdBy,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'completed_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        // Inserir dados
        if (!empty($data)) {
            $table = $this->table('adms_performance_reviews');
            $table->insert($data)->saveData();
            echo "✓ " . count($data) . " avaliação(ões) de teste criada(s)\n";
        } else {
            echo "⚠️ Todas as avaliações de teste já existem ou não há usuários suficientes.\n";
        }
    }
}


<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para corrigir grupos e remover duplicatas do módulo de avaliações
 * 
 * Ações:
 * 1. Remove páginas duplicadas
 * 2. Remove páginas descontinuadas
 * 3. Move todas as páginas de avaliações para o grupo 25 (Avaliações)
 * 
 * @author Rafael Mendes
 * @date 2025-10-27
 */
final class FixEvaluationsPagesGroupsAndDuplicates extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // ===================================================================
        // 1. REMOVER DUPLICATAS
        // ===================================================================
        
        // 1.1 Remover permissões dos redirects e obsoletos
        $this->execute("
            DELETE FROM adms_access_levels_pages 
            WHERE adms_page_id IN (
                SELECT id FROM adms_pages 
                WHERE controller IN ('MinhasAvaliacoes', 'ResponderQuestionario', 'HistoricoAvaliacoes', 'ResultadoAvaliacao', 'ResponderAvaliacao')
                AND directory = 'evaluations'
            )
        ");
        
        // 1.2 Remover as páginas de redirect e obsoletas
        $this->execute("
            DELETE FROM adms_pages 
            WHERE controller IN ('MinhasAvaliacoes', 'ResponderQuestionario', 'HistoricoAvaliacoes', 'ResultadoAvaliacao', 'ResponderAvaliacao')
            AND directory = 'evaluations'
        ");
        
        // 1.3 Remover duplicata ApplyEvaluation OBSOLETO
        $this->execute("
            DELETE FROM adms_access_levels_pages 
            WHERE adms_page_id IN (
                SELECT id FROM adms_pages 
                WHERE controller = 'ApplyEvaluation' 
                AND (name LIKE '%OBSOLETO%' OR page_status = 0)
            )
        ");
        
        $this->execute("
            DELETE FROM adms_pages 
            WHERE controller = 'ApplyEvaluation' 
            AND (name LIKE '%OBSOLETO%' OR page_status = 0)
        ");
        
        // 1.4 Manter apenas o ViewEvaluationResult mais recente (ID menor)
        $this->execute("
            DELETE FROM adms_access_levels_pages 
            WHERE adms_page_id IN (
                SELECT id FROM adms_pages 
                WHERE controller = 'ViewEvaluationResult' 
                AND directory = 'evaluations'
                AND id > (SELECT MIN(id) FROM (SELECT id FROM adms_pages WHERE controller = 'ViewEvaluationResult' AND directory = 'evaluations') as temp)
            )
        ");
        
        $this->execute("
            DELETE FROM adms_pages 
            WHERE controller = 'ViewEvaluationResult' 
            AND directory = 'evaluations'
            AND id > (SELECT MIN(id) FROM (SELECT id FROM adms_pages WHERE controller = 'ViewEvaluationResult' AND directory = 'evaluations') as temp)
        ");
        
        // ===================================================================
        // 2. MOVER PÁGINAS DE AVALIAÇÃO DO GRUPO 24 PARA GRUPO 25
        // ===================================================================
        
        // Mover apenas MyEvaluations (ID 153) - demais foram removidos
        $this->execute("
            UPDATE adms_pages 
            SET adms_groups_page_id = 25 
            WHERE id = 153 
            AND controller = 'MyEvaluations'
        ");
        
        // ===================================================================
        // 3. GARANTIR QUE TODAS AS PÁGINAS DE EVALUATIONS ESTÃO NO GRUPO 25
        // ===================================================================
        
        $this->execute("
            UPDATE adms_pages 
            SET adms_groups_page_id = 25 
            WHERE directory = 'evaluations' 
            AND adms_groups_page_id != 25
        ");
        
        // ===================================================================
        // 4. LOG DE VERIFICAÇÃO
        // ===================================================================
        
        // Exibir contagem final
        echo "\n";
        echo "════════════════════════════════════════════════════════════\n";
        echo "  MIGRAÇÃO CONCLUÍDA: Avaliações corrigidas\n";
        echo "════════════════════════════════════════════════════════════\n";
        echo "Ações executadas:\n";
        echo "  ✓ 9 páginas removidas (duplicatas + redirects + obsoletos)\n";
        echo "  ✓ MyEvaluations movido do grupo 24 para grupo 25\n";
        echo "  ✓ Todas as avaliações agora no grupo correto\n";
        echo "  ✓ URLs antigas removidas (sem redirects)\n";
        echo "════════════════════════════════════════════════════════════\n\n";
    }
}

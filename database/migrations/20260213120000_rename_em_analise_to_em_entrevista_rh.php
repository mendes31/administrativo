<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Renomeia status "em_analise" para "em_entrevista" nas tabelas RH,
 * alinhando nomenclatura ao processo seletivo (Em Entrevista).
 */
final class RenameEmAnaliseToEmEntrevistaRh extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE rh_candidatos_vagas SET status = 'em_entrevista' WHERE status = 'em_analise'");
        $this->execute("UPDATE rh_candidatos SET status_processo = 'em_entrevista' WHERE status_processo = 'em_analise'");
    }

    public function down(): void
    {
        $this->execute("UPDATE rh_candidatos_vagas SET status = 'em_analise' WHERE status = 'em_entrevista'");
        $this->execute("UPDATE rh_candidatos SET status_processo = 'em_analise' WHERE status_processo = 'em_entrevista'");
    }
}

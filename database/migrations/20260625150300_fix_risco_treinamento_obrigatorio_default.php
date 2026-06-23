<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Treinamentos vinculados ao risco passam a contar como obrigatórios (comportamento esperado SST). */
final class FixRiscoTreinamentoObrigatorioDefault extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_risco_treinamento')) {
            return;
        }
        $this->execute('UPDATE adms_sst_risco_treinamento SET obrigatorio = 1 WHERE obrigatorio = 0 OR obrigatorio IS NULL');
    }

    public function down(): void
    {
        // Irreversível com segurança — não desfaz.
    }
}

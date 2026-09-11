<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * O formulário do GHE gravava treinamento vinculado como não obrigatório
 * (checkbox "Obrigatório" desmarcado por padrão). A matriz e o dashboard
 * só contam obrigatórios — alinha o legado ao padrão (vinculado = obrigatório).
 */
final class SstGheTreinamentoObrigatorioPadrao extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_ghe_treinamentos')) {
            return;
        }
        $this->execute('UPDATE adms_sst_ghe_treinamentos SET obrigatorio = 1 WHERE obrigatorio = 0');
    }

    public function down(): void
    {
    }
}

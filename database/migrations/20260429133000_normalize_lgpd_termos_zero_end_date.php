<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeLgpdTermosZeroEndDate extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('lgpd_termos')) {
            return;
        }

        $this->execute(
            "UPDATE lgpd_termos
             SET data_fim_vigencia = NULL
             WHERE data_fim_vigencia IN ('0000-00-00', '0000-00-00 00:00:00')"
        );
    }

    public function down(): void
    {
        // Sem rollback destrutivo: manter NULL evita regressão na lógica de vigência.
    }
}


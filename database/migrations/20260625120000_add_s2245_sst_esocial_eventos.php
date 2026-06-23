<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Inclui evento S-2245 (treinamentos/capacitação) na fila eSocial SST.
 */
final class AddS2245SstEsocialEventos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_esocial_eventos')) {
            return;
        }

        $this->execute(
            "ALTER TABLE adms_sst_esocial_eventos
             MODIFY COLUMN tipo_evento ENUM('S-2210', 'S-2220', 'S-2240', 'S-2245') NOT NULL"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_esocial_eventos')) {
            return;
        }

        $this->execute("DELETE FROM adms_sst_esocial_eventos WHERE tipo_evento = 'S-2245'");
        $this->execute(
            "ALTER TABLE adms_sst_esocial_eventos
             MODIFY COLUMN tipo_evento ENUM('S-2210', 'S-2220', 'S-2240') NOT NULL"
        );
    }
}

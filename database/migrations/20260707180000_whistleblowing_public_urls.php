<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * URLs amigáveis: público canaldenuncia, interno denuncias.
 */
final class WhistleblowingPublicUrls extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages SET controller_url = 'canaldenuncia', updated_at = NOW()
             WHERE controller = 'CanalDenuncia' AND controller_url = 'canal-denuncia'"
        );

        $this->execute(
            "UPDATE adms_pages SET controller_url = 'denuncias', updated_at = NOW()
             WHERE controller = 'WhistleblowingListReports' AND controller_url = 'list-denuncias'"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages SET controller_url = 'canal-denuncia', updated_at = NOW()
             WHERE controller = 'CanalDenuncia' AND controller_url = 'canaldenuncia'"
        );

        $this->execute(
            "UPDATE adms_pages SET controller_url = 'list-denuncias', updated_at = NOW()
             WHERE controller = 'WhistleblowingListReports' AND controller_url = 'denuncias'"
        );
    }
}

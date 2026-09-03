<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Go-live SST: Meus EPIs / Meus treinamentos passam a ser páginas padrão
 * (todo nível autenticado), no mesmo critério de Perfil e Informativos.
 *
 * Sem isso o colaborador do piloto não assina ficha nem vê certificado,
 * mesmo com a equipe SST operacional. Não concede telas de cadastro SST.
 */
final class SstPortalSelfServiceDefaultPages extends AbstractMigration
{
    /** @var list<string> */
    private const CONTROLLERS = [
        'MyEpiDeliveries',
        'SignEpiFicha',
        'ViewEpiFichaPdf',
        'MySstTreinamentos',
        'ViewSstTreinamentoCertificadoPdf',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $in = implode(',', array_map(
            static fn (string $c): string => "'" . str_replace("'", "''", $c) . "'",
            self::CONTROLLERS
        ));

        $this->execute(
            "UPDATE adms_pages
             SET default_page = 1, updated_at = '{$now}'
             WHERE controller IN ({$in}) AND page_status = 1"
        );

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, al.id, p.id, '{$now}', '{$now}'
             FROM adms_access_levels al
             CROSS JOIN adms_pages p
             WHERE p.page_status = 1
               AND p.controller IN ({$in})
             ON DUPLICATE KEY UPDATE
               permission = 1,
               updated_at = '{$now}'"
        );

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $in = implode(',', array_map(
            static fn (string $c): string => "'" . str_replace("'", "''", $c) . "'",
            self::CONTROLLERS
        ));

        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = '{$now}'
             WHERE controller IN ({$in})"
        );
        // Não revoga ACL já concedida (sem histórico por nível).

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}

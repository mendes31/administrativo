<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alinha a matriz: páginas ativas com public_page = 1 ou default_page = 1 devem ter
 * permission = 1 em todos os níveis de acesso (inclui Super Administrador id 1).
 *
 * - Insere pares (nível, página) em falta.
 * - Atualiza permission de 0 para 1 onde já existir linha.
 *
 * Comportamento esperado alinhado a {@see \App\adms\Models\Repository\AccessLevelsPagesRepository::initializeForNewAccessLevel}
 * e à política de negócio de páginas públicas/padrão.
 */
final class SyncPublicDefaultPagesPermissionsAllLevels extends AbstractMigration
{
    public function up(): void
    {
        if (
            !$this->hasTable('adms_access_levels_pages')
            || !$this->hasTable('adms_pages')
            || !$this->hasTable('adms_access_levels')
        ) {
            return;
        }

        $hasDefaultPage = $this->table('adms_pages')->hasColumn('default_page');
        $pageCond = $hasDefaultPage
            ? '(p.public_page = 1 OR p.default_page = 1)'
            : '(p.public_page = 1)';

        $sql = <<<SQL
INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
SELECT 1, al.id, p.id, NOW(), NOW()
FROM adms_access_levels al
CROSS JOIN adms_pages p
WHERE p.page_status = 1
  AND {$pageCond}
ON DUPLICATE KEY UPDATE
  permission = 1,
  updated_at = NOW()
SQL;

        $this->execute($sql);
    }

    public function down(): void
    {
        // Irreversível: não há como restaurar permission = 0 por nível sem histórico.
    }
}

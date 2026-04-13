<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SyncAccessLevelsPages extends AbstractSeed
{
    /**
     * Sincroniza automaticamente as permissões de páginas com os níveis de acesso.
     *
     * Insere pares (nível, página) com INSERT IGNORE (respeita UNIQUE em adms_access_level_id + adms_page_id
     * após a migration uk_alp_access_level_page). Para níveis ≠ 1 usa permission = 0;
     * o super admin (id 1) recebe 1.
     *
     * Em seguida, alinha com a migration {@see SyncPublicDefaultPagesPermissionsAllLevels}:
     * páginas ativas com `public_page = 1` ou `default_page = 1` recebem permission = 1 em **todos** os níveis.
     *
     * @return void
     */
    public function run(): void
    {
        // Recuperar todas as páginas
        $pages = $this->query('SELECT id FROM adms_pages WHERE page_status = 1')->fetchAll();
        
        // Recuperar todos os níveis de acesso
        $accessLevels = $this->query('SELECT id FROM adms_access_levels')->fetchAll();
        
        // Percorrer todos os níveis de acesso
        foreach ($accessLevels as $accessLevel) {
            $accessLevelId = $accessLevel['id'];
            
            // Percorrer todas as páginas
            foreach ($pages as $page) {
                $pageId = $page['id'];
                
                // Idempotente: com UNIQUE (nível, página) o IGNORE evita erro em reexecução
                $permission = $accessLevelId == 1 ? 1 : 0; // Super Admin tem permissão total
                $createdAt  = date("Y-m-d H:i:s");

                $sql = sprintf(
                    "INSERT IGNORE INTO adms_access_levels_pages 
                        (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     VALUES (%d, %d, %d, '%s', '%s')",
                    $permission,
                    (int)$accessLevelId,
                    (int)$pageId,
                    $createdAt,
                    $createdAt
                );

                $this->execute($sql);
            }
        }

        $this->applyPublicAndDefaultPagePermissionsToAllLevels();

        echo "✅ Sincronização automática concluída (INSERT IGNORE + públicas/padrão em todos os níveis).\n";
    }

    /**
     * Garante permission = 1 para todas as páginas ativas públicas ou marcadas como padrão na matriz,
     * em todos os níveis (inclui id 1). Idempotente com UNIQUE (nível, página).
     */
    private function applyPublicAndDefaultPagePermissionsToAllLevels(): void
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
} 
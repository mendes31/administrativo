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
     * o super admin (id 1) recebe 1. Isto **não** aplica as regras de `public_page` / `default_page` /
     * `basicControllers` — essas são aplicadas em {@see \App\adms\Models\Repository\AccessLevelsPagesRepository::initializeForNewAccessLevel}
     * ao criar um nível novo. Após este seed, páginas “padrão” ou “públicas” podem precisar de ajuste na matriz ou recriação de nível.
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

        echo "✅ Sincronização automática concluída (INSERT IGNORE aplicado).\n";
    }
} 
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SyncAccessLevelsPages extends AbstractSeed
{
    /**
     * Sincroniza automaticamente as permissões de páginas com os níveis de acesso.
     *
     * Este seed adiciona todas as páginas cadastradas a todos os níveis de acesso,
     * mas sem permissão (permission = 0). Isso garante que todas as páginas apareçam
     * na tela de permissões para serem liberadas manualmente.
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
                
                // Inserir permissão de forma idempotente usando INSERT IGNORE para evitar erros de chave primária/única
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
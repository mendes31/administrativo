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
        
        // Array para armazenar os dados a serem inseridos
        $data = [];
        
        // Percorrer todos os níveis de acesso
        foreach ($accessLevels as $accessLevel) {
            $accessLevelId = $accessLevel['id'];
            
            // Percorrer todas as páginas
            foreach ($pages as $page) {
                $pageId = $page['id'];
                
                // Verificar se já existe a permissão para esta combinação
                $existingPermission = $this->query(
                    'SELECT id FROM adms_access_levels_pages WHERE adms_access_level_id = ? AND adms_page_id = ?',
                    [$accessLevelId, $pageId]
                )->fetch();
                
                // Se não existir, adicionar ao array de dados
                if (!$existingPermission) {
                    $data[] = [
                        'permission' => $accessLevelId == 1 ? 1 : 0, // Super Admin tem permissão total
                        'adms_access_level_id' => $accessLevelId,
                        'adms_page_id' => $pageId,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
            }
        }
        
        // Se houver dados para inserir
        if (!empty($data)) {
            $table = $this->table('adms_access_levels_pages');
            $table->insert($data)->save();
            
            echo "✅ Sincronização automática concluída!\n";
            echo "📋 " . count($data) . " permissões foram adicionadas.\n";
        } else {
            echo "ℹ️  Todas as permissões já estão sincronizadas.\n";
        }
    }
} 
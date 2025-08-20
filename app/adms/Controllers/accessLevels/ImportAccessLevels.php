<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\AccessLevelsPagesRepository;

class ImportAccessLevels
{
    private array|string|null $data = null;

    public function index(string $action = ''): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            return;
        }

        if ($action === 'template') {
            $this->template();
            return;
        }

        $this->data['form'] = $_POST ?? [];

        if (!empty($_FILES['file']) && isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_import_access_levels', $this->data['form']['csrf_token'])) {
            $this->processFile();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Importar Níveis de Acesso',
            'menu' => 'list-access-levels',
            'buttonPermission' => ['ListAccessLevels'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/accessLevels/importAccessLevels', $this->data);
        $loadView->loadView();
    }

    private function processFile(): void
    {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Falha ao enviar o arquivo.';
            $this->view();
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv'], true)) {
            $this->data['errors'][] = 'Formato inválido. Envie um arquivo CSV (recomendado).';
            $this->view();
            return;
        }

        $handled = $this->processCsv($file['tmp_name']);
        if (!$handled) {
            $this->data['errors'][] = 'Não foi possível processar o arquivo. Verifique o template.';
        }
        $this->view();
    }

    private function processCsv(string $tmpPath): bool
    {
        $fp = fopen($tmpPath, 'r');
        if (!$fp) return false;

        // Autodetectar separador e normalizar para UTF-8
        $probe = fgets($fp);
        if ($probe === false) { fclose($fp); return false; }
        $countSemicolon = substr_count($probe, ';');
        $countComma = substr_count($probe, ',');
        $delimiter = $countSemicolon >= $countComma ? ';' : ',';
        rewind($fp);

        $header = fgetcsv($fp, 0, $delimiter);
        if (!$header) { fclose($fp); return false; }
        $encodingFrom = 'UTF-8, ISO-8859-1, Windows-1252';
        $header = array_map(fn($v) => mb_convert_encoding((string)$v, 'UTF-8', $encodingFrom), $header);

        // Cabeçalhos esperados - agora inclui permissões
        $expected = ['name', 'permissions'];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header, true);
            $map[$col] = $idx !== false ? (int)$idx : null;
        }

        $repo = new AccessLevelsRepository();
        $created = 0; $updated = 0; $skipped = 0; $errors = 0; $rows = 1;
        $this->data['report'] = [];

        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            foreach ($row as &$val) { $val = mb_convert_encoding((string)$val, 'UTF-8', $encodingFrom); }
            unset($val);
            $rows++;
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;

            $name = trim((string)($row[$map['name']] ?? ''));
            if ($name === '') { $skipped++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'ignorado','msg'=>'Nome vazio']; continue; }

            try {
                $existing = $repo->getByName($name);
                if ($existing) {
                    // Nível já existe - ATUALIZAR permissões
                    $newPermissions = $this->processPermissions($row[$map['permissions']] ?? '1');
                    
                    // Sempre incluir Dashboard (ID 1) se não estiver nas permissões
                    if (!in_array(1, $newPermissions)) {
                        $newPermissions[] = 1;
                    }
                    
                    // Obter permissões existentes
                    $accessLevelsPagesRepo = new AccessLevelsPagesRepository();
                    $existingPermissions = $accessLevelsPagesRepo->getPagesAccessLevelsArray((int)$existing['id']);
                    
                    // Remover permissões antigas primeiro
                    $accessLevelsPagesRepo->removeAllPermissionsByAccessLevel((int)$existing['id']);
                    
                    // Criar permissões completas: 1 para páginas permitidas, 0 para as demais
                    $this->createCompletePermissions((int)$existing['id'], $newPermissions);
                    
                    $updated++;
                    $this->data['report'][] = [
                        'linha'=>$rows,
                        'acao'=>'atualizado',
                        'msg'=>'Permissões atualizadas: ' . count($existingPermissions ?: []) . ' → ' . count($newPermissions)
                    ];
                } else {
                    // Nível não existe - CRIAR NOVO
                    $okId = $repo->createAccessLevel(['name'=>$name]);
                    if ($okId) { 
                        // Processar permissões do CSV
                        $permissions = $this->processPermissions($row[$map['permissions']] ?? '1');
                        
                        // Sempre incluir Dashboard (ID 1) se não estiver nas permissões
                        if (!in_array(1, $permissions)) {
                            $permissions[] = 1;
                        }
                        
                        // Criar permissões completas: 1 para páginas permitidas, 0 para as demais
                        $this->createCompletePermissions($okId, $permissions);
                        
                        $created++; 
                        $this->data['report'][] = ['linha'=>$rows,'acao'=>'criado','msg'=>'Nível criado com ' . count($permissions) . ' permissões']; 
                    }
                    else { $errors++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'erro','msg'=>'Falha ao criar']; }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->data['report'][] = ['linha'=>$rows,'acao'=>'erro','msg'=>$e->getMessage()];
                GenerateLog::generateLog('error','Falha ao importar nível de acesso.', ['name'=>$name, 'e'=>$e->getMessage()]);
            }
        }
        fclose($fp);

        $this->data['summary'] = compact('created','updated','skipped','errors');
        $_SESSION['success'] = "Importação concluída: criados {$created}, atualizados {$updated}, ignorados {$skipped}, erros {$errors}.";
        return true;
    }

    /**
     * Processa as permissões do CSV e retorna array de IDs válidos
     */
    private function processPermissions(string $permissionsStr): array
    {
        if (empty($permissionsStr)) {
            return [1]; // Apenas Dashboard por padrão
        }

        // Tratar caso especial "ALL" para Super Administrador
        if (strtoupper(trim($permissionsStr)) === 'ALL') {
            return ['ALL']; // Marca especial para permissão total
        }

        // Separar IDs por vírgula e converter para inteiros
        $permissions = array_map('intval', explode(',', $permissionsStr));
        
        // Filtrar apenas IDs válidos (maiores que 0)
        $permissions = array_filter($permissions, fn($id) => $id > 0);
        
        // Se não houver permissões válidas, retornar apenas Dashboard
        if (empty($permissions)) {
            return [1];
        }

        return array_values($permissions);
    }

    /**
     * Cria permissões completas para um nível de acesso:
     * - permission = 1 para páginas permitidas (incluindo Dashboard)
     * - permission = 0 para todas as outras páginas
     * 
     * @param int $accessLevelId ID do nível de acesso
     * @param array $allowedPageIds Array com IDs das páginas permitidas
     */
    private function createCompletePermissions(int $accessLevelId, array $allowedPageIds): void
    {
        // Se for o nível 1 (Super Administrador), dar permissão total
        if ($accessLevelId === 1) {
            $this->createSuperAdminPermissions($accessLevelId);
            return;
        }

        // Para outros níveis, criar permissões específicas
        $accessLevelsPagesRepo = new AccessLevelsPagesRepository();
        
        // Obter todas as páginas do sistema
        $pagesRepo = new \App\adms\Models\Repository\PagesRepository();
        $allPages = $pagesRepo->getAllPagesFull();
        
        // Preparar dados para inserção em massa
        $data = [];
        foreach ($allPages as $page) {
            $pageId = $page['id'];
            $permission = in_array($pageId, $allowedPageIds) ? 1 : 0;
            
            $data[] = [
                'permission' => $permission,
                'adms_access_level_id' => $accessLevelId,
                'adms_page_id' => $pageId,
                'created_at' => date("Y-m-d H:i:s")
            ];
        }
        
        // Inserir todas as permissões de uma vez
        $this->insertPermissionsInBulk($data);
    }

    /**
     * Cria permissões totais para o Super Administrador (nível 1)
     * 
     * @param int $accessLevelId ID do nível de acesso (deve ser 1)
     */
    private function createSuperAdminPermissions(int $accessLevelId): void
    {
        if ($accessLevelId !== 1) {
            throw new \Exception("Método createSuperAdminPermissions só pode ser usado para nível 1");
        }

        $accessLevelsPagesRepo = new AccessLevelsPagesRepository();
        
        // Obter todas as páginas do sistema
        $pagesRepo = new \App\adms\Models\Repository\PagesRepository();
        $allPages = $pagesRepo->getAllPagesFull();
        
        // Preparar dados para inserção em massa (todas com permission = 1)
        $data = [];
        foreach ($allPages as $page) {
            $data[] = [
                'permission' => 1, // Super Admin tem permissão total
                'adms_access_level_id' => $accessLevelId,
                'adms_page_id' => $page['id'],
                'created_at' => date("Y-m-d H:i:s")
            ];
        }
        
        // Inserir todas as permissões de uma vez
        $this->insertPermissionsInBulk($data);
    }

    /**
     * Insere permissões em massa no banco de dados
     * 
     * @param array $data Array com dados das permissões
     */
    private function insertPermissionsInBulk(array $data): void
    {
        if (empty($data)) {
            return;
        }

        try {
            $pdo = (new \App\adms\Models\Repository\AccessLevelsPagesRepository())->getConnection();
            $pdo->beginTransaction();

            // Preparar query de inserção em massa
            $sql = "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at) VALUES ";
            $placeholders = [];
            $values = [];

            foreach ($data as $row) {
                $placeholders[] = "(?, ?, ?, ?)";
                $values[] = $row['permission'];
                $values[] = $row['adms_access_level_id'];
                $values[] = $row['adms_page_id'];
                $values[] = $row['created_at'];
            }

            $sql .= implode(", ", $placeholders);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $pdo->commit();
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function template(): void
    {
        $filename = 'template_importacao_niveis_acesso.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        
        // Cabeçalho com permissões
        fputcsv($out, ['name', 'permissions'], ';');
        
        // Exemplos com permissões
        fputcsv($out, ['Super Administrador', 'ALL'], ';'); // Nível 1 - permissão total
        fputcsv($out, ['Líder de Equipe', '1,2,3,4,5,8,29,30,31,32,35,36,37,38'], ';');
        fputcsv($out, ['Analista RH', '1,2,4,29,30,31,35,36,37'], ';');
        fputcsv($out, ['Gerente Administrativo', '1,2,3,4,5,6,8,29,30,31,32,33,35,36,37,38,39'], ';');
        fputcsv($out, ['Supervisor', '1,2,4,5,29,30,31,32,35,36,37,38'], ';');
        fputcsv($out, ['Coordenador', '1,2,3,4,5,8,13,14,15,16,29,30,31,32,35,36,37,38'], ';');
        fputcsv($out, ['Assistente', '1,2,4,29,30,31,35,36,37'], ';');
        fputcsv($out, ['Diretor', '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40'], ';');
        
        fclose($out);
        exit;
    }
}



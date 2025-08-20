<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\PagesRepository;
use Mpdf\Mpdf;
use Exception;

/**
 * Controller responsável pela exportação de permissões de nível de acesso para PDF.
 * 
 * Exporta:
 * - Permissões organizadas por grupos
 * - Usuários ativos vinculados ao nível
 * - Resumo estatístico
 */
class ExportAccessLevelPermissionsPdf
{
    private AccessLevelsRepository $accessLevelsRepo;
    private AccessLevelsPagesRepository $accessLevelsPagesRepo;
    private UsersAccessLevelsRepository $usersAccessLevelsRepo;
    private PagesRepository $pagesRepo;

    public function __construct()
    {
        $this->accessLevelsRepo = new AccessLevelsRepository();
        $this->accessLevelsPagesRepo = new AccessLevelsPagesRepository();
        $this->usersAccessLevelsRepo = new UsersAccessLevelsRepository();
        $this->pagesRepo = new PagesRepository();
    }

    /**
     * Método padrão - exporta permissões de um nível específico.
     */
    public function index(string $accessLevelId = null): void
    {
        if (!$accessLevelId) {
            $this->showError("ID do nível de acesso não fornecido");
            return;
        }

        try {
            $this->exportAccessLevelPermissions((int)$accessLevelId);
        } catch (Exception $e) {
            error_log("ExportAccessLevelPermissionsPdf: Erro capturado: " . $e->getMessage());
            $this->showError("Erro ao gerar PDF: " . $e->getMessage());
        }
    }

    /**
     * Exporta permissões de um nível de acesso específico para PDF.
     */
    private function exportAccessLevelPermissions(int $accessLevelId): void
    {
        // Limpar buffer
        if (ob_get_length()) ob_end_clean();
        
        // Definir headers para PDF
        header('Content-Type: application/pdf');

        // Recuperar dados do nível de acesso
        $accessLevel = $this->accessLevelsRepo->getAccessLevel($accessLevelId);
        if (!$accessLevel) {
            throw new Exception("Nível de acesso não encontrado");
        }

        // Recuperar permissões
        $permissions = $this->accessLevelsPagesRepo->getPagesAccessLevelsArray($accessLevelId, true);
        
        // Recuperar todas as páginas para agrupar
        $allPages = $this->pagesRepo->getAllPagesFull();
        
        // Recuperar usuários vinculados
        $users = $this->usersAccessLevelsRepo->getUsersByAccessLevel($accessLevelId);

        // Gerar HTML
        $html = $this->generatePermissionsHtml($accessLevel, $permissions, $allPages, $users);

        // Configurar mPDF mantendo o mesmo layout/HTML
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => sys_get_temp_dir()
        ]);
        $mpdf->showImageErrors = true;
        $mpdf->SetDisplayMode('fullwidth');
        $mpdf->SetTitle('Permissões - ' . $accessLevel['name']);
        $mpdf->WriteHTML($html);

        // Output do PDF (visualizar no navegador)
        $filename = "Permissoes_" . preg_replace('/[^a-zA-Z0-9]/', '_', $accessLevel['name']) . "_" . date('Y-m-d') . ".pdf";
        $mpdf->Output($filename, 'I');
        exit;
    }

    /**
     * Gera HTML para o relatório de permissões.
     */
    private function generatePermissionsHtml(array $accessLevel, array $permissions, array $allPages, array $users): string
    {
        // Agrupar páginas por categoria
        $groupedPages = $this->groupPagesByCategory($allPages, $permissions);
        
        // Estatísticas
        $totalPages = count($allPages);
        $totalPermissions = count($permissions);
        $totalUsers = count($users);

        $html = '<div style="font-family: Arial, sans-serif; margin: 20px; background: #ffffff;">';
        
        // Header com logo real
        $html .= '<div style="text-align: center; border: 2px solid #0A7C35; border-radius: 8px; padding: 15px; margin-bottom: 25px; background: #ffffff;">';
        $html .= '<div style="display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">';
        $html .= '<img src="' . $this->getLogoDataUri() . '" style="height: 50px; width: auto; margin-right: 0;" alt="Logo TIARAJU">';
        $html .= '</div>';
        $html .= '<div style="font-size: 14px; color: #6c757d; margin-bottom: 5px;">Sistema Administrativo</div>';
        $html .= '<div style="font-size: 14px; color: #6c757d;">Relatório de Permissões de Nível de Acesso</div>';
        $html .= '</div>';
        
        // Nome do nível de acesso com cor verde
        $html .= '<div style="text-align: center; background: #0A7C35; color: #ffffff; padding: 15px; margin-bottom: 25px; border-radius: 8px; font-size: 18px; font-weight: bold;">';
        $html .= htmlspecialchars($accessLevel['name']);
        $html .= '</div>';
        
        // Cards de estatísticas - todos na mesma linha usando tabela
        $html .= '<div style="margin-bottom: 30px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; border: none;">';
        $html .= '<tr>';
        
        $html .= '<td style="width: 25%; padding: 0 5px; vertical-align: top;">';
        $html .= '<div style="background: #ffffff; border: 1px solid #e9ecef; padding: 20px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #0A7C35; margin-bottom: 8px;">' . $totalPages . '</div>';
        $html .= '<div style="font-size: 12px; color: #6c757d; text-transform: uppercase; font-weight: 500;">Total de Páginas</div>';
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '<td style="width: 25%; padding: 0 5px; vertical-align: top;">';
        $html .= '<div style="background: #ffffff; border: 1px solid #e9ecef; padding: 20px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #0A7C35; margin-bottom: 8px;">' . $totalPermissions . '</div>';
        $html .= '<div style="font-size: 12px; color: #6c757d; text-transform: uppercase; font-weight: 500;">Permissões Ativas</div>';
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '<td style="width: 25%; padding: 0 5px; vertical-align: top;">';
        $html .= '<div style="background: #ffffff; border: 1px solid #e9ecef; padding: 20px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #0A7C35; margin-bottom: 8px;">' . $totalUsers . '</div>';
        $html .= '<div style="font-size: 12px; color: #6c757d; text-transform: uppercase; font-weight: 500;">Usuários Vinculados</div>';
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '<td style="width: 25%; padding: 0 5px; vertical-align: top;">';
        $html .= '<div style="background: #ffffff; border: 1px solid #e9ecef; padding: 20px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #0A7C35; margin-bottom: 8px;">' . round(($totalPermissions / $totalPages) * 100, 1) . '%</div>';
        $html .= '<div style="font-size: 12px; color: #6c757d; text-transform: uppercase; font-weight: 500;">Cobertura</div>';
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Seção de permissões com cor verde
        $html .= '<div style="margin-bottom: 30px;">';
        $html .= '<div style="background: #0A7C35; color: #ffffff; padding: 15px; border-radius: 8px 8px 0 0; font-size: 16px; font-weight: bold;">Permissões por Grupo</div>';
        $html .= $this->generateGroupsHtml($groupedPages);
        $html .= '</div>';
        
        // Seção de usuários com cor verde
        $html .= '<div style="margin-bottom: 30px;">';
        $html .= '<div style="background: #0A7C35; color: #ffffff; padding: 15px; border-radius: 8px 0 0; font-size: 16px; font-weight: bold;">Usuários Vinculados</div>';
        $html .= $this->generateUsersHtml($users);
        $html .= '</div>';
        
        // Footer com cor azul claro
        $html .= '<div style="text-align: center; border-top: 2px solid #0A7C35; padding: 20px; background: #BEDFF0; border-radius: 8px; margin-top: 30px;">';
        $html .= '<p style="margin: 5px 0; font-size: 12px; color: #0A7C35;">Relatório gerado em ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '<p style="margin: 5px 0; font-size: 12px; color: #0A7C35;">Sistema Tiaraju - Administrativo</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Agrupa páginas por categoria.
     */
    private function groupPagesByCategory(array $allPages, array $permissions): array
    {
        $grouped = [];
        
        foreach ($allPages as $page) {
            $groupName = $page['agp_name'] ?? 'Sem Grupo';
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'pages' => []
                ];
            }
            
            $grouped[$groupName]['pages'][] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'allowed' => isset($permissions[$page['id']])
            ];
        }
        
        // Ordenar por nome do grupo
        ksort($grouped);
        
        return $grouped;
    }

    /**
     * Gera HTML para os grupos de páginas.
     */
    private function generateGroupsHtml(array $groupedPages): string
    {
        if (empty($groupedPages)) {
            return '<div style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">Nenhuma página encontrada</div>';
        }

        $html = '';
        
        foreach ($groupedPages as $groupName => $groupData) {
            $pages = $groupData['pages'];
            $totalPages = count($pages);
            $allowedPages = count(array_filter($pages, fn($p) => $p['allowed']));
            $deniedPages = $totalPages - $allowedPages;
            
            $html .= '<div style="border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">';
            
                         // Cabeçalho do grupo com cor azul claro
             $html .= '<div style="background: #BEDFF0; padding: 12px 15px; border-bottom: 1px solid #dee2e6; font-weight: bold; color: #0A7C35; font-size: 14px;">';
            $html .= htmlspecialchars($groupName) . ' - Total: ' . $totalPages . ' | Autorizadas: ' . $allowedPages . ' | Revogadas: ' . $deniedPages;
            $html .= '</div>';
            
            // Lista de páginas
            $html .= '<div style="padding: 0;">';
            $html .= '<table style="width: 100%; border-collapse: collapse; border: none;">';
            $html .= '<tbody>';
            
            foreach ($pages as $index => $page) {
                $statusClass = $page['allowed'] ? 'status-allowed' : 'status-denied';
                $statusText = $page['allowed'] ? 'Permitido' : 'Negado';
                $rowBg = $index % 2 === 0 ? '#ffffff' : '#f8f9fa';
                
                $html .= '<tr style="background: ' . $rowBg . ';">';
                $html .= '<td style="padding: 12px 15px; border: none; width: 75%; font-size: 12px; color: #2c3e50; font-weight: 500;">';
                $html .= htmlspecialchars($page['name']);
                $html .= '</td>';
                $html .= '<td style="padding: 12px 15px; border: none; width: 25%; text-align: center;">';
                $html .= '<span style="padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; ';
                $html .= $page['allowed'] ? 'background: #e8f5e8; color: #2d5a2d; border: 1px solid #c3e6c3;' : 'background: #f8e8e8; color: #5a2d2d; border: 1px solid #e6c3c3;';
                $html .= '">' . $statusText . '</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        return $html;
    }

    /**
     * Gera HTML para a lista de usuários.
     */
    private function generateUsersHtml(array $users): string
    {
        if (empty($users)) {
            return '<div style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">Nenhum usuário vinculado a este nível de acesso</div>';
        }

        $html = '<div style="padding: 0;">';
        
        foreach ($users as $index => $user) {
            $rowBg = $index % 2 === 0 ? '#ffffff' : '#f8f9fa';
            
            $html .= '<div style="background: ' . $rowBg . '; padding: 15px; border-bottom: 1px solid #f1f3f4; border-radius: 4px; margin-bottom: 8px;">';
            $html .= '<div style="font-weight: bold; color: #2c3e50; font-size: 14px; margin-bottom: 5px;">';
            $html .= htmlspecialchars($user['name']);
            $html .= '</div>';
            $html .= '<div style="font-size: 12px; color: #6c757d; line-height: 1.4;">';
            $html .= htmlspecialchars($user['email']) . ' | ' . htmlspecialchars($user['username']) . ' | ' . htmlspecialchars($user['department_name']) . ' | ' . htmlspecialchars($user['position_name']);
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Obtém o caminho correto para o logo baseado no ambiente.
     */
    private function getLogoPath(): string
    {
        // Tentar diferentes caminhos baseados no ambiente
        $possiblePaths = [
            // Ambiente local (WAMP/XAMPP)
            $_SERVER['DOCUMENT_ROOT'] . '/administrativo/public/adms/image/logo/logo.png',
            // Ambiente de hospedagem (subdiretório)
            $_SERVER['DOCUMENT_ROOT'] . '/public/adms/image/logo/logo.png',
            // Ambiente de hospedagem (raiz)
            $_SERVER['DOCUMENT_ROOT'] . '/administrativo/public/adms/image/logo/logo.png',
            // Caminho relativo ao diretório atual
            dirname(__FILE__) . '/../../../../public/adms/image/logo/logo.png',
            // Caminho absoluto padrão
            'C:/wamp64/www/administrativo/public/adms/image/logo/logo.png'
        ];
        
        // Verificar qual caminho existe
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Se nenhum caminho funcionar, retornar o padrão
        return $possiblePaths[0];
    }

    /**
     * Retorna a logo como data URI (base64) para garantir carregamento em qualquer ambiente.
     */
    private function getLogoDataUri(): string
    {
        $path = $this->getLogoPath();
        if (!file_exists($path)) {
            return '';
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return '';
        }
        $mime = 'image/' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $base64 = base64_encode($data);
        return 'data:' . $mime . ';base64,' . $base64;
    }

    /**
     * Exibe erro amigável.
     */
    private function showError(string $message): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Erro ao Gerar PDF</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; background: #f8f9fa; }
                .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
                .btn { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                .btn:hover { background-color: #0056b3; }
            </style>
        </head>
        <body>
            <h1 style='text-align: center; color: #2c3e50;'>Erro ao Gerar PDF</h1>
            <div class='error'>
                <p><strong>Erro:</strong> {$message}</p>
            </div>
            <div style='text-align: center;'>
                <a href='javascript:history.back()' class='btn'>Voltar</a>
            </div>
        </body>
        </html>";
        exit;
    }
}

<?php

namespace App\adms\Controllers\dashboard;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class Dashboard
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    public function index()
    {
        $this->data['user_name'] = $_SESSION['user_name'] ?? 'Usuário';

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $informativosRepo = new InformativosRepository();
        $informativos = $informativosRepo->getInformativosDashboard(50);
        $this->data['informativos'] = $informativos;
        $this->data['informativos_urgentes'] = $informativosRepo->countInformativosUrgentes();
        $this->data['informativos_ativos'] = count(array_filter($informativos, fn($i) => $i['ativo']));

        // Categorias dos informativos
        $categorias = [];
        foreach ($informativos as $info) {
            $cat = $info['categoria'] ?? 'Geral';
            if (!isset($categorias[$cat])) {
                $categorias[$cat] = [
                    'count' => 0,
                    'imagem' => null,
                    'has_anexo' => false
                ];
            }
            $categorias[$cat]['count']++;
            if (!$categorias[$cat]['imagem'] && !empty($info['imagem'])) {
                $categorias[$cat]['imagem'] = $info['imagem'];
            }
            if (!$categorias[$cat]['has_anexo'] && !empty($info['anexo'])) {
                $categorias[$cat]['has_anexo'] = true;
            }
        }
        $this->data['categorias_informativos'] = $categorias;

        // Buscar aniversariantes do mês (com departamento)
        $usersRepo = new UsersRepository();
        $mesAtual = date('m');
        $sql = 'SELECT u.id, u.name, u.image, u.user_department_id, u.user_position_id, DATE_FORMAT(u.data_nascimento, "%d/%m") as aniversario, u.data_nascimento, d.name as departamento
                FROM adms_users u
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE u.status = 1 AND MONTH(u.data_nascimento) = :mes
                ORDER BY DAY(u.data_nascimento) ASC';
        $stmt = $usersRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':mes', $mesAtual, \PDO::PARAM_INT);
        $stmt->execute();
        $aniversariantes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // Ajuste: normalizar caminho da imagem do usuário e validar existência
        foreach ($aniversariantes as &$aniv) {
            if (empty($aniv['image'])) {
                $aniv['image'] = null;
                continue;
            }
            $baseUploads = 'public/adms/uploads/';
            $hasSubdir = strpos($aniv['image'], '/') !== false || strpos($aniv['image'], '\\') !== false;
            $relativePath = $hasSubdir ? $aniv['image'] : ('users/' . $aniv['id'] . '/' . $aniv['image']);
            if (file_exists($baseUploads . $relativePath)) {
                $aniv['image'] = $relativePath;
            } else {
                $aniv['image'] = null;
            }
        }
        unset($aniv);
        $this->data['aniversariantes_mes'] = $aniversariantes;
        $this->data['qtd_aniversariantes_mes'] = count($aniversariantes);

        $pageElements = [
            'title_head' => 'Dashboard',
            'menu' => 'dashboard',
            'buttonPermission' => [],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/dashboard/dashboard", $this->data);
        $loadView->loadView();
    }
      
}
<?php

namespace App\adms\Controllers\dashboard;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Services\CandidateRetentionService;
use App\adms\Models\Services\InformativosStatusUpdaterService;

class Dashboard
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    public function index()
    {
        // Atualizar retenção/anomização de currículos (LGPD) no primeiro acesso do dia
        CandidateRetentionService::ensureUpdated();

        // Atualizar status de informativos (publicação/expiração) no primeiro acesso (com janela de 10 minutos)
        InformativosStatusUpdaterService::ensureUpdated();

        $this->data['user_name'] = $_SESSION['user_name'] ?? 'Usuário';

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $informativosRepo = new InformativosRepository();
        $informativos = $informativosRepo->getInformativosDashboard(50);
        $this->data['informativos'] = $informativos;
        $this->data['informativos_ativos'] = count(array_filter($informativos, fn($i) => $i['ativo']));

        // Políticas Internas para card de destaque
        $policiesRepo = new PoliciesRepository();
        $policiesDashboard = $policiesRepo->getPoliciesDashboard(50);
        $this->data['policies_dashboard'] = $policiesDashboard;
        $this->data['policies_urgentes'] = $policiesRepo->countPoliciesUrgentes();
        $this->data['policies_ativas'] = count(array_filter($policiesDashboard, fn($p) => $p['ativo']));

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

        // Buscar aniversariantes do mês (data de nascimento) e aniversários de empresa (data de admissão)
        $usersRepo = new UsersRepository();
        $mesAtual = date('m');

        // Aniversário de nascimento
        $sql = 'SELECT u.id, u.name, u.image, u.user_department_id, u.user_position_id,
                       DATE_FORMAT(u.data_nascimento, "%d/%m") as aniversario,
                       u.data_nascimento,
                       d.name as departamento
                FROM adms_users u
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE u.status = 1 AND MONTH(u.data_nascimento) = :mes
                ORDER BY DAY(u.data_nascimento) ASC';
        $stmt = $usersRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':mes', $mesAtual, \PDO::PARAM_INT);
        $stmt->execute();
        $aniversariantes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Aniversário de empresa (data de admissão)
        $sqlEmpresa = 'SELECT u.id, u.name, u.image, u.user_department_id, u.user_position_id,
                              DATE_FORMAT(u.data_admissao, "%d/%m") as aniversario_empresa,
                              u.data_admissao,
                              d.name as departamento
                       FROM adms_users u
                       LEFT JOIN adms_departments d ON u.user_department_id = d.id
                       WHERE u.status = 1
                         AND u.data_admissao IS NOT NULL
                         AND MONTH(u.data_admissao) = :mes
                       ORDER BY DAY(u.data_admissao) ASC';
        $stmtEmpresa = $usersRepo->getConnection()->prepare($sqlEmpresa);
        $stmtEmpresa->bindValue(':mes', $mesAtual, \PDO::PARAM_INT);
        $stmtEmpresa->execute();
        $aniversariantesEmpresa = $stmtEmpresa->fetchAll(\PDO::FETCH_ASSOC);

        // Ajuste: normalizar valor da coluna image e ignorar imagens padrão
        foreach ([$aniversariantes, $aniversariantesEmpresa] as &$listaRef) {
            foreach ($listaRef as &$aniv) {
                if (empty($aniv['image'])) {
                    $aniv['image'] = null;
                    continue;
                }

                // Se for a imagem padrão (em qualquer formato de caminho), trata como "sem imagem"
                $basename = basename((string)$aniv['image']);
                if ($basename === 'icon_user.png') {
                    $aniv['image'] = null;
                }
            }
            unset($aniv);
        }
        unset($listaRef);

        // Calcular anos de casa para aniversários de empresa
        $anoAtual = (int)date('Y');
        foreach ($aniversariantesEmpresa as &$anivEmp) {
            $anos = null;
            if (!empty($anivEmp['data_admissao'])) {
                $anoAdm = (int)date('Y', strtotime($anivEmp['data_admissao']));
                if ($anoAdm > 0 && $anoAtual >= $anoAdm) {
                    $anos = max(0, $anoAtual - $anoAdm);
                }
            }
            $anivEmp['anos_empresa'] = $anos;
        }
        unset($anivEmp);

        $this->data['aniversariantes_mes'] = $aniversariantes;
        $this->data['qtd_aniversariantes_mes'] = count($aniversariantes);
        $this->data['aniversariantes_empresa_mes'] = $aniversariantesEmpresa;
        $this->data['qtd_aniversariantes_empresa_mes'] = count($aniversariantesEmpresa);

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
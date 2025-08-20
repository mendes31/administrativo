<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiaRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pelo dashboard de Testes de Impacto às Atividades (TIA).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaDashboard
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    /** @var LgpdRopaRepository $ropaRepo */
    private LgpdRopaRepository $ropaRepo;

    /** @var DepartmentsRepository $deptRepo */
    private DepartmentsRepository $deptRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
        $this->ropaRepo = new LgpdRopaRepository();
        $this->deptRepo = new DepartmentsRepository();
    }

    /**
     * Método principal para exibir o dashboard TIA.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Carregar estatísticas gerais
            $this->data['estatisticas'] = $this->tiaRepo->getEstatisticas();
            
            // Carregar dados para gráficos
            $this->data['risco_por_departamento'] = $this->getRiscoPorDepartamento();
            $this->data['status_por_mes'] = $this->getStatusPorMes();
            $this->data['tias_recentes'] = $this->getTiasRecentes();
            $this->data['tias_pendentes'] = $this->getTiasPendentes();
            $this->data['departamentos_ativos'] = $this->getDepartamentosAtivos();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Dashboard TIA - Testes de Impacto às Atividades',
                'menu' => 'LgpdTiaDashboard',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate', 'LgpdTiaEdit', 'LgpdTiaView', 'LgpdTiaDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/dashboard", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTiaDashboard: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar dashboard TIA!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-dashboard");
            exit;
        }
    }

    /**
     * Obtém estatísticas de risco por departamento.
     *
     * @return array
     */
    private function getRiscoPorDepartamento(): array
    {
        try {
            $query = "SELECT 
                        d.name as departamento,
                        COUNT(CASE WHEN t.resultado = 'Baixo Risco' THEN 1 END) as baixo_risco,
                        COUNT(CASE WHEN t.resultado = 'Médio Risco' THEN 1 END) as medio_risco,
                        COUNT(CASE WHEN t.resultado = 'Alto Risco' THEN 1 END) as alto_risco,
                        COUNT(CASE WHEN t.resultado = 'Necessita AIPD' THEN 1 END) as necessita_aipd,
                        COUNT(*) as total
                      FROM lgpd_tia t
                      JOIN adms_departments d ON t.departamento_id = d.id
                      GROUP BY d.id, d.name
                      ORDER BY total DESC";
            
            $stmt = $this->tiaRepo->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar risco por departamento: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém estatísticas de status por mês.
     *
     * @return array
     */
    private function getStatusPorMes(): array
    {
        try {
            $query = "SELECT 
                        DATE_FORMAT(t.created_at, '%Y-%m') as mes,
                        COUNT(CASE WHEN t.status = 'Em Andamento' THEN 1 END) as em_andamento,
                        COUNT(CASE WHEN t.status = 'Concluído' THEN 1 END) as concluido,
                        COUNT(CASE WHEN t.status = 'Aprovado' THEN 1 END) as aprovado,
                        COUNT(*) as total
                      FROM lgpd_tia t
                      WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
                      ORDER BY mes DESC";
            
            $stmt = $this->tiaRepo->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar status por mês: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém testes TIA recentes.
     *
     * @return array
     */
    private function getTiasRecentes(): array
    {
        try {
            $query = "SELECT 
                        t.id,
                        t.codigo,
                        t.titulo,
                        t.resultado,
                        t.status,
                        d.name as departamento_nome
                      FROM lgpd_tia t
                      JOIN adms_departments d ON t.departamento_id = d.id
                      ORDER BY t.created_at DESC
                      LIMIT 10";
            
            $stmt = $this->tiaRepo->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar TIA recentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém testes TIA pendentes.
     *
     * @return array
     */
    private function getTiasPendentes(): array
    {
        try {
            $query = "SELECT 
                        t.id,
                        t.codigo,
                        t.titulo,
                        t.departamento_id,
                        t.data_teste,
                        d.name as departamento_nome
                      FROM lgpd_tia t
                      JOIN adms_departments d ON t.departamento_id = d.id
                      WHERE t.status = 'Em Andamento'
                      ORDER BY t.data_teste ASC
                      LIMIT 10";
            
            $stmt = $this->tiaRepo->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar TIA pendentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém departamentos com atividades TIA.
     *
     * @return array
     */
    private function getDepartamentosAtivos(): array
    {
        try {
            $query = "SELECT 
                        d.name as departamento,
                        COUNT(t.id) as total_tias,
                        COUNT(CASE WHEN t.status = 'Em Andamento' THEN 1 END) as em_andamento,
                        COUNT(CASE WHEN t.status = 'Concluído' THEN 1 END) as concluido,
                        COUNT(CASE WHEN t.status = 'Aprovado' THEN 1 END) as aprovado
                      FROM adms_departments d
                      LEFT JOIN lgpd_tia t ON d.id = t.departamento_id
                      GROUP BY d.id, d.name
                      HAVING total_tias > 0
                      ORDER BY total_tias DESC";
            
            $stmt = $this->tiaRepo->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar departamentos ativos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém dados para gráficos via AJAX.
     *
     * @return void
     */
    public function getChartData(): void
    {
        header('Content-Type: application/json');
        
        try {
            $data = [
                'risco_por_departamento' => $this->getRiscoPorDepartamento(),
                'status_por_mes' => $this->getStatusPorMes(),
                'departamentos_ativos' => $this->getDepartamentosAtivos()
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar dados dos gráficos'
            ]);
        }
    }
}

<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdDashboardRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pelo Dashboard LGPD.
 *
 * Esta classe gerencia a exibição do dashboard principal do módulo LGPD,
 * apresentando indicadores, métricas e estatísticas de compliance.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdDashboard
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdDashboardRepository $dashboardRepo */
    private LgpdDashboardRepository $dashboardRepo;

    public function __construct()
    {
        $this->dashboardRepo = new LgpdDashboardRepository();
    }

    /**
     * Método principal do dashboard LGPD.
     *
     * @return void
     */
    public function index(): void
    {
        // Carregar todos os indicadores do dashboard
        $this->data['indicadores'] = $this->dashboardRepo->getIndicadores();
        
        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Dashboard LGPD',
            'menu' => 'lgpd-dashboard',
            'buttonPermission' => ['LgpdDashboard'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/dashboard", $this->data);
        $loadView->loadView();
    }

    /**
     * Método para obter dados do dashboard via AJAX.
     *
     * @return void
     */
    public function getData(): void
    {
        header('Content-Type: application/json');
        
        try {
            $indicadores = $this->dashboardRepo->getIndicadores();
            echo json_encode([
                'success' => true,
                'data' => $indicadores
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar dados do dashboard'
            ]);
        }
    }

    /**
     * Método para obter alertas e notificações.
     *
     * @return void
     */
    public function getAlertas(): void
    {
        header('Content-Type: application/json');
        
        try {
            $indicadores = $this->dashboardRepo->getIndicadores();
            $alertas = [];
            
            // Verificar pendências críticas
            if (!empty($indicadores['compliance']['pendencias_criticas'])) {
                foreach ($indicadores['compliance']['pendencias_criticas'] as $pendencia) {
                    $alertas[] = [
                        'tipo' => 'pendencia',
                        'titulo' => $pendencia['tipo'],
                        'mensagem' => "{$pendencia['quantidade']} item(s) pendente(s)",
                        'prioridade' => $pendencia['prioridade'],
                        'url' => $this->getUrlByPendencia($pendencia['tipo'])
                    ];
                }
            }
            
            // Verificar score de compliance baixo
            if ($indicadores['compliance']['score_geral'] < 70) {
                $alertas[] = [
                    'tipo' => 'compliance',
                    'titulo' => 'Score de Compliance Baixo',
                    'mensagem' => "Score atual: {$indicadores['compliance']['score_geral']}%",
                    'prioridade' => 'Alta',
                    'url' => $_ENV['URL_ADM'] . 'lgpd-dashboard'
                ];
            }
            
            // Verificar alto risco no inventário
            if ($indicadores['riscos']['percentual_alto_risco'] > 30) {
                $alertas[] = [
                    'tipo' => 'risco',
                    'titulo' => 'Alto Risco no Inventário',
                    'mensagem' => "{$indicadores['riscos']['percentual_alto_risco']}% dos dados são de alto risco",
                    'prioridade' => 'Alta',
                    'url' => $_ENV['URL_ADM'] . 'lgpd-inventory'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'alertas' => $alertas,
                'total_alertas' => count($alertas)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar alertas'
            ]);
        }
    }

    /**
     * Obter URL baseada no tipo de pendência.
     *
     * @param string $tipo
     * @return string
     */
    private function getUrlByPendencia(string $tipo): string
    {
        switch ($tipo) {
            case 'ROPA em Revisão':
                return $_ENV['URL_ADM'] . 'lgpd-ropa';
            case 'Incidentes Abertos':
                return $_ENV['URL_ADM'] . 'lgpd-incidentes';
            case 'Consentimentos Expirados':
                return $_ENV['URL_ADM'] . 'lgpd-consentimentos';
            default:
                return $_ENV['URL_ADM'] . 'lgpd-dashboard';
        }
    }
} 
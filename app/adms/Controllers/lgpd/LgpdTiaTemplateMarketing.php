<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Controller para template de TIA - Marketing
 */
class LgpdTiaTemplateMarketing
{
    /** @var array $data Recebe os dados que devem ser enviados para VIEW */
    private array $data = [];

    /** @var DepartmentsRepository $deptRepo */
    private DepartmentsRepository $deptRepo;

    /** @var UsersRepository $usersRepo */
    private UsersRepository $usersRepo;

    /**
     * Construtor da classe
     */
    public function __construct()
    {
        $this->deptRepo = new DepartmentsRepository();
        $this->usersRepo = new UsersRepository();
    }

    /**
     * Método principal para exibir template de TIA para Marketing.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $template = $this->getTemplateMarketing();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Marketing',
                'menu' => 'LgpdTiaTemplate',
                'menuPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();
            $this->data['usuarios'] = $this->usersRepo->getAllUsersSelect();

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/template-marketing", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro ao carregar template Marketing: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar template Marketing!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia-templates");
            exit;
        }
    }

    /**
     * Retorna template para Marketing.
     *
     * @return array
     */
    private function getTemplateMarketing(): array
    {
        return [
            'titulo' => 'Teste de Impacto às Atividades - Marketing',
            'descricao' => 'Avaliação de impacto à privacidade para operações de Marketing, incluindo campanhas, análise de comportamento e segmentação de público.',
            'checklist' => [
                'Coleta de dados para campanhas',
                'Segmentação de público-alvo',
                'Análise de comportamento online',
                'Gestão de leads e prospects',
                'Campanhas de email marketing',
                'Publicidade direcionada',
                'Análise de métricas e ROI',
                'Gestão de redes sociais'
            ],
            'riscos_identificados' => [
                'Coleta excessiva de dados pessoais',
                'Rastreamento de comportamento online',
                'Segmentação baseada em dados sensíveis',
                'Compartilhamento com plataformas de publicidade',
                'Retenção de dados de leads'
            ],
            'medidas_protecao' => [
                'Consentimento explícito para cookies',
                'Política de privacidade transparente',
                'Minimização de dados coletados',
                'Controle de preferências de marketing',
                'Procedimentos de exclusão de dados',
                'Treinamento em marketing responsável'
            ],
            'recomendacoes' => [
                'Implementar banner de cookies transparente',
                'Revisar políticas de privacidade',
                'Estabelecer procedimentos de exclusão de dados',
                'Treinar equipe em práticas de marketing responsável',
                'Monitorar compliance com regulamentações'
            ]
        ];
    }
}

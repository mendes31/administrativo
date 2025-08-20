<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Controller para template de TIA - Financeiro
 */
class LgpdTiaTemplateFinanceiro
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
     * Método principal para exibir template de TIA para Financeiro.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $template = $this->getTemplateFinanceiro();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Financeiro',
                'menu' => 'LgpdTiaTemplate',
                'menuPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();
            $this->data['usuarios'] = $this->usersRepo->getAllUsersSelect();

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/template-financeiro", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro ao carregar template Financeiro: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar template Financeiro!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia-templates");
            exit;
        }
    }

    /**
     * Retorna template para Financeiro.
     *
     * @return array
     */
    private function getTemplateFinanceiro(): array
    {
        return [
            'titulo' => 'Teste de Impacto às Atividades - Financeiro',
            'descricao' => 'Avaliação de impacto à privacidade para operações financeiras, incluindo processamento de pagamentos, análise de crédito e conformidade regulatória.',
            'checklist' => [
                'Processamento de pagamentos e transações',
                'Análise de crédito e scoring',
                'Prevenção à lavagem de dinheiro',
                'Relatórios para órgãos reguladores',
                'Gestão de contas e investimentos',
                'Seguros e produtos financeiros',
                'Auditoria e compliance',
                'Gestão de riscos'
            ],
            'riscos_identificados' => [
                'Dados financeiros altamente sensíveis',
                'Obrigações regulatórias rigorosas',
                'Risco de fraude e segurança',
                'Compartilhamento com autoridades',
                'Retenção obrigatória de dados'
            ],
            'medidas_protecao' => [
                'Criptografia de ponta a ponta',
                'Controle de acesso rigoroso',
                'Monitoramento contínuo de transações',
                'Backup e recuperação de dados',
                'Conformidade com regulamentações',
                'Segregação de ambientes'
            ],
            'recomendacoes' => [
                'Implementar controles de segurança avançados',
                'Revisar procedimentos de compliance',
                'Estabelecer plano de resposta a incidentes',
                'Treinamento específico em segurança financeira',
                'Auditorias independentes regulares'
            ]
        ];
    }
}

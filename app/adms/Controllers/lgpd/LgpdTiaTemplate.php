<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pelos templates de TIA por setor.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaTemplate
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    /** @var DepartmentsRepository $deptRepo */
    private DepartmentsRepository $deptRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
        $this->deptRepo = new DepartmentsRepository();
    }

    /**
     * Método principal para exibir templates de TIA.
     * Este método detecta automaticamente qual template foi solicitado baseado na URL.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Detectar qual template foi solicitado baseado na URL
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $templateType = $this->detectTemplateType($requestUri);
            
            if ($templateType) {
                // Rotear para o template específico
                $this->routeToTemplate($templateType);
                return;
            }
            
            // Se não for um template específico, mostrar a página principal de seleção
            $this->showTemplatesList();
            
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTiaTemplate: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar templates de TIA!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-dashboard");
            exit;
        }
    }
    
    /**
     * Detecta o tipo de template baseado na URL.
     *
     * @param string $requestUri
     * @return string|null
     */
    private function detectTemplateType(string $requestUri): ?string
    {
        if (strpos($requestUri, 'lgpd-tia-template-rh') !== false) {
            return 'rh';
        }
        if (strpos($requestUri, 'lgpd-tia-template-marketing') !== false) {
            return 'marketing';
        }
        if (strpos($requestUri, 'lgpd-tia-template-financeiro') !== false) {
            return 'financeiro';
        }
        if (strpos($requestUri, 'lgpd-tia-template-ti') !== false) {
            return 'ti';
        }
        
        return null;
    }
    
    /**
     * Roteia para o template específico.
     *
     * @param string $templateType
     * @return void
     */
    private function routeToTemplate(string $templateType): void
    {
        switch ($templateType) {
            case 'rh':
                $this->templateRh();
                break;
            case 'marketing':
                $this->templateMarketing();
                break;
            case 'financeiro':
                $this->templateFinanceiro();
                break;
            case 'ti':
                $this->templateTi();
                break;
            default:
                $this->showTemplatesList();
                break;
        }
    }
    
    /**
     * Mostra a lista de templates disponíveis.
     *
     * @return void
     */
    private function showTemplatesList(): void
    {
        // Carregar departamentos disponíveis
        $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();
        
        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Templates de TIA por Setor',
            'menu' => 'lgpd-tia',
            'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/tia/templates", $this->data);
        $loadView->loadView();
    }

    /**
     * Aplica template de TIA para RH.
     *
     * @return void
     */
    public function templateRh(): void
    {
        try {
            $template = $this->getTemplateRh();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Recursos Humanos',
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/template-rh", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro ao carregar template RH: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar template RH!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia-templates");
            exit;
        }
    }

    /**
     * Aplica template de TIA para Marketing.
     *
     * @return void
     */
    public function templateMarketing(): void
    {
        try {
            $template = $this->getTemplateMarketing();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Marketing',
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();

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
     * Aplica template de TIA para Financeiro.
     *
     * @return void
     */
    public function templateFinanceiro(): void
    {
        try {
            $template = $this->getTemplateFinanceiro();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Financeiro',
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();

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
     * Aplica template de TIA para TI.
     *
     * @return void
     */
    public function templateTi(): void
    {
        try {
            $template = $this->getTemplateTi();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Tecnologia da Informação',
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/template-ti", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro ao carregar template TI: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar template TI!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia-templates");
            exit;
        }
    }

    /**
     * Retorna template para RH.
     *
     * @return array
     */
    private function getTemplateRh(): array
    {
        return [
            'titulo' => 'Teste de Impacto às Atividades - Recursos Humanos',
            'descricao' => 'Avaliação de impacto à privacidade para operações de Recursos Humanos, incluindo recrutamento, gestão de funcionários, folha de pagamento e benefícios.',
            'checklist' => [
                'Dados pessoais coletados durante recrutamento',
                'Informações de funcionários armazenadas',
                'Processamento de folha de pagamento',
                'Gestão de benefícios e férias',
                'Avaliações de desempenho',
                'Treinamentos e capacitações',
                'Saúde ocupacional e medicina do trabalho',
                'Relatórios gerenciais de RH'
            ],
            'riscos_identificados' => [
                'Alto volume de dados pessoais sensíveis',
                'Acesso amplo a informações de funcionários',
                'Retenção de dados por longos períodos',
                'Compartilhamento com terceiros (planos de saúde, previdência)',
                'Monitoramento de atividades dos funcionários'
            ],
            'medidas_protecao' => [
                'Controle de acesso baseado em função',
                'Criptografia de dados sensíveis',
                'Política de retenção de dados',
                'Acordos de confidencialidade',
                'Auditoria regular de acessos',
                'Treinamento de equipe em LGPD'
            ],
            'recomendacoes' => [
                'Implementar princípio de menor privilégio',
                'Revisar periodicamente permissões de acesso',
                'Documentar todas as operações de dados pessoais',
                'Estabelecer procedimentos de resposta a incidentes',
                'Realizar testes de impacto regularmente'
            ]
        ];
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
            'descricao' => 'Avaliação de impacto à privacidade para operações de Marketing, incluindo campanhas publicitárias, análise de comportamento e relacionamento com clientes.',
            'checklist' => [
                'Coleta de dados para campanhas publicitárias',
                'Segmentação de público-alvo',
                'Análise de comportamento do usuário',
                'Cookies e tecnologias de rastreamento',
                'Marketing direto por e-mail/SMS',
                'Redes sociais e publicidade online',
                'CRM e gestão de relacionamento',
                'Análise de conversão e ROI'
            ],
            'riscos_identificados' => [
                'Coleta excessiva de dados pessoais',
                'Rastreamento sem consentimento explícito',
                'Perfil de usuário detalhado',
                'Compartilhamento com parceiros publicitários',
                'Retenção de dados de navegação'
            ],
            'medidas_protecao' => [
                'Consentimento granular e específico',
                'Transparência sobre uso de cookies',
                'Opção de opt-out em todas as comunicações',
                'Minimização de dados coletados',
                'Anonimização quando possível',
                'Controle de terceiros e parceiros'
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

    /**
     * Retorna template para TI.
     *
     * @return array
     */
    private function getTemplateTi(): array
    {
        return [
            'titulo' => 'Teste de Impacto às Atividades - Tecnologia da Informação',
            'descricao' => 'Avaliação de impacto à privacidade para operações de TI, incluindo infraestrutura, desenvolvimento de sistemas e suporte técnico.',
            'checklist' => [
                'Gestão de infraestrutura e servidores',
                'Desenvolvimento e manutenção de sistemas',
                'Suporte técnico e helpdesk',
                'Backup e recuperação de dados',
                'Segurança da informação',
                'Monitoramento de sistemas',
                'Gestão de usuários e permissões',
                'Integração com sistemas terceiros'
            ],
            'riscos_identificados' => [
                'Acesso administrativo a todos os sistemas',
                'Logs detalhados de atividades',
                'Backup de dados pessoais',
                'Integração com sistemas externos',
                'Monitoramento de comportamento dos usuários'
            ],
            'medidas_protecao' => [
                'Princípio de menor privilégio',
                'Logs de auditoria seguros',
                'Criptografia de dados em repouso',
                'Controle de acesso baseado em função',
                'Segregação de ambientes',
                'Monitoramento de atividades suspeitas'
            ],
            'recomendacoes' => [
                'Implementar controles de acesso rigorosos',
                'Revisar políticas de backup e retenção',
                'Estabelecer procedimentos de segurança',
                'Treinamento em segurança da informação',
                'Testes de penetração regulares'
            ]
        ];
    }
}

<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Controller para template de TIA - Recursos Humanos
 */
class LgpdTiaTemplateRh
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
     * Método principal para exibir template de TIA para RH.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $template = $this->getTemplateRh();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Recursos Humanos',
                'menu' => 'LgpdTiaTemplate',
                'menuPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();
            $this->data['usuarios'] = $this->usersRepo->getAllUsersSelect();

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
                'Treinamento em proteção de dados'
            ],
            'recomendacoes' => [
                'Implementar controle de acesso rigoroso',
                'Revisar políticas de retenção',
                'Estabelecer procedimentos de exclusão',
                'Treinar equipe em LGPD',
                'Monitorar compliance regularmente'
            ]
        ];
    }
}

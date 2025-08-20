<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Controller para template de TIA - Tecnologia da Informação
 */
class LgpdTiaTemplateTi
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
     * Método principal para exibir template de TIA para TI.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $template = $this->getTemplateTi();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Template TIA - Tecnologia da Informação',
                'menu' => 'LgpdTiaTemplate',
                'menuPermission' => ['LgpdTia', 'LgpdTiaCreate'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageElements);
            $this->data['template'] = $template;
            $this->data['departamentos'] = $this->deptRepo->getAllDepartmentsSelect();
            $this->data['usuarios'] = $this->usersRepo->getAllUsersSelect();

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

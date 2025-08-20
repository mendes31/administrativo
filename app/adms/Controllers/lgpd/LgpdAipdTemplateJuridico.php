<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\lgpd\LgpdAipdCreate;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;

class LgpdAipdTemplateJuridico extends LgpdAipdCreate
{
    public function index(): void
    {
        $this->data = [];
        
        // Instanciar repositórios
        $ropaRepository = new LgpdRopaRepository();
        $departmentsRepository = new DepartmentsRepository();
        $usersRepository = new UsersRepository();
        
        // Carregar dados necessários
        $this->data['ropas'] = $ropaRepository->getAllRopaForSelect();
        $this->data['departamentos'] = $departmentsRepository->getAllDepartmentsSelect();
        $this->data['usuarios'] = $usersRepository->getAllUsersForSelect();
        
        // Definir template específico para Jurídico
        $this->data['template_juridico'] = [
            'titulo_padrao' => 'AIPD - Atividades Jurídicas e Advocatícias',
            'objetivo_padrao' => 'Avaliar os riscos à proteção de dados pessoais decorrentes das atividades jurídicas, advocatícias e consultoria legal.',
            'escopo_padrao' => 'Este documento abrange todas as operações de tratamento de dados pessoais relacionadas às atividades jurídicas, incluindo processos, documentos legais, informações de clientes e consultoria.',
            'metodologia_padrao' => 'Avaliação baseada na análise de riscos específicos do setor jurídico, considerando o sigilo profissional, LGPD e demais normas aplicáveis.',
            'conclusoes_padrao' => 'As atividades jurídicas apresentam riscos elevados devido ao sigilo profissional e à natureza sensível dos dados tratados.',
            'riscos_identificados' => [
                '• Violação do sigilo profissional',
                '• Vazamento de informações de processos',
                '• Acesso indevido a documentos legais',
                '• Exposição de dados de clientes',
                '• Falhas na segurança de sistemas jurídicos',
                '• Não conformidade com o Estatuto da Advocacia'
            ],
            'medidas_mitigacao' => [
                '• Implementação de criptografia em documentos',
                '• Controle rigoroso de acesso aos sistemas jurídicos',
                '• Auditorias regulares de segurança',
                '• Treinamento específico para equipes jurídicas',
                '• Monitoramento contínuo de sistemas',
                '• Conformidade com o Estatuto da Advocacia'
            ],
            'recomendacoes_padrao' => [
                '• Revisão trimestral das medidas de segurança',
                '• Implementação de sistema de detecção de intrusão',
                '• Estabelecimento de procedimentos de resposta a incidentes',
                '• Manutenção de registros detalhados de acesso',
                '• Realização de testes de penetração regulares'
            ],
            'campos_especificos' => [
                'tipo_servico_juridico' => [
                    'label' => 'Tipo de Serviço Jurídico',
                    'options' => [
                        'advocacia_geral' => 'Advocacia Geral',
                        'advocacia_especializada' => 'Advocacia Especializada',
                        'consultoria_juridica' => 'Consultoria Jurídica',
                        'compliance' => 'Compliance',
                        'gestao_riscos' => 'Gestão de Riscos',
                        'auditoria_juridica' => 'Auditoria Jurídica'
                    ]
                ],
                'area_juridica' => [
                    'label' => 'Área Jurídica',
                    'options' => [
                        'civil' => 'Direito Civil',
                        'trabalhista' => 'Direito Trabalhista',
                        'tributario' => 'Direito Tributário',
                        'empresarial' => 'Direito Empresarial',
                        'administrativo' => 'Direito Administrativo',
                        'penal' => 'Direito Penal',
                        'ambiental' => 'Direito Ambiental',
                        'digital' => 'Direito Digital'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_cadastrais' => 'Dados Cadastrais',
                        'dados_processo' => 'Dados de Processo',
                        'documentos_legais' => 'Documentos Legais',
                        'informacoes_cliente' => 'Informações do Cliente',
                        'dados_biometricos' => 'Dados Biométricos',
                        'dados_financeiros' => 'Dados Financeiros',
                        'dados_sensiveis' => 'Dados Sensíveis',
                        'dados_pagamento' => 'Dados de Pagamento'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'execucao_contrato' => 'Execução de Contrato',
                        'cumprimento_obrigacao_legal' => 'Cumprimento de Obrigação Legal',
                        'interesse_legitimo' => 'Interesse Legítimo',
                        'protecao_credito' => 'Proteção ao Crédito',
                        'sigilo_profissional' => 'Sigilo Profissional'
                    ]
                ]
            ]
        ];
        
        // Processar formulário se enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->preProcessarDadosJuridico();
        }
        
        // Configurar layout da página
        $pageElements = [
            'title_head' => 'Template AIPD - Jurídico',
            'menu' => 'lgpd-aipd-template-juridico',
            'buttonPermission' => ['LgpdAipdTemplateJuridico'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar view
        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-juridico", $this->data);
        $loadView->loadView();
    }
    
    private function preProcessarDadosJuridico(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken($_POST['csrf_token'] ?? '', 'form_template_juridico')) {
            $this->data['error'] = 'Token de segurança inválido.';
            return;
        }
        
        // Processar dados do formulário
        $this->data['form'] = $_POST;
        
        // Validar campos obrigatórios
        if (empty($_POST['titulo']) || empty($_POST['departamento_id']) || 
            empty($_POST['ropa_id']) || empty($_POST['responsavel_id'])) {
            $this->data['error'] = 'Todos os campos obrigatórios devem ser preenchidos.';
            return;
        }
        
        // Salvar AIPD usando o repositório
        $repo = new LgpdAipdRepository();
        $result = $repo->create($this->data['form']);
        
        if ($result) {
            $_SESSION['success'] = "AIPD cadastrada com sucesso!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        } else {
            $this->data['error'] = "AIPD não cadastrada!";
        }
    }
    
    private function getRopasRelevantesParaJuridico(): array
    {
        $ropaRepository = new LgpdRopaRepository();
        $todasRopas = $ropaRepository->getAllRopaForSelect();
        
        $palavrasChave = [
            'juridico', 'advocacia', 'legal', 'processo', 'documento', 'cliente',
            'consultoria', 'compliance', 'auditoria', 'risco', 'contrato',
            'estatuto', 'advocacia', 'sigilo', 'profissional'
        ];
        
        $ropasFiltradas = [];
        foreach ($todasRopas as $ropa) {
            $texto = strtolower($ropa['atividade'] . ' ' . $ropa['codigo']);
            foreach ($palavrasChave as $palavra) {
                if (strpos($texto, $palavra) !== false) {
                    $ropasFiltradas[] = $ropa;
                    break;
                }
            }
        }
        
        return $ropasFiltradas;
    }
}

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

class LgpdAipdTemplateLogistica extends LgpdAipdCreate
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
        
        // Definir template específico para Logística
        $this->data['template_logistica'] = [
            'titulo_padrao' => 'AIPD - Atividades de Logística e Transporte',
            'objetivo_padrao' => 'Avaliar os riscos à proteção de dados pessoais decorrentes das atividades de logística, transporte e supply chain.',
            'escopo_padrao' => 'Este documento abrange todas as operações de tratamento de dados pessoais relacionadas à logística, incluindo rastreamento de cargas, dados de motoristas, informações de entrega e gestão de supply chain.',
            'metodologia_padrao' => 'Avaliação baseada na análise de riscos específicos do setor de logística, considerando regulamentações de transporte, LGPD e demais normas aplicáveis.',
            'conclusoes_padrao' => 'As atividades de logística apresentam riscos elevados devido ao tratamento de dados de localização, informações de motoristas e rastreamento de cargas.',
            'riscos_identificados' => [
                '• Exposição não autorizada de dados de localização',
                '• Vazamento de informações de motoristas e funcionários',
                '• Acesso indevido a dados de rastreamento de cargas',
                '• Interceptação de comunicações de logística',
                '• Falhas na segurança de sistemas de rastreamento',
                '• Não conformidade com regulamentações de transporte'
            ],
            'medidas_mitigacao' => [
                '• Implementação de criptografia em sistemas de rastreamento',
                '• Controle rigoroso de acesso aos dados de logística',
                '• Auditorias regulares de segurança',
                '• Treinamento específico para equipes de logística',
                '• Monitoramento contínuo de sistemas de transporte',
                '• Conformidade com regulamentações de transporte'
            ],
            'recomendacoes_padrao' => [
                '• Revisão trimestral das medidas de segurança',
                '• Implementação de sistema de detecção de intrusão',
                '• Estabelecimento de procedimentos de resposta a incidentes',
                '• Manutenção de registros detalhados de acesso',
                '• Realização de testes de penetração regulares'
            ],
            'campos_especificos' => [
                'tipo_servico_logistica' => [
                    'label' => 'Tipo de Serviço de Logística',
                    'options' => [
                        'transporte_rodoviario' => 'Transporte Rodoviário',
                        'transporte_aereo' => 'Transporte Aéreo',
                        'transporte_maritimo' => 'Transporte Marítimo',
                        'armazenagem' => 'Armazenagem',
                        'distribuicao' => 'Distribuição',
                        'supply_chain' => 'Supply Chain Management'
                    ]
                ],
                'categoria_carga' => [
                    'label' => 'Categoria de Carga',
                    'options' => [
                        'carga_geral' => 'Carga Geral',
                        'carga_perigosa' => 'Carga Perigosa',
                        'carga_refrigerada' => 'Carga Refrigerada',
                        'carga_expressa' => 'Carga Expressa',
                        'carga_fragil' => 'Carga Frágil',
                        'carga_especializada' => 'Carga Especializada'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_cadastrais' => 'Dados Cadastrais',
                        'dados_localizacao' => 'Dados de Localização',
                        'dados_motorista' => 'Dados do Motorista',
                        'dados_rastreamento' => 'Dados de Rastreamento',
                        'dados_entrega' => 'Dados de Entrega',
                        'dados_veiculo' => 'Dados do Veículo',
                        'dados_biometricos' => 'Dados Biométricos',
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
                        'regulamentacao_transporte' => 'Regulamentação de Transporte'
                    ]
                ]
            ]
        ];
        
        // Processar formulário se enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->preProcessarDadosLogistica();
        }
        
        // Configurar layout da página
        $pageElements = [
            'title_head' => 'Template AIPD - Logística',
            'menu' => 'lgpd-aipd-template-logistica',
            'buttonPermission' => ['LgpdAipdTemplateLogistica'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar view
        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-logistica", $this->data);
        $loadView->loadView();
    }
    
    private function preProcessarDadosLogistica(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken($_POST['csrf_token'] ?? '', 'form_template_logistica')) {
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
    
    private function getRopasRelevantesParaLogistica(): array
    {
        $ropaRepository = new LgpdRopaRepository();
        $todasRopas = $ropaRepository->getAllRopaForSelect();
        
        $palavrasChave = [
            'logistica', 'transporte', 'carga', 'rastreamento', 'entrega',
            'motorista', 'veiculo', 'supply', 'chain', 'armazenagem',
            'distribuicao', 'frete', 'rota', 'localizacao'
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

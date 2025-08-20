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

class LgpdAipdTemplateTelecom extends LgpdAipdCreate
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
        
        // Definir template específico para Telecomunicações
        $this->data['template_telecom'] = [
            'titulo_padrao' => 'AIPD - Atividades de Telecomunicações',
            'objetivo_padrao' => 'Avaliar os riscos à proteção de dados pessoais decorrentes das atividades de telecomunicações, incluindo prestação de serviços de telefonia, internet e comunicação de dados.',
            'escopo_padrao' => 'Este documento abrange todas as operações de tratamento de dados pessoais relacionadas aos serviços de telecomunicações, incluindo dados de localização, histórico de chamadas, dados de uso de internet e informações de faturamento.',
            'metodologia_padrao' => 'Avaliação baseada na análise de riscos específicos do setor de telecomunicações, considerando regulamentações da ANATEL, LGPD e demais normas aplicáveis.',
            'conclusoes_padrao' => 'As atividades de telecomunicações apresentam riscos elevados devido à natureza sensível dos dados tratados e à obrigatoriedade de retenção de dados conforme regulamentação específica.',
            'riscos_identificados' => [
                '• Exposição não autorizada de dados de localização',
                '• Vazamento de histórico de chamadas e mensagens',
                '• Acesso indevido a dados de faturamento',
                '• Interceptação de comunicações',
                '• Falhas na segurança de redes e sistemas',
                '• Não conformidade com regulamentações da ANATEL'
            ],
            'medidas_mitigacao' => [
                '• Implementação de criptografia end-to-end',
                '• Controle rigoroso de acesso aos sistemas',
                '• Auditorias regulares de segurança',
                '• Treinamento específico para equipes técnicas',
                '• Monitoramento contínuo de redes',
                '• Conformidade com regulamentações da ANATEL'
            ],
            'recomendacoes_padrao' => [
                '• Revisão trimestral das medidas de segurança',
                '• Implementação de sistema de detecção de intrusão',
                '• Estabelecimento de procedimentos de resposta a incidentes',
                '• Manutenção de registros detalhados de acesso',
                '• Realização de testes de penetração regulares'
            ],
            'campos_especificos' => [
                'tipo_servico' => [
                    'label' => 'Tipo de Serviço de Telecomunicação',
                    'options' => [
                        'telefonia_fixa' => 'Telefonia Fixa',
                        'telefonia_movel' => 'Telefonia Móvel',
                        'internet' => 'Internet',
                        'tv_por_assinatura' => 'TV por Assinatura',
                        'transmissao_dados' => 'Transmissão de Dados',
                        'servicos_valor_agregado' => 'Serviços de Valor Agregado'
                    ]
                ],
                'categoria_cliente' => [
                    'label' => 'Categoria de Cliente',
                    'options' => [
                        'residencial' => 'Residencial',
                        'empresarial' => 'Empresarial',
                        'governo' => 'Governo',
                        'operadora' => 'Operadora',
                        'revendedor' => 'Revendedor'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_cadastrais' => 'Dados Cadastrais',
                        'dados_localizacao' => 'Dados de Localização',
                        'historico_chamadas' => 'Histórico de Chamadas',
                        'dados_uso_internet' => 'Dados de Uso de Internet',
                        'dados_faturamento' => 'Dados de Faturamento',
                        'dados_equipamento' => 'Dados do Equipamento',
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
                        'regulamentacao_anatel' => 'Regulamentação ANATEL'
                    ]
                ]
            ]
        ];
        
        // Processar formulário se enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->preProcessarDadosTelecom();
        }
        
        // Configurar layout da página
        $pageElements = [
            'title_head' => 'Template AIPD - Telecomunicações',
            'menu' => 'lgpd-aipd-template-telecom',
            'buttonPermission' => ['LgpdAipdTemplateTelecom'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar view
        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-telecom", $this->data);
        $loadView->loadView();
    }
    
    private function preProcessarDadosTelecom(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken($_POST['csrf_token'] ?? '', 'form_template_telecom')) {
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
    
    private function getRopasRelevantesParaTelecom(): array
    {
        $ropaRepository = new LgpdRopaRepository();
        $todasRopas = $ropaRepository->getAllRopaForSelect();
        
        $palavrasChave = [
            'telecom', 'telefonia', 'internet', 'comunicacao', 'dados',
            'anatel', 'operadora', 'servico', 'rede', 'transmissao',
            'chamada', 'mensagem', 'localizacao', 'faturamento'
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

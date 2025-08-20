<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller responsável pelo template de AIPD específico para o setor de e-commerce.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateEcommerce
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor de e-commerce
            $this->data['form'] = $this->preProcessarDadosEcommerce($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor de e-commerce cadastrada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não cadastrada!";
            }
        }

        // Carregar dados para os selects
        $departmentsRepo = new DepartmentsRepository();
        $ropaRepo = new LgpdRopaRepository();
        $usersRepo = new UsersRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['ropas'] = $this->getRopasRelevantesParaEcommerce($ropaRepo);
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor de e-commerce
        $this->data['template_ecommerce'] = $this->getTemplateEcommerce();

        $pageElements = [
            'title_head' => 'AIPD - Setor de E-commerce',
            'menu' => 'lgpd-aipd-template-ecommerce',
            'buttonPermission' => ['LgpdAipdTemplateEcommerce'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-ecommerce", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor de e-commerce
     */
    private function getTemplateEcommerce(): array
    {
        return [
            'titulo_padrao' => 'AIPD - E-commerce e Vendas Online',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais nas operações de e-commerce, incluindo coleta, processamento, armazenamento e compartilhamento de dados de clientes em plataformas de vendas online.',
            'escopo_padrao' => 'Esta AIPD abrange todas as operações de e-commerce da organização, incluindo plataformas de vendas online, marketplaces, sistemas de pagamento, logística de entrega, marketing digital e relacionamento com clientes.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD, considerando os riscos específicos do e-commerce, incluindo fraudes, vazamentos de dados de pagamento, e práticas de marketing digital.',
            'campos_especificos' => [
                'tipo_plataforma' => [
                    'label' => 'Tipo de Plataforma E-commerce',
                    'options' => [
                        'loja_propria' => 'Loja Própria',
                        'marketplace' => 'Marketplace',
                        'dropshipping' => 'Dropshipping',
                        'afiliados' => 'Programa de Afiliados',
                        'social_commerce' => 'Social Commerce',
                        'mobile_commerce' => 'Mobile Commerce'
                    ]
                ],
                'categoria_produtos' => [
                    'label' => 'Categoria Principal de Produtos',
                    'options' => [
                        'eletronicos' => 'Eletrônicos',
                        'moda' => 'Moda e Acessórios',
                        'casa' => 'Casa e Decoração',
                        'beleza' => 'Beleza e Cuidados',
                        'alimentacao' => 'Alimentação',
                        'livros' => 'Livros e Mídia',
                        'esportes' => 'Esportes e Lazer',
                        'automotivo' => 'Automotivo',
                        'outros' => 'Outros'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_identificacao' => 'Dados de Identificação',
                        'dados_contato' => 'Dados de Contato',
                        'dados_pagamento' => 'Dados de Pagamento',
                        'dados_endereco' => 'Dados de Endereço',
                        'dados_preferencias' => 'Dados de Preferências',
                        'dados_navegacao' => 'Dados de Navegação',
                        'dados_comportamento' => 'Dados de Comportamento',
                        'dados_biometricos' => 'Dados Biométricos (se aplicável)',
                        'dados_localizacao' => 'Dados de Localização'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'execucao_contrato' => 'Execução de Contrato',
                        'interesse_legitimo' => 'Interesse Legítimo',
                        'consentimento' => 'Consentimento',
                        'cumprimento_obrigacao' => 'Cumprimento de Obrigação Legal',
                        'protecao_credito' => 'Proteção ao Crédito',
                        'exercicio_direitos' => 'Exercício Regular de Direitos'
                    ]
                ]
            ],
            'riscos_identificados' => [
                'Vazamento de dados de pagamento (cartões, PIX)',
                'Fraude e roubo de identidade',
                'Ataques de phishing e engenharia social',
                'Vazamento de dados pessoais de clientes',
                'Uso inadequado de cookies e tracking',
                'Compartilhamento não autorizado com terceiros',
                'Falhas de segurança em APIs de pagamento',
                'Armazenamento inadequado de senhas',
                'Monitoramento excessivo de comportamento',
                'Vazamento de dados de localização'
            ],
            'medidas_mitigacao' => [
                'Implementar criptografia de ponta a ponta para dados de pagamento',
                'Utilizar tokens de pagamento em vez de dados reais',
                'Implementar autenticação multifator',
                'Realizar auditorias de segurança regulares',
                'Implementar políticas de retenção de dados',
                'Utilizar certificados SSL/TLS',
                'Implementar monitoramento de fraudes',
                'Treinar equipe em segurança da informação',
                'Implementar políticas de backup e recuperação',
                'Utilizar ferramentas de detecção de intrusão'
            ],
            'conclusoes_padrao' => 'O e-commerce apresenta riscos elevados devido ao volume e sensibilidade dos dados tratados. É essencial implementar controles robustos de segurança e conformidade.',
            'recomendacoes_padrao' => [
                'Implementar criptografia de ponta a ponta',
                'Adotar padrões PCI DSS para dados de pagamento',
                'Implementar monitoramento contínuo de segurança',
                'Realizar testes de penetração regulares',
                'Implementar políticas de retenção de dados',
                'Treinar equipe em LGPD e segurança',
                'Implementar sistema de gestão de incidentes',
                'Realizar auditorias de conformidade trimestrais'
            ]
        ];
    }

    /**
     * Pré-processar dados específicos do setor de e-commerce
     */
    private function preProcessarDadosEcommerce(array $form): array
    {
        // Definir nível de risco padrão para e-commerce
        if (empty($form['nivel_risco'])) {
            $form['nivel_risco'] = 'Alto';
        }

        // Definir necessidade de ANPD
        if (empty($form['necessita_anpd'])) {
            $form['necessita_anpd'] = 1; // Sim, por padrão
        }

        // Processar dados específicos do setor
        $dadosEspecificos = [
            'tipo_plataforma' => $form['tipo_plataforma'] ?? '',
            'categoria_produtos' => $form['categoria_produtos'] ?? '',
            'dados_tratados' => $form['dados_tratados'] ?? [],
            'base_legal' => $form['base_legal'] ?? ''
        ];

        $form['dados_especificos'] = json_encode($dadosEspecificos);
        $form['template_usado'] = 'ecommerce';

        return $form;
    }

    /**
     * Filtrar ROPAs relevantes para o setor de e-commerce
     */
    private function getRopasRelevantesParaEcommerce(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        $palavrasChave = [
            'e-commerce', 'venda', 'compra', 'pagamento', 'cartão', 'pix', 'boleto',
            'marketplace', 'loja', 'produto', 'cliente', 'pedido', 'entrega',
            'frete', 'cobrança', 'faturamento', 'nota fiscal', 'cupom', 'promoção'
        ];
        
        foreach ($todasRopas as $ropa) {
            $atividade = strtolower($ropa['atividade']);
            $finalidade = strtolower($ropa['finalidade'] ?? '');
            
            foreach ($palavrasChave as $palavra) {
                if (strpos($atividade, $palavra) !== false || strpos($finalidade, $palavra) !== false) {
                    $ropasRelevantes[] = $ropa;
                    break;
                }
            }
        }
        
        return empty($ropasRelevantes) ? $todasRopas : $ropasRelevantes;
    }
}

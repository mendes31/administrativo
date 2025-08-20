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
 * Controller responsável pelo template de AIPD específico para o setor de marketing.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateMarketing
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor de marketing
            $this->data['form'] = $this->preProcessarDadosMarketing($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor de marketing cadastrada com sucesso!";
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
        $this->data['ropas'] = $this->getRopasRelevantesParaMarketing($ropaRepo);
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor de marketing
        $this->data['template_marketing'] = $this->getTemplateMarketing();

        $pageElements = [
            'title_head' => 'AIPD - Setor de Marketing',
            'menu' => 'lgpd-aipd-template-marketing',
            'buttonPermission' => ['LgpdAipdTemplateMarketing'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-marketing", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor de marketing
     */
    private function getTemplateMarketing(): array
    {
        return [
            'titulo_padrao' => 'AIPD - Tratamento de Dados de Marketing',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais relacionados ao tratamento de dados para atividades de marketing, publicidade e relacionamento com clientes.',
            'escopo_padrao' => 'Esta avaliação abrange o tratamento de dados para marketing, incluindo campanhas publicitárias, análise de comportamento, segmentação de público, remarketing, leads e relacionamento com clientes.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD para o setor de marketing, considerando os riscos específicos de tratamento de dados para publicidade e as medidas de segurança necessárias.',
            'campos_especificos' => [
                'tipo_campanha' => [
                    'label' => 'Tipo de Campanha de Marketing',
                    'options' => [
                        'email_marketing' => 'Email Marketing',
                        'redes_sociais' => 'Redes Sociais',
                        'google_ads' => 'Google Ads',
                        'facebook_ads' => 'Facebook Ads',
                        'remarketing' => 'Remarketing',
                        'influencer' => 'Marketing de Influenciadores',
                        'content_marketing' => 'Content Marketing',
                        'seo' => 'SEO',
                        'outros' => 'Outros'
                    ]
                ],
                'canal_marketing' => [
                    'label' => 'Canal de Marketing',
                    'options' => [
                        'digital' => 'Marketing Digital',
                        'offline' => 'Marketing Offline',
                        'omnichannel' => 'Omnichannel',
                        'social_media' => 'Social Media',
                        'search' => 'Search Marketing',
                        'display' => 'Display Advertising',
                        'video' => 'Video Marketing',
                        'mobile' => 'Mobile Marketing',
                        'outros' => 'Outros'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_identificacao' => 'Dados de Identificação',
                        'dados_contato' => 'Dados de Contato',
                        'dados_comportamento' => 'Dados de Comportamento',
                        'dados_preferencias' => 'Dados de Preferências',
                        'dados_navegacao' => 'Dados de Navegação',
                        'dados_localizacao' => 'Dados de Localização',
                        'dados_demograficos' => 'Dados Demográficos',
                        'dados_psicograficos' => 'Dados Psicográficos',
                        'dados_transacionais' => 'Dados Transacionais',
                        'dados_biometricos' => 'Dados Biométricos (se aplicável)'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'consentimento' => 'Consentimento',
                        'interesse_legitimo' => 'Interesse Legítimo',
                        'execucao_contrato' => 'Execução de Contrato',
                        'cumprimento_obrigacao' => 'Cumprimento de Obrigação Legal',
                        'protecao_credito' => 'Proteção ao Crédito',
                        'exercicio_direitos' => 'Exercício Regular de Direitos'
                    ]
                ]
            ],
            'riscos_identificados' => [
                'Uso inadequado de cookies e tracking',
                'Vazamento de dados de comportamento',
                'Compartilhamento não autorizado com terceiros',
                'Monitoramento excessivo de atividades online',
                'Uso inadequado de dados para segmentação',
                'Falta de controle sobre dados de terceiros',
                'Vazamento de dados de localização',
                'Uso inadequado de dados para personalização',
                'Falta de políticas de retenção de dados',
                'Compartilhamento inadequado com plataformas de anúncios'
            ],
            'medidas_mitigacao' => [
                'Implementação de políticas de consentimento claras',
                'Controle rigoroso sobre uso de cookies e tracking',
                'Implementação de políticas de retenção de dados',
                'Auditoria regular de práticas de marketing',
                'Treinamento específico da equipe sobre LGPD',
                'Implementação de controles de acesso baseados em papéis',
                'Monitoramento de atividades de marketing',
                'Implementação de logs detalhados para auditoria',
                'Política de acesso baseada no princípio do menor privilégio',
                'Implementação de sistema de gestão de consentimentos'
            ],
            'conclusoes_padrao' => 'O marketing apresenta riscos específicos relacionados ao uso de dados para publicidade e personalização. É essencial implementar controles robustos de consentimento e transparência.',
            'recomendacoes_padrao' => [
                'Implementar sistema de gestão de consentimentos',
                'Estabelecer políticas claras de uso de cookies',
                'Realizar treinamentos regulares sobre LGPD para equipe de marketing',
                'Implementar políticas de retenção de dados específicas',
                'Realizar auditorias de conformidade trimestrais',
                'Implementar sistema de gestão de incidentes',
                'Estabelecer procedimentos para solicitações de titulares',
                'Implementar monitoramento contínuo de práticas de marketing',
                'Implementar controles de acesso granular',
                'Realizar testes de segurança regulares'
            ]
        ];
    }

    /**
     * Pré-processar dados específicos do setor de marketing
     */
    private function preProcessarDadosMarketing(array $form): array
    {
        // Definir nível de risco padrão para marketing
        if (empty($form['nivel_risco'])) {
            $form['nivel_risco'] = 'Médio';
        }

        // Definir necessidade de ANPD
        if (empty($form['necessita_anpd'])) {
            $form['necessita_anpd'] = 0; // Não, por padrão (depende do volume)
        }

        // Processar dados específicos do setor
        $dadosEspecificos = [
            'tipo_campanha' => $form['tipo_campanha'] ?? '',
            'canal_marketing' => $form['canal_marketing'] ?? '',
            'dados_tratados' => $form['dados_tratados'] ?? [],
            'base_legal' => $form['base_legal'] ?? ''
        ];

        $form['dados_especificos'] = json_encode($dadosEspecificos);
        $form['template_usado'] = 'marketing';

        return $form;
    }

    /**
     * Filtrar ROPAs relevantes para o setor de marketing
     */
    private function getRopasRelevantesParaMarketing(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        $palavrasChave = [
            'marketing', 'publicidade', 'propaganda', 'campanha', 'anúncio',
            'email', 'newsletter', 'redes sociais', 'facebook', 'instagram',
            'google ads', 'remarketing', 'lead', 'cliente', 'prospecção',
            'segmentação', 'comportamento', 'preferência', 'navegação'
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

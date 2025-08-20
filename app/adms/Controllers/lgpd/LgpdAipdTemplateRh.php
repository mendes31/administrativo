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
 * Controller responsável pelo template de AIPD específico para o setor de RH.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateRh
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor de RH
            $this->data['form'] = $this->preProcessarDadosRh($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor de RH cadastrada com sucesso!";
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
        $this->data['ropas'] = $this->getRopasRelevantesParaRh($ropaRepo);
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor de RH
        $this->data['template_rh'] = $this->getTemplateRh();

        $pageElements = [
            'title_head' => 'AIPD - Setor de RH',
            'menu' => 'lgpd-aipd-template-rh',
            'buttonPermission' => ['LgpdAipdTemplateRh'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-rh", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor de RH
     */
    private function getTemplateRh(): array
    {
        return [
            'titulo_padrao' => 'AIPD - Tratamento de Dados de Recursos Humanos',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais relacionados ao tratamento de dados de funcionários, candidatos e processos de Recursos Humanos.',
            'escopo_padrao' => 'Esta avaliação abrange o tratamento de dados de RH, incluindo recrutamento, seleção, admissão, gestão de funcionários, folha de pagamento, benefícios, avaliações de desempenho e demissão.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD para o setor de RH, considerando os riscos específicos de tratamento de dados de funcionários e as medidas de segurança necessárias.',
            'campos_especificos' => [
                'tipo_processo' => [
                    'label' => 'Tipo de Processo de RH',
                    'options' => [
                        'recrutamento_selecao' => 'Recrutamento e Seleção',
                        'admissao' => 'Admissão de Funcionários',
                        'gestao_funcionarios' => 'Gestão de Funcionários',
                        'folha_pagamento' => 'Folha de Pagamento',
                        'beneficios' => 'Gestão de Benefícios',
                        'avaliacao_desempenho' => 'Avaliação de Desempenho',
                        'treinamento' => 'Treinamento e Desenvolvimento',
                        'demissao' => 'Processo de Demissão',
                        'outros' => 'Outros'
                    ]
                ],
                'categoria_funcionarios' => [
                    'label' => 'Categoria de Funcionários',
                    'options' => [
                        'efetivos' => 'Funcionários Efetivos',
                        'temporarios' => 'Funcionários Temporários',
                        'terceirizados' => 'Funcionários Terceirizados',
                        'estagiarios' => 'Estagiários',
                        'aprendizes' => 'Aprendizes',
                        'candidatos' => 'Candidatos',
                        'ex_funcionarios' => 'Ex-Funcionários',
                        'outros' => 'Outros'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_identificacao' => 'Dados de Identificação',
                        'dados_contato' => 'Dados de Contato',
                        'dados_bancarios' => 'Dados Bancários',
                        'dados_familiares' => 'Dados Familiares',
                        'dados_academicos' => 'Dados Acadêmicos',
                        'dados_profissionais' => 'Dados Profissionais',
                        'dados_saude' => 'Dados de Saúde',
                        'dados_biometricos' => 'Dados Biométricos',
                        'dados_financeiros' => 'Dados Financeiros',
                        'dados_avaliacao' => 'Dados de Avaliação'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'execucao_contrato' => 'Execução de Contrato',
                        'cumprimento_obrigacao' => 'Cumprimento de Obrigação Legal',
                        'interesse_legitimo' => 'Interesse Legítimo',
                        'consentimento' => 'Consentimento',
                        'protecao_credito' => 'Proteção ao Crédito',
                        'exercicio_direitos' => 'Exercício Regular de Direitos'
                    ]
                ]
            ],
            'riscos_identificados' => [
                'Vazamento de dados pessoais de funcionários',
                'Acesso não autorizado a informações salariais',
                'Compartilhamento inadequado com terceiros',
                'Falta de controle de acesso aos sistemas de RH',
                'Armazenamento inadequado de dados sensíveis',
                'Transmissão insegura de informações de funcionários',
                'Uso inadequado de dados para discriminação',
                'Falta de políticas de retenção de dados',
                'Vazamento de dados de saúde ocupacional',
                'Acesso não autorizado a avaliações de desempenho'
            ],
            'medidas_mitigacao' => [
                'Implementação de criptografia de dados em repouso e em trânsito',
                'Controle rigoroso de acesso com autenticação multifator',
                'Auditoria regular de acessos aos sistemas de RH',
                'Treinamento específico da equipe sobre proteção de dados',
                'Política de retenção e descarte de dados de funcionários',
                'Implementação de backup seguro e recuperação de dados',
                'Controle de acesso baseado em papéis',
                'Monitoramento de atividades suspeitas',
                'Implementação de logs detalhados para auditoria',
                'Política de acesso baseada no princípio do menor privilégio'
            ],
            'conclusoes_padrao' => 'O tratamento de dados de RH apresenta riscos elevados devido à sensibilidade das informações e volume de dados tratados. É obrigatória a implementação de medidas robustas de segurança.',
            'recomendacoes_padrao' => [
                'Implementar sistema de logs detalhados para auditoria',
                'Estabelecer política de acesso baseada no princípio do menor privilégio',
                'Realizar treinamentos regulares sobre LGPD para equipe de RH',
                'Implementar políticas de retenção de dados específicas para RH',
                'Realizar auditorias de conformidade semestrais',
                'Implementar sistema de gestão de incidentes',
                'Estabelecer procedimentos para solicitações de titulares',
                'Implementar monitoramento contínuo de segurança',
                'Implementar políticas específicas para dados de saúde ocupacional',
                'Realizar testes de segurança regulares'
            ]
        ];
    }

    /**
     * Pré-processar dados específicos do setor de RH
     */
    private function preProcessarDadosRh(array $form): array
    {
        // Definir nível de risco padrão para RH
        if (empty($form['nivel_risco'])) {
            $form['nivel_risco'] = 'Alto';
        }

        // Definir necessidade de ANPD
        if (empty($form['necessita_anpd'])) {
            $form['necessita_anpd'] = 1; // Sim, por padrão
        }

        // Processar dados específicos do setor
        $dadosEspecificos = [
            'tipo_processo' => $form['tipo_processo'] ?? '',
            'categoria_funcionarios' => $form['categoria_funcionarios'] ?? '',
            'dados_tratados' => $form['dados_tratados'] ?? [],
            'base_legal' => $form['base_legal'] ?? ''
        ];

        $form['dados_especificos'] = json_encode($dadosEspecificos);
        $form['template_usado'] = 'rh';

        return $form;
    }

    /**
     * Filtrar ROPAs relevantes para o setor de RH
     */
    private function getRopasRelevantesParaRh(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        $palavrasChave = [
            'rh', 'recursos humanos', 'funcionário', 'colaborador', 'empregado',
            'admissão', 'demissão', 'folha', 'pagamento', 'salário', 'benefício',
            'recrutamento', 'seleção', 'candidato', 'avaliação', 'desempenho',
            'treinamento', 'capacitação', 'pessoal', 'trabalhador'
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

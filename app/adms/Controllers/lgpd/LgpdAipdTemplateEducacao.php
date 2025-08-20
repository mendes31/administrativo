<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pelo template de AIPD específico para o setor de educação.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateEducacao
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor de educação
            $this->data['form'] = $this->preProcessarDadosEducacao($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor de educação cadastrada com sucesso!";
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
        $this->data['ropas'] = $this->getRopasRelevantesParaEducacao($ropaRepo);
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor de educação
        $this->data['template_educacao'] = $this->getTemplateEducacao();

        $pageElements = [
            'title_head' => 'AIPD - Setor de Educação',
            'menu' => 'lgpd-aipd-template-educacao',
            'buttonPermission' => ['LgpdAipdTemplateEducacao'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-educacao", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor de educação
     */
    private function getTemplateEducacao(): array
    {
        return [
            'titulo_padrao' => 'AIPD - Tratamento de Dados Educacionais',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais relacionados ao tratamento de dados educacionais, incluindo informações de alunos, professores, funcionários e processos acadêmicos.',
            'escopo_padrao' => 'Esta avaliação abrange o tratamento de dados educacionais, incluindo matrículas, notas, frequência, histórico acadêmico, informações de professores e funcionários, e processos administrativos educacionais.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD para o setor educacional, considerando os riscos específicos de tratamento de dados de menores e as medidas de segurança necessárias.',
            'campos_especificos' => [
                'tipo_instituicao' => [
                    'label' => 'Tipo de Instituição Educacional',
                    'options' => [
                        'escola_fundamental' => 'Escola Fundamental',
                        'escola_medio' => 'Escola de Ensino Médio',
                        'faculdade' => 'Faculdade/Universidade',
                        'escola_tecnica' => 'Escola Técnica',
                        'curso_livre' => 'Curso Livre',
                        'treinamento_corporativo' => 'Treinamento Corporativo',
                        'e_learning' => 'E-learning/Online',
                        'outros' => 'Outros'
                    ]
                ],
                'nivel_ensino' => [
                    'label' => 'Nível de Ensino',
                    'options' => [
                        'infantil' => 'Educação Infantil',
                        'fundamental' => 'Ensino Fundamental',
                        'medio' => 'Ensino Médio',
                        'superior' => 'Ensino Superior',
                        'pos_graduacao' => 'Pós-Graduação',
                        'tecnico' => 'Técnico',
                        'treinamento' => 'Treinamento',
                        'outros' => 'Outros'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Dados Pessoais Tratados',
                    'options' => [
                        'dados_alunos' => 'Dados de Alunos',
                        'dados_professores' => 'Dados de Professores',
                        'dados_funcionarios' => 'Dados de Funcionários',
                        'dados_responsaveis' => 'Dados de Responsáveis',
                        'dados_academicos' => 'Dados Acadêmicos',
                        'dados_financeiros' => 'Dados Financeiros',
                        'dados_biometricos' => 'Dados Biométricos (se aplicável)',
                        'dados_saude' => 'Dados de Saúde (se aplicável)'
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
                'Vazamento de dados de menores de idade',
                'Acesso não autorizado a informações acadêmicas',
                'Compartilhamento inadequado com terceiros',
                'Falta de controle de acesso aos sistemas educacionais',
                'Armazenamento inadequado de dados sensíveis',
                'Transmissão insegura de informações educacionais',
                'Uso inadequado de dados para marketing',
                'Falta de políticas de retenção de dados'
            ],
            'medidas_mitigacao' => [
                'Implementação de criptografia de dados em repouso e em trânsito',
                'Controle rigoroso de acesso com autenticação multifator',
                'Auditoria regular de acessos aos sistemas educacionais',
                'Treinamento específico da equipe sobre proteção de dados',
                'Política de retenção e descarte de dados educacionais',
                'Implementação de backup seguro e recuperação de dados',
                'Controle de acesso baseado em papéis',
                'Monitoramento de atividades suspeitas'
            ],
            'conclusoes_padrao' => 'O tratamento de dados educacionais apresenta riscos elevados, especialmente quando envolve menores de idade. É obrigatória a implementação de medidas robustas de segurança.',
            'recomendacoes_padrao' => [
                'Implementar sistema de logs detalhados para auditoria',
                'Estabelecer política de acesso baseada no princípio do menor privilégio',
                'Realizar treinamentos regulares sobre LGPD para equipe educacional',
                'Implementar políticas de retenção de dados específicas para o setor',
                'Realizar auditorias de conformidade semestrais',
                'Implementar sistema de gestão de incidentes',
                'Estabelecer procedimentos para solicitações de titulares',
                'Implementar monitoramento contínuo de segurança'
            ]
        ];
    }

    /**
     * Pré-processar dados específicos do setor de educação
     */
    private function preProcessarDadosEducacao(array $form): array
    {
        // Definir nível de risco padrão para educação
        if (empty($form['nivel_risco'])) {
            $form['nivel_risco'] = 'Alto';
        }

        // Definir necessidade de ANPD
        if (empty($form['necessita_anpd'])) {
            $form['necessita_anpd'] = 1; // Sim, por padrão
        }

        // Processar dados específicos do setor
        $dadosEspecificos = [
            'tipo_instituicao' => $form['tipo_instituicao'] ?? '',
            'nivel_ensino' => $form['nivel_ensino'] ?? '',
            'dados_tratados' => $form['dados_tratados'] ?? [],
            'base_legal' => $form['base_legal'] ?? ''
        ];

        $form['dados_especificos'] = json_encode($dadosEspecificos);
        $form['template_usado'] = 'educacao';

        return $form;
    }

    /**
     * Filtrar ROPAs relevantes para o setor de educação
     */
    private function getRopasRelevantesParaEducacao(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        $palavrasChave = [
            'educação', 'ensino', 'escola', 'faculdade', 'universidade', 'curso',
            'aluno', 'professor', 'matrícula', 'nota', 'frequência', 'acadêmico',
            'treinamento', 'capacitação', 'aprendizado', 'estudo', 'pedagógico'
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

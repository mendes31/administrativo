<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pelos questionários dinâmicos de AIPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdQuestionnaire
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Processar respostas do questionário
            $result = $this->processarQuestionario($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "Questionário processado com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd-create?questionario=" . $result);
                exit;
            } else {
                $this->data['errors'][] = "Erro ao processar questionário!";
            }
        }

        // Carregar dados para o questionário
        $ropaRepo = new LgpdRopaRepository();
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        
        $this->data['ropas'] = $ropaRepo->getAllRopaForSelect();
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['questionario'] = $this->getQuestionarioDinamico();

        $pageElements = [
            'title_head' => 'Questionário AIPD',
            'menu' => 'LgpdAipdQuestionnaire',
            'buttonPermission' => ['LgpdAipdQuestionnaire'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/questionnaire", $this->data);
        $loadView->loadView();
    }

    /**
     * Gera questionário dinâmico baseado no contexto
     */
    private function getQuestionarioDinamico(): array
    {
        return [
            'secao_1' => [
                'titulo' => 'Identificação da Operação',
                'perguntas' => [
                    [
                        'id' => 'q1_1',
                        'pergunta' => 'Qual é o objetivo principal do tratamento de dados?',
                        'tipo' => 'select',
                        'opcoes' => [
                            'marketing' => 'Marketing e Publicidade',
                            'vendas' => 'Vendas e Comercial',
                            'rh' => 'Recursos Humanos',
                            'financeiro' => 'Financeiro e Contábil',
                            'operacional' => 'Operacional',
                            'compliance' => 'Compliance e Legal',
                            'outro' => 'Outro'
                        ],
                        'obrigatoria' => true
                    ],
                    [
                        'id' => 'q1_2',
                        'pergunta' => 'Qual é o volume estimado de dados pessoais tratados?',
                        'tipo' => 'select',
                        'opcoes' => [
                            'pequeno' => 'Até 1.000 registros',
                            'medio' => '1.000 a 10.000 registros',
                            'grande' => '10.000 a 100.000 registros',
                            'massa' => 'Mais de 100.000 registros'
                        ],
                        'obrigatoria' => true
                    ]
                ]
            ],
            'secao_2' => [
                'titulo' => 'Tipos de Dados',
                'perguntas' => [
                    [
                        'id' => 'q2_1',
                        'pergunta' => 'Quais tipos de dados pessoais são tratados?',
                        'tipo' => 'checkbox',
                        'opcoes' => [
                            'identificacao' => 'Dados de identificação (nome, CPF, RG)',
                            'contato' => 'Dados de contato (email, telefone, endereço)',
                            'financeiro' => 'Dados financeiros (renda, histórico bancário)',
                            'comportamento' => 'Dados de comportamento (preferências, histórico)',
                            'biometrico' => 'Dados biométricos (impressão digital, facial)',
                            'saude' => 'Dados de saúde',
                            'sensivel' => 'Dados sensíveis (racial, religioso, político)',
                            'criancas' => 'Dados de crianças e adolescentes'
                        ],
                        'obrigatoria' => true
                    ]
                ]
            ],
            'secao_3' => [
                'titulo' => 'Finalidades e Base Legal',
                'perguntas' => [
                    [
                        'id' => 'q3_1',
                        'pergunta' => 'Qual é a base legal principal para o tratamento?',
                        'tipo' => 'select',
                        'opcoes' => [
                            'consentimento' => 'Consentimento do titular',
                            'execucao_contrato' => 'Execução de contrato',
                            'obrigacao_legal' => 'Obrigação legal',
                            'interesse_legitimo' => 'Interesse legítimo',
                            'protecao_credito' => 'Proteção ao crédito',
                            'saude_publica' => 'Proteção à saúde pública'
                        ],
                        'obrigatoria' => true
                    ],
                    [
                        'id' => 'q3_2',
                        'pergunta' => 'Os dados são compartilhados com terceiros?',
                        'tipo' => 'radio',
                        'opcoes' => [
                            'sim' => 'Sim',
                            'nao' => 'Não'
                        ],
                        'obrigatoria' => true
                    ]
                ]
            ],
            'secao_4' => [
                'titulo' => 'Medidas de Segurança',
                'perguntas' => [
                    [
                        'id' => 'q4_1',
                        'pergunta' => 'Quais medidas de segurança estão implementadas?',
                        'tipo' => 'checkbox',
                        'opcoes' => [
                            'criptografia' => 'Criptografia de dados',
                            'acesso_controlado' => 'Controle de acesso',
                            'backup' => 'Backup regular',
                            'monitoramento' => 'Monitoramento de acesso',
                            'auditoria' => 'Auditoria de logs',
                            'treinamento' => 'Treinamento da equipe'
                        ],
                        'obrigatoria' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * Processa as respostas do questionário e gera recomendações
     */
    private function processarQuestionario(array $respostas): string|false
    {
        $pontuacao = 0;
        $recomendacoes = [];

        // Análise da seção 1 - Identificação
        if (isset($respostas['q1_1'])) {
            switch ($respostas['q1_1']) {
                case 'marketing':
                case 'vendas':
                    $pontuacao += 20;
                    $recomendacoes[] = 'Considerar implementar mecanismos de opt-out';
                    break;
                case 'rh':
                case 'financeiro':
                    $pontuacao += 15;
                    break;
            }
        }

        if (isset($respostas['q1_2'])) {
            switch ($respostas['q1_2']) {
                case 'grande':
                case 'massa':
                    $pontuacao += 25;
                    $recomendacoes[] = 'Tratamento em larga escala - AIPD obrigatória';
                    break;
            }
        }

        // Análise da seção 2 - Tipos de Dados
        if (isset($respostas['q2_1']) && is_array($respostas['q2_1'])) {
            foreach ($respostas['q2_1'] as $tipo) {
                switch ($tipo) {
                    case 'biometrico':
                        $pontuacao += 30;
                        $recomendacoes[] = 'Dados biométricos - AIPD obrigatória';
                        break;
                    case 'saude':
                        $pontuacao += 25;
                        $recomendacoes[] = 'Dados de saúde - AIPD recomendada';
                        break;
                    case 'sensivel':
                        $pontuacao += 30;
                        $recomendacoes[] = 'Dados sensíveis - AIPD obrigatória';
                        break;
                    case 'criancas':
                        $pontuacao += 35;
                        $recomendacoes[] = 'Dados de crianças - AIPD obrigatória';
                        break;
                }
            }
        }

        // Análise da seção 3 - Base Legal
        if (isset($respostas['q3_1'])) {
            switch ($respostas['q3_1']) {
                case 'interesse_legitimo':
                    $pontuacao += 10;
                    $recomendacoes[] = 'Avaliar se o interesse legítimo é válido';
                    break;
            }
        }

        if (isset($respostas['q3_2']) && $respostas['q3_2'] === 'sim') {
            $pontuacao += 15;
            $recomendacoes[] = 'Compartilhamento com terceiros - revisar contratos';
        }

        // Gerar código único para o questionário
        $codigo = 'QST-' . date('Ymd') . '-' . rand(1000, 9999);

        // Salvar resultado do questionário (implementar se necessário)
        // $this->salvarResultadoQuestionario($codigo, $respostas, $pontuacao, $recomendacoes);

        return $codigo;
    }
}

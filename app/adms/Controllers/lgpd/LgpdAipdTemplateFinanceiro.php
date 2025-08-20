<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pelo template de AIPD específico para o setor financeiro.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateFinanceiro
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor financeiro
            $this->data['form'] = $this->preProcessarDadosFinanceiro($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor financeiro cadastrada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não cadastrada!";
            }
        }

        // Carregar dados para os selects
        $departmentsRepo = new DepartmentsRepository();
        $ropaRepo = new LgpdRopaRepository();
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $usersRepo = new UsersRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['ropas'] = $this->getRopasRelevantesParaFinanceiro($ropaRepo);
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor financeiro
        $this->data['template_financeiro'] = $this->getTemplateFinanceiro();

        $pageElements = [
            'title_head' => 'AIPD - Setor Financeiro',
            'menu' => 'lgpd-aipd-template-financeiro',
            'buttonPermission' => ['LgpdAipdTemplateFinanceiro'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-financeiro", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor financeiro
     */
    private function getTemplateFinanceiro(): array
    {
        return [
            'titulo_padrao' => 'AIPD - Tratamento de Dados Financeiros',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais relacionados ao tratamento de dados financeiros, incluindo informações bancárias, de crédito e patrimoniais.',
            'escopo_padrao' => 'Esta avaliação abrange o tratamento de dados financeiros de clientes, incluindo informações bancárias, histórico de crédito, renda, patrimônio e transações financeiras, conforme estabelecido na LGPD e regulamentações do Banco Central.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD e regulamentações do setor financeiro, considerando os riscos específicos de tratamento de dados financeiros e as medidas de segurança necessárias para proteção da privacidade dos clientes.',
            'riscos_identificados' => [
                'Fraude e roubo de identidade',
                'Vazamento de informações bancárias',
                'Acesso não autorizado a dados de crédito',
                'Compartilhamento inadequado com terceiros',
                'Falta de controle de acesso aos sistemas financeiros',
                'Armazenamento inadequado de dados sensíveis',
                'Transmissão insegura de informações financeiras',
                'Violação de regulamentações do Banco Central'
            ],
            'medidas_mitigacao' => [
                'Implementação de criptografia de dados em repouso e em trânsito',
                'Controle rigoroso de acesso com autenticação multifator',
                'Auditoria regular de acessos aos sistemas financeiros',
                'Treinamento específico da equipe sobre proteção de dados financeiros',
                'Backup seguro e recuperação de dados',
                'Política de retenção e descarte de dados financeiros',
                'Monitoramento contínuo de transações suspeitas',
                'Conformidade com regulamentações do Banco Central'
            ],
            'conclusoes_padrao' => 'O tratamento de dados financeiros apresenta riscos elevados devido à sensibilidade das informações e regulamentações específicas do setor. É obrigatória a implementação de medidas robustas de segurança e controle de acesso.',
            'recomendacoes_padrao' => [
                'Implementar sistema de logs detalhados para auditoria',
                'Estabelecer política de acesso baseada no princípio do menor privilégio',
                'Realizar treinamentos regulares sobre LGPD para equipe financeira',
                'Implementar criptografia end-to-end para transmissão de dados',
                'Estabelecer procedimentos de resposta a incidentes específicos para dados financeiros',
                'Manter conformidade com regulamentações do Banco Central',
                'Implementar monitoramento de fraudes em tempo real'
            ],
            'nivel_risco_padrao' => 'Alto',
            'status_padrao' => 'Em Andamento',
            'campos_especificos' => [
                'tipo_instituicao' => [
                    'label' => 'Tipo de Instituição Financeira',
                    'options' => [
                        'banco' => 'Banco',
                        'cooperativa' => 'Cooperativa de Crédito',
                        'fintech' => 'Fintech',
                        'corretora' => 'Corretora',
                        'seguradora' => 'Seguradora',
                        'outro' => 'Outro'
                    ]
                ],
                'produto_financeiro' => [
                    'label' => 'Produto/Serviço Financeiro Principal',
                    'options' => [
                        'conta_corrente' => 'Conta Corrente',
                        'conta_poupanca' => 'Conta Poupança',
                        'credito_pessoal' => 'Crédito Pessoal',
                        'financiamento' => 'Financiamento',
                        'investimentos' => 'Investimentos',
                        'seguros' => 'Seguros',
                        'pagamentos' => 'Pagamentos e Transferências',
                        'outro' => 'Outro'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Tipos de Dados Financeiros Tratados',
                    'options' => [
                        'dados_bancarios' => 'Dados Bancários (conta, agência)',
                        'dados_credito' => 'Dados de Crédito (score, histórico)',
                        'dados_patrimoniais' => 'Dados Patrimoniais (renda, bens)',
                        'dados_transacoes' => 'Dados de Transações',
                        'dados_biometricos' => 'Dados Biométricos',
                        'dados_localizacao' => 'Dados de Localização',
                        'dados_comportamento' => 'Dados de Comportamento Financeiro',
                        'outro' => 'Outro'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'execucao_contrato' => 'Execução de contrato ou procedimento preliminar',
                        'obrigacao_legal' => 'Obrigação legal ou regulatória',
                        'protecao_credito' => 'Proteção ao crédito',
                        'interesse_legitimo' => 'Interesse legítimo',
                        'consentimento' => 'Consentimento do titular'
                    ]
                ]
            ]
        ];
    }

    /**
     * Pré-processa dados específicos do setor financeiro
     */
    private function preProcessarDadosFinanceiro(array $form): array
    {
        // Definir nível de risco baseado no tipo de dados
        if (isset($form['dados_tratados']) && is_array($form['dados_tratados'])) {
            if (in_array('dados_biometricos', $form['dados_tratados'])) {
                $form['nivel_risco'] = 'Crítico';
            } else {
                $form['nivel_risco'] = 'Alto';
            }
        }

        // Adicionar observações específicas do setor
        $form['observacoes'] = ($form['observacoes'] ?? '') . "\n\n" . 
            "AIPD específica para setor financeiro - Considerações adicionais:\n" .
            "- Conformidade com regulamentações do Banco Central\n" .
            "- Necessidade de medidas de segurança robustas\n" .
            "- Importância da proteção contra fraudes\n" .
            "- Controle rigoroso de acesso aos dados financeiros\n" .
            "- Monitoramento de transações suspeitas";

        return $form;
    }

    /**
     * Filtra ROPAs relevantes para o setor financeiro
     */
    private function getRopasRelevantesParaFinanceiro(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        
        // Palavras-chave relacionadas ao setor financeiro
        $palavrasChave = [
            'financeiro', 'banco', 'crédito', 'conta', 'pagamento', 'transferência',
            'investimento', 'seguro', 'financiamento', 'cartão', 'boleto',
            'pix', 'cobrança', 'empréstimo', 'aplicação', 'corretora'
        ];
        
        foreach ($todasRopas as $ropa) {
            $atividade = strtolower($ropa['atividade']);
            $finalidade = strtolower($ropa['finalidade'] ?? '');
            
            // Verifica se a ROPA contém palavras-chave financeiras
            foreach ($palavrasChave as $palavra) {
                if (strpos($atividade, $palavra) !== false || strpos($finalidade, $palavra) !== false) {
                    $ropasRelevantes[] = $ropa;
                    break;
                }
            }
        }
        
        // Se não encontrar ROPAs específicas, retorna todas
        return empty($ropasRelevantes) ? $todasRopas : $ropasRelevantes;
    }
}

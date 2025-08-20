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
 * Controller responsável pelo template de AIPD específico para o setor de saúde.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdTemplateSaude
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Pré-processar dados específicos do setor de saúde
            $this->data['form'] = $this->preProcessarDadosSaude($this->data['form']);
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD para setor de saúde cadastrada com sucesso!";
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
        $this->data['ropas'] = $this->getRopasRelevantesParaSaude($ropaRepo);
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        // Carregar template específico do setor de saúde
        $this->data['template_saude'] = $this->getTemplateSaude();

        $pageElements = [
            'title_head' => 'AIPD - Setor de Saúde',
            'menu' => 'lgpd-aipd-template-saude',
            'buttonPermission' => ['LgpdAipdTemplateSaude'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/template-saude", $this->data);
        $loadView->loadView();
    }

    /**
     * Template específico para o setor de saúde
     */
    private function getTemplateSaude(): array
    {
        return [
            'titulo_padrao' => 'AIPD - Tratamento de Dados de Saúde',
            'objetivo_padrao' => 'Avaliar os riscos e impactos à proteção de dados pessoais relacionados ao tratamento de dados de saúde, incluindo prontuários médicos, exames laboratoriais, prescrições e informações clínicas.',
            'escopo_padrao' => 'Esta avaliação abrange o tratamento de dados de saúde de pacientes, incluindo informações médicas, diagnósticos, tratamentos, exames e histórico clínico, conforme estabelecido na LGPD e regulamentações específicas do setor de saúde.',
            'metodologia_padrao' => 'Análise baseada nas diretrizes da ANPD para o setor de saúde, considerando os riscos específicos de tratamento de dados sensíveis e as medidas de segurança necessárias para proteção da privacidade dos pacientes.',
            'riscos_identificados' => [
                'Acesso não autorizado a prontuários médicos',
                'Vazamento de informações de saúde sensíveis',
                'Compartilhamento inadequado com terceiros',
                'Falta de controle de acesso aos sistemas médicos',
                'Armazenamento inadequado de dados de saúde',
                'Transmissão insegura de informações médicas'
            ],
            'medidas_mitigacao' => [
                'Implementação de criptografia de dados em repouso e em trânsito',
                'Controle rigoroso de acesso com autenticação multifator',
                'Auditoria regular de acessos aos sistemas médicos',
                'Treinamento específico da equipe sobre proteção de dados de saúde',
                'Backup seguro e recuperação de dados',
                'Política de retenção e descarte de dados médicos'
            ],
            'conclusoes_padrao' => 'O tratamento de dados de saúde apresenta riscos elevados devido à sensibilidade das informações. É obrigatória a implementação de medidas robustas de segurança e controle de acesso.',
            'recomendacoes_padrao' => [
                'Implementar sistema de logs detalhados para auditoria',
                'Estabelecer política de acesso baseada no princípio do menor privilégio',
                'Realizar treinamentos regulares sobre LGPD para equipe médica',
                'Implementar criptografia end-to-end para transmissão de dados',
                'Estabelecer procedimentos de resposta a incidentes específicos para dados de saúde'
            ],
            'nivel_risco_padrao' => 'Alto',
            'status_padrao' => 'Em Andamento',
            'campos_especificos' => [
                'tipo_estabelecimento' => [
                    'label' => 'Tipo de Estabelecimento de Saúde',
                    'options' => [
                        'hospital' => 'Hospital',
                        'clinica' => 'Clínica',
                        'laboratorio' => 'Laboratório',
                        'consultorio' => 'Consultório',
                        'farmacia' => 'Farmácia',
                        'outro' => 'Outro'
                    ]
                ],
                'especialidade' => [
                    'label' => 'Especialidade Médica Principal',
                    'options' => [
                        'clinico_geral' => 'Clínico Geral',
                        'cardiologia' => 'Cardiologia',
                        'neurologia' => 'Neurologia',
                        'pediatria' => 'Pediatria',
                        'ginecologia' => 'Ginecologia',
                        'ortopedia' => 'Ortopedia',
                        'psiquiatria' => 'Psiquiatria',
                        'outro' => 'Outro'
                    ]
                ],
                'dados_tratados' => [
                    'label' => 'Tipos de Dados de Saúde Tratados',
                    'options' => [
                        'prontuario' => 'Prontuário Médico',
                        'exames' => 'Exames Laboratoriais',
                        'imagens' => 'Imagens Médicas (Raio-X, Tomografia, etc.)',
                        'prescricoes' => 'Prescrições Médicas',
                        'historico' => 'Histórico Clínico',
                        'dados_geneticos' => 'Dados Genéticos',
                        'dados_biometricos' => 'Dados Biométricos',
                        'outro' => 'Outro'
                    ]
                ],
                'base_legal' => [
                    'label' => 'Base Legal Principal',
                    'options' => [
                        'execucao_contrato' => 'Execução de contrato ou procedimento preliminar',
                        'obrigacao_legal' => 'Obrigação legal ou regulatória',
                        'protecao_credito' => 'Proteção ao crédito',
                        'saude_publica' => 'Proteção à saúde pública',
                        'interesse_legitimo' => 'Interesse legítimo',
                        'consentimento' => 'Consentimento do titular'
                    ]
                ]
            ]
        ];
    }

    /**
     * Pré-processa dados específicos do setor de saúde
     */
    private function preProcessarDadosSaude(array $form): array
    {
        // Definir nível de risco baseado no tipo de dados
        if (isset($form['dados_tratados']) && is_array($form['dados_tratados'])) {
            if (in_array('dados_geneticos', $form['dados_tratados']) || 
                in_array('dados_biometricos', $form['dados_tratados'])) {
                $form['nivel_risco'] = 'Crítico';
            } else {
                $form['nivel_risco'] = 'Alto';
            }
        }

        // Adicionar observações específicas do setor
        $form['observacoes'] = ($form['observacoes'] ?? '') . "\n\n" . 
            "AIPD específica para setor de saúde - Considerações adicionais:\n" .
            "- Conformidade com regulamentações específicas do setor\n" .
            "- Necessidade de medidas de segurança robustas\n" .
            "- Importância da confidencialidade médica\n" .
            "- Controle rigoroso de acesso aos dados";

        return $form;
    }

    /**
     * Filtra ROPAs relevantes para o setor de saúde
     */
    private function getRopasRelevantesParaSaude(LgpdRopaRepository $ropaRepo): array
    {
        $todasRopas = $ropaRepo->getAllRopaForSelect();
        $ropasRelevantes = [];
        
        // Palavras-chave relacionadas ao setor de saúde
        $palavrasChave = [
            'saúde', 'médico', 'hospital', 'clínica', 'laboratório', 'paciente',
            'prontuário', 'exame', 'consulta', 'tratamento', 'medicamento',
            'enfermagem', 'farmacêutico', 'odontologia', 'psicologia'
        ];
        
        foreach ($todasRopas as $ropa) {
            $atividade = strtolower($ropa['atividade']);
            $finalidade = strtolower($ropa['finalidade'] ?? '');
            
            // Verifica se a ROPA contém palavras-chave de saúde
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

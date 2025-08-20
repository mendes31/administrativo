<?php

use Phinx\Seed\AbstractSeed;

class AddLgpdFinalidades extends AbstractSeed
{
    public function run(): void
    {
        $finalidades = [
            [
                'finalidade' => 'Ações de saúde e segurança no trabalho',
                'exemplo' => 'Controle de ASOs, exames periódicos, CIPA',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Admissão e gestão de contratos de trabalho',
                'exemplo' => 'Cadastro do colaborador, assinatura de contrato, exame admissional',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Análise de crédito e cobrança',
                'exemplo' => 'Consulta ao Serasa, envio de boletos e negativação',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Análise de dados para inovação e melhorias',
                'exemplo' => 'Uso de dados internos para aperfeiçoar produtos ou serviços',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Análise de risco e prevenção a fraudes',
                'exemplo' => 'Due diligence de parceiros e fornecedores',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Atendimento a solicitações de titulares',
                'exemplo' => 'Respostas a pedidos de acesso, retificação ou exclusão de dados',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Backup e recuperação de dados',
                'exemplo' => 'Armazenamento em nuvem, recuperação de sistemas após falhas',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Controle de acesso e segurança patrimonial',
                'exemplo' => 'Registro de ponto, catracas e câmeras internas',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Controle de acesso lógico e físico',
                'exemplo' => 'Usuários e senhas, registro de acessos a sistemas',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Controle de contas a pagar e receber',
                'exemplo' => 'Cadastro bancário de clientes/fornecedores, pagamentos e cobranças',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Controle de entrada e saída de pessoas e bens',
                'exemplo' => 'Registro de visitantes e veículos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Cumprimento de obrigações legais e regulatórias',
                'exemplo' => 'Atendimento a fiscalizações e auditorias',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Cumprimento de obrigações legais e regulatórias trabalhistas',
                'exemplo' => 'Envio de dados ao eSocial, DIRF, RAIS',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Cumprimento de obrigações legais fiscais e contábeis',
                'exemplo' => 'Escrituração contábil, envio de SPED',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Elaboração de propostas e contratos',
                'exemplo' => 'Geração de orçamentos personalizados',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Emissão de notas fiscais e controle de tributos',
                'exemplo' => 'Emissão de NF-e, retenção de impostos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Execução de campanhas de marketing',
                'exemplo' => 'Envio de e-mails, anúncios segmentados',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Execução de contratos com fornecedores e clientes',
                'exemplo' => 'Pagamento conforme contrato, cumprimento de cláusulas contratuais',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Execução de contratos logísticos',
                'exemplo' => 'Contratos com transportadoras, prazos de entrega',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Garantia da segurança da informação',
                'exemplo' => 'Testes de vulnerabilidade, políticas de segurança',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Gestão de contratos e registros legais',
                'exemplo' => 'Assinaturas, prazos e cláusulas de contratos corporativos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Gestão de desempenho e carreira',
                'exemplo' => 'Avaliação anual, promoções, plano de desenvolvimento individual',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Gestão de processos judiciais e administrativos',
                'exemplo' => 'Registro de dados em processos trabalhistas ou cíveis',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Gestão de transporte e entrega de produtos',
                'exemplo' => 'Cadastro de motoristas, roteirização',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Melhoria contínua dos serviços com base em feedback',
                'exemplo' => 'Análise de reclamações para aprimorar processos internos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Monitoramento de uso de sistemas e redes',
                'exemplo' => 'Logs de atividades, antivírus, firewalls',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Personalização de ofertas e experiências',
                'exemplo' => 'Recomendação de produtos com base no histórico do cliente',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Pesquisa de satisfação e comportamento do cliente',
                'exemplo' => 'Aplicação de questionários após a venda',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Processamento de folha de pagamento e benefícios',
                'exemplo' => 'Geração de holerites, gestão de vale-transporte e plano de saúde',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Prospecção e relacionamento com clientes',
                'exemplo' => 'Armazenamento de leads, envio de propostas comerciais',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Rastreabilidade de pedidos e mercadorias',
                'exemplo' => 'Rastreamento de entregas com dados do destinatário',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Registro de reclamações e solicitações',
                'exemplo' => 'Histórico de chamados, número de protocolo',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Suporte ao cliente e acompanhamento de chamados',
                'exemplo' => 'Identificação do cliente para atendimento personalizado',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Suporte técnico e manutenção de sistemas',
                'exemplo' => 'Abertura de chamados, histórico de atendimentos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Testes de produtos com consumidores',
                'exemplo' => 'Coleta de feedback de usuários em protótipos',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Tratamento de dados anonimizados para estudos estatísticos',
                'exemplo' => 'Relatórios com dados agregados para tendências de consumo',
                'status' => 'Ativo'
            ],
            [
                'finalidade' => 'Treinamentos e desenvolvimento de colaboradores',
                'exemplo' => 'Inscrição em cursos internos, trilhas de capacitação',
                'status' => 'Ativo'
            ]
        ];

        // Variável para receber os dados que devem ser inseridos
        $data = [];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($finalidades as $finalidade) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM lgpd_finalidades WHERE finalidade=:finalidade', ['finalidade' => $finalidade['finalidade']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                $data[] = $finalidade;
            }
        }

        // Se houver dados para inserir, insere na tabela
        if (!empty($data)) {
            $this->insert('lgpd_finalidades', $data);
        }
    }
} 
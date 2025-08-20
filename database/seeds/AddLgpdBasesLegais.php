<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddLgpdBasesLegais extends AbstractSeed
{
    /**
     * Adiciona bases legais LGPD padrão ao sistema.
     *
     * Este seed adiciona as principais bases legais para tratamento de dados pessoais
     * conforme especificado na LGPD, incluindo descrições e exemplos práticos.
     *
     * @return void
     */
    public function run(): void
    {
        $basesLegais = [
            [
                'base_legal' => 'Consentimento (Art. 7)',
                'descricao' => 'Manifestação livre, informada e inequívoca do titular.',
                'exemplo' => 'Marketing: envio de promoções. RH: uso de fotos em eventos. TI: cookies não essenciais.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Obrigação Legal ou Regulamentar (Art. 7)',
                'descricao' => 'Para cumprir obrigações previstas em lei.',
                'exemplo' => 'Fiscal: emissão de NF-e. RH: envio de eSocial. Financeiro: retenções tributárias.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Execução de Contrato (Art. 7)',
                'descricao' => 'Para firmar ou cumprir contrato com o titular.',
                'exemplo' => 'Vendas: entrega de produto. Suporte: atendimento técnico. RH: contrato de trabalho.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Exercício Regular de Direitos (Art. 7)',
                'descricao' => 'Para uso em processo judicial, administrativo ou arbitral.',
                'exemplo' => 'Jurídico: defesa em processos. Comercial: cobrança judicial.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Proteção da Vida (Art. 7)',
                'descricao' => 'Para proteger a vida/incolumidade física.',
                'exemplo' => 'Segurança: emergências médicas. RH: contato de emergência.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Tutela da Saúde (Art. 7)',
                'descricao' => 'Exclusiva para entidades/profissionais da saúde.',
                'exemplo' => 'Clínicas: exames e prontuários. Planos: gestão de tratamentos.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Interesse Legítimo (Art. 7)',
                'descricao' => 'Quando não infringe direitos do titular.',
                'exemplo' => 'TI: monitoramento de acesso. Marketing: remarketing. Segurança: câmeras internas.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Proteção do Crédito (Art. 7)',
                'descricao' => 'Para análise e proteção de crédito.',
                'exemplo' => 'Financeiro: análise de risco. Comercial: consulta em bureaus.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Administração Pública (Art. 7)',
                'descricao' => 'Para políticas públicas, conforme a lei.',
                'exemplo' => 'Governo: cadastro único. Órgãos Públicos: concursos.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Pesquisa (anonimizada) (Art. 7)',
                'descricao' => 'Estudos, preferencialmente com dados anonimizados.',
                'exemplo' => 'Pesquisa: estudos acadêmicos. Instituições: análises demográficas.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Consentimento Explícito (Art. 11)',
                'descricao' => 'Manifestação específica e destacada.',
                'exemplo' => 'RH: dados de saúde para benefícios. Marketing: campanhas segmentadas com base em religião.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Obrigação Legal (Art. 11)',
                'descricao' => 'Cumprimento de lei específica.',
                'exemplo' => 'RH: laudo para INSS. Empresa: cotas de PCD.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Tratamento Compartilhado com Administração Pública (Art. 11)',
                'descricao' => 'Exclusivo para políticas públicas.',
                'exemplo' => 'Hospitais Públicos: vacinação.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Estudos por Órgão de Pesquisa (Art. 11)',
                'descricao' => 'Com anonimização sempre que possível.',
                'exemplo' => 'Universidades: estudo com dados de saúde.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Execução de Contrato (Art. 11)',
                'descricao' => 'Para viabilizar contrato com o titular.',
                'exemplo' => 'Planos de Saúde: dados para cobertura contratual.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Exercício Regular de Direitos (Art. 11)',
                'descricao' => 'Em processos judiciais, arbitrais ou administrativos.',
                'exemplo' => 'Advocacia: prova em ações trabalhistas.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Proteção da Vida ou Incolumidade (Art. 11)',
                'descricao' => 'Situações emergenciais.',
                'exemplo' => 'Emergência Médica: acesso a histórico clínico.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Tutela da Saúde (Art. 11)',
                'descricao' => 'Por profissionais/entidades de saúde.',
                'exemplo' => 'Clínicas: tratamento médico.',
                'status' => 'Ativo'
            ],
            [
                'base_legal' => 'Prevenção à Fraude e Segurança (Art. 11)',
                'descricao' => 'Em processos de identificação/autenticação.',
                'exemplo' => 'TI: reconhecimento facial. Bancos: biometria para login.',
                'status' => 'Ativo'
            ]
        ];

        // Variável para receber os dados que devem ser inseridos
        $data = [];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($basesLegais as $baseLegal) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM lgpd_bases_legais WHERE base_legal=:base_legal', ['base_legal' => $baseLegal['base_legal']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                $data[] = $baseLegal;
            }
        }

        // Se houver dados para inserir, insere na tabela
        if (!empty($data)) {
            $this->insert('lgpd_bases_legais', $data);
            
            echo "✅ Seed de Bases Legais LGPD executado com sucesso!\n";
            echo "📋 " . count($data) . " bases legais foram adicionadas.\n";
            echo "\nBases legais adicionadas:\n";
            
            foreach ($data as $baseLegal) {
                echo "- {$baseLegal['base_legal']}\n";
            }
        } else {
            echo "ℹ️ Seed de Bases Legais LGPD: Todas as bases legais já existem no banco de dados.\n";
        }
    }
} 
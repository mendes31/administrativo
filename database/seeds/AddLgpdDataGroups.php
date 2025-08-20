<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddLgpdDataGroups extends AbstractSeed
{
    /**
     * Adiciona os grupos de dados padrão para LGPD.
     *
     * Este seed cria os grupos de dados padronizados seguindo as boas práticas
     * da ANPD, ISO 27701 e NIST, facilitando o cadastro no inventário e
     * garantindo consistência entre Inventário → ROPA → Data Mapping.
     *
     * @return void
     */
    public function run(): void
    {
        // Array com os grupos de dados padrão
        $dataGroups = [
            [
                'name' => 'Identificação pessoal',
                'category' => 'Pessoal',
                'example_fields' => 'Nome, CPF, RG, CNH, Data de nascimento, Nacionalidade',
                'is_sensitive' => false,
                'notes' => 'Utilizado em cadastros e documentos de identificação'
            ],
            [
                'name' => 'Contato',
                'category' => 'Pessoal',
                'example_fields' => 'E-mail, Telefone, Celular, Endereço, CEP, Cidade, Estado',
                'is_sensitive' => false,
                'notes' => 'Comunicação, entrega, suporte e relacionamento'
            ],
            [
                'name' => 'Financeiro',
                'category' => 'Sensível',
                'example_fields' => 'Salário, conta bancária, cartão de crédito, histórico de crédito, débitos',
                'is_sensitive' => true,
                'notes' => 'Pagamento de salários, análise de crédito, benefícios'
            ],
            [
                'name' => 'Profissional',
                'category' => 'Pessoal',
                'example_fields' => 'Cargo, CTPS, matrícula, jornada, avaliação de desempenho, treinamentos',
                'is_sensitive' => false,
                'notes' => 'Gestão de RH, controle de jornada, folha de pagamento'
            ],
            [
                'name' => 'Saúde',
                'category' => 'Sensível',
                'example_fields' => 'CID, exames médicos, atestados, plano de saúde, histórico médico',
                'is_sensitive' => true,
                'notes' => 'PCMSO, atestados, ergonomia, benefícios de saúde'
            ],
            [
                'name' => 'Biometria',
                'category' => 'Sensível',
                'example_fields' => 'Impressão digital, reconhecimento facial, íris, voz',
                'is_sensitive' => true,
                'notes' => 'Ponto eletrônico, controle de acesso físico, segurança'
            ],
            [
                'name' => 'Acesso a sistemas',
                'category' => 'Pessoal',
                'example_fields' => 'Login, senha, logs de acesso, IP, permissões, tokens',
                'is_sensitive' => false,
                'notes' => 'Segurança da informação, auditoria, controle de acesso'
            ],
            [
                'name' => 'Imagem e áudio',
                'category' => 'Pessoal',
                'example_fields' => 'Foto, vídeo, gravação de voz, imagem de segurança',
                'is_sensitive' => false,
                'notes' => 'Monitoramento, marketing, segurança patrimonial'
            ],
            [
                'name' => 'Dados comportamentais',
                'category' => 'Pessoal',
                'example_fields' => 'Preferências, hábitos de navegação, cliques, tempo de uso',
                'is_sensitive' => false,
                'notes' => 'Marketing, UX, personalização de serviços'
            ],
            [
                'name' => 'Geolocalização',
                'category' => 'Pessoal',
                'example_fields' => 'Localização GPS, IP geolocalizado, histórico de localização',
                'is_sensitive' => false,
                'notes' => 'Logística, rastreamento, dispositivos móveis'
            ],
            [
                'name' => 'Dados acadêmicos',
                'category' => 'Pessoal',
                'example_fields' => 'Formação acadêmica, cursos, certificados, diplomas',
                'is_sensitive' => false,
                'notes' => 'Recrutamento, desenvolvimento de pessoal, qualificação'
            ],
            [
                'name' => 'Família / dependentes',
                'category' => 'Sensível',
                'example_fields' => 'Filiação, filhos, responsáveis legais, dados do cônjuge',
                'is_sensitive' => true,
                'notes' => 'Benefícios, convênios, cadastro de dependentes'
            ],
            [
                'name' => 'Dados legais/jurídicos',
                'category' => 'Sensível',
                'example_fields' => 'Antecedentes, ações judiciais, multas, registros legais',
                'is_sensitive' => true,
                'notes' => 'Contratos, litígios, obrigações legais'
            ],
            [
                'name' => 'Dados de navegação',
                'category' => 'Pessoal',
                'example_fields' => 'Cookies, IP, tempo de acesso, páginas visitadas, histórico',
                'is_sensitive' => false,
                'notes' => 'Websites, aplicativos, análises de comportamento'
            ],
            [
                'name' => 'Convicções pessoais',
                'category' => 'Sensível',
                'example_fields' => 'Religião, orientação sexual, opinião política, filiação sindical',
                'is_sensitive' => true,
                'notes' => 'Só pode ser tratado com consentimento expresso ou exceção legal'
            ]
        ];

        // Array para armazenar os dados que devem ser inseridos
        $data = [];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($dataGroups as $group) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM lgpd_data_groups WHERE name=:name', ['name' => $group['name']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                // Criar o array com os dados do grupo
                $data[] = [
                    'name' => $group['name'],
                    'category' => $group['category'],
                    'example_fields' => $group['example_fields'],
                    'is_sensitive' => $group['is_sensitive'],
                    'notes' => $group['notes'],
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
            }
        }

        // Se houver dados para inserir
        if (!empty($data)) {
            // Indicar em qual tabela deve salvar
            $lgpd_data_groups = $this->table('lgpd_data_groups');

            // Inserir os registros na tabela
            $lgpd_data_groups->insert($data)->save();

            echo "✅ " . count($data) . " grupos de dados LGPD foram criados com sucesso!\n";
        } else {
            echo "ℹ️  Todos os grupos de dados LGPD já existem no banco de dados.\n";
        }
    }
}
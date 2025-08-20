<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddLgpdCategoriasTitulares extends AbstractSeed
{
    /**
     * Adiciona categorias de titulares LGPD padrão ao sistema.
     *
     * Este seed adiciona as principais categorias de titulares de dados pessoais
     * conforme especificado na LGPD, incluindo exemplos práticos de cada categoria.
     *
     * @return void
     */
    public function run(): void
    {
        $categorias = [
            [
                'titular' => 'Candidatos a emprego',
                'exemplo' => 'Pessoas que enviam currículos ou participam de processos seletivos.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Clientes/Consumidores',
                'exemplo' => 'Pessoas que adquirem produtos ou serviços de uma empresa (e.g., clientes de e-commerce, assinantes, compradores).',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Colaboradores',
                'exemplo' => 'Funcionários de uma organização, incluindo empregados, estagiários, aprendizes,Ex-funcionários (dados mantidos para cumprimento de obrigação legal), etc.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Crianças e Adolescentes',
                'exemplo' => 'Necessário consentimento de pelo menos um dos pais ou responsável legal para crianças até 12 anos (Art. 14 da LGPD).',
                'status' => 'Ativo'
            ],      
            [
                'titular' => 'Dependentes',
                'exemplo' => 'Familiares de clientes, colaboradores ou associados que também são afetados pelo tratamento de dados.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Leads e Prospectos',
                'exemplo' => 'Pessoas que interagem com campanhas de marketing, assinam newsletters ou demonstram interesse em produtos/serviços.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Sócios e Representantes Legais de Empresas',
                'exemplo' => 'Dados pessoais (como CPF, RG, assinatura) são coletados para fins contratuais e de compliance.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Terceiros',
                'exemplo' => 'Profissionais autônomos, representantes comerciais, prestadores de serviços individuais.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Usuários',
                'exemplo' => 'Indivíduos que utilizam serviços ou plataformas online, mesmo que não sejam clientes diretos.',
                'status' => 'Ativo'
            ],
            [
                'titular' => 'Visitantes',
                'exemplo' => 'Pessoas que acessam fisicamente as dependências da empresa (dados de portaria, imagens de câmeras de segurança).',
                'status' => 'Ativo'
            ]
        ];

        // Variável para receber os dados que devem ser inseridos
        $data = [];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($categorias as $categoria) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM lgpd_categorias_titulares WHERE titular=:titular', ['titular' => $categoria['titular']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                $data[] = $categoria;
            }
        }

        // Se houver dados para inserir, insere na tabela
        if (!empty($data)) {
            $this->insert('lgpd_categorias_titulares', $data);
            
            echo "✅ Seed de Categorias de Titulares LGPD executado com sucesso!\n";
            echo "📋 " . count($data) . " categorias foram adicionadas.\n";
            echo "\nCategorias adicionadas:\n";
            
            foreach ($data as $categoria) {
                echo "- {$categoria['titular']}\n";
            }
        } else {
            echo "ℹ️ Seed de Categorias de Titulares LGPD: Todas as categorias já existem no banco de dados.\n";
        }
    }
} 
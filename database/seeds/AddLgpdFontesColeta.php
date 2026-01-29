<?php

use Phinx\Seed\AbstractSeed;

class AddLgpdFontesColeta extends AbstractSeed
{
    public function run(): void
    {
        $fontes = [
            [
                'nome' => 'Formulário Online',
                'descricao' => 'Formulários preenchidos no site da empresa',
                'ativo' => 1
            ],
            [
                'nome' => 'E-mail',
                'descricao' => 'Dados coletados via e-mail',
                'ativo' => 1
            ],
            [
                'nome' => 'Telefone',
                'descricao' => 'Dados coletados via telefone',
                'ativo' => 1
            ],
            [
                'nome' => 'Presencial',
                'descricao' => 'Dados coletados pessoalmente',
                'ativo' => 1
            ],
            [
                'nome' => 'LinkedIn',
                'descricao' => 'Dados coletados via LinkedIn',
                'ativo' => 1
            ],
            [
                'nome' => 'WhatsApp',
                'descricao' => 'Dados coletados via WhatsApp',
                'ativo' => 1
            ],
            [
                'nome' => 'Aplicativo Mobile',
                'descricao' => 'Dados coletados via aplicativo mobile',
                'ativo' => 1
            ],
            [
                'nome' => 'Eventos',
                'descricao' => 'Dados coletados em eventos',
                'ativo' => 1
            ],
            [
                'nome' => 'Redes Sociais',
                'descricao' => 'Dados coletados via redes sociais',
                'ativo' => 1
            ],
            [
                'nome' => 'Sistema Interno',
                'descricao' => 'Dados coletados via sistema interno',
                'ativo' => 1
            ]
        ];

        // Montar apenas as fontes que ainda não existem, para evitar duplicação
        $data = [];

        foreach ($fontes as $fonte) {
            $existing = $this->query(
                'SELECT id FROM lgpd_fontes_coleta WHERE nome = :nome',
                ['nome' => $fonte['nome']]
            )->fetch();

            if (!$existing) {
                $data[] = $fonte;
            }
        }

        if (!empty($data)) {
            $this->table('lgpd_fontes_coleta')->insert($data)->save();
            echo '✅ Seed de Fontes de Coleta LGPD executado com sucesso. ' . count($data) . " fonte(s) adicionada(s).\n";
        } else {
            echo "ℹ️ Seed de Fontes de Coleta LGPD: todas as fontes já existem no banco de dados.\n";
        }
    }
} 
<?php

use Phinx\Seed\AbstractSeed;

class AddLgpdFontesColeta extends AbstractSeed
{
    public function run(): void
    {
        $data = [
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

        $this->table('lgpd_fontes_coleta')->insert($data)->save();
    }
} 
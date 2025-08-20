<?php

use Phinx\Seed\AbstractSeed;

class AddLgpdTiposDados extends AbstractSeed
{
    public function run(): void
    {
        $tiposDados = [
            [
                'tipo_dado' => 'Dado Anonimizado',
                'exemplos' => 'Não identifica o titular diretamente.',
                'status' => 'Ativo'
            ],
            [
                'tipo_dado' => 'Dado Comum',
                'exemplos' => 'Informação que identifica uma pessoa física.',
                'status' => 'Ativo'
            ],
            [
                'tipo_dado' => 'Dado Pseudonimizado',
                'exemplos' => 'Requer chave adicional para reidentificação.',
                'status' => 'Ativo'
            ],
            [
                'tipo_dado' => 'Dado Sensível',
                'exemplos' => 'Dados que exigem maior proteção legal.',
                'status' => 'Ativo'
            ]
        ];

        // Variável para receber os dados que devem ser inseridos
        $data = [];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($tiposDados as $tipoDado) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM lgpd_tipos_dados WHERE tipo_dado=:tipo_dado', ['tipo_dado' => $tipoDado['tipo_dado']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                $data[] = $tipoDado;
            }
        }

        // Se houver dados para inserir, insere na tabela
        if (!empty($data)) {
            $this->insert('lgpd_tipos_dados', $data);
        }
    }
} 
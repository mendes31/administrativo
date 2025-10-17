<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsInformativosCategorias extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $names = [
            'Alerta',
            'Aniversários',
            'Benefícios',
            'Biblioteca',
            'Boas-vindas',
            'Cardápio',
            'Comunicados',
            'Cultura',
            'Datas comemorativas',
            'Eventos',
            'Férias',
            'Integração',
            'Reconhecimentos',
            'Resultados',
            'Saúde',
            'Segurança',
            'Sustentabilidade',
            'Tecnologia',
            'Welcome Baby',
        ];

        $data = [];
        foreach ($names as $name) {
            // Evitar duplicidade
            $exists = $this->query('SELECT id FROM adms_informativos_categorias WHERE name = :name', [ 'name' => $name ])->fetch();
            if ($exists) { continue; }
            $data[] = [
                'name' => $name,
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            $table = $this->table('adms_informativos_categorias');
            $table->insert($data)->save();
        }
    }
}



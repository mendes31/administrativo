<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class AddInventoryBasics extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Unidades
        $units = [
            ['code' => 'UN', 'name' => 'Unidade'],
            ['code' => 'KG', 'name' => 'Quilograma'],
            ['code' => 'M', 'name' => 'Metro'],
            ['code' => 'CX', 'name' => 'Caixa'],
        ];

        foreach ($units as $unit) {
            $exists = $this->query('SELECT id FROM inv_units WHERE code = :code', ['code' => $unit['code']])->fetch();
            if (!$exists) {
                $unit['created_at'] = $now;
                $this->table('inv_units')->insert($unit)->saveData();
            }
        }

        // Categorias
        $categories = [
            ['name' => 'Geral'],
            ['name' => 'PA - PROJETO'],
        ];
        foreach ($categories as $cat) {
            $exists = $this->query('SELECT id FROM inv_categories WHERE name = :name', ['name' => $cat['name']])->fetch();
            if (!$exists) {
                $cat['created_at'] = $now;
                $this->table('inv_categories')->insert($cat)->saveData();
            }
        }

        // Motivos de movimentação
        $reasons = [
            // Entradas
            ['type' => 'entry', 'code' => 'COMPRA', 'description' => 'Entrada por compra'],
            ['type' => 'entry', 'code' => 'DEVOLUCAO_USO', 'description' => 'Devolução de item não utilizado'],
            ['type' => 'entry', 'code' => 'RETORNO_MANUT', 'description' => 'Retorno de equipamento/manutenção'],
            // Saídas
            ['type' => 'exit', 'code' => 'MANUT_EQUIP', 'description' => 'Saída para manutenção em equipamento'],
            ['type' => 'exit', 'code' => 'PERDA_QUEBRA', 'description' => 'Saída por perda/quebra/vencimento'],
            ['type' => 'exit', 'code' => 'SAIDA_OUTRO_ESTOQUE', 'description' => 'Saída para outro almoxarifado/filial'],
            // Transferências
            ['type' => 'transfer', 'code' => 'TRANSF_ENTRE_ESTOQUES', 'description' => 'Transferência entre estoques'],
            ['type' => 'transfer', 'code' => 'TRANSF_ENTRE_POSICOES', 'description' => 'Transferência entre posições internas'],
            // Ajuste
            ['type' => 'adjust', 'code' => 'AJUSTE_ESTOQUE', 'description' => 'Ajuste de inventário'],
        ];

        foreach ($reasons as $reason) {
            $exists = $this->query('SELECT id FROM inv_movement_reasons WHERE type = :type AND code = :code', [
                'type' => $reason['type'],
                'code' => $reason['code']
            ])->fetch();
            if (!$exists) {
                $reason['active'] = 1;
                $reason['created_at'] = $now;
                $this->table('inv_movement_reasons')->insert($reason)->saveData();
            }
        }
    }
}





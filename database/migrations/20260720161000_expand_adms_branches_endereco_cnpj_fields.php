<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Endereço e demais campos do comprovante CNPJ em adms_branches.
 */
final class ExpandAdmsBranchesEnderecoCnpjFields extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        $table = $this->table('adms_branches');
        $cols = [
            'data_abertura' => ['type' => 'date', 'null' => true, 'default' => null, 'after' => 'nome_fantasia', 'comment' => 'Data de abertura (Receita)'],
            'porte' => ['type' => 'string', 'limit' => 50, 'null' => true, 'default' => null, 'after' => 'data_abertura'],
            'cnae_principal' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null, 'after' => 'porte', 'comment' => 'Código e descrição CNAE principal'],
            'natureza_juridica' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null, 'after' => 'cnae_principal'],
            'logradouro' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null, 'after' => 'natureza_juridica'],
            'numero' => ['type' => 'string', 'limit' => 30, 'null' => true, 'default' => null, 'after' => 'logradouro'],
            'complemento' => ['type' => 'string', 'limit' => 100, 'null' => true, 'default' => null, 'after' => 'numero'],
            'cep' => ['type' => 'string', 'limit' => 8, 'null' => true, 'default' => null, 'after' => 'complemento'],
            'bairro' => ['type' => 'string', 'limit' => 120, 'null' => true, 'default' => null, 'after' => 'cep'],
            'municipio' => ['type' => 'string', 'limit' => 120, 'null' => true, 'default' => null, 'after' => 'bairro'],
            'uf' => ['type' => 'string', 'limit' => 2, 'null' => true, 'default' => null, 'after' => 'municipio'],
            'situacao_cadastral' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null, 'after' => 'uf', 'comment' => 'Ex.: ATIVA'],
        ];

        foreach ($cols as $name => $def) {
            if ($table->hasColumn($name)) {
                continue;
            }
            $type = $def['type'];
            unset($def['type']);
            $table->addColumn($name, $type, $def);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        $table = $this->table('adms_branches');
        foreach ([
            'situacao_cadastral', 'uf', 'municipio', 'bairro', 'cep', 'complemento', 'numero',
            'logradouro', 'natureza_juridica', 'cnae_principal', 'porte', 'data_abertura',
        ] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}

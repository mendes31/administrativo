<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Categoria de informativo para anúncios de vaga interna / seleção.
 */
final class AddInformativoCategoriaSelecaoInterna extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_informativos_categorias')) {
            return;
        }

        $name = 'Seleção Interna';
        $exists = $this->fetchRow(
            'SELECT id FROM adms_informativos_categorias WHERE name = ' . $this->getAdapter()->getConnection()->quote($name) . ' LIMIT 1'
        );
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_informativos_categorias')->insert([
            [
                'name' => $name,
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_informativos_categorias')) {
            return;
        }

        $this->execute(
            'DELETE FROM adms_informativos_categorias WHERE name = '
            . $this->getAdapter()->getConnection()->quote('Seleção Interna')
        );
    }
}

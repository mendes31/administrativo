<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterAdmsInformativosAddFkAndSchedule extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_informativos');

        if (!$table->hasColumn('categoria_id')) {
            $table->addColumn('categoria_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'resumo',
                'comment' => 'FK para adms_informativos_categorias'
            ]);
        }

        if (!$table->hasColumn('department_id')) {
            $table->addColumn('department_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'usuario_id',
                'comment' => 'Departamento que está publicando'
            ]);
        }

        if (!$table->hasColumn('publish_at')) {
            $table->addColumn('publish_at', 'datetime', [
                'null' => true,
                'after' => 'ativo',
                'comment' => 'Data/hora de início de publicação'
            ]);
        }

        if (!$table->hasColumn('expire_at')) {
            $table->addColumn('expire_at', 'datetime', [
                'null' => true,
                'after' => 'publish_at',
                'comment' => 'Data/hora de expiração'
            ]);
        }

        $table->addIndex(['categoria_id'])
              ->addIndex(['department_id'])
              ->addIndex(['publish_at'])
              ->addIndex(['expire_at'])
              ->save();

        // FKs
        $this->execute('ALTER TABLE adms_informativos ADD CONSTRAINT fk_informativos_categoria_id FOREIGN KEY (categoria_id) REFERENCES adms_informativos_categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT');
        $this->execute('ALTER TABLE adms_informativos ADD CONSTRAINT fk_informativos_department_id FOREIGN KEY (department_id) REFERENCES adms_departments(id) ON UPDATE CASCADE ON DELETE RESTRICT');
    }

    public function down(): void
    {
        // Remover FKs com segurança
        $this->execute('ALTER TABLE adms_informativos DROP FOREIGN KEY fk_informativos_categoria_id');
        $this->execute('ALTER TABLE adms_informativos DROP FOREIGN KEY fk_informativos_department_id');

        $table = $this->table('adms_informativos');
        if ($table->hasColumn('expire_at')) {
            $table->removeColumn('expire_at');
        }
        if ($table->hasColumn('publish_at')) {
            $table->removeColumn('publish_at');
        }
        if ($table->hasColumn('department_id')) {
            $table->removeColumn('department_id');
        }
        if ($table->hasColumn('categoria_id')) {
            $table->removeColumn('categoria_id');
        }
        $table->save();
    }
}



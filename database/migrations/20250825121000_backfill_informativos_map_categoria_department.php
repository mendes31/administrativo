<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BackfillInformativosMapCategoriaDepartment extends AbstractMigration
{
    public function up(): void
    {
        // Remover FKs temporariamente para permitir alteração de colunas
        $this->execute('ALTER TABLE adms_informativos DROP FOREIGN KEY fk_informativos_categoria_id');
        $this->execute('ALTER TABLE adms_informativos DROP FOREIGN KEY fk_informativos_department_id');

        // Mapear categoria textual existente para categoria_id
        $this->execute('
            UPDATE adms_informativos i
            LEFT JOIN adms_informativos_categorias c
              ON LOWER(TRIM(c.name)) = LOWER(TRIM(i.categoria))
            SET i.categoria_id = c.id
            WHERE i.categoria_id IS NULL
        ');

        // Fallback: setar "Comunicados" quando não mapeado
        $this->execute('
            UPDATE adms_informativos i
            JOIN adms_informativos_categorias c ON c.name = "Comunicados"
            SET i.categoria_id = c.id
            WHERE i.categoria_id IS NULL
        ');

        // Definir department_id a partir do usuário criador
        $this->execute('
            UPDATE adms_informativos i
            LEFT JOIN adms_users u ON u.id = i.usuario_id
            SET i.department_id = u.user_department_id
            WHERE i.department_id IS NULL AND u.user_department_id IS NOT NULL
        ');

        // Fallback: department_id = 1 quando ainda nulo
        $this->execute('UPDATE adms_informativos SET department_id = 1 WHERE department_id IS NULL');

        // Tornar NOT NULL após backfill
        $table = $this->table('adms_informativos');
        if ($table->hasColumn('categoria_id')) {
            $table->changeColumn('categoria_id', 'integer', ['null' => false, 'signed' => false])->save();
        }
        if ($table->hasColumn('department_id')) {
            $table->changeColumn('department_id', 'integer', ['null' => false, 'signed' => false])->save();
        }

        // Recriar FKs
        $this->execute('ALTER TABLE adms_informativos ADD CONSTRAINT fk_informativos_categoria_id FOREIGN KEY (categoria_id) REFERENCES adms_informativos_categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT');
        $this->execute('ALTER TABLE adms_informativos ADD CONSTRAINT fk_informativos_department_id FOREIGN KEY (department_id) REFERENCES adms_departments(id) ON UPDATE CASCADE ON DELETE RESTRICT');
    }

    public function down(): void
    {
        // Reverter NOT NULL
        $table = $this->table('adms_informativos');
        if ($table->hasColumn('categoria_id')) {
            $table->changeColumn('categoria_id', 'integer', ['null' => true, 'signed' => false])->save();
        }
        if ($table->hasColumn('department_id')) {
            $table->changeColumn('department_id', 'integer', ['null' => true, 'signed' => false])->save();
        }
    }
}


